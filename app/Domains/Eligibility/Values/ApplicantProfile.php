<?php

declare(strict_types=1);

namespace App\Domains\Eligibility\Values;

/**
 * Fotografía de la unidad de convivencia en el momento de evaluarla.
 *
 * Es un objeto inmutable y sin dependencias del framework a propósito: las reglas de
 * elegibilidad deben poder ejecutarse y probarse sin base de datos, sin HTTP y sin
 * servicios externos. Es lo que permite tener cientos de casos límite en las pruebas
 * sin que la suite tarde minutos.
 *
 * Los datos llegan aquí ya verificados, sea por consulta a la Plataforma de
 * Intermediación de Datos o por documentación aportada. Este objeto no sabe de dónde
 * vienen y no debe saberlo.
 */
final readonly class ApplicantProfile
{
    /**
     * @param  int  $householdSize  Número de miembros de la unidad de convivencia.
     * @param  Money  $annualNetIncome  Ingresos netos anuales agregados de la unidad.
     * @param  bool  $hasLegalResidence  Nacionalidad española o residencia legal acreditada.
     * @param  bool  $ownsProperty  Titular de vivienda en propiedad.
     * @param  bool  $ownedPropertyIsUnusable  Excepción: la vivienda en propiedad no es utilizable (indivisa, en usufructo ajeno, inhabitable…).
     * @param  bool  $isCurrentWithTaxAuthority  Al corriente con la AEAT.
     * @param  bool  $isCurrentWithSocialSecurity  Al corriente con la Seguridad Social.
     * @param  bool  $requiresAccessibleHousing  Necesita vivienda adaptada. Dato de salud: art. 9 RGPD.
     * @param  int  $minorsInHousehold  Menores a cargo.
     */
    public function __construct(
        public int $householdSize,
        public Money $annualNetIncome,
        public bool $hasLegalResidence,
        public bool $ownsProperty,
        public bool $ownedPropertyIsUnusable = false,
        public bool $isCurrentWithTaxAuthority = true,
        public bool $isCurrentWithSocialSecurity = true,
        public bool $requiresAccessibleHousing = false,
        public int $minorsInHousehold = 0,
    ) {
        if ($householdSize < 1) {
            throw new \InvalidArgumentException('Una unidad de convivencia tiene al menos un miembro.');
        }

        if ($minorsInHousehold > $householdSize) {
            throw new \InvalidArgumentException('Hay más menores que miembros en la unidad.');
        }
    }
}
