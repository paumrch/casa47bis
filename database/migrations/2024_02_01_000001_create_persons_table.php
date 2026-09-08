<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Persona física identificada por documento (NIF/NIE).
 *
 * Se separa deliberadamente de `accounts`: una persona puede constar en un
 * expediente (por ejemplo, como miembro de una unidad de convivencia) sin
 * haberse registrado nunca como usuaria del sistema. Ver §5.2 y §5.3 del
 * informe de arquitectura.
 *
 * Datos identificativos (nombre, documento, domicilio, contacto): acceso
 * restringido por rol y auditoría de toda lectura, conforme a §5.4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('document_type', 10); // NIF, NIE, PASSPORT...
            $table->string('document_number', 20);
            $table->string('first_name');
            $table->string('last_name_1');
            $table->string('last_name_2')->nullable();
            $table->date('birth_date')->nullable();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('address_line')->nullable();
            $table->string('municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();

            $table->timestamps();

            $table->unique(['document_type', 'document_number']);
            $table->index('municipality');
        });

        DB::statement("COMMENT ON TABLE persons IS 'Datos identificativos y de contacto de personas físicas. Retención: mientras exista un expediente o vínculo activo, más el plazo legal de conservación de expedientes administrativos tras su archivo; después, bloqueo y purga programada.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
