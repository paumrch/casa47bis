<?php

declare(strict_types=1);

use App\Integrations\Scsp\UnavailableDataVerificationGateway;
use App\Integrations\Testing\FakeDataVerificationGateway;
use App\Integrations\Testing\RecordingNotificationGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Adaptadores de integración
    |--------------------------------------------------------------------------
    |
    | Cada puerto del dominio se resuelve aquí a un adaptador concreto. Cambiar de
    | proveedor (o desplegar sin uno) es cambiar una línea de este fichero.
    |
    | El valor POR DEFECTO de la verificación de datos es el adaptador que declara
    | la comprobación automática como no disponible. Es deliberado: mientras no
    | exista el alta en la Plataforma de Intermediación, el convenio de cesión y
    | la conectividad con Red SARA, el sistema funciona por vía documental. Y
    | funciona, no se queda esperando.
    |
    */

    'data_verification' => env('DATA_VERIFICATION_ADAPTER', UnavailableDataVerificationGateway::class),

    'notifications' => env('NOTIFICATION_ADAPTER', RecordingNotificationGateway::class),

    /*
    | Dobles disponibles para el demostrador y las pruebas. Se enumeran aquí para
    | que quede constancia de que existen y de para qué sirven.
    */
    'doubles' => [
        'data_verification' => FakeDataVerificationGateway::class,
        'notifications' => RecordingNotificationGateway::class,
    ],

];
