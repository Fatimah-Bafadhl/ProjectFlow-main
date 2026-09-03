<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
        $table->timestamp('archived_at')->nullable()->after('status');
        $table->softDeletes(); // adds nullable deleted_at, no arguments needed
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
        $table->dropColumn(['archived_at', 'deleted_at']);
    });
    }
};
