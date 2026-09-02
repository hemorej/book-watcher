<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widen `library_books` so a volume can also be entered by hand (the "Add
 * volume" modal), not only ingested from an ISBN list.
 *
 * - `isbn` becomes nullable — a hand-entered volume has no ISBN. It stays
 *   unique so ISBN ingestion still de-dupes (MySQL/MariaDB allow many NULLs
 *   under a unique index).
 * - `edition` / `condition` / `acquired_at` carry the extra bibliographic
 *   detail the redesign keeps on record for a future volume detail view.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_books', function (Blueprint $table) {
            $table->string('isbn', 13)->nullable()->change();
            $table->string('edition')->nullable()->after('year');
            $table->string('condition')->nullable()->after('edition');
            $table->timestamp('acquired_at')->nullable()->after('condition');
        });
    }

    public function down(): void
    {
        Schema::table('library_books', function (Blueprint $table) {
            $table->dropColumn(['edition', 'condition', 'acquired_at']);
            $table->string('isbn', 13)->nullable(false)->change();
        });
    }
};
