<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registro inmutable de quién hizo qué, cuándo, sobre qué y desde dónde
 * (§5.2). Sólo inserción: sin `updated_at`, y el usuario de aplicación debe
 * tener revocados `UPDATE`/`DELETE` sobre esta tabla a nivel de permisos de
 * base de datos (§12.3), eso se gestiona en el aprovisionamiento del rol
 * de conexión, fuera del alcance de esta migración, que no conoce ese rol.
 *
 * Particionada por RANGE sobre `occurred_at` (§12.3, §5.5): es la tabla
 * mayor del sistema (10⁷ filas a cuatro años) y de sólo inserción, el caso
 * de libro para particionado por fecha. La clave de partición debe formar
 * parte de toda restricción única, de ahí la clave primaria compuesta
 * `(id, occurred_at)` en lugar de `id` solo.
 *
 * `previous_hash`/`hash`: cada fila encadena el hash de la anterior, de
 * modo que una manipulación posterior sea detectable (exigencia del ENS
 * sobre integridad de la traza, §12.3).
 *
 * Se crean las particiones del año en curso más una adicional, y una
 * función `audit_events_create_partition_for(date)` para crear las
 * siguientes. Debe invocarse desde un trabajo programado mensual (por
 * ejemplo, `SELECT audit_events_create_partition_for(CURRENT_DATE + INTERVAL '2 months')`
 * ejecutado el día 1 de cada mes) para mantener siempre una partición de
 * margen; no se resuelve solo con esta migración porque la creación de
 * particiones futuras es responsabilidad de un proceso recurrente, no de
 * un cambio de esquema puntual.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE audit_events (
                id uuid NOT NULL,
                occurred_at timestamptz NOT NULL,
                actor_type varchar(100),
                actor_id uuid,
                action varchar(100) NOT NULL,
                entity_type varchar(100) NOT NULL,
                entity_id uuid,
                ip_address varchar(45),
                metadata jsonb,
                previous_hash varchar(64),
                hash varchar(64) NOT NULL,
                PRIMARY KEY (id, occurred_at)
            ) PARTITION BY RANGE (occurred_at)
        SQL);

        DB::statement('CREATE INDEX audit_events_entity_idx ON audit_events (entity_type, entity_id, occurred_at)');
        DB::statement('CREATE INDEX audit_events_actor_idx ON audit_events (actor_type, actor_id, occurred_at)');

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION audit_events_create_partition_for(target date)
            RETURNS void AS $$
            DECLARE
                partition_name text := 'audit_events_y' || to_char(target, 'YYYY') || 'm' || to_char(target, 'MM');
                range_start date := date_trunc('month', target);
                range_end date := range_start + interval '1 month';
            BEGIN
                EXECUTE format(
                    'CREATE TABLE IF NOT EXISTS %I PARTITION OF audit_events FOR VALUES FROM (%L) TO (%L)',
                    partition_name, range_start, range_end
                );
            END;
            $$ LANGUAGE plpgsql
        SQL);

        DB::statement("COMMENT ON FUNCTION audit_events_create_partition_for(date) IS 'Crea (si no existe) la partición mensual de audit_events que contiene la fecha dada. Ejecutar mensualmente por adelantado desde un trabajo programado.'");

        // Particiones del año en curso más una de margen.
        $year = (int) now()->format('Y');
        for ($month = 1; $month <= 12; $month++) {
            DB::statement("SELECT audit_events_create_partition_for('{$year}-".str_pad((string) $month, 2, '0', STR_PAD_LEFT)."-01')");
        }
        $nextYear = $year + 1;
        DB::statement("SELECT audit_events_create_partition_for('{$nextYear}-01-01')");

        DB::statement("COMMENT ON TABLE audit_events IS 'Retención: conforme a la política de conservación de trazas de auditoría exigida por el ENS; sólo inserción, sin borrado ni actualización por la aplicación.'");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS audit_events CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS audit_events_create_partition_for(date)');
    }
};
