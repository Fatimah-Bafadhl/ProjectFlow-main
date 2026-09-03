<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('project_user', function (Blueprint $table) {
        $table->id();
        $table->foreignId('project_id')->constrained('projects', 'project_id')->cascadeOnDelete();
        $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
        $table->unique(['project_id', 'user_id']);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('project_user');
}
};
