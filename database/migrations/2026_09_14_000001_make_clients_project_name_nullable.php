<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Same reasoning as the 2026_09_07_143317 migration: PRAGMA foreign_keys
    // only takes effect outside an active transaction, and SQLite can't
    // ALTER COLUMN directly, so we rebuild the table.
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF;');

        DB::statement('
            CREATE TABLE "clients_new" (
                "client_id" integer primary key autoincrement not null,
                "name" varchar not null,
                "company_name" varchar not null,
                "email" varchar not null,
                "phone" varchar not null,
                "project_name" varchar,
                "user_id" integer,
                "created_at" datetime,
                "updated_at" datetime,
                "deleted_at" datetime,
                foreign key("user_id") references "users"("user_id") on delete set null
            )
        ');
        DB::statement('INSERT INTO "clients_new" SELECT * FROM "clients"');
        DB::statement('DROP TABLE "clients"');
        DB::statement('ALTER TABLE "clients_new" RENAME TO "clients"');

        DB::statement('PRAGMA foreign_keys = ON;');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF;');

        DB::statement('
            CREATE TABLE "clients_old" (
                "client_id" integer primary key autoincrement not null,
                "name" varchar not null,
                "company_name" varchar not null,
                "email" varchar not null,
                "phone" varchar not null,
                "project_name" varchar not null,
                "user_id" integer,
                "created_at" datetime,
                "updated_at" datetime,
                "deleted_at" datetime,
                foreign key("user_id") references "users"("user_id") on delete set null
            )
        ');
        DB::statement('INSERT INTO "clients_old" SELECT * FROM "clients"');
        DB::statement('DROP TABLE "clients"');
        DB::statement('ALTER TABLE "clients_old" RENAME TO "clients"');

        DB::statement('PRAGMA foreign_keys = ON;');
    }
};