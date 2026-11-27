---
applyTo: "**"
excludeAgent: "cloud-agent"
---

# Code review instructions

Book Watcher monitors publisher pages and emails when a book becomes available. It uses Laravel, Livewire/Volt, Flux UI and MariaDB, with a database queue. Merging to `master` deploys to production and runs migrations.

## What to review

In priority order:

1. Security vulnerabilities and data loss.
2. Correctness: bugs, unhandled edge cases, race conditions.
3. Regressions: changed behaviour of existing callers, API shapes or stored data.
4. Performance: repeated or unbounded work, queries, requests or memory use.
5. Documentation that no longer matches the code.
6. Readability that is likely to cause a future mistake.

## What to report

- Report an issue only if you can name the input or sequence of events that triggers it and the resulting wrong behaviour. For documentation, quote the statement and name the code that contradicts it.
- If an issue depends on something you could not verify, say what you assumed.
- State the impact and suggest a concrete fix.
- Skip formatting, naming preferences, style that doesn't hurt readability, and speculative refactors.
- Don't report problems in code the pull request doesn't touch or affect.
- Finding nothing is an acceptable result.

## Documentation alignment

For every behaviour change, check the code comments and `readme.md`, `infra/README.md`, `CLAUDE.md`, `.env.example` and `infra/env.example`. Flag:

- Comments or docs that now describe old behaviour, including in unchanged lines.
- Renamed or removed files, routes, scripts, settings or environment variables still referenced by their old name.
- New environment variables, routes, scripts or scheduled work that are undocumented.
- Changes to `infra/` files that need a matching manual step which the docs don't mention.

Don't ask for comments on self-explanatory code.

## Project rules

Security:

- Pages that read or change data stay behind the `auth` middleware. Check authorization inside Livewire actions too, since public properties and methods can be called by the client.
- Validate Livewire input before it is saved or used in a query.
- Book URLs and scraped pages are untrusted. Watch for server-side request forgery and unescaped output in Blade views or emails.
- Never log secrets or credentials.

Checking and notifications:

- A fetch or parse failure results in `Unsure`, not an exception or a false `Available`.
- Checkers are tried in registration order and `DefaultChecker` stays last.
- Books with `override` set are never checked.
- Email is sent only on a change into `Available`. Jobs retry, so check that a retry can't send twice or lose a notification.
- A secondary source changes the status only on a confident, in-stock match.
- Outbound requests need timeouts. Parser changes need a test using representative page content.

Data and operations:

- Migrations must be safe on existing production data.
- Read settings through `config()`, not `env()`, outside config files, because config is cached in production.
- Watch for N+1 queries and for work repeated on every `wire:poll` render.
- Tests use Pest and must not call external services.
- Use pnpm for JavaScript and Composer for PHP.
