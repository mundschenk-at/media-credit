<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2023 Peter Putzer.
 * Copyright 2010-2011 Scott Bressler.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  ***
 *
 * @package mundschenk-at/media-credit
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Warning message for invalid use of deprecated API functions.
 *
 * @internal
 *
 * @var string
 */
const MEDIA_CREDIT_ATTACHMENT_OBJECT_OR_ID_REQUIRED = 'You need to specify an attachment object or ID.';

if ( ! function_exists( 'get_media_credit' ) ) {
	/**
	 * Template tag to return the media credit as plain text for some media attachment.
	 *
	 * @deprecated 4.0.0 Deprecated in favor of `Media_Credit::get_plaintext`.
	 *
	 * @since 4.2.0 The parameter $post needs needs to be specified.
	 *
	 * @param  int|\WP_Post $post  An attachment ID or the corresponding \WP_Post object.
	 *
	 * @return string
	 */
	function get_media_credit( $post = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		_deprecated_function( __FUNCTION__, '4.0.0', 'Media_Credit::get_plaintext' );

		if ( empty( $post ) ) {
			_doing_it_wrong( __FUNCTION__, \MEDIA_CREDIT_ATTACHMENT_OBJECT_OR_ID_REQUIRED, '4.2.0' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return '';
		}

		return Media_Credit::get_plaintext( $post );
	}
}

if ( ! function_exists( 'the_media_credit' ) ) {
	/**
	 * Template tag to print the media credit as plain text for some media attachment.
	 *
	 * @deprecated 4.0.0 Deprecated in favor of `Media_Credit::plaintext`.
	 *
	 * @since 4.2.0 The parameter $post needs needs to be specified.
	 *
	 * @param  int|\WP_Post $post  An attachment ID or the corresponding \WP_Post object.
	 *
	 * @return void
	 */
	function the_media_credit( $post = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		_deprecated_function( __FUNCTION__, '4.0.0', 'Media_Credit::plaintext' );

		if ( empty( $post ) ) {
			_doing_it_wrong( __FUNCTION__, \MEDIA_CREDIT_ATTACHMENT_OBJECT_OR_ID_REQUIRED, '4.2.0' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		Media_Credit::plaintext( $post );
	}
}

if ( ! function_exists( 'get_media_credit_url' ) ) {
	/**
	 * Template tag to return the media credit URL as plain text for some media attachment.
	 *
	 * @deprecated 4.0.0 Deprecated in favor of `Media_Credit::get_url`.
	 *
	 * @since 4.2.0 The parameter $post needs needs to be specified.
	 *
	 * @param  int|\WP_Post $post  An attachment ID or the corresponding \WP_Post object.
	 *
	 * @return string
	 */
	function get_media_credit_url( $post = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		_deprecated_function( __FUNCTION__, '4.0.0', 'Media_Credit::get_url' );

		if ( empty( $post ) ) {
			_doing_it_wrong( __FUNCTION__, \MEDIA_CREDIT_ATTACHMENT_OBJECT_OR_ID_REQUIRED, '4.2.0' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return '';
		}

		return Media_Credit::get_url( $post );
	}
}

if ( ! function_exists( 'the_media_credit_url' ) ) {
	/**
	 * Template tag to print the media credit URL as plain text for some media attachment.
	 *
	 * @deprecated 4.0.0
	 *
	 * @since  4.2.0 The parameter $post needs needs to be specified.
	 *
	 * @param  int|\WP_Post $post  An attachment ID or the corresponding \WP_Post object.
	 *
	 * @return void
	 */
	function the_media_credit_url( $post = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		_deprecated_function( __FUNCTION__, '4.0.0' );

		if ( empty( $post ) ) {
			_doing_it_wrong( __FUNCTION__, \MEDIA_CREDIT_ATTACHMENT_OBJECT_OR_ID_REQUIRED, '4.2.0' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		echo \sanitize_url( \get_media_credit_url( $post ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitize_url is currently not recognized properly.
	}
}

if ( ! function_exists( 'get_media_credit_html' ) ) {
	/**
	 * Template tag to return the media credit as HTML with a link to the author page if one exists for some media attachment.
	 *
	 * @deprecated 4.0.0 Deprecated in favor of `Media_Credit::get_html`.
	 *
	 * @since  4.2.0 The parameter $post needs needs to be specified.
	 *
	 * @param  int|\WP_Post $post An attachment ID or the corresponding \WP_Post object.
	 *
	 * @return string
	 */
	function get_media_credit_html( $post = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		_deprecated_function( __FUNCTION__, '4.0.0', 'Media_Credit::get_html' );

		if ( empty( $post ) ) {
			_doing_it_wrong( __FUNCTION__, \MEDIA_CREDIT_ATTACHMENT_OBJECT_OR_ID_REQUIRED, '4.2.0' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return '';
		}

		return Media_Credit::get_html( $post );
	}
}

if ( ! function_exists( 'the_media_credit_html' ) ) {
	/**
	 * Template tag to print the media credit as HTML with a link to the author page if one exists for some media attachment.
	 *
	 * @deprecated 4.0.0 Deprecated in favor of `Media_Credit::html`.
	 *
	 * @since  4.2.0 The parameter $post needs needs to be specified.
	 *
	 * @param  int|\WP_Post $post  An attachment ID or the corresponding \WP_Post object.
	 *
	 * @return void
	 */
	function the_media_credit_html( $post = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		_deprecated_function( __FUNCTION__, '4.0.0', 'Media_Credit::html' );

		if ( empty( $post ) ) {
			_doing_it_wrong( __FUNCTION__, \MEDIA_CREDIT_ATTACHMENT_OBJECT_OR_ID_REQUIRED, '4.2.0' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		Media_Credit::html( $post );
	}
}

if ( ! function_exists( 'get_media_credit_html_by_user_id' ) ) {
	/**
	 * Template tag to return the media credit as HTML with a link to the author page if one exists for a WordPress user.
	 *
	 * @deprecated 4.0.0 Deprecated in favor of `Media_Credit::get_html_by_user_id`.
	 *
	 * @param  int $id User ID of a WordPress user.
	 *
	 * @return string
	 */
	function get_media_credit_html_by_user_id( $id ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		_deprecated_function( __FUNCTION__, '4.0.0', 'Media_Credit::get_html_by_user_id' );

		return Media_Credit::get_html_by_user_id( $id );
	}
}

if ( ! function_exists( 'the_media_credit_html_by_user_id' ) ) {
	/**
	 * Template tag to print the media credit as HTML with a link to the author page if one exists for a WordPress user.
	 *
	 * @deprecated 4.0.0 Deprecated in favor of `Media_Credit::html_by_user_id`.
	 *
	 * @param  int $id User ID of a WordPress user.
	 *
	 * @return void
	 */
	function the_media_credit_html_by_user_id( $id ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		_deprecated_function( __FUNCTION__, '4.0.0', 'Media_Credit::html_by_user_id' );

		Media_Credit::html_by_user_id( $id );
	}
}


if ( ! function_exists( 'display_author_media' ) ) {
	/**
	 * Template tag to display the recently added media attachments for given author.
	 *
	 * @deprecated 4.0.0 Deprecated in favor of `Media_Credit::display_author_media`.
	 *
	 * @param  int    $author_id           The user ID of the author.
	 * @param  bool   $sidebar             Display as sidebar or inline. Optional. Default true.
	 * @param  int    $limit               Optional. Default 10.
	 * @param  bool   $link_without_parent Optional. Default false.
	 * @param  string $header              HTML-formatted heading. Optional. Default <h3>Recent Media</h3> (translated).
	 * @param  bool   $exclude_unattached  Optional. Default true.
	 *
	 * @return void
	 */
	function display_author_media( $author_id, $sidebar = true, $limit = 10, $link_without_parent = false, $header = null, $exclude_unattached = true ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		_deprecated_function( __FUNCTION__, '4.0.0', 'Media_Credit::display_author_media' );

		$args = [
			// Non-query variables.
			'sidebar'             => $sidebar,
			'link_without_parent' => $link_without_parent,
			'header'              => $header,

			// Query variables.
			'author_id'           => $author_id,
			'number'              => $limit,
			'exclude_unattached'  => $exclude_unattached,
		];

		if ( $limit < 1 ) {
			unset( $args['number'] );
		}

		Media_Credit::display_author_media( $args );
	}
}

if ( ! function_exists( 'author_media_and_posts' ) ) {
	/**
	 * Template tag to return the recently added media attachments and posts for a given author.
	 *
	 * @deprecated 4.0.0 Deprecated in favor of `Media_Credit::author_media_and_posts`.
	 *
	 * @param  int  $author_id          A user ID.
	 * @param  bool $include_posts      Optional. A flag indicating whether posts (as well as attachments) should be included in the results. Default false.
	 * @param  int  $limit              Optional. The maximum number of objects to retrieve. Default 0 (unlimited).
	 * @param  bool $exclude_unattached Optional. A flag indicating whether media items not currently attached to a parent post should be excluded from the results. Default true.
	 *
	 * @return object[]                 An integer-keyed array of row objects.
	 */
	function author_media_and_posts( $author_id, $include_posts = true, $limit = 0, $exclude_unattached = true ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		_deprecated_function( __FUNCTION__, '4.0.0', 'Media_Credit::author_media_and_posts' );

		$args = [
			'author_id'          => $author_id,
			'number'             => $limit,
			'include_posts'      => $include_posts,
			'exclude_unattached' => $exclude_unattached,
		];

		if ( $limit < 1 ) {
			unset( $args['number'] );
		}

		return Media_Credit::author_media_and_posts( $args );
	}
}

if ( ! function_exists( 'author_media' ) ) {
	/**
	 * Returns the recently added media attachments for a given author.
	 *
	 * @deprecated 4.0.0 Deprecated in favor of `Media_Credit::author_media_and_posts`.
	 *
	 * @param  int  $author_id          A user ID.
	 * @param  int  $limit              Optional. The upper limit to the number of returned posts. Default 0 (no limit).
	 * @param  bool $exclude_unattached Optional. Flag indicating if media not attached to a post should be included. Default true.
	 *
	 * @return object[]                 An integer-keyed array of row objects.
	 */
	function author_media( $author_id, $limit = 0, $exclude_unattached = true ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- legacy API.
		_deprecated_function( __FUNCTION__, '4.0.0', 'Media_Credit::author_media_and_posts' );

		$args = [
			'author_id'          => $author_id,
			'number'             => $limit,
			'include_posts'      => false,
			'exclude_unattached' => $exclude_unattached,
		];

		if ( $limit < 1 ) {
			unset( $args['number'] );
		}

		return Media_Credit::author_media_and_posts( $args );
	}
}
