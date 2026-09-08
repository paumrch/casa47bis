<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * NO usa el rasgo `WithoutModelEvents`, que viene por defecto en el esqueleto de
 * Laravel. Aquí sería activamente dañino: los identificadores UUID v7 se generan en el
 * evento `creating` de cada modelo, y desactivar los eventos hacía que el sembrado
 * intentara insertar filas sin clave primaria.
 *
 * Se deja escrito porque el fallo era silencioso hasta el momento del INSERT y no es
 * evidente leyendo el rasgo.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogoDemoSeeder::class);
    }
}
