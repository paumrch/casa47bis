<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Manejadores de la bandeja de salida
    |--------------------------------------------------------------------------
    |
    | Cada clase implementa OutboxHandler y declara a qué eventos atiende. Un
    | evento sin manejador registrado va directo a la bandeja de fallos: es un
    | error de configuración, no de entrega, y reintentarlo no lo arregla.
    |
    */

    'handlers' => [
        // Pendiente: adaptadores reales de notificación (DEHú) y del sistema
        // económico. Hasta entonces esta lista está vacía a propósito, y el
        // despachador lo dice en voz alta si llega un evento sin destino.
    ],

];
