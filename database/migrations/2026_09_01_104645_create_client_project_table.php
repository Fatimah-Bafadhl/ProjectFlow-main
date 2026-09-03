<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained(table: 'clients', column: 'client_id')->onDelete('cascade');
            $table->foreignId('project_id')->constrained(table: 'projects', column: 'project_id')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['client_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_project');
    }
};