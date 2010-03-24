=== Media Credit ===
Contributors: sbressler
Donate link: http://www.scottbressler.com/wp/
Tags: media, image, images, credit, byline, author, user
Requires at least: 2.8
Tested up to: 2.9.2
Stable tag: 0.5.5

This plugin adds a "Credit" field to the media uploading and editing tool and inserts this credit when the images appear on your blog.

== Description ==

This plugin adds a "Credit" field to the media uploading and editing tool and inserts this credit when the images appear on your blog.

When adding media through the Media Uploader tool or editing media already in the Media Library, this plugin adds a new field to the media form that allows users to assign credit for given media to a user of your blog (assisted with autocomplete) or to any freeform text (e.g. courtesy photos, etc.).

When this media is then inserted into a post, a new quicktag surrounds the media, [media-credit], inside of any caption, with the media credit information. Media credit inside this quicktag is then displayed on your blog under your media with the class .media-credit, which has some default styling but you can customize to your heart's content.

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

== Installation ==

This section describes how to install the plugin and get it working.

The easiest way to install this plugin is to go to **Add New** in the **Plugins** section of your blog admin and search for "Media Credit". On the far right side of the search results, click "Install."

If the automatic process above fails, follow these simple steps to do a manual install:

1. Extract the contents of the zip file into your /wp-content/plugins/ directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Party.

== Frequently Asked Questions ==

= Can I display all or recent media credited to a given author? =

Indeed, just call `display_author_media($author_id)`, which has optional parameters if you want to customize the CSS or text. The default options will display thumbnails of the 10 most recent media items credited to the given user floated to the right with a width of 150px and a header of `<h3>Recent Media</h3>`. These options can be changed with a more verbose call to the function: `display_author_media($author_id, $float = false, $header = "<h1>A Big Header</h1", $limit = 5)`. This will make only the 5 most recent media items display with the given header taking up the maximum width it's afforded.

= More generally, can I insert media credit information into my themes with a template tag, for instance on category pages? =

I'm so glad you asked; you certainly can! Just call `<?php get_media_credit_html($post); ?>` with an attachment_id (int) or post object for an attachment to get a string. To echo the results, call `<?php the_media_credit_html($post); ?>`.

= Is there a template tag that just gives plain text rather than a link to the author page for users of my blog? =

Yep! If you would prefer plain-text rather than a link for all media credit (and leaving out the separator and organization), call `<?php get_media_credit($post); ?>` which uses the same parameter as above. To echo the results, call `<?php the_media_credit($post); ?>`.

Feel free to get in touch with me about anything you'd like me to add to this list. Tweet me [here](http://twitter.com/?status=@bressler%20&in_reply_to=bressler "Scott Bressler on Twitter").

== Screenshots ==

Forthcoming!

== Changelog ==

= 0.5.6 =
* Added author media rendering methods (see FAQ)
* If media credit is edited in the Media Library, the media credit in the post to which media is attached to will now update as well
* Switched rendering of media-credit shortcode credit info to div instead of span for more readable RSS feed
* Fixed a potentially fatal bug due to a typo

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

= 0.5.5 =
Fixed autocomplete and losing control of AJAX functionality in WordPress admin, and settings are sanitized better

= 0.5.1 =
Autocomplete list of users is filtered better, particularly for WordPress MU users.
