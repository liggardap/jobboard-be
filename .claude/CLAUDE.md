# CLAUDE.md — jobboard-be

Guidance for Claude Code when working in this repository.

---

## Project Overview

**jobboard-be** is a Laravel 13 REST API for a job board platform (companies post jobs, candidates search and apply). The primary purpose is to implement and demonstrate the full AJobThing-style backend stack:

```
Laravel (MySQL) → Redis Pub/Sub → Node.js Indexer → Elasticsearch
```

**PRD:** `docs/prds/prd-jobboard-be.md`
**Architecture reference:** `/Users/prayoga/Development/stak-be`
**Tech stack:** PHP 8.3, Laravel 13, MySQL 8.0, Redis 7, Elasticsearch 8, `tymon/jwt-auth`, `lorisleiva/laravel-actions`, `spatie/laravel-query-builder`, `elastic/elasticsearch-php`

---

## Knowledge Base — docs/

All files under `docs/` are canonical references. Treat them as ground truth when implementing features.

| File | Topic | Key concepts |
|------|-------|-------------|
| `docs/README.md` | Interview prep overview | Topic priorities |
| `docs/WHY.md` | Project motivation | AJobThing stack context |
| `docs/elasticsearch/elasticsearch.md` | Elasticsearch | Inverted index, mappings, Query DSL, BM25, fuzzy search, aggregations, reindexing |
| `docs/nodejs/nodejs-event-loop.md` | Node.js async | Event loop, microtasks vs macrotasks, async/await, Promise.all, non-blocking I/O |
| `docs/database/mysql-optimization.md` | MySQL | EXPLAIN, indexes, composite index column order, N+1, covering indexes, slow query log |
| `docs/php/php-fpm.md` | PHP-FPM | pm modes, max_requests, OPcache, request lifecycle |
| `docs/redis/redis.md` | Redis | Data types, pub/sub, cache-aside, distributed locks, queue, RDB vs AOF |
| `docs/prds/prd-jobboard-be.md` | Full PRD | Schema, ES index mapping, API endpoints, Node.js indexer, 3-phase plan |

---

## Workflow Rules

- **Never commit or push automatically.** Wait for explicit instruction before running `git commit` or `git push`.
- **Never drop or reset the database** outside of local development. `make fresh` is permitted locally only.
- **Never commit files from `docs/`** — local documentation only, gitignored.
- **Always branch from the correct base.** Run `git branch --show-current` before `git checkout -b`. Creating a branch from a feature branch silently carries its unmerged commits.
- **Before running any `make` command**, check that containers are running: `podman-compose ps`. Verify no port conflicts on `8000`, `3306`, `6379`, `9200`, `8025`, `1025`.

---

## Git Workflow

### Branch Naming

```
feature/<short-description>     # New functionality
fix/<short-description>         # Bug fix
chore/<short-description>       # Non-functional (deps, config, tooling)
hotfix/<short-description>      # Urgent fix — branch from main
```

### Commit Messages

Follow Conventional Commits:

```
<type>(<scope>): <short summary>

- change one
- change two

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
```

Types: `feat`, `fix`, `chore`, `refactor`, `test`, `docs`, `style`.

---

## Architecture Rules

### Layer Boundaries — Strict

```
HTTP Request
    │
    ▼
FormRequest       (validation only)
    │
    ▼
Action            (single use-case via lorisleiva/laravel-actions)
    │
    ▼
Service           (business logic, orchestrates repositories)
    │
    ▼
Repository        (all Eloquent queries — never in controllers or services)
    │
    ▼
Model             (ORM, relationships, casts, accessors)
```

**Elasticsearch is a parallel read layer — not a replacement for repositories:**

```
SearchAction → SearchService → ElasticsearchRepository
```

MySQL is always the source of truth. Elasticsearch is the read model for search only.

| Layer | Responsibility | Must NOT |
|---|---|---|
| `FormRequest` | Validation only | Contain business logic |
| `Action` | Single use-case (lorisleiva) | Query Eloquent directly |
| `Service` | Business logic, orchestrate repositories | Query Eloquent directly |
| `Repository` | All Eloquent queries via QueryBuilder | Contain business logic |
| `ElasticsearchRepository` | All ES client calls | Contain business logic |
| `Model` | ORM, relationships, casts, scopes | Contain business logic |

- **All Eloquent queries go through Repository classes.** Never `Model::where(...)` in actions or services.
- **Every Service and Repository has a matching interface** in `app/Interfaces/`. Bind all in `AppServiceProvider` using `bind()`.
- **Never use `make()` outside of providers.** Inject via constructor.

### Response Tracks — Never Cross

| Track | Used for | Content-Type | Class |
|---|---|---|---|
| `BaseResponse` | All `2xx` success responses | `application/json` | `app/Http/Resources/BaseResponse.php` |
| Problem Details | All `4xx` / `5xx` errors | `application/problem+json` | RFC 9457 via `withExceptions()` |

### JWT / Auth

- JWT claims **must** include: `iss`, `sub`, `aud`, `jti`, `iat`, `exp`, `roles`.
- Algorithm **must** be pinned to `HS256`. Never accept `alg: none`.
- Validate `iss`, `aud`, and `exp` before trusting any other claim.
- No cookie-based auth, no query-string token. Bearer header only.
- Signing secret must be at least 256 bits, in `.env`, never hardcoded.

### Actions (lorisleiva)

- Action classes extend `BaseAction`.
- Let exceptions bubble to `withExceptions()`. Only catch when adding context or transforming.
- One Action per operation: `CreateJobAction`, `SearchJobsAction`, `ApplyToJobAction`.

---

## Elasticsearch Rules

Reference: `docs/elasticsearch/elasticsearch.md`

- **`text`** for full-text search fields (title, description). **`keyword`** for exact match/sort/filter fields (category, status, location).
- **Never use MySQL for search queries** (`GET /jobs?q=...`). All search goes through `ElasticsearchRepository`.
- **MySQL is source of truth**. After every `Job` create/update/delete, publish to Redis for ES sync.
- **Use the alias `jobs`**, never the versioned index name (`jobs_v1`, `jobs_v2`). Zero-downtime reindex via alias swap.
- **Bool query structure**: `must` for relevance (affects score), `filter` for exact conditions (no score impact, cached).
- **Fuzziness**: use `"AUTO"` for typo tolerance on title/description fields.
- **Boosting**: `title^3`, `company.name^2`, `description^1` in `multi_match`.
- When reindexing: `jobs_v1` → create `jobs_v2` → `POST /_reindex` → atomic alias switch → delete `jobs_v1`.

---

## Redis Rules

Reference: `docs/redis/redis.md`

### Pub/Sub Channels

| Channel | Publisher | Subscriber | Payload |
|---------|-----------|------------|---------|
| `jobs:index` | `JobService` (PHP) | Node.js indexer | Full job document |
| `jobs:delete` | `JobService` (PHP) | Node.js indexer | `{ "id": 123 }` |

- **Pub/sub is ephemeral** — if the Node.js indexer is down, events are lost. Phase 1 accepts this; Phase 3 replaces with a Redis List queue for guaranteed delivery.
- **Always use `Redis::publish()`** from the Service layer, not from Actions or Controllers.
- **Distributed locks**: `Cache::lock('resource:id', $seconds)` for exclusive access. Never raw `SET ... NX EX` in application code.
- **Cache-aside**: `Cache::remember($key, $ttl, fn() => ...)`. Forget on update: `Cache::forget($key)`.
- **TTL strategy**: session data = short (minutes), entity data = medium (hours), static config = manual invalidation.

---

## Node.js Indexer Rules

Reference: `docs/nodejs/nodejs-event-loop.md`

The Node.js indexer lives in `indexer/` — a separate directory, not part of the Laravel app.

- **Always `async/await`** for Redis and ES calls — never callback style, never blocking.
- **`Promise.all()`** for parallel independent operations (e.g., batch reindex).
- **Never `await` inside `forEach`** — use `for...of` or `Promise.all()`.
- The subscriber must handle both `jobs:index` and `jobs:delete` channels.
- Errors in the subscriber must be logged but must not crash the process — wrap handlers in `try/catch`.

---

## MySQL Optimization Rules

Reference: `docs/database/mysql-optimization.md`

- **No `SELECT *`** anywhere in the codebase. All repositories select explicit columns.
- **Run `EXPLAIN`** on any new query touching tables over 10k rows. `type: ALL` on large tables is a blocker.
- **All JOIN columns must be indexed**: `company_id`, `user_id`, `job_id`.
- **Composite index column order** follows query patterns — most selective column or the one filtered most often goes first.
- **Never wrap indexed columns in functions** in WHERE clauses: use `BETWEEN` for dates, never `YEAR(col) = ?`.
- **`LIKE '%keyword%'` never uses an index** — all text search goes through Elasticsearch.
- **Eager-load relationships** in repositories with `->with()` to prevent N+1.
- **Slow query log enabled** in dev: `long_query_time = 0.5`.

---

## PHP-FPM Rules

Reference: `docs/php/php-fpm.md`

- **`pm = dynamic`** in production and local containers. Never `ondemand` for job board traffic.
- **`pm.max_requests = 500`** — kills workers after 500 requests to prevent PHP memory leaks.
- **OPcache enabled** (`opcache.enable = 1`) — never disable in production. `validate_timestamps = 0` in production.
- **`request_terminate_timeout = 30s`** — kills stuck workers. No endpoint should take longer than 10s; 30s is the hard kill.
- PHP-FPM config lives at `docker/php-fpm.conf`. OPcache config at `docker/php.ini`.

---

## Engineering Principles

### SOLID

| Principle | Application |
|-----------|------------|
| **S** — Single Responsibility | Controller = HTTP only. Service = business logic. Repository = data access. |
| **O** — Open/Closed | Add new behavior via new classes or interfaces, not by modifying existing ones. |
| **L** — Liskov Substitution | `ElasticsearchRepository` and `JobRepository` implement the same interface — interchangeable. |
| **I** — Interface Segregation | `SearchRepositoryInterface` does not include CRUD methods. `JobRepositoryInterface` does not include search. |
| **D** — Dependency Inversion | Inject `JobRepositoryInterface`, never `JobRepository` directly. |

### DRY — Don't Repeat Yourself

- Extract any logic used more than once into a shared method, service, or helper.
- Never duplicate query logic, validation rules, or transformation code across files.

### YAGNI — You Ain't Gonna Need It

- Don't add features, abstractions, or flexibility for hypothetical future needs.
- Three similar lines is better than a premature abstraction.
- No half-finished implementations. No feature flags for local-only code.

### KISS — Keep It Simple

- Prefer the simplest solution that correctly solves the problem.
- Boring, predictable code beats clever code.
- If a method needs a long comment to explain what it does, the method is too complex.

### High Cohesion, Low Coupling

- Classes communicate through interfaces, not concrete types.
- Avoid god classes and services that orchestrate too many unrelated concerns.

### No Magic Numbers or Hardcoded Strings

- Never hardcode IDs, slugs, or domain-specific names inline.
- Use enums for status values: `JobStatus::Active`, not `'active'`.
- Use named constants or config values for environment-specific identifiers.
- Use intention-revealing names: `$activeJobs` over `$j2`, `findPublishedByCompany` over `getList`.

### Import Statements

- Always `use` classes at the top of files. Never fully-qualified inline (`\App\Models\Job` in method bodies).
- Group: framework → third-party → application. Alphabetical within each group.

---

## Database Engineering Principles

- **Single source of truth**: a fact lives in exactly one table. MySQL only.
- **No nullable columns without justification.** Every nullable column requires a comment in the migration explaining the invariant.
- **No redundant or derivable columns.** If it can be computed from existing data, make it an accessor.
- **No JSON for relational data.** Use proper tables with FK constraints.
- **Every migration has a `down()` method.** Never `migrate:fresh` outside of local dev.
- **Migrations are append-only.** Never modify a previously-run migration — always add a new one.
- **Schema changes follow**: add column → backfill → add constraint. Never drop data silently.

### Database Audit Criteria

When reviewing or designing schema:

| # | Criterion | Key questions |
|---|-----------|--------------|
| 1 | Normalization | 3NF? Duplication? Denormalization justified? |
| 2 | Nullable & Integrity | Nullable intentional? Could this be NOT NULL + default? |
| 3 | Constraints | PKs, FKs, unique indexes defined? |
| 4 | Indexing | Indexes for WHERE/JOIN/ORDER BY? Composite index column order correct? |
| 5 | Data Types | Correct types? Over-wide VARCHAR? TEXT where keyword suffices? |
| 6 | Naming | snake_case, descriptive, consistent? |
| 7 | Transactions | Multi-step writes wrapped in `DB::transaction()`? |
| 8 | Performance | N+1 risk? Paginated? Eager-loaded? |
| 9 | Security | Sensitive data hashed? No schema-level exposure risk? |

---

## API Design Rules

- All JSON response keys must be `snake_case` — never camelCase.
- All timestamps use RFC 3339 with `Z` suffix: `"2026-05-21T10:00:00Z"`. Never Unix timestamps.
- All error responses use `Content-Type: application/problem+json` and RFC 9457 shape: `type`, `title`, `status`, `instance`.
- `401` responses must include `WWW-Authenticate` header per RFC 6750 §3.1.
- `GET` and `HEAD` endpoints must be side-effect free.
- Pagination via `spatie/laravel-query-builder` returning `LengthAwarePaginator`, wrapped in `PaginationResource → BaseResponse`.
- All filtering: `?filter[field]=value`. All sorting: `?sort=field` / `?sort=-field`. No custom param names per endpoint.
- Correct HTTP status codes: 200 OK, 201 Created, 204 No Content, 422 Unprocessable, 404 Not Found, 409 Conflict, 403 Forbidden.

---

## Code Style

- **No comments** unless the WHY is non-obvious (hidden constraint, subtle invariant, specific bug workaround). Never explain WHAT the code does.
- No trailing summaries or change logs in code files. Those belong in commit messages.
- Code style enforced by `laravel/pint`. Run `make pint` before committing.
- Static analysis enforced by PHPStan level 5. Run `make phpstan` before committing.

---

## Testing

- All test classes extend `tests/TestCase.php` which uses `RefreshDatabase`.
- Test environment: `DB_DATABASE=jobboard_test`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`.
- Every model has a factory in `database/factories/`.
- Run tests with `make test`.
- **Coverage target: 100% lines and methods** across all `app/` classes.
- Feature tests cover full HTTP request/response cycles.
- Unit tests cover service and repository logic in isolation (mock repositories via Mockery against interfaces).
- **Repository tests must assert `EXPLAIN` type is not `ALL`** for queries on tables over 10k rows.

### Naming Convention

```php
test_create_job_publishes_to_redis_and_returns_201()
test_search_jobs_with_typo_returns_fuzzy_results()
test_apply_to_job_prevents_duplicate_application()
test_company_cannot_update_another_companys_job()
```

---

## Task Management — task-master

Tasks tracked in `.taskmaster/tasks/tasks.json`, managed via `task-master` CLI.

- **Always run `task-master show <id>` before acting on a task.**
- Mark in-progress before starting: `task-master set-status --id=<id> --status=in-progress`
- Mark done immediately when complete: `task-master set-status --id=<id> --status=done`
- Log implementation notes: `task-master update-subtask --id=<id> --prompt="..."`
- Never manually edit `tasks.json`.

---

## Development Commands

```bash
make up           # Start all containers (nginx, app, mysql, redis, elasticsearch, indexer, mailpit)
make down         # Stop containers
make shell        # Open bash in app container
make artisan cmd= # Run artisan command (e.g. make artisan cmd="route:list")
make migrate      # Run migrations
make seed         # Seed roles and default data
make fresh        # Drop and recreate dev DB, then seed (local dev only)
make test         # Run tests (migrates jobboard_test first)
make pint         # Run Pint code style check
make phpstan      # Run PHPStan static analysis
make logs         # Tail container logs
make cache-clear  # Clear Laravel caches
make indexer      # Start Node.js indexer manually (runs via docker in normal `make up`)
make es-reindex   # Full reindex: MySQL → Elasticsearch via artisan command
```

### Services

| Service | Port |
|---|---|
| nginx (app) | 8000 |
| MySQL 8.0 | 3306 |
| Redis 7 | 6379 |
| Elasticsearch 8 | 9200 |
| Mailpit UI | 8025 |
| Mailpit SMTP | 1025 |

---

## Key Concepts Map

Every feature in this codebase directly maps to a studied concept. When implementing, verify alignment:

| Code area | Concept from docs |
|---|---|
| `ElasticsearchRepository::search()` | Inverted index, Query DSL, BM25 |
| ES mapping (`text` vs `keyword`) | `docs/elasticsearch` §Mapping |
| `SearchService::buildQuery()` | Bool query: must + filter |
| Fuzzy title match | `fuzziness: "AUTO"` |
| `title^3` in multi_match | Boosting |
| `JobService::create()` → `Redis::publish()` | Redis Pub/Sub |
| `indexer/index.js` subscriber | Node.js event loop, non-blocking I/O |
| `await esClient.index(...)` | async/await, microtask queue |
| `Promise.all()` in batch reindex | Parallel async operations |
| Composite index on `(company_id, status)` | Left-to-right composite index rule |
| `EXPLAIN ANALYZE` in repository tests | MySQL query optimization |
| `pm = dynamic` in `docker/php-fpm.conf` | PHP-FPM process manager |
| `pm.max_requests = 500` | PHP memory leak prevention |
| OPcache in `docker/php.ini` | Bytecode caching |
| `Cache::remember(...)` | Cache-aside pattern |
| `Cache::lock(...)` | Distributed lock |
