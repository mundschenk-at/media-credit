=== Media Credit ===
Contributors: sbressler
Donate link: http://www.scottbressler.com/wp/
Tags: media, image, images, credit, byline, author, user
Requires at least: 2.8
Tested up to: 2.9.2
Stable tag: 0.5.1

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

= Can I insert media credit information into my themes with a template tag, for instance on category pages? =

I'm so glad you asked; you certainly can! Just call `<?php get_media_credit_html($post); ?>` with an attachment_id (int) or post object for an attachment to get a string. To echo the results, call `<?php the_media_credit_html($post); ?>`.

= Is there a template tag that just gives plain text rather than a link to the author page for users of my blog? =

Yep! If you would prefer plain-text rather than a link for all media credit (and leaving out the separator and organization), call `<?php get_media_credit($post); ?>` which uses the same parameter as above. To echo the results, call `<?php the_media_credit($post); ?>`.

Feel free to get in touch with me about anything you'd like me to add to this list. Tweet me [here](http://twitter.com/?status=@bressler%20&in_reply_to=bressler "Scott Bressler on Twitter").

== Screenshots ==

Forthcoming!

== Changelog ==

= 0.5.1 =
* Fixed autocomplete when selecting credit so that it only shows currently selectable users (particularly important for WPMU users).
* Made it so that upon clicking in the Credit field the text already there will be highlighted - start typing right away!
* Hid media credit inline with attachments if the "Display credits after post" option is enabled.

= 0.5 =
* Initial release.

== Upgrade Notice ==

= 0.5.1 =
Autocomplete list of users is filtered better, particularly for WPMU users.