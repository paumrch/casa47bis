<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Evento pendiente de entregar a un sistema externo (§5.2, §12.3). Su
 * valor está en compartir transacción con el dominio: se inserta en la
 * misma transacción que el cambio de estado que lo origina, evitando la
 * doble escritura entre base de datos y bus de mensajes.
 *
 * Índice parcial `(delivered_at, available_at) WHERE delivered_at IS NULL`
 * para que el trabajador que reparte eventos sólo recorra los pendientes,
 * usando `SELECT ... FOR UPDATE SKIP LOCKED` (§12.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_events', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('aggregate_type', 100);
            $table->uuid('aggregate_id');
            $table->string('event_name', 100);
            $table->jsonb('payload');

            $table->timestamp('occurred_at');
            $table->timestamp('available_at');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('dead_lettered_at')->nullable();

            $table->index(['aggregate_type', 'aggregate_id']);
        });

        DB::statement('CREATE INDEX outbox_events_pending_idx ON outbox_events (delivered_at, available_at) WHERE delivered_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_events');
    }
};
