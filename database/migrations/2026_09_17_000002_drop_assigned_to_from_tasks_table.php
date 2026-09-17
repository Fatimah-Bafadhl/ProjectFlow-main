<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');

        Schema::create('tasks_new', function (Blueprint $table) {
            $table->id('task_id');
            $table->string('task_title');
            $table->text('task_description');
            $table->date('start_task');
            $table->date('end_task');
            $table->string('company_name')->nullable();
            $table->unsignedInteger('progress')->default(0);
            $table->string('status')->default('Not started');
            $table->foreignId('project_id')->constrained('projects', 'project_id')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('stage_id')->nullable()->constrained('project_stages', 'project_stage_id')->nullOnDelete();
            $table->string('priority')->default('متوسط');
        });

        DB::statement('
            INSERT INTO tasks_new (
                task_id, task_title, task_description, start_task, end_task,
                company_name, progress, status, project_id,
                created_at, updated_at, deleted_at, stage_id, priority
            )
            SELECT
                task_id, task_title, task_description, start_task, end_task,
                company_name, progress, status, project_id,
                created_at, updated_at, deleted_at, stage_id, priority
            FROM tasks
        ');

        Schema::drop('tasks');
        Schema::rename('tasks_new', 'tasks');

        DB::statement('PRAGMA foreign_keys=ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');

        Schema::create('tasks_old', function (Blueprint $table) {
            $table->id('task_id');
            $table->string('task_title');
            $table->text('task_description');
            $table->date('start_task');
            $table->date('end_task');
            $table->string('company_name')->nullable();
            $table->unsignedInteger('progress')->default(0);
            $table->string('status')->default('Not started');
            $table->foreignId('project_id')->constrained('projects', 'project_id')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('employees', 'employee_id')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('stage_id')->nullable()->constrained('project_stages', 'project_stage_id')->nullOnDelete();
            $table->string('priority')->default('متوسط');
        });

        DB::statement('
            INSERT INTO tasks_old (
                task_id, task_title, task_description, start_task, end_task,
                company_name, progress, status, project_id,
                created_at, updated_at, deleted_at, stage_id, priority
            )
            SELECT
                task_id, task_title, task_description, start_task, end_task,
                company_name, progress, status, project_id,
                created_at, updated_at, deleted_at, stage_id, priority
            FROM tasks
        ');

        Schema::drop('tasks');
        Schema::rename('tasks_old', 'tasks');

        DB::statement('PRAGMA foreign_keys=ON');
    }
};