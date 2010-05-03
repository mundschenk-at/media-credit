=== Media Credit ===
Contributors: sbressler
Donate link: http://www.scottbressler.com/blog/plugins/
Tags: media, image, images, credit, byline, author, user
Requires at least: 2.8
Tested up to: 3.0
Stable tag: 1.0.2

This plugin adds a "Credit" field to the media uploading and editing tool and inserts this credit when the images appear on your blog.

== Description ==

This plugin adds a "Credit" field to the media uploading and editing tool and inserts this credit when the images appear on your blog.

When adding media through the Media Uploader tool or editing media already in the Media Library, this plugin adds a new field to the media form that allows users to assign credit for given media to a user of your blog (assisted with autocomplete) or to any freeform text (e.g. courtesy photos, etc.).

When this media is then inserted into a post, a new shortcode surrounds the media, [media-credit], inside of any caption, with the media credit information. Media credit inside this shortcode is then displayed on your blog under your media with the class .media-credit, which has some default styling but you can customize to your heart's content.

Feel free to get in touch with me about anything you'd like me to add to this plugin. E-mail me [here](mailto:sbressler@gmail.com "E-mail Scott!").

== Installation ==

This section describes how to install the plugin and get it working.

The easiest way to install this plugin is to go to **Add New** in the **Plugins** section of your blog admin and search for "Media Credit". On the far right side of the search results, click "Install."

If the automatic process above fails, follow these simple steps to do a manual install:

1. Extract the contents of the zip file into your /wp-content/plugins/ directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Party.

== Frequently Asked Questions ==

= I disabled the plugin and now unparsed [media-credit] shortcodes are appearing all over my site - help! =

Add this to your theme's functions.php file to get rid of those pesky media-credit shortcodes:

`<?php
function ignore_media_credit_shortcode( $atts, $content = null ) {
	return $content;
}
global $shortcode_tags;
if ( !array_key_exists( 'media-credit', $shortcode_tags ) )
	add_shortcode('media-credit', 'ignore_media_credit_shortcode' );
?>`

Also, I'd really appreciate it if you gave me [some feedback](mailto:sbressler@gmail.com "Let Scott know why you disabled the plugin!") as to why you disabled the plugin.

= Can I display all or recent media credited to a given author? =

Indeed, just call the template tag `<?php display_author_media($author_id); ?>` in your theme's author.php (or elsewhere, if you want). The template tag has optional parameters if you want to customize the CSS or text. The default options will display thumbnails of the 10 most recent media items credited to the given user floated to the right with a width of 150px and a header of `<h3>Recent Media</h3>`.

These options can be changed with a more verbose call to the function: `<?php display_author_media($author_id, $sidebar = true, $limit = 10, $link_without_parent = false, $header = "<h3>Recent Media</h3>", $exclude_unattached = true); ?>`. This will make only the 10 most recent media items that are attached to a post display with the given header taking up the maximum width it's afforded. Each image will link to the post in which it appears, or the attachment page if it has no parent post (unless $link_without_parent is set to false). If you don't care about whether the media is attached to a post, change $exclude_unattached to false. This function as a whole will only display media uploaded and credited to a user after this plugin was installed.

= More generally, can I insert media credit information into my themes with a template tag, for instance on category pages? =

I'm so glad you asked; you certainly can! Just call `<?php get_media_credit_html($post); ?>` with an attachment_id (int) or post object for an attachment to get the media credit, including a link to the author page. To echo the results, call `<?php the_media_credit_html($post); ?>`.

= Is there a template tag that just gives plain text rather than a link to the author page for users of my blog? =

Yep! If you would prefer plain-text rather than a link for all media credit (and leaving out the separator and organization), call `<?php get_media_credit($post); ?>` which uses the same parameter as above. To echo the results, call `<?php the_media_credit($post); ?>`.

Feel free to get in touch with me about anything you'd like me to add to this list. E-mail me [here](mailto:sbressler@gmail.com "E-mail Scott!").

== Screenshots ==

1. Media can easily be credited to the creator of the media with the new Credit field visible when uploading or editing media
2. Media credit is nicely displayed underneath photos appearing on your blog
3. Recent media items attributed to an author can be displayed nicely on the author's page using a very simple template tag (see the [FAQ](http://wordpress.org/extend/plugins/media-credit/faq/) for more information)


== Changelog ==

= 1.0.2 =
* Added filter on the_author so that media credit is properly displayed in Media Library (not yet for unattached media, though - will be added in WP 3.1 hopefully)
* Made $post parameter actually optional in template tags (used global $post if not given)

= 1.0.1 =
* Changed post meta field from media-credit to _media_credit so that it doesn't appear in custom fields section on Post edit page normally. Upgrade script will handle changing the key for all existing metadata.

= 1.0 =
* Added author media rendering methods (see [FAQ](http://wordpress.org/extend/plugins/media-credit/faq/))
* If media credit is edited in the Media Library, the media credit in the post to which media is attached to will now update as well!
* Only load JS and CSS in admin on pages that need it
* Blank credit can now be assigned to media
* Switched rendering of media-credit shortcode credit info to div instead of span for more readable RSS feed

= 0.5.5 =
* Switched autocomplete to an older, more stable version - should be working great now for all blogs!
* With above, fixed loss of control of AJAX functionality in WordPress admin area
* Default options are now correctly registered when the plugin is activated
* Any pre-existing options will not be overwritten when activating the plugin
* Separator and organization names on the settings page are properly escaped

= 0.5.1 =
* Fixed autocomplete when selecting credit so that it only shows currently selectable users (particularly important for WordPress MU users).
* Made it so that upon clicking in the Credit field the text already there will be highlighted - start typing right away!
* Hid media credit inline with attachments if the "Display credits after post" option is enabled.

= 0.5 =
* Initial release.

== Upgrade Notice ==

= 1.0.2 =
Filtering the author in Media Library and $post parameter in media credit template tags truly optional.

= 1.0.1 =
Changed postmeta key from media-credit to _media_credit. Upgrade script will handle changing the key for all existing metadata.

= 1.0 =
Finalized version 1.0 with added simple author media template tags, and ensuring that changes to media credit now reflected in parent post

= 0.5.5 =
Fixed autocomplete and losing control of AJAX functionality in WordPress admin, and settings are sanitized better

= 0.5.1 =
Autocomplete list of users is filtered better, particularly for WordPress MU users.

== Other Notes == 
**Options**

This plugin provides a few options which appear on the **Media** page under **Settings**. These options are:

* Separator
* Organization
* Display credits after post

**Example**

This is best explained with an example. With a separator of " | " and an organization of The Daily Times, media inserted will be followed with a credit line appearing as follows, with the username linking to the author page for that user:

[John Smith]() | The Daily Times

**Further explanation**

*Separator*: These are the characters that separate the display name for a user on your blog from the name of the organization, as described below. The default separator is " | " but feel free to change this to suit your needs.

*Organization*: This is what appears after the separator as listed above. The default organization is the name of your blog.

*Display credits after post*: With this option enabled, media credit shortcodes will not appear by default when inserting media into your posts. Instead, the plugin will look through the content of your posts for any media attachments and display something like the following at the end of each post with the CSS class .media-credit-end:

Images courtesy of [John Smith]() | The Daily Times, Michael Scott and Jane Doe.

In this example, John Smith is a user of your blog, while the latter two credits are not.
