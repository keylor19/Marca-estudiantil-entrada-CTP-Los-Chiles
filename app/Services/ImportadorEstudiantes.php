<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\Seccion;
use App\Models\Subgrupo;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use OpenSpout\Reader\CSV\Options as OpcionesCsv;
use OpenSpout\Reader\CSV\Reader as LectorCsv;
use OpenSpout\Reader\XLSX\Reader as LectorXlsx;
use Throwable;

class ImportadorEstudiantes
{
    /**
     * Encabezados aceptados (ya normalizados) para cada campo.
     *
     * @var array<string, list<string>>
     */
    private const COLUMNAS = [
        'cedula' => ['cedula', 'identificacion', 'id'],
        'nombre' => ['nombre', 'nombres'],
        'apellido1' => ['apellido1', 'primer_apellido', 'apellido_1'],
        'apellido2' => ['apellido2', 'segundo_apellido', 'apellido_2'],
        'seccion' => ['seccion', 'grupo'],
        'subgrupo' => ['subgrupo', 'sub_grupo'],
        'especialidad' => ['especialidad'],
        'fecha_nacimiento' => ['fecha_nacimiento', 'nacimiento'],
        'encargado_nombre' => ['encargado', 'encargado_legal', 'nombre_encargado', 'encargado_nombre'],
        'encargado_parentesco' => ['parentesco', 'encargado_parentesco'],
        'encargado_telefono' => ['telefono_encargado', 'telefono', 'celular', 'whatsapp', 'encargado_telefono'],
        'encargado_correo' => ['correo_encargado', 'correo', 'email', 'encargado_correo'],
    ];

    /**
     * Datos del encargado que se actualizan solo si la columna viene en el archivo.
     */
    private const CAMPOS_ENCARGADO = ['encargado_nombre', 'encargado_parentesco', 'encargado_telefono', 'encargado_correo'];

    private const NIVELES = [
        7 => 'Sétimo', 8 => 'Octavo', 9 => 'Noveno',
        10 => 'Décimo', 11 => 'Undécimo', 12 => 'Duodécimo',
    ];

    private int $anioMatricula;

    /**
     * Importa (o actualiza por cédula) los estudiantes de un archivo .xlsx o .csv y los deja
     * matriculados en el año indicado.
     *
     * @return array{creados: int, actualizados: int, errores: list<string>}
     */
    public function importar(string $ruta, string $extension, ?int $anioMatricula = null): array
    {
        $this->anioMatricula = $anioMatricula ?? Estudiante::cursoLectivo();
        $resultado = ['creados' => 0, 'actualizados' => 0, 'errores' => []];
        $mapa = null;

        foreach ($this->filas($ruta, strtolower($extension)) as $numero => $valores) {
            if ($mapa === null) {
                $mapa = $this->mapearEncabezados($valores);
                $faltantes = array_diff(['cedula', 'nombre', 'apellido1', 'seccion'], array_keys($mapa));

                if ($faltantes !== []) {
                    $resultado['errores'][] = 'Faltan columnas obligatorias en el encabezado: '.implode(', ', $faltantes).'.';

                    return $resultado;
                }

                continue;
            }

            $datos = array_map(fn (int $indice) => $this->texto($valores[$indice] ?? null), $mapa);

            if (implode('', $datos) === '') {
                continue;
            }

            try {
                $this->importarFila($datos, $mapa, $valores, $resultado);
            } catch (Throwable $error) {
                $resultado['errores'][] = "Fila {$numero}: {$error->getMessage()}";
            }
        }

        return $resultado;
    }

    /**
     * @param  array<string, string>  $datos
     * @param  array<string, int>  $mapa
     * @param  list<mixed>  $valores
     * @param  array{creados: int, actualizados: int, errores: list<string>}  $resultado
     */
    private function importarFila(array $datos, array $mapa, array $valores, array &$resultado): void
    {
        foreach (['cedula' => 'cédula', 'nombre' => 'nombre', 'apellido1' => 'primer apellido', 'seccion' => 'sección'] as $campo => $etiqueta) {
            if ($datos[$campo] === '') {
                throw new \InvalidArgumentException("falta el dato \"{$etiqueta}\".");
            }
        }

        $subgrupo = $this->subgrupo($datos['seccion'], $datos['subgrupo'] ?? '', $datos['especialidad'] ?? '');

        $estudiante = Estudiante::query()->firstOrNew(['cedula' => $datos['cedula']]);
        $esNuevo = ! $estudiante->exists;

        $estudiante->fill([
            'nombre' => $datos['nombre'],
            'apellido1' => $datos['apellido1'],
            'apellido2' => ($datos['apellido2'] ?? '') ?: null,
            'subgrupo_id' => $subgrupo->id,
            'activo' => true,
            // Nunca se retrocede: importar un archivo viejo no vence una matrícula ya actualizada.
            'anio_matricula' => max($this->anioMatricula, (int) $estudiante->anio_matricula),
        ]);

        foreach (self::CAMPOS_ENCARGADO as $campo) {
            if (isset($mapa[$campo])) {
                $estudiante->{$campo} = $datos[$campo] ?: null;
            }
        }

        if (isset($datos['encargado_correo']) && $datos['encargado_correo'] !== '' && ! filter_var($datos['encargado_correo'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("el correo del encargado \"{$datos['encargado_correo']}\" no es válido.");
        }

        if (isset($mapa['fecha_nacimiento'])) {
            $estudiante->fecha_nacimiento = $this->fecha($valores[$mapa['fecha_nacimiento']] ?? null);
        }

        $estudiante->save();

        $resultado[$esNuevo ? 'creados' : 'actualizados']++;
    }

    private function subgrupo(string $nombreSeccion, string $nombreSubgrupo, string $especialidad): Subgrupo
    {
        $nombreSeccion = strtoupper(str_replace(' ', '', $nombreSeccion));
        $nivel = (int) Str::before($nombreSeccion, '-');

        $seccion = Seccion::query()->firstOrCreate(
            ['nombre' => $nombreSeccion],
            ['nivel' => self::NIVELES[$nivel] ?? (string) $nivel],
        );

        $subgrupo = $seccion->subgrupos()->firstOrCreate(
            ['nombre' => strtoupper($nombreSubgrupo) ?: 'A'],
            ['especialidad' => $especialidad ?: null],
        );

        if ($especialidad !== '' && $subgrupo->especialidad !== $especialidad) {
            $subgrupo->update(['especialidad' => $especialidad]);
        }

        return $subgrupo;
    }

    /**
     * @param  list<mixed>  $encabezados
     * @return array<string, int>
     */
    private function mapearEncabezados(array $encabezados): array
    {
        $mapa = [];

        foreach ($encabezados as $indice => $encabezado) {
            $normalizado = Str::of($this->texto($encabezado))->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();

            foreach (self::COLUMNAS as $campo => $alias) {
                if (! isset($mapa[$campo]) && in_array($normalizado, $alias, true)) {
                    $mapa[$campo] = $indice;
                }
            }
        }

        return $mapa;
    }

    /**
     * @return \Generator<int, list<mixed>>
     */
    private function filas(string $ruta, string $extension): \Generator
    {
        $lector = $extension === 'xlsx'
            ? new LectorXlsx
            : new LectorCsv(new OpcionesCsv(FIELD_DELIMITER: $this->delimitador($ruta)));

        $lector->open($ruta);

        try {
            foreach ($lector->getSheetIterator() as $hoja) {
                $numero = 0;

                foreach ($hoja->getRowIterator() as $fila) {
                    $numero++;

                    yield $numero => $fila->toArray();
                }

                break; // solo la primera hoja
            }
        } finally {
            $lector->close();
        }
    }

    /**
     * Excel en español guarda los CSV con ";" en lugar de ",".
     */
    private function delimitador(string $ruta): string
    {
        $primeraLinea = (string) fgets($archivo = fopen($ruta, 'r'));
        fclose($archivo);

        return substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',') ? ';' : ',';
    }

    private function texto(mixed $valor): string
    {
        return match (true) {
            $valor instanceof DateTimeInterface => $valor->format('Y-m-d'),
            is_float($valor) && floor($valor) === $valor => (string) (int) $valor,
            default => trim((string) $valor),
        };
    }

    private function fecha(mixed $valor): ?string
    {
        if ($valor instanceof DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        $texto = $this->texto($valor);

        if ($texto === '') {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $texto)->toDateString();
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }
}
