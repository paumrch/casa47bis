<?php

declare(strict_types=1);

namespace App\Domains\Documents;

/**
 * Tipos de documento acreditativo que un solicitante puede aportar a un expediente.
 *
 * `containsSpecialCategoryData()` marca los tipos que, además de datos personales
 * corrientes, contienen datos de categoría especial del art. 9 RGPD. El certificado de
 * discapacidad es el caso claro: revela información de salud. El resto de tipos de esta
 * lista son datos identificativos, económicos o laborales — sensibles por lo que
 * permiten decidir (el acceso a una vivienda pública), pero no de categoría especial en
 * el sentido estricto del reglamento.
 *
 * Esta distinción no es cosmética: en el módulo de auditoría y en cualquier exportación
 * futura de datos personales, los documentos marcados aquí exigen el tratamiento
 * reforzado que el RGPD impone a la categoría especial (base jurídica explícita,
 * medidas de seguridad adicionales).
 */
enum DocumentType: string
{
    /** Documento Nacional de Identidad o Número de Identidad de Extranjero. */
    case NationalId = 'national_id';

    /** Declaración del Impuesto sobre la Renta de las Personas Físicas. */
    case IncomeTaxReturn = 'income_tax_return';

    /** Nómina del solicitante o de un miembro de la unidad de convivencia. */
    case Payslip = 'payslip';

    /** Certificado de empadronamiento. */
    case ResidencyCertificate = 'residency_certificate';

    /** Certificado de discapacidad. Revela datos de salud: categoría especial. */
    case DisabilityCertificate = 'disability_certificate';

    /** Libro de familia. */
    case FamilyRecordBook = 'family_record_book';

    /** Contrato de trabajo vigente. */
    case EmploymentContract = 'employment_contract';

    /** Informe de vida laboral de la Seguridad Social. */
    case EmploymentHistory = 'employment_history';

    /** Cualquier otro documento acreditativo no catalogado explícitamente. */
    case Other = 'other';

    /**
     * Si el tipo contiene datos de categoría especial del art. 9 RGPD.
     *
     * Sólo el certificado de discapacidad lo hace: es el único de esta lista que
     * revela por sí mismo información sobre la salud de la persona.
     */
    public function containsSpecialCategoryData(): bool
    {
        return $this === self::DisabilityCertificate;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }
}
