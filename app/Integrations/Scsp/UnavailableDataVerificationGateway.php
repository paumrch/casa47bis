<?php

declare(strict_types=1);

namespace App\Integrations\Scsp;

use App\Integrations\Contracts\DataVerificationGateway;
use App\Integrations\Contracts\Values\VerificationOutcome;
use App\Integrations\Contracts\Values\VerificationRequest;
use Psr\Log\LoggerInterface;

/**
 * Adaptador que declara que la verificación automática NO está disponible.
 *
 * NO ES UN HUECO NI UN MARCADOR DE POSICIÓN. Es el adaptador que se despliega en
 * producción el primer día, y probablemente durante meses.
 *
 * Consumir la Plataforma de Intermediación de Datos exige, antes que una línea de
 * código: el alta del organismo, un convenio de cesión ligado a este procedimiento
 * concreto, certificados de componente, un entorno de pruebas y conectividad con Red
 * SARA —por convenio NubeSARA con la SGAD o por un punto de presencia propio—. Nada de
 * eso lo resuelve el equipo de desarrollo, y ninguno de esos trámites tiene una fecha que
 * dependa de nosotros.
 *
 * La consecuencia de diseño es la que importa: el sistema debe funcionar sin ese
 * adaptador desde el primer día. Con este, cada comprobación devuelve `unavailable`, el
 * expediente cae a la vía documental y el procedimiento sigue su curso. Cuando llegue el
 * alta, se cambia una línea de configuración.
 *
 * Escribir en su lugar un adaptador SCSP «terminado» que nunca se ha probado contra el
 * servicio real sería peor que esto: daría la impresión de que la integración está hecha.
 */
final readonly class UnavailableDataVerificationGateway implements DataVerificationGateway
{
    public function __construct(private LoggerInterface $logger) {}

    public function verify(VerificationRequest $request): VerificationOutcome
    {
        $this->logger->info('Verificación automática no disponible; se deriva a vía documental.', [
            'check' => $request->check,
            'correlation_id' => $request->correlationId,
        ]);

        return VerificationOutcome::unavailable(
            'No hay integración activa con la Plataforma de Intermediación de Datos. '.
            'La comprobación se realizará mediante la documentación aportada.'
        );
    }

    /**
     * Ninguna. Se declara vacío a propósito: el dominio consulta esta lista para saber
     * qué puede automatizar, y así pide desde el principio la documentación de todo.
     *
     * @return list<string>
     */
    public function supportedChecks(): array
    {
        return [];
    }
}
