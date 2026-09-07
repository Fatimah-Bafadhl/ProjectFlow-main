<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Required so the PRAGMA foreign_keys toggle below actually takes effect —
    // SQLite ignores that pragma if it's issued inside an active transaction,
    // and Laravel wraps migrations in one by default.
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF;');

        // --- employees ---
        DB::statement('
            CREATE TABLE "employees_new" (
                "employee_id" integer primary key autoincrement not null,
                "name" varchar not null,
                "department" varchar not null,
                "email" varchar not null,
                "phone" varchar not null,
                "user_id" integer,
                "created_at" datetime,
                "updated_at" datetime,
                "deleted_at" datetime,
                foreign key("user_id") references "users"("user_id") on delete set null
            )
        ');
        DB::statement('INSERT INTO "employees_new" SELECT * FROM "employees"');
        DB::statement('DROP TABLE "employees"');
        DB::statement('ALTER TABLE "employees_new" RENAME TO "employees"');

        // --- clients ---
        DB::statement('
            CREATE TABLE "clients_new" (
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
        DB::statement('INSERT INTO "clients_new" SELECT * FROM "clients"');
        DB::statement('DROP TABLE "clients"');
        DB::statement('ALTER TABLE "clients_new" RENAME TO "clients"');

        DB::statement('PRAGMA foreign_keys = ON;');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF;');

        DB::statement('
            CREATE TABLE "employees_old" (
                "employee_id" integer primary key autoincrement not null,
                "name" varchar not null,
                "department" varchar not null,
                "email" varchar not null,
                "phone" varchar not null,
                "user_id" integer not null,
                "created_at" datetime,
                "updated_at" datetime,
                "deleted_at" datetime,
                foreign key("user_id") references "users"("user_id") on delete cascade
            )
        ');
        DB::statement('INSERT INTO "employees_old" SELECT * FROM "employees"');
        DB::statement('DROP TABLE "employees"');
        DB::statement('ALTER TABLE "employees_old" RENAME TO "employees"');

        DB::statement('
            CREATE TABLE "clients_old" (
                "client_id" integer primary key autoincrement not null,
                "name" varchar not null,
                "company_name" varchar not null,
                "email" varchar not null,
                "phone" varchar not null,
                "project_name" varchar not null,
                "user_id" integer not null,
                "created_at" datetime,
                "updated_at" datetime,
                "deleted_at" datetime,
                foreign key("user_id") references "users"("user_id") on delete cascade
            )
        ');
        DB::statement('INSERT INTO "clients_old" SELECT * FROM "clients"');
        DB::statement('DROP TABLE "clients"');
        DB::statement('ALTER TABLE "clients_old" RENAME TO "clients"');

        DB::statement('PRAGMA foreign_keys = ON;');
    }
};