<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give every watched and owned book an owner.
 *
 * Rows that predate this migration go to the oldest account. `user_id` stays
 * nullable so the migration also succeeds on a database with books but no
 * users; unowned rows are not shown to anyone.
 *
 * ISBNs become unique per user instead of across the whole table.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['books', 'library_books'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        $ownerId = DB::table('users')->min('id');

        if ($ownerId !== null) {
            DB::table('books')->update(['user_id' => $ownerId]);
            DB::table('library_books')->update(['user_id' => $ownerId]);
        }

        Schema::table('library_books', function (Blueprint $table) {
            $table->dropUnique(['isbn']);
            $table->unique(['user_id', 'isbn']);
        });
    }

    public function down(): void
    {
        if (DB::table('library_books')
            ->select('isbn')
            ->whereNotNull('isbn')
            ->groupBy('isbn')
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new RuntimeException(
                'Cannot roll back ownership: multiple library rows share an ISBN. Preserve these records and resolve duplicate ISBNs before retrying; no schema changes have been made.'
            );
        }

        Schema::table('library_books', function (Blueprint $table) {
            // The foreign key needs an index of its own before the composite one can go.
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'isbn']);
            $table->unique('isbn');
            $table->dropColumn('user_id');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
