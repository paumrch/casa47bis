<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Documents;

use App\Domains\Documents\Scanning\AlwaysCleanScanner;
use App\Domains\Documents\ScanStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El escáner de desarrollo siempre da por limpio cualquier contenido.
 *
 * Es la implementación mínima del contrato `MalwareScanner`, pensada sólo para que el
 * dominio y sus pruebas puedan ejercitar el flujo sin un antivirus real levantado. En
 * producción el contrato lo implementa un adaptador contra un antivirus de verdad.
 */
final class AlwaysCleanScannerTest extends TestCase
{
    #[Test]
    public function siempre_devuelve_limpio_sea_cual_sea_el_contenido(): void
    {
        $scanner = new AlwaysCleanScanner;

        $this->assertSame(ScanStatus::Clean, $scanner->scan(''));
        $this->assertSame(ScanStatus::Clean, $scanner->scan('contenido cualquiera'));
        $this->assertSame(ScanStatus::Clean, $scanner->scan("\x7FELF binario simulado"));
    }
}
