<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promoción: conjunto de viviendas de una actuación.
 *
 * Sin `Location` como entidad propia (§5.2): municipio, provincia y código
 * postal son atributos; la geometría son dos columnas numéricas (§12.2),
 * sin PostGIS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('developments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('developer_name')->nullable();
            $table->string('construction_status', 30); // planned, under_construction, finished

            $table->string('address_line')->nullable();
            $table->string('municipality');
            $table->string('province');
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();

            $table->timestamps();

            $table->index('municipality');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('developments');
    }
};
