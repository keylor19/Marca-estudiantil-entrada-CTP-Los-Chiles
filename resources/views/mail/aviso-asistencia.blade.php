<x-mail::message>
# Aviso de asistencia

Estimado(a) {{ $datos['encargado'] }}:

Le informamos que el/la estudiante **{{ $datos['estudiante'] }}** (sección {{ $datos['seccion'] }}) registró su **{{ $datos['accion'] }}** en el colegio.

<x-mail::panel>
**Fecha:** {{ $datos['fecha'] }}<br>
**Hora:** {{ $datos['hora'] }}
</x-mail::panel>

Este es un mensaje automático del sistema de control de asistencia; por favor no responda a este correo. Ante cualquier consulta comuníquese con la administración del colegio.

Saludos,<br>
{{ $datos['colegio'] }}
</x-mail::message>
