<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El sembrado de demostración funciona.
 *
 * Parece una prueba trivial y no lo es: el sembrado se rompió en cuanto se escribió,
 * porque el esqueleto de Laravel trae `WithoutModelEvents` en el seeder por defecto y
 * eso desactivaba la generación de identificadores. El fallo no se veía leyendo el
 * código; sólo aparecía al insertar.
 *
 * Un demostrador que no se puede poblar no demuestra nada, así que esto se comprueba.
 */
final class SembradoDeDemostracionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function el_catalogo_de_demostracion_se_genera_completo(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, DB::table('developments')->count(), 'Sin promociones.');
        $this->assertGreaterThan(0, DB::table('properties')->count(), 'Sin viviendas.');
        $this->assertGreaterThan(0, DB::table('calls')->count(), 'Sin convocatorias.');
        $this->assertGreaterThan(0, DB::table('call_property')->count(), 'Ninguna vivienda asociada a una convocatoria.');
    }

    #[Test]
    public function todas_las_filas_sembradas_tienen_identificador(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['developments', 'properties', 'calls'] as $table) {
            $this->assertSame(
                0,
                DB::table($table)->whereNull('id')->count(),
                "Hay filas sin identificador en «{$table}».",
            );
        }
    }

    #[Test]
    public function hay_una_convocatoria_abierta_para_el_demostrador(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(
            0,
            DB::table('calls')->where('status', 'open')->count(),
            'El demostrador necesita al menos una convocatoria abierta.',
        );
    }
}
