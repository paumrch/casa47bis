<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Viviendas o promociones solicitadas, en orden de preferencia (§5.2). Una
 * preferencia apunta a una vivienda concreta o a una promoción completa,
 * nunca a ambas: se garantiza con un `CHECK`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignUuid('property_id')->nullable()->constrained('properties')->restrictOnDelete();
            $table->foreignUuid('development_id')->nullable()->constrained('developments')->restrictOnDelete();

            $table->unsignedSmallInteger('priority_order');

            $table->timestamps();

            $table->unique(['application_id', 'priority_order']);
        });

        DB::statement('ALTER TABLE application_preferences ADD CONSTRAINT application_preferences_target_check CHECK (num_nonnulls(property_id, development_id) = 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('application_preferences');
    }
};
