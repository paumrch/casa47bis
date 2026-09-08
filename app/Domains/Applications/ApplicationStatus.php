<?php

declare(strict_types=1);

namespace App\Domains\Applications;

/**
 * Estados de una solicitud de vivienda.
 *
 * Esta enumeración es la única fuente de verdad del procedimiento. No hay un motor
 * de procesos externo, y es deliberado: un procedimiento administrativo cambia cuando
 * cambia la norma —no semanalmente—, y su modificación exige análisis, pruebas y
 * despliegue controlado porque afecta a derechos de las personas.
 *
 * Tener el flujo aquí significa que está versionado en git, que se revisa como código
 * y que una prueba automática puede recorrerlo entero. Un motor gráfico habría puesto
 * la lógica en un segundo lugar que mantener sincronizado con la base de datos.
 */
enum ApplicationStatus: string
{
    /** Borrador del ciudadano. Todavía no ha producido efectos jurídicos. */
    case Draft = 'draft';

    /** Presentada. A partir de aquí hay fecha de registro y plazos que corren. */
    case Submitted = 'submitted';

    /** En comprobación de requisitos frente a terceros o mediante documentación. */
    case UnderVerification = 'under_verification';

    /** Falta documentación o aclaración. El plazo del ciudadano está corriendo. */
    case AwaitingApplicant = 'awaiting_applicant';

    /** Cumple los requisitos. Entra en baremación. */
    case Eligible = 'eligible';

    /** No cumple. Resolución motivada y recurrible. */
    case Ineligible = 'ineligible';

    /** Baremada y a la espera del acto de adjudicación. */
    case Scored = 'scored';

    /** Se le ha adjudicado una vivienda. Corre el plazo de aceptación. */
    case Awarded = 'awarded';

    /** Ha aceptado la adjudicación. */
    case Accepted = 'accepted';

    /** Ha renunciado a la vivienda adjudicada. */
    case Declined = 'declined';

    /** Contrato de arrendamiento formalizado. Fin del procedimiento. */
    case Contracted = 'contracted';

    /** Desistimiento del ciudadano. */
    case Withdrawn = 'withdrawn';

    /** Caducada por inactividad tras requerimiento. */
    case Expired = 'expired';

    /**
     * Transiciones permitidas.
     *
     * Se declaran de forma exhaustiva a propósito: lo que no está aquí no puede
     * ocurrir, y añadir un camino nuevo obliga a tocar este fichero, que es donde
     * un revisor lo va a buscar.
     *
     * @return list<self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted, self::Withdrawn],
            self::Submitted => [self::UnderVerification, self::Withdrawn],
            self::UnderVerification => [
                self::Eligible,
                self::Ineligible,
                self::AwaitingApplicant,
                self::Withdrawn,
            ],
            self::AwaitingApplicant => [self::UnderVerification, self::Expired, self::Withdrawn],
            self::Eligible => [self::Scored, self::Withdrawn],
            self::Scored => [self::Awarded, self::Withdrawn],
            self::Awarded => [self::Accepted, self::Declined, self::Expired],
            self::Accepted => [self::Contracted, self::Declined],

            // Estados finales. Un expediente no vuelve a la vida: si hay que
            // rectificar, se abre uno nuevo con referencia al anterior. Esto no es
            // rigidez técnica, es cómo funciona un procedimiento administrativo.
            self::Ineligible,
            self::Declined,
            self::Contracted,
            self::Withdrawn,
            self::Expired => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedNext(), strict: true);
    }

    /** Un estado final no admite ninguna transición posterior. */
    public function isFinal(): bool
    {
        return $this->allowedNext() === [];
    }

    /**
     * A partir de la presentación, la solicitud produce efectos jurídicos y su
     * modificación exige trazabilidad reforzada.
     */
    public function hasLegalEffect(): bool
    {
        return $this !== self::Draft;
    }

    /** Etiqueta para el ciudadano. Se traduce en la capa de presentación. */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Submitted => 'Presentada',
            self::UnderVerification => 'En comprobación',
            self::AwaitingApplicant => 'Pendiente de documentación',
            self::Eligible => 'Admitida',
            self::Ineligible => 'Excluida',
            self::Scored => 'Baremada',
            self::Awarded => 'Vivienda adjudicada',
            self::Accepted => 'Adjudicación aceptada',
            self::Declined => 'Renuncia',
            self::Contracted => 'Contrato firmado',
            self::Withdrawn => 'Desistida',
            self::Expired => 'Caducada',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}
