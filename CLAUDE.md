# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development environment

The app runs inside Docker via Laravel Sail. All `artisan`, `composer`, and `php` commands must be prefixed with `sail` when targeting the runtime environment.

```bash
sail up -d                        # start containers (PHP 8.5, MariaDB 11)
sail artisan migrate              # run migrations
sail down                         # stop containers
composer run dev                  # start everything locally: server + queue worker + pail + vite
```

The `composer run dev` script uses `concurrently` to spin up four processes at once: `php artisan serve`, `php artisan queue:listen --tries=1`, `php artisan pail`, and `npm run dev`. The queue worker is required — availability checks are dispatched as jobs and will not run otherwise.

## Testing

```bash
composer test                     # config:clear then php artisan test (Pest)
php artisan test --filter=<name>  # run a single test by name
php artisan test tests/Feature/Auth/AuthenticationTest.php  # run one file
```

Tests use `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`, `CACHE_STORE=array`, `MAIL_MAILER=array` — no external services needed.

## Linting

```bash
./vendor/bin/pint                 # fix code style (Laravel Pint / PSR-12)
./vendor/bin/pint --test          # check without fixing
```

## Key environment variables

- `QUEUE_CONNECTION=database` — must be `database` (not `sync`) in production/dev for jobs to queue properly
- `NOTIFICATION_RECIPIENT` — email address that receives availability alerts
- `MAIL_*` — configured for SMTP; uses `log` driver in testing

## Architecture

### Book availability checking (the core feature)

**Flow:** User clicks "Check Now" → `checkAll()` in the Volt component dispatches one `CheckBookAvailability` job per book → queue worker processes jobs → each job calls `BookCheckerService` → updates `books.status` + `books.last_checked_at` → sends `BookAvailable` mail if a book newly became available → Livewire `wire:poll.5s` re-fetches the book list and shows updated statuses.

**Strategy pattern for site-specific parsing** (`app/Services/BookChecker/`):
- `CheckerInterface` — `supports(url)` + `check(pageContent, url): BookStatus`
- `SteidlChecker` — matches `steidl` in URL; parses the `.headline-left` div for "Free shipping" (available) or "Not yet published" (unavailable)
- `MackChecker` — matches `mackbooks` in URL; "Available to pre-order" → unavailable, otherwise → available
- `DefaultChecker` — catch-all, always returns `Unsure`
- `BookCheckerService` — fetches the page via `Http::` and iterates checkers in order; HTTP failures return `Unsure`

To add a new publisher, create a class implementing `CheckerInterface` and register it in `AppServiceProvider::register()` before `DefaultChecker`.

**`BookStatus` enum** (`app/Enums/BookStatus.php`): three cases — `Available`, `Unavailable`, `Unsure`. The `Book` model casts `status` to this enum. A book with `override = true` is skipped by the checker; its status was set manually by the user.

### Livewire/Volt component pattern

All UI is Volt single-file components under `resources/views/livewire/`. PHP logic lives in the `<?php ... ?>` block at the top using `new class extends Component`. The main page is `livewire/books/index.blade.php`, registered as route `books` via `Volt::route()`.

`#[\Livewire\Attributes\Computed]` methods are re-evaluated on every render (including polls). The root `<div wire:poll.5s>` drives the live status refresh.

The add-book form lives inside a `flux:modal name="add-book"` — opened via `flux:modal.trigger`, closed programmatically with `$this->modal('add-book')->close()`.

### Queue

`QUEUE_CONNECTION=database` with the `jobs` table (migration `0001_01_01_000002`). `CheckBookAvailability` implements `ShouldQueue` with `$tries = 3`. In tests `QUEUE_CONNECTION=sync` so jobs run inline.

### Mail

`BookAvailable` uses the modern `envelope()` / `content()` mailable API. Template is `resources/views/emails/book-available.blade.php`. The `$book` model is injected as a public property and available directly in the view.
