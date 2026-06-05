# BriBooks Platform — PHP Developer Assignment

A simplified book creation and publishing platform built with **Laravel 11**, **PHP 8.3+**, **MySQL 8+**, **JWT Authentication**, and **PHPUnit**.

---

## Table of Contents

- [Architecture Decisions](#architecture-decisions)
- [Installation](#installation)
- [Running Tests](#running-tests)
- [API Reference](#api-reference)
- [Workflow](#workflow)
- [Assumptions & Trade-offs](#assumptions--trade-offs)

---

## Architecture Decisions

### Framework
**Laravel 11** was chosen for its mature ecosystem, built-in support for queues, events, and notifications — all required by this project.

### Authentication
**JWT via `tymon/jwt-auth`** instead of Sanctum, as the assignment explicitly requires JWT. Each token embeds `role` and `name` as custom claims to avoid extra DB lookups per request.

### Version Control (Snapshot Design)
Each version stores a full **JSON snapshot** of the book's metadata, chapters, and pages at the time of the snapshot. This design:
- Makes rollback simple and atomic (no diff computation needed)
- Allows reading a historical version without touching the live tables
- Is scalable: snapshots are immutable blobs, no foreign key joins needed to read version history
- Trade-off: storage grows with each snapshot. Mitigation: gzip the JSON column for large books (can be added as a mutator on the `BookVersion` model)

### Moderation
Content moderation runs **synchronously** before submission (blocking). For production, this should be moved to a queue job and the book held in a `pending_moderation` status. The word lists are simple arrays — swap in `wamania/php-profanity-filter` or OpenAI's Moderation API for production.

### Document Conversion
Uses **LibreOffice headless** (`soffice`) to convert `.doc`/`.docx` → HTML, then splits output into chapters (by `<h1>/<h2>`) and pages (by ~3000 character chunks). Dispatched as a **background queue job** (`ConvertDocumentJob`) so uploads respond immediately with `202 Accepted`.

### Events & Listeners
Full event-driven architecture:
- `BookCreated` → `LogBookActivity`
- `BookSubmitted` → `LogBookActivity`, `NotifyOnBookSubmitted` (emails reviewers)
- `BookApproved` → `LogBookActivity`, `NotifyOnBookApproved` (emails author)
- `BookPublished` → `LogBookActivity`, `NotifyOnBookPublished` (emails author)
- `BookVersionCreated` → `LogBookActivity`

### Caching (Bonus)
Dashboard uses `Cache::remember()` with a 5-minute TTL. Set `CACHE_DRIVER=redis` in `.env` to use Redis automatically — no code changes required.

### Queue (Bonus)
Set `QUEUE_CONNECTION=redis` in `.env` to push `ConvertDocumentJob` into Redis queues.

---

## Installation

### Requirements
- PHP 8.3+
- MySQL 8+
- Composer
- LibreOffice (for document conversion): `sudo apt install libreoffice`
- Redis (optional, for bonus features)

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/your-org/bribooks.git
cd bribooks

# 2. Install dependencies
composer install

# 3. Configure environment
cp .env.example .env
# Edit .env: set DB_DATABASE, DB_USERNAME, DB_PASSWORD, MAIL_* as needed

# 4. Generate app key and JWT secret
php artisan key:generate
php artisan jwt:secret

# 5. Run migrations and seed test users
php artisan migrate --seed

# 6. (Optional) Start the queue worker
php artisan queue:work

# 7. Start the development server
php artisan serve
```

**Seeded test accounts** (password: `password`):

| Email | Role |
|---|---|
| admin@bribooks.com | admin |
| reviewer@bribooks.com | reviewer |
| author@bribooks.com | author |

---

## Running Tests

```bash
# Run all tests
php artisan test

# Or with PHPUnit directly
./vendor/bin/phpunit

# Run a specific suite
./vendor/bin/phpunit --testsuite Feature
./vendor/bin/phpunit --testsuite Unit

# Run a specific test file
./vendor/bin/phpunit tests/Feature/WorkflowTest.php
```

Tests use an **in-memory SQLite** database — no MySQL required for testing.

---

## API Reference

All endpoints are prefixed with `/api`. Protected routes require `Authorization: Bearer <token>`.

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/auth/register` | No | Register (role: author/reviewer/admin) |
| POST | `/api/auth/login` | No | Login, returns JWT |
| GET | `/api/profile` | Yes | Get current user |
| POST | `/api/logout` | Yes | Invalidate token |
| POST | `/api/auth/refresh` | Yes | Refresh JWT |

### Books

| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/books` | All | List books (authors see own; others see all) |
| POST | `/api/books` | Author | Create book |
| GET | `/api/books/{id}` | All | Get book detail |
| PUT | `/api/books/{id}` | Author | Update book (draft only) |
| DELETE | `/api/books/{id}` | Author | Soft-delete book |

### Version Control

| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| POST | `/api/books/{id}/versions` | Author | Create snapshot |
| GET | `/api/books/{id}/versions` | All | List versions |
| GET | `/api/books/{id}/versions/{vid}` | All | Get version detail + snapshot |
| POST | `/api/books/{id}/versions/{vid}/restore` | Author | Rollback to version |

### Chapters

| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/books/{id}/chapters` | All | List chapters |
| POST | `/api/books/{id}/chapters` | Author | Add chapter |
| PUT | `/api/chapters/{id}` | Author | Update chapter |
| DELETE | `/api/chapters/{id}` | Author | Delete chapter |

### Pages

| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/chapters/{id}/pages` | All | List pages |
| POST | `/api/chapters/{id}/pages` | Author | Add page |
| PUT | `/api/pages/{id}` | Author | Update page |
| DELETE | `/api/pages/{id}` | Author | Delete page |

### Document Upload

| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| POST | `/api/books/{id}/upload` | Author | Upload `.doc`/`.docx` (async conversion) |

### Workflow

| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| POST | `/api/books/{id}/submit` | Author | Submit for review (runs moderation) |
| POST | `/api/books/{id}/approve` | Reviewer | Approve book |
| POST | `/api/books/{id}/reject` | Reviewer | Reject with reason |
| POST | `/api/books/{id}/publish` | Admin | Publish approved book |

### Dashboard

| Method | Endpoint | Role | Description |
|--------|----------|------|-------------|
| GET | `/api/dashboard` | All | Role-specific stats (cached 5 min) |

---

## Workflow

```
Draft ──► Submitted ──► Under Review ──► Approved ──► Published
  ▲                           │
  └───────── Rejected ◄───────┘
```

- **Authors** can submit (draft or rejected → submitted)
- **Reviewers** can approve or reject (submitted/under_review → approved/rejected)
- **Admins** can publish (approved → published)
- **Published books are permanently read-only**

---

## Assumptions & Trade-offs

1. **Role assignment at registration** — In production, role upgrades (e.g. granting reviewer) would be an admin-only operation. For this assignment, the role is accepted at registration to simplify testing.

2. **Moderation is synchronous** — Runs inline before submission. For high-volume production, move to a `pending_moderation` status with a queue job.

3. **Version snapshots are full copies** — Simpler to implement and query than diffs. For very large books (thousands of pages), consider a delta/diff approach or compression.

4. **LibreOffice for conversion** — Industry-standard, handles `.doc` and `.docx` well. Alternative: `phpoffice/phpword` for pure-PHP parsing without a system dependency.

5. **Soft deletes on Books, Chapters, Pages** — Allows recovery and prevents accidental data loss. Versions are not soft-deleted (they are historical records).

6. **No separate `under_review` transition API** — A reviewer approving or rejecting implicitly moves the book through that state. A `POST /api/books/{id}/start-review` endpoint could be added trivially.
