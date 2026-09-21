<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['employees', 'clients'] as $tableName) {
            $duplicates = DB::table($tableName)
                ->select('email')
                ->groupBy('email')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('email');

            if ($duplicates->isNotEmpty()) {
                throw new RuntimeException("Duplicate emails in {$tableName}: ".$duplicates->implode(', '));
            }
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->unique('email');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
    }
};