=== Media Credit ===
Contributors: pputzer, sbressler
Tags: media, image, images, credit, byline, author, user
Tested up to: 6.2
Stable tag: 4.3.0
License: GPLv2 or later

Adds a "Credit" field when uploading media to posts and displays it under the images on your blog to properly credit the artist.


== Description ==

Feel free to get in touch with us about anything you'd like me to add to this plugin or any feedback. We love hearing from our users! [Start a thread on the plugin forum](https://wordpress.org/support/plugin/media-credit/#postform) and we'll get back to you shortly!

This plugin adds a "Credit" field when uploading media to posts and displays it under the images on your blog to properly credit the artist.

When adding media through the Media Uploader tool or editing media already in the Media Library, this plugin adds a new field to the media form that allows users to assign credit for given media to a user of your blog (assisted with autocomplete) or to any freeform text (e.g. courtesy photos, etc.).

When this media is then inserted into a post, a new shortcode, `[media-credit]`, surrounds the media, inside of any caption, with the provided media credit information. Media credit inside this shortcode is then displayed on your blog under your media with the class `.media-credit`, which has some default styling but which you can customize to your heart's content.

You can also display all the media by an author on the author's page. See more in the [FAQ](https://wordpress.org/plugins/media-credit/faq/).

== Frequently Asked Questions ==

= I disabled the plugin and now unparsed [media-credit] shortcodes are appearing all over my site. Help! =

Add this to your theme's `functions.php` file to get rid of those pesky
`[media-credit]` shortcodes:

`<?php
function ignore_media_credit_shortcode( $atts, $content = null ) {
	return $content;
}
global $shortcode_tags;
if ( ! array_key_exists( 'media-credit', $shortcode_tags ) )
	add_shortcode( 'media-credit', 'ignore_media_credit_shortcode' );
?>`

Also, I'd really appreciate it if you gave us [some feedback](https://wordpress.org/support/plugin/media-credit/#postform) as to why you disabled the plugin and how it could have better suited your needs.

= Can I display all or recent media credited to a given author? =

Indeed, just call the template tag `<?php \Media_Credit::display_author_media( $author_id ); ?>` in your theme's author.php (or elsewhere, if you want). The template tag has optional parameters if you want to customize the CSS or text. The default options will display thumbnails of the 10 most recent media items credited to the given user floated to the right with a width of `150px` and a header of `<h3>Recent Media</h3>`.

These options can be changed with a more verbose call to the function: `<?php \Media_Credit::display_author_media( $author_id, $sidebar = true, $limit = 10, $link_without_parent = false, $header = "<h3>Recent Media</h3>", $exclude_unattached = true ); ?>`. This will make only the 10 most recent media items that are attached to a post display with the given header taking up the maximum width it's afforded. Each image will link to the post in which it appears, or the attachment page if it has no parent post (unless `$link_without_parent` is set to `false`). If you don't care about whether the media is attached to a post, change `$exclude_unattached` to `false`. This function as a whole will only display media uploaded and credited to a user after this plugin was installed.

= More generally, can I insert media credit information into my themes with a template tag, for instance on category pages? =

I'm so glad you asked; you certainly can! Just call `<?php \Media_Credit::get_html( $attachment ); ?>` with an attachment id (`int`) or `WP_Post` object for an attachment to get the media credit, including a link to the author page. To echo the results, call `<?php \Media_Credit::html( $attachment ); ?>`.

= Is there a template tag that just gives plain text rather than a link to the author page for users of my blog? =

Yep! If you would prefer plain-text rather than a link for all media credit (and leaving out the separator and organization), call `<?php \Media_Credit::get_plaintext( $attachment ); ?>` which uses the same parameter as above. To echo the results, call `<?php \Media_Credit::plaintext( $attachment ); ?>`.

= Can I link to an artist inside a media credit field? =

You sure can. Just add the link attribute in the media-credit shortcode, found in HTML view for a post, or directly when adding an image. For example, if your post contains:

`[media-credit name="Artist" align="left" width="300"]<img src="https://www.mysite.com/files/2010/09/image.jpg" width="300" height="450" class="size-300 wp-image-2" />[/media-credit]`

change it to:

`[media-credit name="Artist" link="https://www.artistwebsite.com/" align="left" width="300"]<img src="https://www.mysite.com/files/2010/09/image.jpg" width="300" height="450" class="size-300 wp-image-2" />[/media-credit]`

Note the link to `www.artistwebsite.com` above.

= Why do I get unparsed [media-credit] shortcodes in my Facebook/Twitter/... previews with JetPack Publicize? =

Unfortunately, this is a known bug in JetPack that can only be fixed by Automattic. As a workaround, use a different plugin for posting to social networks. Alternatively, if you make sure that you haven't got any images with credits within the first 55 words of your article, you should be fine, too.

= Your question was not answered in the FAQ? =

Feel free to get in touch with us about anything you'd like us to add to this list by <strong>leaving a message in the Wordpress.org [support forums here](https://wordpress.org/support/plugin/media-credit/#postform).</strong>

== Screenshots ==

1.  Media can easily be credited to the creator of the media with the new Credit
    field visible when uploading or editing media
2.  Media credit is nicely displayed underneath photos appearing on your blog
3.  Recent media items attributed to an author can be displayed nicely on the author's
    page using a very simple template tag (see the [FAQ](https://wordpress.org/plugins/media-credit/faq/)
    for more information)


== Changelog ==

= 4.3.0 (April 1, 2023) =

* _Feature_: Media Credit is now fully compatible with PHP 8.2.
* _Bugfix_: Author queries now use capabilities instead of the deprecated `'who' => 'authors'`.
* _Bugfix_: `run_wptexturize` filter now can be used even after the plugin is initialized.
* _Change_: WordPress minimum version increased to 5.9.0.
* _Change_: PHP minimum version increased to 7.4.0.

= 4.2.1 (July 30, 2021) =
* _Bugfix_: Don't eat content between multiple shortcodes on save and switch between visual and HTML mode in the classic editor.

= 4.2.0 (July 22, 2021) =
* _Feature_: A shorter label for credits displayed at the end of a post can be enabled via the new filter hook `media_credit_at_end_use_short_label` (`Images:` instead of `Images courtesy of`).
* _Feature_: The automatic linking of user credits to the WordPress author page can be disabled with the new filter hook `media_credit_disable_author_urls`.
* _Feature_: Media Credit is now fully compatible with PHP 8.0.
* _Bugfix_: The credit overlay cannot be selected any more by accident in the classic editor.
* _Bugfix_: Several visual glitches in classic editor have been fixed and parsing has been made more robust.
* _Bugfix_: Credit width is set properly for custom image sizes when editing image properties in the classic editor.
* _Bugfix_: Organization and separator are set correctly when adding a user credit via the image properties dialogue in the classic editor.
* _Bugfix_: Autocomplete works reliably again in Media Library.
* _Bugfix_: Credits can be deliberately set to be empty again when credits to Wordpress authors are enabled (had been broken since 4.0.0).
* _Bugfix_: No more `Notice: register_rest_route was called incorrectly` during plugin initialization.
* _Change_: WordPress minimum version increased to 5.2.0.
* _Change_: PHP minimum version increased to 7.0.0.
* _Change_: Support for Internet Explorer 11 has been dropped.
* _Change_: A fabulous new plugin icon designed by [Johanna Amann](https://www.instagram.com/_jo_am/).

== Other Notes ==
**Options**

This plugin provides a few options which appear on the **Media** page under **Settings**. These options are:

*   Separator
*   Organization
*   Display credits after post

**Example**

This is best explained with an example. With a separator of " | " and an organization of The Daily Times, media inserted will be followed with a credit line appearing as follows, with the username linking to the author page for that user:

[John Smith]() | The Daily Times

**Further explanation**

*Separator*: These are the characters that separate the display name for a user on your blog from the name of the organization, as described below. The default separator is " | " but feel free to change this to suit your needs.

*Organization*: This is what appears after the separator as listed above. The default organization is the name of your blog.

*Display credits after post*: With this option enabled, media credit shortcodes will not appear by default when inserting media into your posts. Instead, the plugin will look through the content of your posts for any media attachments and display something like the following at the end of each post with the CSS class .media-credit-end:

Images courtesy of [John Smith]() | The Daily Times, Michael Scott and Jane Doe.

In this example, John Smith is a user of your blog, while the latter two credits are not.
