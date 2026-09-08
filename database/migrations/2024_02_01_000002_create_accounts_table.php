<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Credencial de acceso ciudadano. Vincula una `Person` con su identificador
 * federado de Cl@ve.
 *
 * §5.2 deja abierto si se admite además autenticación con contraseña local
 * (decisión pendiente, §24 del informe). Se modela `password` como nullable
 * para no bloquear esa decisión: si la autenticación acaba siendo
 * exclusivamente Cl@ve, la columna queda sin uso y se retira en una
 * migración posterior.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_id')->constrained('persons')->restrictOnDelete();

            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('clave_identifier')->nullable()->unique();
            $table->rememberToken();

            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();

            $table->timestamps();
        });

        DB::statement("COMMENT ON TABLE accounts IS 'Credenciales de acceso ciudadano. Retención: mientras la cuenta esté activa; tras baja voluntaria o inactividad prolongada, anonimización conforme a política de retención.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
