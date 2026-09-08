<?php

declare(strict_types=1);

namespace App\Domains\Eligibility\Rules;

use App\Domains\Eligibility\Values\ApplicantProfile;
use App\Domains\Eligibility\Values\EligibilityResult;

/**
 * Un conjunto de reglas de elegibilidad, identificado por versión.
 *
 * POR QUÉ ESTO ESTÁ VERSIONADO
 *
 * Las reglas cambian: cambia el IPREM cada año, cambian los umbrales, cambian las
 * excepciones. Y una solicitud resuelta en 2026 debe poder reevaluarse en 2029 -ante un
 * recurso, una inspección o un tribunal- con las reglas que estaban vigentes cuando se
 * resolvió, no con las de hoy.
 *
 * Por eso la solicitud almacena la versión del conjunto de reglas que se le aplicó, y
 * las versiones antiguas NO SE BORRAN NUNCA. Ocupan unos kilobytes y son la diferencia
 * entre poder justificar una decisión y no poder hacerlo.
 */
interface RuleSet
{
    /** Identificador estable. Se guarda en `applications.eligibility_rule_version`. */
    public function version(): string;

    /** Fecha desde la que este conjunto es aplicable. */
    public function effectiveFrom(): \DateTimeImmutable;

    public function evaluate(ApplicantProfile $profile): EligibilityResult;

    /**
     * Los requisitos, redactados para el ciudadano.
     *
     * Existe para que la página pública de requisitos se genere DESDE AQUÍ y no se
     * escriba a mano en una plantilla. Es el mismo problema que ya nos mordió con la
     * enumeración de estados y la restricción de la base: dos copias de la misma verdad
     * acaban divergiendo, y aquí la divergencia significa publicar unos requisitos y
     * aplicar otros. En un procedimiento con derechos de por medio, eso no es un fallo
     * de contenido: es un vicio del procedimiento.
     *
     * @return list<array{code: string, title: string, detail: string}>
     */
    public function describe(): array;
}
