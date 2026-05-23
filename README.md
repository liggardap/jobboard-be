# jobboard-be

A Laravel 13 REST API for a job board platform — companies post jobs, candidates search and apply. Built to demonstrate a production-grade backend stack with real-time search sync via Redis Pub/Sub.

## Stack

| Layer | Technology |
|---|---|
| API framework | PHP 8.3, Laravel 13 |
| Database | MySQL 8.0 |
| Cache / Pub/Sub | Redis 7 |
| Search | Elasticsearch 8 |
| Indexer | Node.js (Redis subscriber → ES writer) |
| Auth | JWT (`tymon/jwt-auth`) |
| Actions | `lorisleiva/laravel-actions` |
| Query builder | `spatie/laravel-query-builder` |
| API docs | `darkaonline/l5-swagger` (OpenAPI 3.0) |
| Container runtime | Podman + podman-compose |

## Architecture

```
HTTP Request
    │
    ▼
FormRequest (validation)
    │
    ▼
Action (single use-case)
    │
    ▼
Service (business logic)
    │
    ▼
Repository (all Eloquent queries)
    │
    ▼
Model (ORM, relationships)
```

**Search path (parallel read layer):**

```
GET /v1/jobs?q=...
    │
    ▼
SearchJobs Action
    │
    ▼
SearchService (builds Query DSL)
    │
    ▼
ElasticsearchRepository
    │
    ▼
Elasticsearch 8
```

**Sync path (write → search index):**

```
JobService::create/update/delete
    │
    ▼
Redis::publish("jobs:index" | "jobs:delete")
    │
    ▼
Node.js indexer (subscriber)
    │
    ▼
Elasticsearch 8
```

MySQL is always the source of truth. Elasticsearch is the read model for search only.

## Prerequisites

- [Podman](https://podman.io/) + [podman-compose](https://github.com/containers/podman-compose)
- Ports `8000`, `3306`, `6379`, `9200`, `8025`, `1025` must be free

## Setup

```bash
# 1. Clone and copy environment file
cp .env.example .env

# 2. Start all containers (nginx, app, mysql, redis, elasticsearch, indexer, mailpit)
make up

# 3. Run migrations
make migrate

# 4. Seed roles and default data
make seed

# 5. (Optional) Full Elasticsearch reindex
make es-reindex
```

## Environment Variables

Key variables in `.env`:

| Variable | Description |
|---|---|
| `APP_KEY` | Laravel application key |
| `JWT_SECRET` | JWT signing secret (≥ 256 bits) |
| `DB_HOST` / `DB_DATABASE` | MySQL connection |
| `REDIS_HOST` | Redis connection |
| `ELASTICSEARCH_HOST` | Elasticsearch URL |
| `L5_SWAGGER_GENERATE_ALWAYS` | Auto-generate OpenAPI docs on each request (`true` in dev) |

## Make Commands

| Command | Description |
|---|---|
| `make up` | Start all containers |
| `make down` | Stop containers |
| `make remove` | Stop containers and prune unused images |
| `make shell` | Open bash shell in app container |
| `make migrate` | Run database migrations |
| `make seed` | Seed roles and default data |
| `make fresh` | Drop DB, re-migrate, and seed (local dev only) |
| `make test` | Run full test suite |
| `make coverage` | Run tests with 100% coverage enforcement |
| `make pint` | Run Laravel Pint code style check |
| `make phpstan` | Run PHPStan static analysis (level 5) |
| `make logs` | Tail all container logs |
| `make cache-clear` | Clear all Laravel caches |
| `make es-reindex` | Full reindex: MySQL → Elasticsearch |
| `make swagger` | Generate OpenAPI documentation |
| `make artisan cmd=` | Run any artisan command |

## Services

| Service | URL / Port |
|---|---|
| API | http://localhost:8000/api/v1 |
| Swagger UI | http://localhost:8000/api/documentation |
| Mailpit UI | http://localhost:8025 |
| MySQL | localhost:3306 |
| Redis | localhost:6379 |
| Elasticsearch | http://localhost:9200 |

## API Endpoints

All endpoints are prefixed with `/v1`. Authentication uses JWT Bearer tokens.

### Auth

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/auth/register` | — | Register (candidate or company) |
| POST | `/auth/login` | — | Login, returns JWT |
| POST | `/auth/logout` | Bearer | Invalidate token |
| POST | `/auth/refresh` | Bearer | Rotate JWT |
| POST | `/auth/forgot-password` | — | Send password reset email |
| POST | `/auth/reset-password` | — | Reset password via token |

### Jobs

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/jobs` | — | Search jobs (Elasticsearch, supports `?q=`, filters, pagination) |
| GET | `/jobs/{id}` | — | Get single job |
| POST | `/jobs` | Bearer (company) | Create job posting |
| PATCH | `/jobs/{id}` | Bearer (company/admin) | Update job |
| DELETE | `/jobs/{id}` | Bearer (company/admin) | Delete job |
| GET | `/companies/{id}/jobs` | Bearer | List jobs by company |

### Companies

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/companies` | Bearer | List companies |
| GET | `/companies/{id}` | Bearer | Get company |
| POST | `/companies` | Bearer (company) | Create company profile |
| PATCH | `/companies/{id}` | Bearer (company/admin) | Update company |

### Applications

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/jobs/{id}/apply` | Bearer (candidate) | Apply to a job |
| GET | `/jobs/{id}/applications` | Bearer (company) | List applications for a job |
| GET | `/me/applications` | Bearer | List my applications |
| DELETE | `/applications/{id}` | Bearer | Withdraw application |

### Me

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/me` | Bearer | Get authenticated user profile |
| PATCH | `/me` | Bearer | Update name or password |

## Testing

```bash
make test        # full suite
make coverage    # with 100% line/method coverage enforcement
```

Tests are split into three suites in `phpunit.xml`:

- **Unit** — service and repository logic with mocked dependencies
- **Feature** — full HTTP request/response cycles
- **Integration** — repository tests against a real (in-memory SQLite) DB with `EXPLAIN` assertions

## Elasticsearch Reindex

Zero-downtime reindex using versioned indexes and atomic alias swap:

```bash
make es-reindex           # standard reindex
make artisan cmd="es:reindex --fresh"   # drop all old indexes first
```

The command creates a new versioned index (e.g. `jobs_v1695000000`), bulk-indexes all active jobs, atomically swaps the `jobs` alias to the new index, then deletes old versioned indexes.
