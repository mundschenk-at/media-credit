<?php

/*
	Plugin Name: Media Credit
	Plugin URI: https://code.mundschenk.at/media-credit/
	Description: This plugin adds a "Credit" field to the media uploading and editing tool and inserts this credit when the images appear on your blog.
	Version: 3.0.0
	Author: Peter Putzer
	Author: Scott Bressler
	Author URI: https://mundschenk.at/
	Text Domain: media-credit
	License: GPL2
*/

require_once( 'class-media-credit.php' );
new Media_Credit();
