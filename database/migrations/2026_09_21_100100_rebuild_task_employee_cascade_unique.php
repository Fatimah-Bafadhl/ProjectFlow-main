<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('task_employee', 'task_employee_old');

        Schema::create('task_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks', 'task_id')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_id', 'employee_id']);
        });

        // Copy the data, keeping only one row per (task_id, employee_id) pair.
        DB::statement('
            INSERT INTO task_employee (id, task_id, employee_id, created_at, updated_at)
            SELECT MIN(id), task_id, employee_id, MIN(created_at), MIN(updated_at)
            FROM task_employee_old
            GROUP BY task_id, employee_id
        ');

        Schema::drop('task_employee_old');
    }

    public function down(): void
    {
        // Back to the previous shape: no cascade, no unique pair.
        Schema::rename('task_employee', 'task_employee_new');

        Schema::create('task_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks', 'task_id');
            $table->foreignId('employee_id')->constrained('employees', 'employee_id');
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO task_employee (id, task_id, employee_id, created_at, updated_at)
            SELECT id, task_id, employee_id, created_at, updated_at
            FROM task_employee_new
        ');

        Schema::drop('task_employee_new');
    }
};