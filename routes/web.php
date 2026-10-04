<?php

use App\Http\Controllers\CarnetController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EscanerController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\SubgrupoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::get('/logo', [ConfiguracionController::class, 'logo'])->name('logo');

Route::middleware('auth')->group(function () {
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');

    // Todos los roles (el guarda necesita la foto para la pantalla de ingreso).
    Route::middleware('rol:admin,direccion,guarda')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/estudiantes/{estudiante}/foto', [EstudianteController::class, 'foto'])->name('estudiantes.foto');
    });

    // Pantalla de ingreso (lector de código de barras).
    Route::middleware('rol:admin,guarda')->group(function () {
        Route::get('/escaner', [EscanerController::class, 'index'])->name('escaner.index');
        Route::post('/escaner', [EscanerController::class, 'registrar'])->name('escaner.registrar');
    });

    // Administración completa.
    Route::middleware('rol:admin')->group(function () {
        Route::get('/estudiantes/importar', [ImportacionController::class, 'create'])->name('estudiantes.importar');
        Route::post('/estudiantes/importar', [ImportacionController::class, 'store'])->name('estudiantes.importar.store');
        Route::get('/estudiantes/plantilla', [ImportacionController::class, 'plantilla'])->name('estudiantes.plantilla');
        Route::get('/estudiantes/fotos', [ImportacionController::class, 'fotos'])->name('estudiantes.fotos');
        Route::post('/estudiantes/fotos', [ImportacionController::class, 'guardarFotos'])->name('estudiantes.fotos.store');
        Route::post('/estudiantes/deshabilitar-sin-matricula', [EstudianteController::class, 'deshabilitarSinMatricula'])->name('estudiantes.deshabilitar-sin-matricula');

        Route::resource('estudiantes', EstudianteController::class)->except(['index', 'show']);

        Route::get('/carnets', [CarnetController::class, 'index'])->name('carnets.index');
        Route::get('/carnets/imprimir', [CarnetController::class, 'imprimir'])->name('carnets.imprimir');

        Route::resource('secciones', SeccionController::class)->except('show')->parameters(['secciones' => 'seccion']);
        Route::post('/secciones/{seccion}/subgrupos', [SubgrupoController::class, 'store'])->name('subgrupos.store');
        Route::put('/subgrupos/{subgrupo}', [SubgrupoController::class, 'update'])->name('subgrupos.update');
        Route::delete('/subgrupos/{subgrupo}', [SubgrupoController::class, 'destroy'])->name('subgrupos.destroy');

        Route::delete('/registros/{asistencia}', [RegistroController::class, 'destroy'])->name('registros.destroy');

        Route::resource('usuarios', UsuarioController::class)->except('show')->parameters(['usuarios' => 'usuario']);

        Route::get('/configuracion', [ConfiguracionController::class, 'edit'])->name('configuracion.edit');
        Route::put('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
        Route::post('/configuracion/probar-avisos', [ConfiguracionController::class, 'probarAvisos'])->middleware('throttle:10,1')->name('configuracion.probar-avisos');
        Route::post('/configuracion/dias-no-lectivos', [ConfiguracionController::class, 'guardarDiaNoLectivo'])->name('dias-no-lectivos.store');
        Route::delete('/configuracion/dias-no-lectivos/{diaNoLectivo}', [ConfiguracionController::class, 'eliminarDiaNoLectivo'])->name('dias-no-lectivos.destroy');
    });

    // Consulta: administración y dirección.
    Route::middleware('rol:admin,direccion')->group(function () {
        Route::get('/estudiantes', [EstudianteController::class, 'index'])->name('estudiantes.index');
        Route::get('/estudiantes/{estudiante}', [EstudianteController::class, 'show'])->name('estudiantes.show');

        Route::get('/registros', [RegistroController::class, 'index'])->name('registros.index');

        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/estudiante', [ReporteController::class, 'estudiante'])->name('reportes.estudiante');
        Route::get('/reportes/grupo', [ReporteController::class, 'grupo'])->name('reportes.grupo');
        Route::get('/reportes/secciones', [ReporteController::class, 'secciones'])->name('reportes.secciones');
        Route::get('/reportes/diario', [ReporteController::class, 'diario'])->name('reportes.diario');
    });
});

require __DIR__.'/auth.php';
