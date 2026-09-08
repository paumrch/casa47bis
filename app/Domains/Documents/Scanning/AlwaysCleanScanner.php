<?php

declare(strict_types=1);

namespace App\Domains\Documents\Scanning;

use App\Domains\Documents\Contracts\MalwareScanner;
use App\Domains\Documents\ScanStatus;

/**
 * Implementación de desarrollo y pruebas: no analiza nada, siempre responde `Clean`.
 *
 * NUNCA se usa en producción. Ahí el contrato `MalwareScanner` lo implementa un
 * adaptador que habla con un antivirus real (ClamAV, un servicio de análisis en la
 * nube…), y esa implementación vive en `app/Integrations/`, no en el dominio: hablar
 * con un proceso externo es justo lo que separa un puerto de un adaptador.
 *
 * Esta clase existe para que el dominio y sus pruebas puedan ejercitar el flujo
 * completo -subida, estado pendiente, descarga bloqueada, descarga permitida tras el
 * análisis- sin necesitar un antivirus real levantado. Que sea trivial es la garantía:
 * si escaneara de verdad, ya no sería una implementación de pruebas.
 */
final class AlwaysCleanScanner implements MalwareScanner
{
    public function scan(string $contents): ScanStatus
    {
        return ScanStatus::Clean;
    }
}
