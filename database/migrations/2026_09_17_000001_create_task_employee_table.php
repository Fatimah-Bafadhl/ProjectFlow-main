<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks', 'task_id');
            $table->foreignId('employee_id')->constrained('employees', 'employee_id');
            $table->timestamps();
        });

        // Backfill: copy each task's existing single assignment into the new pivot table.
        $tasks = DB::table('tasks')->whereNotNull('assigned_to')->get(['task_id', 'assigned_to']);

        $now = now();
        foreach ($tasks as $task) {
            DB::table('task_employee')->insert([
                'task_id'     => $task->task_id,
                'employee_id' => $task->assigned_to,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_employee');
    }
};