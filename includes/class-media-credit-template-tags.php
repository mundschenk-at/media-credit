<?php

/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2015 Peter Putzer.
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

/**
 * A container of static functions implementing the internals of the
 * plugin's template tags.
 *
 * @since      3.0.0
 * @package    Media_Credit
 * @subpackage Media_Credit/includes
 * @author     Peter Putzer <github@mundschenk.at>
 */
class Media_Credit_Template_Tags implements Media_Credit_Base {

	/**
	 * Template tag to return the media credit as plain text for some media attachment.
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 * @return string The media credit.
	 */
	public static function get_media_credit( $post = null ) {

		$post = get_post( $post );
		$credit_meta = self::get_freeform_media_credit( $post );
		$credit_wp_author = self::get_wpuser_media_credit( $post );

		return ( '' !== $credit_meta ) ? $credit_meta : $credit_wp_author;
	}

	/**
	 * Template tag to return the media credit URL as plain text for some media attachment.
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 */
	public static function get_media_credit_url( $post = null ) {

		$post = get_post( $post );

		return get_post_meta($post->ID, self::URL_POSTMETA_KEY, true);
	}

	/**
	 * Template tag to return the media credit as HTML with a link to the author page if one exists for some media attachment.
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 * @param boolean $include_default_credit Optional flag to decide if default credits (owner) should be returned as well. Default is true.
	 */
	public static function get_media_credit_html( $post = null, $include_default_credit = true ) {

		$post = get_post( $post );
		if ( empty( $post ) ) {
			return ''; // abort
		}

		$credit_meta = self::get_freeform_media_credit( $post );
		$credit_url = self::get_media_credit_url( $post );
		$credit = '';

		if ( '' !== $credit_meta ) {
			if ( ! empty( $credit_url ) ) {
				$credit = '<a href="' . esc_url( $credit_url ) . '">' . $credit_meta . '</a>';
			} else {
				$credit = $credit_meta;
			}
		} elseif ( $include_default_credit ) {
			$credit_wp_author = self::get_wpuser_media_credit( $post );

			$options = get_option( self::OPTION );
			$url = ! empty( $credit_url ) ? $credit_url : self::get_author_posts_url( $post->post_author );

			$credit = '<a href="' . esc_url( $url ) . '">' . $credit_wp_author . '</a>' . $options['separator'] . $options['organization'];
		}

		return $credit;
	}

	/**
	 * Template tag to return the media credit as HTML with a link to the author page if one exists for a WordPress user.
	 *
	 * @param int $id User ID of a WordPress user.
	 */
	public static function get_media_credit_html_by_user_ID( $id ) {

		$credit_wp_author = get_the_author_meta( 'display_name', $id );
		$options = get_option( self::OPTION );

		return '<a href="' . get_author_posts_url( $id ) . '">' . $credit_wp_author . '</a>' . $options['separator'] .
			 $options['organization'];
	}

	/**
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 */
	public static function get_wpuser_media_credit( $post = null ) {

		$post = get_post( $post );

		return get_the_author_meta( 'display_name', $post->post_author );
	}

	/**
	 *
	 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
	 */
	public static function get_freeform_media_credit( $post = null ) {

		$post = get_post( $post );

		return get_post_meta( $post->ID, self::POSTMETA_KEY, true );
	}

	/**
	 *
	 * @param unknown $id
	 * @param string $include_posts
	 * @param number $limit
	 * @param string $exclude_unattached
	 */
	public static function author_media_and_posts( $id, $include_posts = true, $limit = 0, $exclude_unattached = true ) {

		global $wpdb;
		$posts_query = $attached = $date_query = $limit_query = '';

		if ( $include_posts ) {
			$posts_query = "OR (post_type = 'post' AND post_parent = '0' AND post_status = 'publish')";
		}
		$posts_query .= ")";

		if ( $exclude_unattached ) {
			$attached = " AND post_parent != '0'";
		}
		$attached .= ") ";

		$options = get_option( self::OPTION );

		$start_date = $options['install_date'];
		if ($start_date) {
			$date_query = " AND post_date >= '$start_date'";
		}

		if ($limit > 0) {
			$limit_query = " LIMIT $limit";
		}

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts}
														WHERE post_author = %d {$date_query}
													    AND ( (post_type = 'attachment' {$attached} {$posts_query}
													    AND ID NOT IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s )
														GROUP BY ID ORDER BY post_date DESC {$limit_query}, $id, self::POSTMETA_KEY ) );

		return $results;
	}
}
