<?php

use App\Enums\BookStatus;
use App\Jobs\CheckBookAvailability;
use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get('/books');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the books page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/books');
    $response->assertStatus(200);
});

test('a user only sees and checks their own books', function () {
    Queue::fake();
    $this->actingAs($user = User::factory()->create());

    $mine = $user->books()->create(['title' => 'Mine', 'url' => 'https://example.com/mine']);
    User::factory()->create()->books()->create(['title' => 'Someone Elses', 'url' => 'https://example.com/theirs']);

    Volt::test('books.index')
        ->assertSee('Mine')
        ->assertDontSee('Someone Elses')
        ->call('checkAll');

    Queue::assertPushed(CheckBookAvailability::class, 1);
    Queue::assertPushed(CheckBookAvailability::class, fn ($job) => $job->book->is($mine));
});

test('an added book belongs to the signed-in user, even if another user watches the same url', function () {
    User::factory()->create()->books()->create(['title' => 'Theirs', 'url' => 'https://example.com/book']);
    $this->actingAs($user = User::factory()->create());

    Volt::test('books.index')
        ->set('rows', [['author' => '', 'title' => 'Mine', 'url' => 'https://example.com/book']])
        ->call('saveBooks');

    expect($user->books()->sole()->title)->toBe('Mine')
        ->and(Book::count())->toBe(2);
});

test("another user's book cannot be changed or deleted", function (string $action, array $extra) {
    $book = User::factory()->create()->books()->create([
        'title' => 'Theirs', 'url' => 'https://example.com/theirs', 'status' => BookStatus::Unsure,
    ]);
    $this->actingAs(User::factory()->create());

    try {
        Volt::test('books.index')->call($action, $book->id, ...$extra);
    } catch (ModelNotFoundException) {
        // Looked up through the signed-in user, so it doesn't exist for them.
    }

    expect($book->fresh())->not->toBeNull()
        ->status->toBe(BookStatus::Unsure)
        ->override->toBeFalse();
})->with([
    ['deleteBook', []],
    ['setStatus', ['available']],
    ['clearOverride', []],
]);
