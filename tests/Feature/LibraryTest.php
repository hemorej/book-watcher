<?php

use App\Models\LibraryBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('guests are redirected from the library', function () {
    $this->get('/library')->assertRedirect('/login');
});

test('the library lists the volumes a user owns', function () {
    $this->actingAs(User::factory()->create());

    LibraryBook::factory()->create(['author' => 'Robert Adams', 'title' => 'Summer Nights, Walking']);

    Volt::test('library.index')
        ->assertSee('Robert Adams')
        ->assertSee('Summer Nights, Walking');
});

test('search filters by author, title or publisher', function () {
    $this->actingAs(User::factory()->create());

    LibraryBook::factory()->create(['author' => 'Alec Soth', 'title' => 'Sleeping by the Mississippi', 'publisher' => 'MACK']);
    LibraryBook::factory()->create(['author' => 'Sally Mann', 'title' => 'Immediate Family', 'publisher' => 'Aperture']);

    Volt::test('library.index')
        ->set('libQuery', 'mack')
        ->assertSee('Sleeping by the Mississippi')
        ->assertDontSee('Immediate Family');
});

test('sort switches between author and title order', function () {
    $this->actingAs(User::factory()->create());

    LibraryBook::factory()->create(['author' => 'Zoe Strauss', 'title' => 'America']);
    LibraryBook::factory()->create(['author' => 'Ansel Adams', 'title' => 'Yosemite']);

    $byAuthor = Volt::test('library.index')->set('libSort', 'author');
    expect($byAuthor->get('books')->pluck('author')->all())->toBe(['Ansel Adams', 'Zoe Strauss']);

    $byTitle = Volt::test('library.index')->set('libSort', 'title');
    expect($byTitle->get('books')->pluck('title')->all())->toBe(['America', 'Yosemite']);
});

test('adding a volume records it with fallback details', function () {
    $this->actingAs(User::factory()->create());

    Volt::test('library.index')
        ->set('vol.author', 'Daido Moriyama')
        ->set('vol.title', 'Record')
        ->call('saveVolume')
        ->assertDispatched('close-add-volume-modal');

    $volume = LibraryBook::sole();

    expect($volume->author)->toBe('Daido Moriyama')
        ->and($volume->title)->toBe('Record')
        ->and($volume->publisher)->toBe('Unknown publisher')
        ->and($volume->year)->toBeNull()
        ->and($volume->edition)->toBe('Edition not recorded')
        ->and($volume->condition)->toBe('Unrecorded')
        ->and($volume->acquired_at)->not->toBeNull()
        ->and($volume->isbn)->toBeNull();
});

test('a volume with neither author nor title is ignored', function () {
    $this->actingAs(User::factory()->create());

    Volt::test('library.index')
        ->set('vol.publisher', 'Steidl')
        ->call('saveVolume');

    expect(LibraryBook::count())->toBe(0);
});

test('a row can be edited inline', function () {
    $this->actingAs(User::factory()->create());

    $volume = LibraryBook::factory()->create([
        'author' => 'Robert Adams',
        'title' => 'Summer Nights',
        'publisher' => 'Aperture',
        'year' => 2009,
    ]);

    Volt::test('library.index')
        ->call('startEdit', $volume->id)
        ->assertSet('editRow.author', 'Robert Adams')
        ->set('editRow.title', 'Summer Nights, Walking')
        ->set('editRow.publisher', 'Aperture Foundation')
        ->set('editRow.year', '2010')
        ->call('saveEdit')
        ->assertSet('editingId', null);

    expect($volume->fresh())
        ->title->toBe('Summer Nights, Walking')
        ->publisher->toBe('Aperture Foundation')
        ->year->toBe(2010);
});

test('an inline edit that clears author and title is rejected', function () {
    $this->actingAs(User::factory()->create());

    $volume = LibraryBook::factory()->create(['author' => 'Robert Adams', 'title' => 'Summer Nights']);

    Volt::test('library.index')
        ->call('startEdit', $volume->id)
        ->set('editRow.author', '')
        ->set('editRow.title', '')
        ->call('saveEdit')
        ->assertHasErrors('editRow')
        ->assertSet('editingId', $volume->id);

    expect($volume->fresh()->title)->toBe('Summer Nights');
});
