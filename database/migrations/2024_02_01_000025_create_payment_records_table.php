<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cobro registrado contra un recibo (§5.2). Un recibo puede tener varios
 * cobros parciales, de ahí que no se fusione con `charges`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('charge_id')->constrained('charges')->restrictOnDelete();

            $table->decimal('amount', 10, 2);
            $table->timestamp('paid_at');
            $table->string('payment_method', 30);
            $table->string('reference')->nullable();

            $table->timestamps();

            $table->index('charge_id');
            $table->index('paid_at');
        });

        DB::statement("COMMENT ON TABLE payment_records IS 'Datos económicos (§5.4): acceso restringido, nunca en registros de log. Retención conforme al plazo fiscal aplicable.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_records');
    }
};
