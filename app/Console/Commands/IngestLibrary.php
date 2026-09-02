<?php

namespace App\Console\Commands;

use App\Models\LibraryBook;
use App\Services\LibraryMetadata\LibraryMetadataResolver;
use App\Support\Isbn;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Populate the `library_books` table from a plain-text list of ISBNs.
 *
 *   php artisan library:ingest storage/app/catalog.txt
 *
 * The file is one ISBN per line; blank lines and `#` comments are ignored.
 * Each ISBN is normalised to 13 digits ({@see Isbn}) and looked up through
 * {@see LibraryMetadataResolver}. Existing rows (matched on `isbn`) are updated,
 * so the command is safe to re-run. Unresolved and malformed ISBNs are listed
 * at the end and written to `<file>.misses.txt`.
 */
class IngestLibrary extends Command
{
    protected $signature = 'library:ingest
        {file : Path to a newline-delimited list of ISBNs}
        {--dry-run : Resolve and report without writing to the database}
        {--throttle=200 : Milliseconds to pause between lookups}';

    protected $description = 'Ingest a list of ISBNs into the library, resolving title/author/publisher/year';

    public function handle(LibraryMetadataResolver $resolver): int
    {
        $path = $this->argument('file');

        if (! File::exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $lines = collect(preg_split('/\R/', File::get($path)) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '' && ! str_starts_with($line, '#'));

        if ($lines->isEmpty()) {
            $this->warn('No ISBNs found in file.');

            return self::SUCCESS;
        }

        $throttle = max(0, (int) $this->option('throttle')) * 1000;
        $dryRun = (bool) $this->option('dry-run');

        $created = 0;
        $updated = 0;
        $invalid = [];
        $unresolved = [];

        $bar = $this->output->createProgressBar($lines->count());
        $bar->start();

        foreach ($lines as $index => $raw) {
            $isbn = Isbn::normalize($raw);

            if ($isbn === null) {
                $invalid[] = $raw;
                $bar->advance();

                continue;
            }

            $meta = $resolver->resolve($isbn);

            if ($meta === null) {
                $unresolved[] = $isbn;
                $bar->advance();

                continue;
            }

            if (! $dryRun) {
                $book = LibraryBook::firstOrNew(['isbn' => $isbn]);
                $book->fill([
                    'title' => $meta->title,
                    'author' => $meta->authorLine(),
                    'publisher' => $meta->publisher,
                    'year' => $meta->year,
                ]);
                $book->exists ? $updated++ : $created++;
                $book->save();
            }

            $bar->advance();

            if ($throttle > 0 && $index < $lines->count() - 1) {
                usleep($throttle);
            }
        }

        $bar->finish();
        $this->newLine(2);

        $resolvedCount = $lines->count() - count($invalid) - count($unresolved);
        $this->components->info(($dryRun ? 'Dry run — ' : '')."{$resolvedCount} of {$lines->count()} ISBNs resolved.");

        if (! $dryRun) {
            $this->line("  created: {$created}   updated: {$updated}");
        }

        $this->reportMisses($path, $invalid, $unresolved);

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $invalid
     * @param  list<string>  $unresolved
     */
    private function reportMisses(string $path, array $invalid, array $unresolved): void
    {
        if ($invalid === [] && $unresolved === []) {
            return;
        }

        if ($invalid !== []) {
            $this->warn('Malformed ISBNs (skipped):');
            foreach ($invalid as $line) {
                $this->line("  {$line}");
            }
        }

        if ($unresolved !== []) {
            $this->warn('No metadata found:');
            foreach ($unresolved as $isbn) {
                $this->line("  {$isbn}");
            }
        }

        $missesPath = $path.'.misses.txt';
        File::put($missesPath, implode(PHP_EOL, [...$invalid, ...$unresolved]).PHP_EOL);
        $this->line("Misses written to {$missesPath}");
    }
}
