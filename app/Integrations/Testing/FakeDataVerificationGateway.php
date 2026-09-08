<?php

declare(strict_types=1);

namespace App\Integrations\Testing;

use App\Integrations\Contracts\DataVerificationGateway;
use App\Integrations\Contracts\Values\VerificationOutcome;
use App\Integrations\Contracts\Values\VerificationRequest;

/**
 * Doble de la verificación de datos, para el demostrador y para las pruebas.
 *
 * Permite programar la respuesta de cada comprobación, incluida la indisponibilidad, que
 * es el caso que en producción ocurrirá a menudo y el que casi nunca se prueba.
 *
 * Este doble es la razón por la que se puede construir y validar TODO el dominio antes de
 * tener el alta administrativa en la Plataforma de Intermediación. Sin él, el proyecto
 * quedaría bloqueado por un trámite ajeno durante meses, que es exactamente lo que suele
 * pasar en estos desarrollos.
 */
final class FakeDataVerificationGateway implements DataVerificationGateway
{
    /** @var array<string, VerificationOutcome> */
    private array $programmed = [];

    /** @var list<VerificationRequest> */
    public array $received = [];

    /** @var list<string> */
    private array $supported;

    /** @param list<string> $supported */
    public function __construct(array $supported = ['income', 'property_ownership', 'tax_compliance', 'social_security_compliance', 'residence'])
    {
        $this->supported = $supported;
    }

    /** @param array<string, scalar|null> $data */
    public function willConfirm(string $check, array $data = []): self
    {
        $this->programmed[$check] = VerificationOutcome::confirmed($data);

        return $this;
    }

    public function willRefute(string $check, string $reason): self
    {
        $this->programmed[$check] = VerificationOutcome::refuted($reason);

        return $this;
    }

    /** El caso que hay que probar tanto como el feliz. */
    public function willBeUnavailable(string $check, string $reason = 'Servicio no disponible.'): self
    {
        $this->programmed[$check] = VerificationOutcome::unavailable($reason);

        return $this;
    }

    public function verify(VerificationRequest $request): VerificationOutcome
    {
        $this->received[] = $request;

        return $this->programmed[$request->check]
            ?? VerificationOutcome::unavailable('Sin respuesta programada para esta comprobación.');
    }

    /** @return list<string> */
    public function supportedChecks(): array
    {
        return $this->supported;
    }
}
