<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Comunicación emitida y su acuse (§5.2). `kind` distingue **notificación
 * fehaciente** (con efectos jurídicos y plazos) de **aviso informativo**:
 * confundirlas es un defecto grave en un procedimiento administrativo, así
 * que se modela como columna obligatoria con `CHECK`, no como convención
 * en el nombre del canal. `acknowledged_at` sólo tiene sentido para las
 * fehacientes, de ahí que sea nullable independiente de `delivered_at`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('notifiable');
            $table->foreignUuid('application_id')->nullable()->constrained('applications')->restrictOnDelete();

            $table->string('channel', 20); // email, sms, postal, sede_electronica
            $table->string('kind', 15); // fehaciente, informativo
            $table->string('subject');
            $table->text('body')->nullable();
            $table->string('status', 15); // queued, sent, delivered, failed, acknowledged

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();

            $table->timestamps();

            $table->index('kind');
            $table->index('status');
        });

        DB::statement("ALTER TABLE notification_records ADD CONSTRAINT notification_records_kind_check CHECK (kind IN ('fehaciente', 'informativo'))");
        DB::statement("ALTER TABLE notification_records ADD CONSTRAINT notification_records_status_check CHECK (status IN ('queued', 'sent', 'delivered', 'failed', 'acknowledged'))");
        DB::statement("COMMENT ON TABLE notification_records IS 'Retención: las fehacientes se conservan como parte del expediente, con el mismo régimen que applications; los avisos informativos siguen la política general de comunicaciones.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_records');
    }
};
