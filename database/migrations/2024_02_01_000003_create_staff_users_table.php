<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Personal gestor (empleado municipal, gestor externo o proveedor).
 *
 * Deliberadamente distinta de `accounts`: autenticación, ciclo de vida y
 * régimen de auditoría propios de personal interno, separados de la
 * identidad ciudadana (§5.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();

            $table->boolean('active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();

            $table->timestamps();
        });

        DB::statement("COMMENT ON TABLE staff_users IS 'Cuentas de personal gestor. Retención: mientras dure la relación laboral o contractual, más el plazo legal aplicable a registros de personal.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_users');
    }
};
