[![WordPress plugin version](https://img.shields.io/wordpress/plugin/v/attachment-taxonomies?style=for-the-badge)](https://wordpress.org/plugins/attachment-taxonomies/)
[![WordPress tested version](https://img.shields.io/wordpress/plugin/tested/attachment-taxonomies?style=for-the-badge)](https://wordpress.org/plugins/attachment-taxonomies/)
[![WordPress plugin downloads](https://img.shields.io/wordpress/plugin/dt/attachment-taxonomies?style=for-the-badge)](https://wordpress.org/plugins/attachment-taxonomies/)
[![Packagist version](https://img.shields.io/packagist/v/felixarntz/attachment-taxonomies?style=for-the-badge)](https://packagist.org/packages/felixarntz/attachment-taxonomies)
[![Packagist license](https://img.shields.io/packagist/l/felixarntz/attachment-taxonomies?style=for-the-badge)](https://packagist.org/packages/felixarntz/attachment-taxonomies)
[![PHP Unit Testing](https://img.shields.io/github/actions/workflow/status/felixarntz/attachment-taxonomies/php-test.yml?style=for-the-badge&label=PHP%20Unit%20Testing)](https://github.com/felixarntz/attachment-taxonomies/actions/workflows/php-test.yml)
[![E2E Testing](https://img.shields.io/github/actions/workflow/status/felixarntz/attachment-taxonomies/e2e-test.yml?style=for-the-badge&label=E2E%20Testing)](https://github.com/felixarntz/attachment-taxonomies/actions/workflows/e2e-test.yml)

Attachment Taxonomies
=====================

This plugin adds categories and tags to the WordPress media library - lightweight and developer-friendly.

Features
--------

* Adds categories and tags to the Media Library (independent from the regular post categories and tags)
* Inserts filter dropdowns for attachment taxonomies into the media toolbar and media modal
* Allows to pick taxonomy terms for attachments from within the Attachment Selection & Edit modals
* Lightweight plugin following WordPress Core principles
* "Decisions, not Options"
* Can easily be used as a must-use plugin
* Provides a flexible API to add other attachment taxonomies or disable the existing ones for developers
* Developers are free to use the plugin-provided object-oriented taxonomy approach or use familiar WordPress Core functions

Installation and Setup
----------------------

You can download the latest version from the [WordPress plugin repository](http://wordpress.org/plugins/attachment-taxonomies/).

If you like, you can also use it as a must-use plugin by moving the directory into the `wp-content/mu-plugins` directory and then moving the main file `attachment-taxonomies.php` from the plugin's directory to the must-use plugins root directory (i.e. from `wp-content/mu-plugins/attachment-taxonomies` to `wp-content/mu-plugins`). Note that, while must-use plugins have the advantage that they cannot be disabled from the admin area, they cannot be updated through WordPress, so you're recommended to keep them up to date manually.

Once the plugin is activated, you will see two new submenu items under Media (Categories and Tags). The plugin follows the WordPress Core philosophy "Decisions, not Options" - therefore there is no additional settings screen. However, the plugin is easily extendable and adjustable by developers, providing a flexible API. So if the base configuration does not suit your needs, it shouldn't be too hard to change that.

Contributions and Bugs
----------------------

If you have ideas on how to improve the plugin or if you discover a bug, I would appreciate if you shared them with me, right here on Github. In either case, please open a new issue [here](https://github.com/felixarntz/attachment-taxonomies/issues/new)!

You can also contribute to the plugin by translating it. Simply visit [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/attachment-taxonomies) to get started.
