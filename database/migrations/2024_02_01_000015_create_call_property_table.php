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
            $table->foreignUuid('call_id')->constrained('calls')->cascadeOnDelete();
            $table->foreignUuid('property_id')->constrained('properties')->restrictOnDelete();

            $table->timestamps();

            // Clave primaria compuesta por la clave natural. Esta tabla tenía además una
            // columna `id` UUID, y se ha eliminado: no aportaba nada —la relación no
            // tiene atributos propios y (call_id, property_id) ya era única— y obligaba
            // a generar un identificador en cada `attach()`, que es justo lo que rompió
            // el sembrado. Una columna menos que mantener y un fallo menos que tener.
            $table->primary(['call_id', 'property_id']);
            $table->index('property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_property');
    }
};
