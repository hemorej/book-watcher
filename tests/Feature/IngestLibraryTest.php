<?php

use App\Models\LibraryBook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

const OPEN_LIBRARY = 'https://openlibrary.org/api/books*';
const GOOGLE_BOOKS = 'https://www.googleapis.com/books/v1/volumes*';

/** Write an ISBN list to a throwaway file and return its path. */
function catalogFile(array $lines): string
{
    $path = sys_get_temp_dir().'/imprint-catalog-'.uniqid().'.txt';
    File::put($path, implode(PHP_EOL, $lines).PHP_EOL);

    return $path;
}

function openLibraryRecord(string $isbn, array $overrides = []): array
{
    return ["ISBN:{$isbn}" => array_merge([
        'title' => 'Steidl Spring/Summer 2020',
        'authors' => [['name' => 'Gerhard Steidl']],
        'publishers' => [['name' => 'Steidl']],
        'publish_date' => '2020',
    ], $overrides)];
}

function googleBooksRecord(array $volumeInfo): array
{
    return ['totalItems' => 1, 'items' => [['volumeInfo' => $volumeInfo]]];
}

afterEach(function () {
    foreach (glob(sys_get_temp_dir().'/imprint-catalog-*') as $file) {
        File::delete($file);
    }
});

test('resolves via Open Library and stores the row', function () {
    Http::fake([
        OPEN_LIBRARY => Http::response(openLibraryRecord('9780306406157')),
        GOOGLE_BOOKS => Http::response(['totalItems' => 0, 'items' => []]),
    ]);

    $this->artisan('library:ingest', [
        'file' => catalogFile(['978-0-306-40615-7']),
        '--throttle' => 0,
    ])->assertSuccessful();

    $book = LibraryBook::sole();
    expect($book->isbn)->toBe('9780306406157')
        ->and($book->title)->toBe('Steidl Spring/Summer 2020')
        ->and($book->author)->toBe('Gerhard Steidl')
        ->and($book->publisher)->toBe('Steidl')
        ->and($book->year)->toBe(2020);
});

test('falls back to Google Books when Open Library has no record', function () {
    Http::fake([
        OPEN_LIBRARY => Http::response([]), // empty object = unknown ISBN
        GOOGLE_BOOKS => Http::response(googleBooksRecord([
            'title' => 'The Photobook: A History',
            'authors' => ['Martin Parr', 'Gerry Badger'],
            'publisher' => 'Phaidon',
            'publishedDate' => '2004-10-01',
        ])),
    ]);

    $this->artisan('library:ingest', [
        'file' => catalogFile(['9780306406157']),
        '--throttle' => 0,
    ])->assertSuccessful();

    $book = LibraryBook::sole();
    expect($book->title)->toBe('The Photobook: A History')
        ->and($book->author)->toBe('Martin Parr, Gerry Badger')
        ->and($book->publisher)->toBe('Phaidon')
        ->and($book->year)->toBe(2004);
});

test('backfills a missing year from Google Books', function () {
    Http::fake([
        OPEN_LIBRARY => Http::response(openLibraryRecord('9780306406157', [
            'publish_date' => null,
            'publishers' => [],
        ])),
        GOOGLE_BOOKS => Http::response(googleBooksRecord([
            'title' => 'Ignored — Open Library already named it',
            'publisher' => 'Steidl',
            'publishedDate' => '2019',
        ])),
    ]);

    $this->artisan('library:ingest', [
        'file' => catalogFile(['9780306406157']),
        '--throttle' => 0,
    ])->assertSuccessful();

    $book = LibraryBook::sole();
    expect($book->title)->toBe('Steidl Spring/Summer 2020') // kept from Open Library
        ->and($book->publisher)->toBe('Steidl')             // backfilled
        ->and($book->year)->toBe(2019);                     // backfilled
});

test('records unresolved and malformed ISBNs as misses', function () {
    Http::fake([
        OPEN_LIBRARY => Http::response([]),
        GOOGLE_BOOKS => Http::response(['totalItems' => 0, 'items' => []]),
    ]);

    $path = catalogFile(['9780306406157', 'garbage', '# a comment', '']);

    $this->artisan('library:ingest', ['file' => $path, '--throttle' => 0])
        ->assertSuccessful();

    expect(LibraryBook::count())->toBe(0);

    $misses = File::get($path.'.misses.txt');
    expect($misses)->toContain('garbage')
        ->and($misses)->toContain('9780306406157');
});

test('is idempotent — a second run updates rather than duplicates', function () {
    $titles = ['First Title', 'Revised Title'];
    Http::fake([
        OPEN_LIBRARY => function () use (&$titles) {
            return Http::response(openLibraryRecord('9780306406157', ['title' => array_shift($titles)]));
        },
        GOOGLE_BOOKS => Http::response(['totalItems' => 0, 'items' => []]),
    ]);

    $file = catalogFile(['9780306406157']);
    $this->artisan('library:ingest', ['file' => $file, '--throttle' => 0])->assertSuccessful();
    $this->artisan('library:ingest', ['file' => $file, '--throttle' => 0])->assertSuccessful();

    expect(LibraryBook::count())->toBe(1)
        ->and(LibraryBook::sole()->title)->toBe('Revised Title');
});

test('--dry-run resolves without writing', function () {
    Http::fake([
        OPEN_LIBRARY => Http::response(openLibraryRecord('9780306406157')),
        GOOGLE_BOOKS => Http::response(['totalItems' => 0, 'items' => []]),
    ]);

    $this->artisan('library:ingest', [
        'file' => catalogFile(['9780306406157']),
        '--throttle' => 0,
        '--dry-run' => true,
    ])->assertSuccessful();

    expect(LibraryBook::count())->toBe(0);
});
