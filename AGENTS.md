# Attachment Taxonomies

A WordPress plugin that adds categories and tags to the WordPress media library.

## Workflow Commands

- `composer install` - install PHP dependencies
- `composer lint` - lint PHP code
- `composer fix` - auto-fix PHP code
- `pnpm install` — install JS dependencies
- `pnpm lint` - lint JS code
- `pnpm fix` - auto-fix JS code
- `pnpm build` - build JS code
- `pnpm test:php` - run PHP tests (requires [dev server running](#using-the-dev-server))
- `pnpm test:php-multisite` - run PHP tests for Multisite (requires [dev server running](#using-the-dev-server))
- `pnpm test:e2e` - run E2E tests (requires [dev server running](#using-the-dev-server))

**DO NOT** use `composer test` and `composer test-multisite` to run PHP tests!

## Using the Dev Server

This repo comes with a dev server configured via `@wordpress/env` (`wp-env`).

- Run `pnpm wp-env start` to start the dev server.
- Access the dev site at `http://localhost:8888`.
- WP Admin is at `http://localhost:8888/wp-admin/`. Credentials are `admin` and `password`.
- Run `pnpm wp-env stop` to stop the dev server.

## Misc Requirements

- For any new `@since` annotations you add, always use `@since n.e.x.t`. This placeholder format is required so that it gets replaced with the correct version number later, once a release is ready to publish.
