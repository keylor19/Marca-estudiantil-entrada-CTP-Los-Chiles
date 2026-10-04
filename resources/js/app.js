import Alpine from 'alpinejs';
import escaner from './escaner';

const formatoFecha = (fecha) =>
    `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`;

/**
 * Filtro de período libre con atajos (hoy, semana, mes, últimos 3 meses, curso lectivo).
 */
Alpine.data('filtroPeriodo', (desde, hasta) => ({
    desde,
    hasta,
    rango(tipo) {
        const hoy = new Date();
        let inicio = new Date(hoy);

        if (tipo === 'semana') {
            inicio.setDate(hoy.getDate() - ((hoy.getDay() + 6) % 7));
        } else if (tipo === 'mes') {
            inicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        } else if (tipo === 'trimestre') {
            inicio = new Date(hoy.getFullYear(), hoy.getMonth() - 3, hoy.getDate());
        } else if (tipo === 'curso') {
            inicio = new Date(hoy.getFullYear(), 1, 1);
        }

        this.desde = formatoFecha(inicio);
        this.hasta = formatoFecha(hoy);
    },
}));

Alpine.data('escaner', escaner);

window.Alpine = Alpine;

Alpine.start();
