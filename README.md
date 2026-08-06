# LIMAK — Headless WordPress CMS

WordPress used strictly as a headless content backend for the LIMAK React frontend.
No theme, no WooCommerce, no cart/checkout — content and media only, served over the REST API.

## Stack

- WordPress (PHP 8.3, Apache) — core files live in a named Docker volume; only `wp-content` is bind-mounted from this repo, so the plugin code is version-controlled and everything else is disposable.
- MySQL 8.0
- phpMyAdmin (dev convenience only)
- WP-CLI one-shot bootstrap container that installs WordPress, sets pretty permalinks (required for `/wp-json/...` routes), and activates plugins automatically on first `docker compose up`.

All custom backend logic (Custom Post Types, taxonomies, ACF field groups, REST endpoints) lives in a single OOP plugin: `wp-content/plugins/limak-headless/`. This keeps everything version-controlled and independent of any theme.

## Getting started

```bash
cp .env.example .env
# edit .env and set real passwords/admin credentials

docker compose up -d --build
```

- WordPress admin: http://localhost:8080/wp-admin (credentials from `.env`)
- REST API root: http://localhost:8080/wp-json/
- phpMyAdmin: http://localhost:8081

The `wpcli` service runs once, performs setup, and exits (`restart: "no"`) — check its logs with `docker compose logs wpcli` if the site isn't installed yet.

## API

- **`/wp-json/limak/v1/*`** — the official, versioned contract for the React frontend. Every route is `GET`-only; see [`docs/openapi.yaml`](docs/openapi.yaml) for the full OpenAPI 3 spec (paths, params, response schemas, examples). View it with `npx @redocly/cli preview-docs docs/openapi.yaml`, or open [`docs/index.html`](docs/index.html) via a local static server (e.g. `npx serve docs`) — opening it directly as a `file://` URL won't work because browsers block `fetch()` on the local YAML from that origin.
- **`/wp-json/wp/v2/*`** — WordPress's default REST API, left enabled for wp-admin (block editor, media library, etc.), debugging, and future internal integrations. **The frontend must not depend on it** — response shapes there can change with any WordPress/plugin update.
- Both namespaces are read-only for unauthenticated requests: a global guard (`Security\Rest_Guard`) rejects any non-GET request without authentication, and `/wp/v2/users` is hidden from the public entirely. See `includes/Security/` for details.

## Folder structure

```
limak_admin/
├── docker-compose.yml
├── Dockerfile                  # WordPress image + upload/memory tuning
├── docker/wp/uploads.ini       # PHP overrides (upload size, memory, timeouts)
├── scripts/bootstrap.sh        # WP-CLI: install core, permalinks, plugins
├── docs/
│   ├── openapi.yaml             # OpenAPI 3 spec — the frontend contract for limak/v1
│   └── index.html                # Swagger UI viewer for openapi.yaml
├── wp-content/
│   ├── plugins/
│   │   └── limak-headless/     # All custom backend code (OOP, PSR-4 style autoload)
│   │       ├── limak-headless.php
│   │       └── includes/
│   │           ├── PostTypes/           # Products, Projects, Collections CPTs
│   │           ├── Taxonomies/          # Product Categories, Materials, Colors
│   │           ├── Fields/              # ACF field group registration (programmatic)
│   │           ├── Support/Media/       # Gallery storage + image resolution
│   │           ├── Support/             # Shared helpers (Registrable, Post_Type, Taxonomy, nav menus)
│   │           ├── Security/            # REST hardening (Rest_Guard, Disable_Xmlrpc)
│   │           └── REST/
│   │               ├── Controllers/     # limak/v1 route handlers
│   │               └── Transformers/    # WP data -> frontend DTO mapping
│   ├── mu-plugins/
│   └── uploads/
└── README.md
```

## Notes

- ACF (free) is installed automatically by the bootstrap script. Fields that require ACF PRO (Gallery, Repeater) are being implemented with free-tier-compatible equivalents for now; field registration is isolated in `includes/Fields/` so upgrading to PRO later is a contained change.
- `WORDPRESS_DEBUG` is enabled by default for local development; do not deploy this compose file as-is to production.
