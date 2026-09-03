<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::rename('comments', 'comments_old');

        Schema::create('comments', function (Blueprint $table) {
            $table->id('comment_id');
            $table->text('comment_text');
            $table->string('attachment')->nullable();
            $table->foreignId('task_id')
                ->nullable()
                ->constrained('tasks', 'task_id')
                ->onDelete('cascade');
            $table->foreignId('project_id')
                ->nullable()
                ->constrained('projects', 'project_id')
                ->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->boolean('visible_to_client')->default(true);
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO comments (comment_id, comment_text, attachment, task_id, project_id, user_id, visible_to_client, created_at, updated_at)
            SELECT comment_id, comment_text, attachment, task_id, project_id, user_id, visible_to_client, created_at, updated_at
            FROM comments_old
        ');

        Schema::drop('comments_old');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::rename('comments', 'comments_new');

        Schema::create('comments', function (Blueprint $table) {
            $table->id('comment_id');
            $table->text('comment_text');
            $table->string('attachment')->nullable();
            $table->foreignId('task_id')->constrained('tasks', 'task_id')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects', 'project_id')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->boolean('visible_to_client')->default(true);
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO comments (comment_id, comment_text, attachment, task_id, project_id, user_id, visible_to_client, created_at, updated_at)
            SELECT comment_id, comment_text, attachment, task_id, project_id, user_id, visible_to_client, created_at, updated_at
            FROM comments_new
        ');

        Schema::drop('comments_new');

        Schema::enableForeignKeyConstraints();
    }
};