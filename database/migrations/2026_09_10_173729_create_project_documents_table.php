<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_documents', function (Blueprint $table) {
            $table->id('project_document_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id')->cascadeOnDelete();
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
        Schema::dropIfExists('project_documents');
    }
};