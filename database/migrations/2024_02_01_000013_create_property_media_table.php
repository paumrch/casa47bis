<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fotografías, planos y tours virtuales de una vivienda. Sólo metadatos y
 * referencia en almacenamiento; nunca el binario (§9, decisión 9).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();

            $table->string('media_type', 20); // photo, floor_plan, virtual_tour
            $table->string('storage_key');
            $table->unsignedSmallInteger('position')->default(0);

            $table->timestamps();

            $table->index(['property_id', 'position']);
        });

        DB::statement("ALTER TABLE property_media ADD CONSTRAINT property_media_media_type_check CHECK (media_type IN ('photo', 'floor_plan', 'virtual_tour'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('property_media');
    }
};
