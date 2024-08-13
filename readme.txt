=== Attachment Taxonomies ===

Plugin Name:       Attachment Taxonomies
Plugin URI:        https://wordpress.org/plugins/attachment-taxonomies/
Author:            Felix Arntz
Author URI:        https://felix-arntz.me
Contributors:      flixos90
Donate link:       https://felix-arntz.me/wordpress-plugins/
Tested up to:      6.6
Stable tag:        1.2.1
License:           GPLv3 or later
License URI:       https://www.gnu.org/licenses/gpl-3.0.html
Tags:              attachment, media, categories, tags, taxonomies

This plugin adds categories and tags to the WordPress media library - lightweight and developer-friendly.

== Description ==

The plugin adds two taxonomies to the WordPress media library which are then available to categorize and tag your attachments. By default, these taxonomies, although sharing the same names and behavior, are separate from the default post taxonomies, but this can easily be changed if desired.

The plugin follows WordPress Core principles and offers a lightweight alternative to similar approaches which often tend to be incredibly flexible, but at the same time complicated and bloated. And if you have a little knowledge of code, you should be able to adjust the plugin exactly to your needs if the default configuration doesn't satisfy you.

= Features =

* Adds categories and tags to the Media Library (independent from the regular post categories and tags)
* Inserts filter dropdowns for attachment taxonomies into the media toolbar and media modal
* Allows to pick taxonomy terms for attachments from within the Attachment Selection & Edit modals
* Adds a setting for the default attachment category
* Enhances the `[gallery]` shortcode so that images of a specific attachment taxonomy can be included automatically

= Quality attributes =

* Lightweight plugin following WordPress Core principles
* "Decisions, not Options"
* Can easily be used as a must-use plugin
* Provides a flexible API to add other attachment taxonomies or disable the existing ones for developers
* Provides an easy-to-use `has_default` argument that can be used when registering an attachment taxonomy in order to automatically add a setting for the default taxonomy term

== Installation ==

= As a regular plugin =

1. Upload the entire `attachment-taxonomies` folder to the `/wp-content/plugins/` directory or download it through the WordPress backend.
2. Activate the plugin through the 'Plugins' menu in WordPress.

= As a must-use plugin =

If you don't know what a must-use plugin is, you might wanna read its [introduction in the WordPress Codex](https://wordpress.org/documentation/article/must-use-plugins/).

1. Upload the entire `attachment-taxonomies` folder to the `/wp-content/mu-plugins/` directory (create the directory if it doesn't exist).
2. Move the file `/wp-content/mu-plugins/attachment-taxonomies/attachment-taxonomies.php` out of its directory to `/wp-content/mu-plugins/attachment-taxonomies.php`.

Note that, while must-use plugins have the advantage that they cannot be disabled from the admin area, they cannot be updated through WordPress, so you're recommended to keep them up to date manually.

= Administration =

Once the plugin is activated, you will see two new submenu items under Media (Categories and Tags), where you can manage available attachment categories and tags.
If you like, you can set a default category to apply to new attachment uploads, via using the added dropdown on the _Settings > Writing_ screen.

The plugin follows the WordPress Core philosophy "Decisions, not Options" - therefore there is no additional settings screen. However, the plugin is easily extendable and adjustable by developers (see [FAQ](https://wordpress.org/plugins/attachment-taxonomies/faq/)). So if the base configuration does not suit your needs, it shouldn't be too hard to change that.

== Frequently Asked Questions ==

Note that all code samples below should be run before the `init` action hook and not earlier than the `plugins_loaded` (or `muplugins_loaded` if you use the plugin as a must-use plugin) hook.

= How can I add more attachment taxonomies? =

You can simply use the WordPress Core function [`register_taxonomy()`](https://developer.wordpress.org/reference/functions/register_taxonomy/) and specify `'attachment'` as the second parameter. As an alternative, you can create your own class for the taxonomy, extending the abstract `Attachment_Taxonomy` class provided by the plugin. Then you can add it using the method `add_taxonomy( $taxonomy )` of the class `Attachment_Taxonomies`.

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

add_action(
	'plugins_loaded',
	function() {
		Attachment_Taxonomies::instance()->add_taxonomy( new Attachment_Location() );
	}
);

`

= How can I remove the default attachment taxonomies? =

To remove one of the default attachment taxonomies you should call the method `remove_taxonomy( $taxonomy_slug )` of the class `Attachment_Taxonomies`.

Example Code (removes the attachment taxonomy "Category"):

`
<?php

add_action(
	'plugins_loaded',
	function() {
		Attachment_Taxonomies::instance()->remove_taxonomy( 'attachment_category' );
	}
);

`

= How can I use the regular post categories and post tags for attachments instead of the additional taxonomies ? =

To accomplish that, first you need to remove the two taxonomies that the plugin adds (`attachment_category` and `attachment_tag`). See above for instructions on how to do that.

Then you can simply use the WordPress Core function [`register_taxonomy_for_object_type()`](https://developer.wordpress.org/reference/functions/register_taxonomy_for_object_type/) and specify `'attachment'` as the second parameter. As an alternative, you can create your own instance of the `Attachment_Existing_Taxonomy` class provided by the plugin. Then you can add it using the method `add_taxonomy( $taxonomy )` of the class `Attachment_Taxonomies`, as seen in the example below.

Example Code (makes the regular category and tag taxonomies available for attachments):

`
<?php

add_action(
	'plugins_loaded',
	function() {
		Attachment_Taxonomies::instance()->add_taxonomy( new Attachment_Existing_Taxonomy( 'category' ) );
		Attachment_Taxonomies::instance()->add_taxonomy( new Attachment_Existing_Taxonomy( 'post_tag' ) );
	}
);

` 

= How do I use the enhanced version of the `[gallery]` shortcode? =

The `[gallery]` shortcode can now be passed taxonomy attributes. They have to have the attachment taxonomy slug as the attribute name and a comma-separated list of term slugs or term IDs as value. You may also specify a new "limit" attribute to limit the amount of images shown. This is especially recommended if the attachment taxonomy term you are querying for contains a lot of images. As of version 1.2.0, the plugin furthermore supports an optional "tax_relation" attribute, which you can set to "AND" in order to show only images that satisfy _all_ of the taxonomy attributes provided on the shortcode.

Example Code (shows images with attachment categories 1 or 2 or attachment tag 5 with a limit of 20 images shown):

`
[gallery attachment_category="1,2" attachment_tag="5" limit="20"]
`

Example Code (shows images with attachment categories 1 or 2 _and_ attachment tag 5):

`
[gallery attachment_category="1,2" attachment_tag="5" tax_relation="AND"]
`

Note that there is currently no UI in the backend for this, and the preview in the editor will not work properly. It will show up correctly in the frontend though.

= Which filters are available in the plugin? =

The plugin provides some filters to adjust taxonomy arguments and labels.

* `attachment_taxonomy_args` where first argument is the array of taxonomy arguments and the second argument is the taxonomy slug that these arguments apply to
* `attachment_taxonomy_{$taxonomy_slug}_args` where the only argument is the array of taxonomy arguments for the taxonomy defined by `$taxonomy_slug`
* `attachment_taxonomy_labels` where first argument is the array of taxonomy labels and the second argument is the taxonomy slug that these labels apply to
* `attachment_taxonomy_{$taxonomy_slug}_labels` where the only argument is the array of taxonomy labels for the taxonomy defined by `$taxonomy_slug`
* `attachment_taxonomy_class_names` where the only argument is the array of class names of the taxonomies to register by default

= Where should I submit my support request? =

For regular support requests, please use the [wordpress.org support forums](https://wordpress.org/support/plugin/attachment-taxonomies). If you have a technical issue with the plugin where you already have more insight on how to fix it, you can also [open an issue on GitHub instead](https://github.com/felixarntz/attachment-taxonomies/issues).

= How can I contribute to the plugin? =

If you have ideas to improve the plugin or to solve a bug, feel free to raise an issue or submit a pull request in the [GitHub repository for the plugin](https://github.com/felixarntz/attachment-taxonomies). Please stick to the [contributing guidelines](https://github.com/felixarntz/attachment-taxonomies/blob/main/CONTRIBUTING.md).

You can also contribute to the plugin by translating it. Simply visit [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/attachment-taxonomies) to get started.

== Changelog ==

= 1.2.1 =
* Fixed: Uploading a new image via the Image block no longer causes a console error when opening the media library afterwards. Props lucymtc.
* Fixed: Ensure the development directory `build-cs` is no longer included in the plugin ZIP.

= 1.2.0 =
* Added: Add support for setting attachment terms via the REST API `wp/v2/media` endpoint.
* Added: Add support for new `tax_relation` attribute on `gallery` shortcode to control whether to include attachments with _any_ or _all_ of the given taxonomies.
* Enhanced: Surface the default attachment taxonomies in the REST API.
* Tweaked: Bump the minimum required WordPress and PHP versions.
* Tweaked: Remove deprecated parameters from 1.1.0.
* Tweaked: Use modern test infrastructure including e2e tests, revised PHPUnit tests, and GitHub Actions to ensure long-term stability.
* Tweaked: Modernize various aspects of legacy logic in PHP and JavaScript code.
* Tweaked: Deprecate singleton pattern, except on plugin main class.
* Fixed: Fix accessibility and styling issues with attachment details taxonomy selectors.
* Fixed: Use correct widths for attachment taxonomy filter dropdowns in media library to avoid offscreen elements.
* Fixed: Ensure selected attachment taxonomy terms are maintained when reopening attachment modal without reload.
* Fixed: Only show any of the plugin's UI for taxonomies that are configured to `show_ui`.
* Fixed: Address Backbone cross-version compatibility issue leading to broken media attachments browser.
* Fixed: Ensure taxonomy filters aren't rendered when in Edit Gallery view.
* Fixed: Remove unnecessary call to `load_plugin_textdomain()`.

= 1.1.1 =
* Enhanced: The plugin's attachment taxonomies now use dedicated capabilities that map to core capabilities
* Tweaked: Remove access docblock annotations per WordPress coding standards
* Fixed: Taxonomy fields in media modal are now readonly if the user does not have sufficient capabilities
* Fixed: Gallery shortcode without taxonomy attributes now works correctly again
* Fixed: No longer are duplicate attachment IDs requested in the gallery shortcode

= 1.1.0 =
* Added: There is now a settings field for the default attachment category. For other attachment taxonomies they can easily be added by passing a `has_default` argument of `true` when registering the taxonomy.
* Added: The `[gallery]` shortcode now supports passing taxonomy arguments: The slug of a taxonomy can be given alongside with a comma-separated list of term slugs or IDs.
* Added: New filter `attachment_taxonomy_class_names` can be used to filter the class names for the taxonomies that should be registered by default.
* Tweaked: Properly escape attributes in admin UI and style rules. Props tareiking.
* Tweaked: Store custom and existing taxonomies in the same internal container and deprecate the now unnecessary `$existing` parameter across several functions.
* Tweaked: Follow WordPress Documentation Standards.
* Tweaked: Modernize Travis-CI configuration and code climate checks.
* Fixed: Initialization no longer fails when using as an mu-plugin without moving the main file one level above. Props tareiking.
* Fixed: Correct property is used when referring to the taxonomy query var and the taxonomy slug respectively. Props tareiking.

= 1.0.1 =
* Fixed: uploads in the post edit screen no longer freeze

= 1.0.0 =
* First stable version
