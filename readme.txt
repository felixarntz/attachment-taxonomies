=== Attachment Taxonomies ===

Plugin Name:       Attachment Taxonomies
Plugin URI:        http://wordpress.org/plugins/attachment-taxonomies/
Author:            Felix Arntz
Author URI:        https://leaves-and-love.net
Contributors:      flixos90
Donate link:       https://leaves-and-love.net/wordpress-plugins/
Requires at least: 3.5
Tested up to:      4.6
Stable tag:        1.0.1
Version:           1.0.1
License:           GNU General Public License v3
License URI:       http://www.gnu.org/licenses/gpl-3.0.html
Tags:              attachment, media, taxonomy, categories, tags

This plugin adds categories and tags to the WordPress media library - lightweight and developer-friendly.

== Description ==

The plugin adds two taxonomies to the WordPress media library which are then available to categorize and tag your attachments. By default, these taxonomies, although sharing the same names and behavior, are separate from the default post taxonomies, but this can easily be changed if desired.

The plugin follows WordPress Core principles and offers a lightweight alternative to similar approaches which often tend to be incredibly flexible, but at the same time complicated and bloated. And if you have a little knowledge of code, you should be able to adjust the plugin exactly to your needs if the default configuration doesn't satisfy you.

= Features =

* Adds categories and tags to the Media Library (independent from the regular post categories and tags)
* Inserts filter dropdowns for attachment taxonomies into the media toolbar and media modal
* Allows to pick taxonomy terms for attachments from within the Attachment Selection & Edit modals
* Lightweight plugin following WordPress Core principles
* "Decisions, not Options"
* Can easily be used as a must-use plugin
* Provides a flexible API to add other attachment taxonomies or disable the existing ones for developers
* Developers are free to use the plugin-provided object-oriented taxonomy approach or use familiar WordPress Core functions

== Installation ==

= As a regular plugin =

1. Upload the entire `attachment-taxonomies` folder to the `/wp-content/plugins/` directory or download it through the WordPress backend.
2. Activate the plugin through the 'Plugins' menu in WordPress.

= As a must-use plugin =

If you don't know what a must-use plugin is, you might wanna read its [introduction in the WordPress Codex](https://codex.wordpress.org/Must_Use_Plugins) - don't worry, that's nothing purely for developers.

1. Upload the entire `attachment-taxonomies` folder to the `/wp-content/mu-plugins/` directory (create the directory if it doesn't exist).
2. Move the file `/wp-content/mu-plugins/attachment-taxonomies/attachment-taxonomies.php` out of its directory to `/wp-content/mu-plugins/attachment-taxonomies.php`.

Note that, while must-use plugins have the advantage that they cannot be disabled from the admin area, they cannot be updated through WordPress, so you're recommended to keep them up to date manually.

= Administration =

Once the plugin is activated, you will see two new submenu items under Media (Categories and Tags). The plugin follows the WordPress Core philosophy "Decisions, not Options" - therefore there is no additional settings screen. However, the plugin is easily extendable and adjustable by developers (see [FAQ](http://wordpress.org/plugins/attachment-taxonomies/faq/)). So if the base configuration does not suit your needs, it shouldn't be too hard to change that.

== Frequently Asked Questions ==

Note that all code samples below should be run before the `init` action hook and not earlier than the `plugins_loaded` (or `muplugins_loaded` if you use the plugin as a must-use plugin) hook.

= How can I add more attachment taxonomies? =

You can simply use the WordPress Core function [`register_taxonomy()`](https://codex.wordpress.org/Function_Reference/register_taxonomy) and specify `'attachment'` as the second parameter. As an alternative, you can create your own class for the taxonomy, extending the abstract `Attachment_Taxonomy` class provided by the plugin. Then you can add it using the method `add_taxonomy( $taxonomy )` of the class `Attachment_Taxonomies`.

Example Code (adds an attachment taxonomy called "Location"):

`
<?php

final class Attachment_Location extends Attachment_Taxonomy {
	protected $slug = 'attachment_location';
	protected $labels = array(
		'name'				=> __( 'Locations', 'textdomain' ),
		'singular_name'		=> __( 'Location', 'textdomain' ),
		/* more labels here... */
	);
	protected $args = array(
		'hierarchical'		=> true,
		'query_var'			=> 'location',
		/* more arguments here... */
	);
}

Attachment_Taxonomies::instance()->add_taxonomy( new Attachment_Location() );

`

= How can I remove the default attachment taxonomies? =

To remove one of the default attachment taxonomies you should call the method `remove_taxonomy( $taxonomy_slug )` of the class `Attachment_Taxonomies`.

Example Code (removes the attachment taxonomy "Category"):

`
<?php

Attachment_Taxonomies::instance()->remove_taxonomy( 'attachment_category' );

`

= How can I use the regular post categories and post tags for attachments instead of the additional taxonomies ? =

To accomplish that, first you need to remove the two taxonomies that the plugin adds (`attachment_category` and `attachment_tag`). See above for instructions on how to do that.

Then you can simply use the WordPress Core function [`register_taxonomy_for_object_type()`](https://codex.wordpress.org/Function_Reference/register_taxonomy_for_object_type) and specify `'attachment'` as the second parameter. As an alternative, you can create your own instance of the `Attachment_Existing_Taxonomy` class provided by the plugin. Then you can add it using the method `add_taxonomy( $taxonomy, $existing )` of the class `Attachment_Taxonomies`, with the second parameter set to `true`.

Example Code (makes the regular category and tag taxonomies available for attachments):

`
<?php

Attachment_Taxonomies::instance()->add_taxonomy( new Attachment_Existing_Taxonomy( 'category' ), true );
Attachment_Taxonomies::instance()->add_taxonomy( new Attachment_Existing_Taxonomy( 'post_tag' ), true );

` 

= Which filters are available in the plugin? =

The plugin provides some filters to adjust taxonomy arguments and labels.

* `attachment_taxonomy_args` where first argument is the array of taxonomy arguments and the second argument is the taxonomy slug that these arguments apply to
* `attachment_taxonomy_{$taxonomy_slug}_args` where the only argument is the array of taxonomy arguments for the taxonomy defined by `$taxonomy_slug`
* `attachment_taxonomy_labels` where first argument is the array of taxonomy labels and the second argument is the taxonomy slug that these labels apply to
* `attachment_taxonomy_{$taxonomy_slug}_labels` where the only argument is the array of taxonomy labels for the taxonomy defined by `$taxonomy_slug`

= Where should I submit my support request? =

I preferably take support requests as [issues on Github](https://github.com/felixarntz/attachment-taxonomies/issues), so I would appreciate if you created an issue for your request there. However, if you don't have an account there and do not want to sign up, you can of course use the [wordpress.org support forums](https://wordpress.org/support/plugin/attachment-taxonomies) as well.

= How can I contribute to the plugin? =

If you're a developer and you have some ideas to improve the plugin or to solve a bug, feel free to raise an issue or submit a pull request in the [Github repository for the plugin](https://github.com/felixarntz/attachment-taxonomies).

You can also contribute to the plugin by translating it. Simply visit [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/attachment-taxonomies) to get started.

== Changelog ==

= 1.0.1 =
* Fixed: uploads in the post edit screen no longer freeze

= 1.0.0 =
* First stable version
