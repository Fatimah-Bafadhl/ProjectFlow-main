<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments_new', function (Blueprint $table) {
            $table->id('comment_id');
            $table->text('comment_text');
            $table->string('attachment')->nullable();
            $table->foreignId('task_id')->nullable()->constrained('tasks', 'task_id')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects', 'project_id')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->string('author_name')->nullable();
            $table->boolean('visible_to_client')->default(true);
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO comments_new (comment_id, comment_text, attachment, task_id, project_id, user_id, visible_to_client, created_at, updated_at)
            SELECT comment_id, comment_text, attachment, task_id, project_id, user_id, visible_to_client, created_at, updated_at
            FROM comments
        ');

        Schema::drop('comments');
        Schema::rename('comments_new', 'comments');
    }

    public function down(): void
    {
        Schema::create('comments_old', function (Blueprint $table) {
            $table->id('comment_id');
            $table->text('comment_text');
            $table->string('attachment')->nullable();
            $table->foreignId('task_id')->nullable()->constrained('tasks', 'task_id')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects', 'project_id')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->boolean('visible_to_client')->default(true);
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO comments_old (comment_id, comment_text, attachment, task_id, project_id, user_id, visible_to_client, created_at, updated_at)
            SELECT comment_id, comment_text, attachment, task_id, project_id, user_id, visible_to_client, created_at, updated_at
            FROM comments
            WHERE user_id IS NOT NULL
        ');

        Schema::drop('comments');
        Schema::rename('comments_old', 'comments');
    }
};