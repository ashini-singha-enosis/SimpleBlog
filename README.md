# SimpleBlog

A small production-style blog built as a Laravel modular monolith. Public readers browse published posts. Signed-in authors manage their own posts, and admins can manage every post.

## Requirements

- Paginated homepage of posts that are published and whose `published_at` is in the past
- SEO-friendly post URLs: `/posts/{slug}`
- Authenticated create, update, and soft delete
- Authors can change their own posts; admins can change any post
- Drafts and scheduled posts stay off the public site
- React, Inertia, and TypeScript for the interface
- Tailwind CSS for layout

## Stack

- Laravel 13, PHP 8.3+
- MySQL or MariaDB
- Eloquent
- Inertia 3, React 19, TypeScript
- Tailwind CSS 4
- Laravel Fortify for login, registration, and email verification
- Pest for tests

The request flow is:

Browser → React/Inertia → routes → middleware → form request → controller → `PostService` → Eloquent → MySQL

There is no repository layer, DTO layer, or extra interface for posts.

## Installation

Requirements: PHP 8.3+, Composer, Node.js, and MySQL or MariaDB.

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
```

## Environment

Copy `.env.example` to `.env`. For a local XAMPP database, use:

```text
APP_NAME=SimpleBlog
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simpleblog
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
```

Create the database before migrating:

```sql
CREATE DATABASE simpleblog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

`CACHE_STORE=database` is enough for the homepage cache. Redis is not required. Switching `CACHE_STORE` to `redis` later does not require a code change because the app uses Laravel's cache abstraction.

Admin routes use the same `auth` and `verified` middleware as the dashboard. Seeded users are already verified. A newly registered user must verify their email before managing posts. With `MAIL_MAILER=log`, the verification link is written to `storage/logs/laravel.log`.

## Database setup

```bash
php artisan migrate
php artisan db:seed
```

The seeder creates:

| Email | Password | Role |
| --- | --- | --- |
| admin@example.com | password | Admin |
| author@example.com | password | Author |

It also creates published posts, drafts, and one post scheduled for the future.

## Development

```bash
composer run dev
```

That starts the Laravel server, queue listener, log tail, and Vite. The app is at http://localhost:8000.

You can also run the pieces separately:

```bash
php artisan serve
npm run dev
```

## Testing

Tests use SQLite in memory, configured in `phpunit.xml`.

```bash
php artisan test
```

## Architecture

- `PostStatus` is a backed enum: `draft` and `published`.
- `Post` uses soft deletes. Normal deletion sets `deleted_at` and leaves the row in place, so restore or permanent deletion can be added later on the same model.
- `PostPolicy` allows any authenticated user to create a post. The author or an admin may update or delete it. `is_admin` on `users` is the only role flag.
- `StorePostRequest` and `UpdatePostRequest` validate input and authorize through the policy. Controllers persist `$request->validated()` only.
- `PostService` creates and updates posts, builds a unique slug from the title, sets `published_at` to the current time when a post is published without a date, writes a short log line, and dispatches post events.
- Slugs stay unique, including against soft-deleted rows. A title change generates a new slug. An unchanged title keeps the current slug.
- `PostCreated`, `PostUpdated`, `PostPublished`, and `PostDeleted` are handled by `InvalidatePostCache`.
- The homepage caches each page for 10 minutes under a versioned key. Those events increment the version, so the next read misses the old pages. The public query still hides anything with a future `published_at`, so a scheduled post can take up to the cache TTL to appear if nothing else changes the cache.
- Post bodies are plain text. React renders them as text, so HTML in a body is escaped.

### Routes

| Method | URI | Name |
| --- | --- | --- |
| GET | `/` | `home` |
| GET | `/posts/{post:slug}` | `posts.show` |
| GET | `/admin/posts` | `admin.posts.index` |
| GET | `/admin/posts/create` | `admin.posts.create` |
| POST | `/admin/posts` | `admin.posts.store` |
| GET | `/admin/posts/{post}/edit` | `admin.posts.edit` |
| PUT | `/admin/posts/{post}` | `admin.posts.update` |
| DELETE | `/admin/posts/{post}` | `admin.posts.destroy` |

### Posts table

`id`, `user_id` (foreign key to `users`, cascade delete), `title`, unique `slug`, nullable `excerpt`, `body`, `status`, nullable `published_at`, timestamps, nullable `deleted_at`.

Indexes: unique `slug`, the foreign key index on `user_id`, and `(status, published_at)` for the homepage query.

## Production notes

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Use a real `APP_KEY`, database user, and mailer.
- Run `php artisan migrate --force` and `npm run build`.
- Point the web server at the `public` directory.
- `config/app.php` and Laravel's default exception handler hide stack traces when debug is off.
- Do not log request bodies, passwords, or tokens. Post logs include only `post_id` and `user_id`.
