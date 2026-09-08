<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sesiones HTTP. Reemplaza la migración por defecto de Laravel: en lugar de
 * un único `user_id`, usa un morph (`authenticatable_type`/`authenticatable_id`)
 * porque hay dos guards independientes -`accounts` (ciudadanía) y
 * `staff_users` (personal gestor)- con ciclo de vida y autenticación
 * distintos (§5.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->nullableUuidMorphs('authenticatable');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
