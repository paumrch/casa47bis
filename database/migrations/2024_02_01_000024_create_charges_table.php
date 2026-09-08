<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recibo emitido de un contrato (§5.2). Un recibo por contrato y periodo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lease_id')->constrained('leases')->restrictOnDelete();

            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->string('status', 20); // pending, paid, partially_paid, overdue, cancelled

            $table->timestamps();

            $table->unique(['lease_id', 'period_year', 'period_month']);
            $table->index('status');
            $table->index('due_date');
        });

        DB::statement("ALTER TABLE charges ADD CONSTRAINT charges_status_check CHECK (status IN ('pending', 'paid', 'partially_paid', 'overdue', 'cancelled'))");
        DB::statement("COMMENT ON TABLE charges IS 'Datos económicos (§5.4): acceso restringido, nunca en registros de log. Retención conforme al plazo fiscal aplicable a documentos de cobro.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
