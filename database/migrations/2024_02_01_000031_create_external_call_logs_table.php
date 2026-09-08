<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traza de cada llamada a un servicio externo, con petición, respuesta y
 * correlación (§5.2). Necesario para poder demostrar qué respondió la
 * Administración (o un tercero) un día concreto. `correlation_id` enlaza
 * varias llamadas de un mismo flujo (por ejemplo, una verificación que
 * dispara dos consultas encadenadas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_call_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('correlation_id');

            $table->string('service_name', 100);
            $table->string('endpoint');
            $table->string('http_method', 10);
            $table->jsonb('request_payload')->nullable();
            $table->jsonb('response_payload')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('success');
            $table->text('error_message')->nullable();

            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index('correlation_id');
            $table->index('service_name');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_call_logs');
    }
};
