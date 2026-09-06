<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets_new', function (Blueprint $table) {
            $table->id('ticket_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id')->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained('clients', 'client_id')->onDelete('set null');
            $table->string('client_name')->nullable();
            $table->text('message');
            $table->string('status')->default('open');
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO tickets_new (ticket_id, project_id, client_id, message, status, created_at, updated_at)
            SELECT ticket_id, project_id, client_id, message, status, created_at, updated_at
            FROM tickets
        ');

        Schema::drop('tickets');
        Schema::rename('tickets_new', 'tickets');
    }

    public function down(): void
    {
        Schema::create('tickets_old', function (Blueprint $table) {
            $table->id('ticket_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients', 'client_id')->onDelete('cascade');
            $table->text('message');
            $table->string('status')->default('open');
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO tickets_old (ticket_id, project_id, client_id, message, status, created_at, updated_at)
            SELECT ticket_id, project_id, client_id, message, status, created_at, updated_at
            FROM tickets
            WHERE client_id IS NOT NULL
        ');

        Schema::drop('tickets');
        Schema::rename('tickets_old', 'tickets');
    }
};