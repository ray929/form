# Forms

A lightweight multi-form system with SQLite, Turnstile verification, and email delivery via Resend API. Built with vanilla PHP — zero dependencies.

## Features

- **Admin Panel** — Login with bcrypt password + Turnstile. Create/edit/delete forms, view submissions, generate embed code.
- **Embeddable Forms** — CORS-friendly, CSP-safe (no inline scripts). Supports `?theme=auto|light|dark` and `?lang=en|zh`.
- **Per-Form Query Pages** — Each form has its own password-protected data viewer at `/query/{id}`. Multiple forms can be viewed simultaneously without session conflicts.
- **Email Delivery** — Asynchronous email via Resend API after form submission. Status tracked per submission (sent/failed/pending).
- **i18n** — Embed forms support English and Chinese. Admin panel is English-only.

## Environment Variables

| Key | Description |
|---|---|
| `resend_api_key` | Resend API key for sending emails |
| `turnstile_site_key` | Cloudflare Turnstile site key (test: `1x00...0AA`) |
| `turnstile_secret_key` | Cloudflare Turnstile secret key (test: `1x00...0AA`) |
| `default_recipient` | Fallback email when form has no recipient set |
| `from_email` | Sender email address |
| `db_path` | SQLite database file path |

Configuration via `config.php` (copy from `config.example.php`).

## Quick Start

```bash
# 1. Clone
git clone git@github.com:ray929/form.git
cd form

# 2. Configure
cp config.example.php config.php
# Edit config.php with your keys

# 3. Create admin password
htpasswd -B -c .htpasswd admin

# 4. Start dev server
php -S localhost:8000 -t public

# 5. Open
# Admin: http://localhost:8000/admin/login
```

## Deployment

```bash
# Upload all files to server, set public/ as web root.
# Ensure db/ directory is writable by PHP.

# Production Turnstile keys must be obtained from Cloudflare dashboard.
# Test keys only work on localhost.
```

## Tech Stack

- **PHP** (vanilla, no framework)
- **SQLite** via PDO
- **Cloudflare Turnstile** for bot protection
- **Resend API** for email delivery
- **bcrypt** for password hashing (htpasswd + per-form query passwords)

## Directory Structure

```
├── api/               PHP API endpoints
├── db/                SQLite database files (gitignored)
├── lang/              Translation dictionaries (en, zh)
├── public/            Web root
│   ├── css/           Stylesheets
│   ├── js/            Client-side scripts
│   └── index.php      Entry point / router
├── views/             PHP templates
│   ├── admin/         Admin panel pages
│   ├── embed/         Embeddable form
│   ├── query/         Data query pages
│   └── partials/      Shared layout components
├── config.example.php Configuration template
├── .htpasswd          Admin password file (gitignored)
└── App.php            Application container
```
