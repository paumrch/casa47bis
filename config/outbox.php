<?php

declare(strict_types=1);
use App\Integrations\Notifications\ApplicationStatusNotifier;

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
        ApplicationStatusNotifier::class,

        // Pendiente: adaptador del sistema económico. El adaptador de notificación
        // real (DEHú) sustituirá al doble cambiando una línea de config/integrations.php.
    ],

];
