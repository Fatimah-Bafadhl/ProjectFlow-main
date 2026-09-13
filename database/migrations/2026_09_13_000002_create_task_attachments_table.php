<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id('task_attachment_id');
            $table->foreignId('task_id')->constrained('tasks', 'task_id')->cascadeOnDelete();
            $table->string('type'); // 'link' or 'file'
            $table->string('title');
            $table->string('url')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->foreignId('added_by_user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('added_by_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_attachments');
    }
};