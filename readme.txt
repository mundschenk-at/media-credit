=== Media Credit ===
Contributors: pputzer
Tags: media, image, images, credit, byline, author, user
Requires at least: 4.7
Tested up to: 4.7
Stable tag: 3.1.7

Adds a "Credit" field when uploading media to posts and displays it under the images on your blog to properly credit the artist.

== Description ==

Feel free to get in touch with us about anything you'd like me to add to this plugin or any feedback. We love hearing from our users! [Start a thread on the plugin forum](https://wordpress.org/support/plugin/media-credit/#postform) and we'll get back to you shortly!

This plugin adds a "Credit" field when uploading media to posts and displays it under the images on your blog to properly credit the artist.

When adding media through the Media Uploader tool or editing media already in the Media Library, this plugin adds a new field to the media form that allows users to assign credit for given media to a user of your blog (assisted with autocomplete) or to any freeform text (e.g. courtesy photos, etc.).

When this media is then inserted into a post, a new shortcode, `[media-credit]`, surrounds the media, inside of any caption, with the provided media credit information. Media credit inside this shortcode is then displayed on your blog under your media with the class `.media-credit`, which has some default styling but which you can customize to your heart's content.

You can also display all the media by an author on the author's page. See more in the [FAQ](https://wordpress.org/plugins/media-credit/faq/).

== Installation ==

This section describes how to install the plugin and get it working.

The easiest way to install this plugin is to go to **Add New** in the **Plugins** section of your blog admin and search for "Media Credit." On the far right side of the search results, click "Install."

If the automatic process above fails, follow these simple steps to do a manual install:

1. Extract the contents of the zip file into your /wp-content/plugins/ directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Party.

== Frequently Asked Questions ==

= I disabled the plugin and now unparsed [media-credit] shortcodes are appearing all over my site. Help! =

Add this to your theme's `functions.php` file to get rid of those pesky `[media-credit]` shortcodes:

`<?php
function ignore_media_credit_shortcode( $atts, $content = null ) {
	return $content;
}
global $shortcode_tags;
if ( !array_key_exists( 'media-credit', $shortcode_tags ) )
	add_shortcode('media-credit', 'ignore_media_credit_shortcode' );
?>`

Also, I'd really appreciate it if you gave us [some feedback](https://wordpress.org/support/plugin/media-credit/#postform) as to why you disabled the plugin and how it could have better suited your needs.

= Can I display all or recent media credited to a given author? =

Indeed, just call the template tag `<?php display_author_media($author_id); ?>` in your theme's author.php (or elsewhere, if you want). The template tag has optional parameters if you want to customize the CSS or text. The default options will display thumbnails of the 10 most recent media items credited to the given user floated to the right with a width of `150px` and a header of `<h3>Recent Media</h3>`.

These options can be changed with a more verbose call to the function: `<?php display_author_media($author_id, $sidebar = true, $limit = 10, $link_without_parent = false, $header = "<h3>Recent Media</h3>", $exclude_unattached = true); ?>`. This will make only the 10 most recent media items that are attached to a post display with the given header taking up the maximum width it's afforded. Each image will link to the post in which it appears, or the attachment page if it has no parent post (unless `$link_without_parent` is set to `false`). If you don't care about whether the media is attached to a post, change `$exclude_unattached` to `false√. This function as a whole will only display media uploaded and credited to a user after this plugin was installed.

= More generally, can I insert media credit information into my themes with a template tag, for instance on category pages? =

I'm so glad you asked; you certainly can! Just call `<?php get_media_credit_html($post); ?>` with an attachment id (`int`) or `WP_Post` object for an attachment to get the media credit, including a link to the author page. To echo the results, call `<?php the_media_credit_html($post); ?>`.

= Is there a template tag that just gives plain text rather than a link to the author page for users of my blog? =

Yep! If you would prefer plain-text rather than a link for all media credit (and leaving out the separator and organization), call `<?php get_media_credit($post); ?>` which uses the same parameter as above. To echo the results, call `<?php the_media_credit($post); ?>`.

= Can I link to an artist inside a media credit field? =

You sure can. Just add the link attribute in the media-credit shortcode, found in HTML view for a post, or directly when adding an image. For example, if your post contains:

`[media-credit name="Artist" align="alignleft" width="300"]<img src="http://www.mysite.com/files/2010/09/image.jpg" width="300" height="450" class="size-300 wp-image-2" />[/media-credit]`

change it to:

`[media-credit name="Artist" link="http://www.artistwebsite.com/" align="alignleft" width="300"]<img src="http://www.mysite.com/files/2010/09/image.jpg" width="300" height="450" class="size-300 wp-image-2" />[/media-credit]`

Note the link to `www.artistwebsite.com` above.

= Why do I get unparsed [media-credit] shortcodes in my Facebook/Twitter/... previews with JetPack Publicize? =

Unfortunately, this is a known bug in JetPack that can only be fixed by Automattic. As a workaround, use a different plugin for posting to social networks. Alternatively, if you make sure that you haven't got any images with credits within the first 55 words of your article, you should be fine, too.

= Your question was not answered in the FAQ? =

Feel free to get in touch with us about anything you'd like us to add to this list by <strong>leaving a message in the Wordpress.org [support forums here](https://wordpress.org/support/plugin/media-credit/#postform).</strong>

== Screenshots ==

1. Media can easily be credited to the creator of the media with the new Credit field visible when uploading or editing media
2. Media credit is nicely displayed underneath photos appearing on your blog
3. Recent media items attributed to an author can be displayed nicely on the author's page using a very simple template tag (see the [FAQ](https://wordpress.org/plugins/media-credit/faq/) for more information)


== Changelog ==

= 3.1.7 (Feb. 24, 2017) =
* JavaScript components should be slightly more fault tolerant now.
* The plugin does not depend on the visual editor being enabled anymore. Props karinamendonca29.

= 3.1.6 (Feb. 4, 2017) =
* Return `''` (the empty string) when retrieving empty freeform credits.
* Honor "Do not display default credit" for featured images.

= 3.1.5 (Jan. 29, 2017) =
* Prevent invalid link nesting in featured image credits. This means that by default, no `<a>` tags are printed for featured image credits.
  The old behaviour can be restored by including `add_filter( 'media_credit_post_thumbnail_include_links', __return_true );` in the theme's `functions.php`.
* "Display credit after posts" is now restricted to the proper single post view (and not every usage of `the_content` hook).
* "Display credit after posts" is honored when used together with "Display credit for featured images".

= 3.1.4 (Jan. 1, 2017) =
* Properly sync models when editing image details.

= 3.1.3 (Dec. 21, 2016) =
* Removed non-existent customizer callback (props @rboulet).

= 3.1.2 (Dec. 11, 2016) =
* Fixed conflict between WPBakery Visual Composer 4.x and Media Credit.
* Updated TinyMCE components.

= 3.1.1 (Aug. 15, 2016) =
* Fixed JavaScript error in media uploaded directly from Edit Post (`wp_prepare_attachment_for_js` only gets called after the upload finishes in 4.5.x).

= 3.1.0 (Aug. 13, 2016) =
* Optional no-follow attribute added.
* Optional schema.org markup added.
* Use HTML5 placeholders instead of default text when "no default credits" is set.
* Settings have been updated & streamlined.
* Added caching for backend queries.
* Updated TinyMCE components.
* Switched to the new Media API based on Backbone.js introduced in WordPress 3.5 for a snappier and more consistent user experience.
* Several security fixes and a general code clean-up have been applied due to automatic enforcement of WordPress coding standards.
* Fixed conflict between "no default credits" and featured image credits.

= 3.0.3 (Jul. 13, 2016) =
* Updating credits via the Visual Editor works again. Props siricar, timausk, jellylegs.
* Consolidated UI in "Edit Image" dialog.

= 3.0.2 (Apr. 12, 2016) =
* Updated visual editor plugin for WordPress 4.5 (minimum WordPress version is now 4.5, as well).

= 3.0.1 (Mar. 20, 2016) =
* Fixed `run_wptexturize` filter breakage caused by calling `get_bloginfo` too early.

= 3.0.0 (Mar. 6, 2016) =
* Refactored plugin for a more future-proof architecture.
* Moved all non-'template tag' functions out of the global namespace. This might break themes that relied on these (undocumented) functions. Please test before updating if you are running a highly customized installation of Media Credit.
* Wrap standalone media credits in `<figure>` if HTML5 support for captions is enabled.
* Uses WordPress language packs for translations.
* Fixed previously broken use case when both caption and credit are removed from an image in the visual editor.
* Added check whether parent is already published when displaying "attached" media.
* Added support for featured image credits.

= 2.7.6 (Feb. 7, 2016) =
* Backported fix for removing both credit and caption in Visual Editor (from 3.0 development branch).
* Please note: This will likely be the last patch before 3.0.

= 2.7.5 (Nov. 30, 2015) =
* Fixed a bug in Preview script.

= 2.7.4 (Oct. 19, 2015) =
* Fixed empty `$credit` array for `media_credit_at_end` filter when there is only one (unique) credit. Props David Higgins.

= 2.7.3 (Sep. 30, 2015) =
* Somewhere, the caption ID attribute got lost. Sorry.

= 2.7.2 (Sep. 25, 2015) =
* Fixed silent error message regarding unset `no_default_credit` option
* Fixed `wpautop` handling. Probably.

= 2.7.1 (Sep. 16, 2015) =
* Missed a few strings
* Fixed incorrect positional parameter strings

= 2.7 (Sep. 16, 2015) =
* Honor 'Do not display default credit' option in conjunction with 'Display credit after posts'
* Added translation functions to user visible strings
* Added German translation
* Fixed responsive image breakage on some themes
* Fixed autocomplete in Customizer

= 2.6.2 (Aug. 29, 2015) =
* Updated JS for switching between Visual and HTML editors
* Fixed freeform credit parsing in Visual editor

= 2.6.1 (Aug. 17, 2015) =
* Fixed "media credit at end"

= 2.6.0 (Aug. 12, 2015) =
* limit selectable users to authors (no subscribers)
* URL can be set from Media Library, not just in the post editor
* Credit URL can override the automatic author link
* Added `media_credit_at_end` filter hook

= 2.5.1 (Aug. 2, 2015) =
* Forgot to remove some debug output

= 2.5.0 (Jun. 7, 2015) =
* Added URL parameter to shortcode and GUI
* Display image toolbar with WP 4.2

= 2.4.1 (Dec. 20, 2014) =
* Fix missing newlines when switching between HTML and Visual mode in the post editor

= 2.4.0 (Dec. 17, 2014) =
* Replaced some deprecated calls
* Fixed bug that didn't add shortcode when editing a "plain" `img` tag.
* Updated UI for Wordpress 4.1

= 2.3.3 (Oct. 26, 2014) =
* Fixed encoding bug with HTML credit lines

= 2.3.2 (Oct. 22, 2014) =
* Fixed editing of image classes in the visual editor

= 2.3.1 (Oct. 9, 2014) =
* Fixed deletion of `[media-credit]` shortcodes without `[caption]` in the visual editor
* Fixed bug that added `\` to apostrophes/single quotes in certain circumstances

= 2.3.0 (Sep. 18, 2014) =
* Code clean-up
* Added support for visual editing of shortcodes
* Fixed edited posts not updating when credits are changed via the media modal.
* Added FAQ regarding unparsed shortcodes with JetPack Publicize

= 2.2.3 (Sep. 10, 2014) =
* Add plugin version to CSS & JS files to ensure the autocomplete bugfix is applied.

= 2.2.2 (Sep. 6, 2014) =
* Fixed long-standing bug with saving autocompleted credits
* Fixed missing autocomplete in WP 4.0 media grid

= 2.2.1 (Sep. 4, 2014) =
* Fixed E_NOTICE level error message

= 2.2.0 (Sep. 4, 2014) =
* Added option to prevent attachment authors as default credit
* Updated TinyMCE plugin for Wordpress 4.0
* Various bugfixes

= 2.1.2 (Aug. 12, 2014) =
* Fixed a packaging error that rendered the plugin inoperational

= 2.1.1 (Aug. 11, 2014) =
* Fix Visual Editor mode when credit field is empty

= 2.1.0 (Apr. 16, 2014) =
* Compatibility with Wordpress 3.9 (TinyMCE 4.x)
* Completely new Visual Editor code

= 2.0.1 (Oct. 29, 2013) =
* Bumped version number to fix update notice for users of 1.1.2

= 2.0 (Oct. 27, 2013) =
* Compatibility with Wordpress 3.5 media dialog
* Fixes for bugs in Visual editor
* Fixed shortcode parsing (broken since Wordpress 3.4)

= 1.1.2 (Mar. 1, 2011) =
* Fixes total autocomplete failure on WordPress 3.1
* Fixes freeform credit situation where a user was selected but freeform text was entered afterward
* Fixes extra output on activation notices (they weren't serious, but I imagine some people were freaking out seeing them)

= 1.1.1 (Sep. 19, 2010) =
* Updating media credit in the Media Library really does update the credit within posts correctly now! (props: Greg Wrey)
* Adding multiple images with freeform media credit to a post also now works as expected (props: Greg Wrey)

= 1.1 (Jun. 25, 2010) =
* Now compatible with TinyMCE! Media credit will appear inline (i.e. below the photo) when using the Visual editor rather than as an ugly shortcode.
* Updating media credit in the Media Library will now correctly and safely update it in an attached post, regardless of whether it's a WP user or not

= 1.0.2 (May 3, 2010) =
* Added filter on `the_author` so that media credit is properly displayed in Media Library (not yet for unattached media, though - will be added in WP 3.1 hopefully)
* Made `$post` parameter actually optional in template tags (used global `$post` if not given)

= 1.0.1 (Apr. 26, 2010) =
* Changed post meta field from `media-credit` to `_media_credit` so that it doesn't appear in custom fields section on Post edit page normally. Upgrade script will handle changing the key for all existing metadata.

= 1.0 (Apr. 26, 2010) =
* Added author media rendering methods (see [FAQ](https://wordpress.org/plugins/media-credit/faq/))
* If media credit is edited in the Media Library, the media credit in the post to which media is attached to will now update as well!
* Only load JS and CSS in admin on pages that need it
* Blank credit can now be assigned to media
* Switched rendering of media-credit shortcode credit info to `div` instead of `span` for more readable RSS feed

= 0.5.5 (Mar. 9, 2010) =
* Switched autocomplete to an older, more stable version - should be working great now for all blogs!
* With above, fixed loss of control of AJAX functionality in WordPress admin area
* Default options are now correctly registered when the plugin is activated
* Any pre-existing options will not be overwritten when activating the plugin
* Separator and organization names on the settings page are properly escaped

= 0.5.1 (Mar. 5, 2010) =
* Fixed autocomplete when selecting credit so that it only shows currently selectable users (particularly important for WordPress MU users).
* Made it so that upon clicking in the Credit field the text already there will be highlighted - start typing right away!
* Hid media credit inline with attachments if the "Display credits after post" option is enabled.

= 0.5 (Mar. 4, 2010) =
* Initial release.

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
