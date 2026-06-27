# Bookwatcher

Monitors book listings at Steidl, Mack, and other publishers and sends an email when a title becomes available.

Built with Laravel 12, Livewire/Volt, Flux UI, and MariaDB. Runs in Docker via Laravel Sail.

## Requirements

- Docker

## Setup

```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d
sail artisan key:generate
sail artisan migrate
```

Set the following in `.env`:

```
NOTIFICATION_RECIPIENT=your@email.com

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=your@email.com
```

## Running

```bash
sail up -d
```

The queue worker runs as a separate Docker service (`queue`) defined in `docker-compose.yml` — availability checks are dispatched as background jobs and will not run without it.

Visit `http://localhost` to manage your book list.

## Development

```bash
sail up -d        # start containers (PHP 8.5 + MariaDB 11 + queue worker)
sail down         # stop containers
```

For local development outside Docker (requires PHP 8.5, Node):

```bash
composer run dev  # starts server + queue worker + pail log viewer + Vite
```

## Testing

```bash
composer test
```

Tests run with `QUEUE_CONNECTION=sync` and array drivers — no external services needed.

## Linting

```bash
./vendor/bin/pint         # fix code style
./vendor/bin/pint --test  # check without fixing
```

## Adding a publisher

Create a class implementing `App\Services\BookChecker\CheckerInterface` and register it in `AppServiceProvider::register()` before `DefaultChecker`. The interface requires two methods:

- `supports(string $url): bool` — return true if this checker handles the given URL
- `check(string $pageContent, string $url): BookStatus` — parse the page and return `Available`, `Unavailable`, or `Unsure`
