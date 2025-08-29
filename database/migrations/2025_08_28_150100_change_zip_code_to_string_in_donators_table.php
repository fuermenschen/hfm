<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'mysql':
                DB::statement('ALTER TABLE donators MODIFY zip_code VARCHAR(16) NOT NULL');
                break;

            case 'pgsql':
                // Ensure cast and not null
                DB::statement('ALTER TABLE donators ALTER COLUMN zip_code TYPE VARCHAR(16) USING zip_code::varchar(16)');
                DB::statement('ALTER TABLE donators ALTER COLUMN zip_code SET NOT NULL');
                break;

            case 'sqlsrv':
                DB::statement('ALTER TABLE donators ALTER COLUMN zip_code NVARCHAR(16) NOT NULL');
                break;

            case 'sqlite':
                // Use SQLite's column rename + add new text column approach
                // 1) Rename original column
                DB::statement('ALTER TABLE donators RENAME COLUMN zip_code TO zip_code_old');
                // 2) Add replacement column as TEXT (nullable during transition)
                DB::statement('ALTER TABLE donators ADD COLUMN zip_code TEXT');
                // 3) Copy values across
                DB::statement('UPDATE donators SET zip_code = CAST(zip_code_old AS TEXT)');
                // 4) Drop the old column (supported on SQLite >= 3.35)
                DB::statement('ALTER TABLE donators DROP COLUMN zip_code_old');
                break;

            default:
                // Unknown driver: no-op to avoid breaking migrations
                break;
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'mysql':
                DB::statement('ALTER TABLE donators MODIFY zip_code INT UNSIGNED NOT NULL');
                break;

            case 'pgsql':
                // Best-effort conversion back to integer; non-numeric zips become 0
                DB::statement("ALTER TABLE donators ALTER COLUMN zip_code TYPE INTEGER USING (CASE WHEN zip_code ~ '^[0-9]+$' THEN zip_code::integer ELSE 0 END)");
                DB::statement('ALTER TABLE donators ALTER COLUMN zip_code SET NOT NULL');
                break;

            case 'sqlsrv':
                DB::statement('ALTER TABLE donators ALTER COLUMN zip_code INT NOT NULL');
                break;

            case 'sqlite':
                // Reverse by renaming and recreating integer column
                DB::statement('ALTER TABLE donators RENAME COLUMN zip_code TO zip_code_text');
                DB::statement('ALTER TABLE donators ADD COLUMN zip_code INTEGER');
                DB::statement("UPDATE donators SET zip_code = CASE WHEN zip_code_text GLOB '[0-9]*' THEN CAST(zip_code_text AS INTEGER) ELSE 0 END");
                DB::statement('ALTER TABLE donators DROP COLUMN zip_code_text');
                break;

            default:
                // Unknown driver: no-op
                break;
        }
    }
};
