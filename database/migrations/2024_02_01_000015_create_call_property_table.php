<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qué viviendas entran en qué convocatoria. Relación N:M con datos propios
 * (§5.2): no es un pivote ciego, lleva su propio identificador por si en el
 * futuro necesita atributos (cupo asignado a esa vivienda en esa
 * convocatoria, por ejemplo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_property', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('call_id')->constrained('calls')->cascadeOnDelete();
            $table->foreignUuid('property_id')->constrained('properties')->restrictOnDelete();

            $table->timestamps();

            $table->unique(['call_id', 'property_id']);
            $table->index('property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_property');
    }
};
