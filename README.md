[![Code Climate](https://codeclimate.com/github/felixarntz/attachment-taxonomies/badges/gpa.svg)](https://codeclimate.com/github/felixarntz/attachment-taxonomies)
[![Latest Stable Version](https://poser.pugx.org/felixarntz/attachment-taxonomies/version)](https://packagist.org/packages/felixarntz/attachment-taxonomies)
[![License](https://poser.pugx.org/felixarntz/attachment-taxonomies/license)](https://packagist.org/packages/felixarntz/attachment-taxonomies)

Attachment Taxonomies
=====================

This plugin adds categories and tags to the WordPress media library - lightweight and developer-friendly.

Features
--------

* Adds categories and tags to the Media Library (independent from the regular post categories and tags)
* Inserts filter dropdowns for attachment taxonomies into the media toolbar and media modal
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
