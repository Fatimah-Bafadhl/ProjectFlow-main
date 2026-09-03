<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Drop the old foreign key referencing users
            $table->dropForeign(['assigned_to']);

            // Re-add the foreign key referencing employees(employee_id)
            $table->foreign('assigned_to')
                  ->references('employee_id')
                  ->on('employees')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);

            $table->foreign('assigned_to')
                  ->references('user_id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }
};