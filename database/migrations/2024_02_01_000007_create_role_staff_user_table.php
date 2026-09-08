<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivote rol–personal gestor. Un `StaffUser` puede tener varios roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_staff_user', function (Blueprint $table) {
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUuid('staff_user_id')->constrained('staff_users')->cascadeOnDelete();
            $table->primary(['role_id', 'staff_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_staff_user');
    }
};
