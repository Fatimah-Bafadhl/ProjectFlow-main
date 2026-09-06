<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects_new', function (Blueprint $table) {
            $table->id('project_id');
            $table->string('project_name');
            $table->text('project_description');
            $table->date('start_project');
            $table->date('end_project');
            $table->integer('progress')->default(0);
            $table->string('status')->default('Not started');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->string('creator_name')->nullable();
            $table->string('company_name')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO projects_new (project_id, project_name, project_description, start_project, end_project, progress, status, user_id, company_name, archived_at, deleted_at, created_at, updated_at)
            SELECT project_id, project_name, project_description, start_project, end_project, progress, status, user_id, company_name, archived_at, deleted_at, created_at, updated_at
            FROM projects
        ');

        Schema::drop('projects');
        Schema::rename('projects_new', 'projects');
    }

    public function down(): void
    {
        Schema::create('projects_old', function (Blueprint $table) {
            $table->id('project_id');
            $table->string('project_name');
            $table->text('project_description');
            $table->date('start_project');
            $table->date('end_project');
            $table->integer('progress')->default(0);
            $table->string('status')->default('Not started');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->string('company_name')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO projects_old (project_id, project_name, project_description, start_project, end_project, progress, status, user_id, company_name, archived_at, deleted_at, created_at, updated_at)
            SELECT project_id, project_name, project_description, start_project, end_project, progress, status, user_id, company_name, archived_at, deleted_at, created_at, updated_at
            FROM projects
            WHERE user_id IS NOT NULL
        ');

        Schema::drop('projects');
        Schema::rename('projects_old', 'projects');
    }
};