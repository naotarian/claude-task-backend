# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Backlog-style project-management SaaS. A Laravel 11 JSON API (`backend/`) plus a separate Next.js 15 App Router frontend (`frontend/`) that consumes it. The organization is the billing tenant and quota unit; freemium limits are enforced server-side. See `~/.claude/plans/backlog-zany-treasure.md` for the full design.

## Commands

Everything runs through Docker — there is no local PHP/Composer/Node toolchain. Use the `Makefile`:

```bash
make up                 # start full stack (php, node, nginx, mysql, redis, mailhog, minio)
make art c="migrate"    # run any artisan command
make fresh              # migrate:fresh --seed
make test               # backend Pest suite, coverage --min=80 (this threshold is enforced)
make types              # regenerate frontend/src/types/generated.d.ts from Laravel Data classes
make front-test         # frontend vitest with coverage
make front-build        # production next build (NODE_ENV=production — required, see below)
```

Run a single backend test: `docker compose run --rm php php artisan test --filter=CreateTaskTest`
Run a single frontend test: `docker compose run --rm --no-deps node npm run test:run -- src/lib/gantt.test.ts`

Service URLs: app via nginx `:8080`, Next.js direct `:3000`, Mailhog `:8025`, MinIO console `:9001`, MySQL `:3307`.

## Architecture

Per-app layered architecture, coding standards, and formatting conventions are documented in each app's own `CLAUDE.md`:

- **Backend** (`backend/CLAUDE.md`) — Controller → FormRequest → UseCase → Service → Repository → Data layering; Pint formatting; Pest testing.
- **Frontend** (`frontend/CLAUDE.md`) — API client → React Query hook → Component layering; ESLint (no Prettier); Vitest.

The cross-cutting concerns below span both apps, so they live here.

### Multi-tenancy

The "current organization" is the tenant. `SetCurrentOrganization` middleware (alias `organization` in `routes/api.php`) resolves it from the `{organization}` route param or the `X-Organization-Id` header, verifies membership, and stores it in the request-scoped `App\Support\CurrentOrganization` singleton that downstream layers read. Routes needing tenant scope sit inside the `Route::middleware('organization')` group; project/task routes are reached by their own model and use cross-organization `project_members`.

### Quotas

`QuotaService` enforces plan limits, always read from a project's **owning organization's** plan. A null plan = free tier (3 projects/org, <20 members/project, <3 GB/project); a null limit = unlimited. UseCases call the `assertCan*` methods before creating; they throw `QuotaExceededException`.

### Gotchas (see project memory for full list)

- `spatie/laravel-data` `toResponse()` defaults to **201 on POST**. Force with `->setStatusCode(200)` when needed (the controllers already do this on `update`).
- Sanctum SPA cookie auth needs an `Origin` header to be treated as stateful — curl/tests without it get 401. The Next.js client handles CSRF via `ensureCsrf()` (`src/lib/api/client.ts`).
- Composer must run in the project's PHP 8.3 image (`docker/php`); the official composer image is PHP 8.5 and breaks `moneyphp/money`.
- Sanctum SPA cookie auth needs an `Origin` header to be treated as stateful — curl/tests without it get 401. The Next.js client handles CSRF via `ensureCsrf()` (`frontend/src/lib/api/client.ts`).

> Per-app details (more gotchas, formatting, testing) are in `backend/CLAUDE.md` and `frontend/CLAUDE.md`.
