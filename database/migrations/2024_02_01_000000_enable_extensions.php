<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extensiones de PostgreSQL requeridas por el esquema.
 *
 * `pg_trgm`: búsqueda tolerante a erratas en municipio/vía (§12.2).
 * PostGIS se descarta deliberadamente: la geolocalización se resuelve con
 * dos columnas `numeric(9,6)` (lat/lon); el disparador para reconsiderarlo
 * es la aparición de un requisito con geometrías de área (§12.2, §12.4).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS pg_trgm');
    }
};
