<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2016 Peter Putzer.
 * Copyright 2010-2011 Scott Bressler.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @link       https://mundschenk.at
 * @since      3.0.0
 *
 * @package    Media_Credit
 * @subpackage Media_Credit/includes
 */

if ( ! function_exists( 'get_media_credit' ) ) {
	/**
	 * Template tag to return the media credit as plain text for some media attachment.
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 */
	function get_media_credit( $post = null ) {
		return Media_Credit_Template_Tags::get_media_credit( $post );
	}
}

if ( ! function_exists( 'the_media_credit' ) ) {
	/**
	 * Template tag to print the media credit as plain text for some media attachment.
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 */
	function the_media_credit( $post = null ) {
		echo esc_html( get_media_credit( $post ) );
	}
}

if ( ! function_exists( 'get_media_credit_url' ) ) {
	/**
	 * Template tag to return the media credit URL as plain text for some media attachment.
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 */
	function get_media_credit_url( $post = null ) {
		return Media_Credit_Template_Tags::get_media_credit_url( $post );
	}
}

if ( ! function_exists( 'the_media_credit_url' ) ) {
	/**
	 * Template tag to print the media credit URL as plain text for some media attachment.
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 */
	function the_media_credit_url( $post = null ) {
		echo esc_url_raw( get_media_credit_url( $post ) );
	}
}

if ( ! function_exists( 'get_media_credit_html' ) ) {
	/**
	 * Template tag to return the media credit as HTML with a link to the author page if one exists for some media attachment.
	 *
	 * @param int|object $post                   Optional post ID or object of attachment. Default is global $post object.
	 * @param boolean    $include_default_credit Optional flag to decide if default credits (owner) should be returned as well. Default is true.
	 */
	function get_media_credit_html( $post = null, $include_default_credit = true ) {
		return Media_Credit_Template_Tags::get_media_credit_html( $post, $include_default_credit );
	}
}

if ( ! function_exists( 'the_media_credit_html' ) ) {
	/**
	 * Template tag to print the media credit as HTML with a link to the author page if one exists for some media attachment.
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 */
	function the_media_credit_html( $post = null ) {
		echo get_media_credit_html( $post ); // XSS OK.
	}
}

if ( ! function_exists( 'get_media_credit_html_by_user_id' ) ) {
	/**
	 * Template tag to return the media credit as HTML with a link to the author page if one exists for a WordPress user.
	 *
	 * @param int $id User ID of a WordPress user.
	 */
	function get_media_credit_html_by_user_id( $id ) {
		return Media_Credit_Template_Tags::get_media_credit_html_by_user_id( $id );
	}
}

if ( ! function_exists( 'the_media_credit_html_by_user_id' ) ) {
	/**
	 * Template tag to print the media credit as HTML with a link to the author page if one exists for a WordPress user.
	 *
	 * @param int $id User ID of a WordPress user.
	 */
	function the_media_credit_html_by_user_id( $id ) {
		echo get_media_credit_html_by_user_id( $id ); // XSS OK.
	}
}

if ( ! function_exists( 'get_wpuser_media_credit' ) ) {
	/**
	 * Retrieve the default media credit for a given post/attachment (i.e. the post author).
	 *
	 * @deprecated since 3.0.0
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 * @return string The post author display name.
	 */
	function get_wpuser_media_credit( $post = null ) {

		_deprecated_function( __FUNCTION__, '3.0.0' );

		return Media_Credit_Template_Tags::get_wpuser_media_credit( $post );
	}
}

if ( ! function_exists( 'get_freeform_media_credit' ) ) {
	/**
	 * Retrieve the freeform emdia credit for a given post/attachment.
	 *
	 * @deprecated since 3.0.0
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 * @return string The freeform credit (or the empty string).
	 */
	function get_freeform_media_credit( $post = null ) {

		_deprecated_function( __FUNCTION__, '3.0.0' );

		return Media_Credit_Template_Tags::get_freeform_media_credit( $post );
	}
}

if ( ! function_exists( 'display_author_media' ) ) {
	/**
	 * Template tag to display the recently added media attachments for given author.
	 *
	 * @param int     $author_id           The user ID of the author.
	 * @param boolean $sidebar             Display as sidebar or inline. Optional. Default true.
	 * @param int     $limit               Optional. Default 10.
	 * @param boolean $link_without_parent Optional. Default false.
	 * @param string  $header              HTML-formatted heading. Optional. Default <h3>Recent Media</h3> (translated).
	 * @param boolean $exclude_unattached  Optional. Default true.
	 */
	function display_author_media( $author_id, $sidebar = true, $limit = 10, $link_without_parent = false, $header = null, $exclude_unattached = true ) {
		Media_Credit_Template_tags::display_author_media( $author_id, $sidebar, $limit, $link_without_parent, $header, $exclude_unattached );
	}
}

if ( ! function_exists( 'author_media_and_posts' ) ) {
	/**
	 * Template tag to return the recently added media attachments and posts for a given author.
	 *
	 * @param int     $author_id          The user ID of the author.
	 * @param boolean $include_posts      Optional. Default true.
	 * @param int     $limit              Optional. Default 0.
	 * @param boolean $exclude_unattached Optional. Default true.
	 */
	function author_media_and_posts( $author_id, $include_posts = true, $limit = 0, $exclude_unattached = true ) {
		return Media_Credit_Template_Tags::author_media_and_posts( $author_id, $include_posts, $limit, $exclude_unattached );
	}
}

if ( ! function_exists( 'author_media' ) ) {
	/**
	 * Template tag to return the recently added media attachments for a given author.
	 *
	 * @param int     $author_id          The user ID of the author.
	 * @param int     $limit              Optional. Default 0.
	 * @param boolean $exclude_unattached Optional. Default true.
	 */
	function author_media( $author_id, $limit = 0, $exclude_unattached = true ) {
		return author_media_and_posts( $author_id, false, $limit, $exclude_unattached );
	}
}
