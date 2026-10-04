/**
 * Pantalla del guarda. Los lectores USB funcionan como un teclado: "escriben" el código y
 * presionan ENTER. Por eso basta un campo que siempre tenga el foco.
 *
 * Algunos lectores no envían ENTER; la lectura se reconoce porque las teclas llegan mucho
 * más rápido de lo que escribe una persona, y se envía sola al terminar la ráfaga.
 */
const MS_ENTRE_TECLAS_LECTOR = 50; // un lector escribe cada ~5–30 ms; una persona, cada 100 ms o más
const MS_FIN_DE_LECTURA = 150; // pausa tras la última tecla para dar la lectura por terminada
const MIN_TECLAS_RAPIDAS = 4;
export default ({ url, segundos, ultimas, totalHoy, registrarSalida }) => ({
    codigo: '',
    modo: 'entrada',
    resultado: null,
    enviando: false,
    ultimas,
    totalHoy,
    registrarSalida,
    reloj: '',
    fecha: '',
    temporizador: null,
    audio: null,
    ultimaTecla: 0,
    teclasRapidas: 0,
    esperaLector: null,

    init() {
        this.actualizarReloj();
        setInterval(() => this.actualizarReloj(), 1000);
        this.enfocar();

        // Si el guarda hace clic en otro lado, el foco vuelve al campo del lector.
        document.addEventListener('click', () => this.enfocar());
        window.addEventListener('focus', () => this.enfocar());
    },

    enfocar() {
        this.$nextTick(() => this.$refs.campo?.focus());
    },

    actualizarReloj() {
        const ahora = new Date();
        this.reloj = ahora.toLocaleTimeString('es-CR', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        const fecha = ahora.toLocaleDateString('es-CR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        this.fecha = fecha.charAt(0).toUpperCase() + fecha.slice(1);
    },

    /**
     * Cada carácter que entra al campo: si llegan en ráfaga (lector), se envía solo al terminar.
     */
    alEscribir() {
        const ahora = performance.now();
        this.teclasRapidas = ahora - this.ultimaTecla <= MS_ENTRE_TECLAS_LECTOR ? this.teclasRapidas + 1 : 0;
        this.ultimaTecla = ahora;

        clearTimeout(this.esperaLector);

        if (this.teclasRapidas + 1 >= MIN_TECLAS_RAPIDAS) {
            this.esperaLector = setTimeout(() => this.enviar(), MS_FIN_DE_LECTURA);
        }
    },

    async enviar() {
        clearTimeout(this.esperaLector);
        this.teclasRapidas = 0;

        const codigo = this.codigo.trim();
        this.codigo = '';

        if (!codigo || this.enviando) {
            return;
        }

        this.enviando = true;

        try {
            const respuesta = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ codigo, modo: this.modo }),
            });

            if (respuesta.status === 419 || respuesta.status === 401) {
                window.location.reload(); // la sesión expiró

                return;
            }

            if (!respuesta.ok) {
                throw new Error(`HTTP ${respuesta.status}`);
            }

            this.mostrar(await respuesta.json());
        } catch (error) {
            this.mostrar({
                tipo: 'error',
                titulo: 'SIN CONEXIÓN CON EL SERVIDOR',
                mensaje: 'No se pudo registrar. Intente de nuevo o avise a administración.',
                estudiante: null,
            });
        } finally {
            this.enviando = false;
            this.enfocar();
        }
    },

    mostrar(resultado) {
        this.resultado = resultado;
        this.sonido(resultado.tipo);

        if (resultado.total_hoy !== undefined) {
            this.totalHoy = resultado.total_hoy;
        }

        if (resultado.estudiante && ['entrada', 'tarde', 'salida'].includes(resultado.resultado)) {
            this.ultimas.unshift({
                nombre: resultado.estudiante.nombre,
                seccion: resultado.estudiante.seccion,
                hora: resultado.resultado === 'salida' ? resultado.hora_salida : resultado.hora_entrada,
                tipo: resultado.resultado === 'salida' ? 'Salida' : 'Entrada',
            });
            this.ultimas = this.ultimas.slice(0, 8);
        }

        clearTimeout(this.temporizador);
        this.temporizador = setTimeout(() => (this.resultado = null), segundos * 1000);
    },

    /**
     * Pitido corto: agudo si todo bien, grave si hay que prestar atención.
     */
    sonido(tipo) {
        try {
            this.audio ??= new (window.AudioContext || window.webkitAudioContext)();
            const oscilador = this.audio.createOscillator();
            const volumen = this.audio.createGain();
            oscilador.frequency.value = tipo === 'exito' ? 1200 : tipo === 'advertencia' ? 660 : 220;
            volumen.gain.value = 0.15;
            oscilador.connect(volumen).connect(this.audio.destination);
            oscilador.start();
            oscilador.stop(this.audio.currentTime + (tipo === 'error' ? 0.6 : 0.15));
        } catch {
            // sin audio disponible
        }
    },

    get estilo() {
        return {
            exito: 'bg-emerald-600',
            advertencia: 'bg-amber-500',
            error: 'bg-red-600',
        }[this.resultado?.tipo] ?? 'bg-ctp-900';
    },
});
