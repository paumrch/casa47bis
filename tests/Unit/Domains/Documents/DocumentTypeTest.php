<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Documents;

use App\Domains\Documents\DocumentType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Qué tipos de documento contienen datos de categoría especial del art. 9 RGPD.
 *
 * Sólo el certificado de discapacidad revela por sí mismo información de salud. El
 * resto son datos identificativos, económicos o laborales: sensibles por lo que
 * permiten decidir, pero no de categoría especial en el sentido estricto del RGPD.
 */
final class DocumentTypeTest extends TestCase
{
    #[Test]
    public function el_certificado_de_discapacidad_es_categoria_especial(): void
    {
        $this->assertTrue(DocumentType::DisabilityCertificate->containsSpecialCategoryData());
    }

    #[Test]
    public function ningun_otro_tipo_es_categoria_especial(): void
    {
        foreach (DocumentType::cases() as $type) {
            if ($type === DocumentType::DisabilityCertificate) {
                continue;
            }

            $this->assertFalse(
                $type->containsSpecialCategoryData(),
                "«{$type->value}» no debería marcarse como categoría especial.",
            );
        }
    }

    #[Test]
    public function values_devuelve_todos_los_valores_de_cadena(): void
    {
        $this->assertCount(count(DocumentType::cases()), DocumentType::values());
        $this->assertContains('disability_certificate', DocumentType::values());
    }
}
