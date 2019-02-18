<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2019 Peter Putzer.
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

namespace Media_Credit\Components;

use Media_Credit\Template_Tags;

/**
 * Combines the WordPress REST API with Media Credit.
 *
 * @since 3.3.0
 */
class REST_API implements \Media_Credit\Component, \Media_Credit\Base {

	/**
	 * The namespace for the custom API endpoints.
	 *
	 * @var string
	 */
	const NAMESPACE_V1 = 'media-credit/v1';

	/**
	 * The JSON schema describing the fields added to the `media` endpoint.
	 *
	 * @var string[]
	 */
	const SCHEMA_MEDIA_CREDIT_FIELDS = [
		'description' => 'Copyright information for the media item',
		'type'        => 'object',
		'context'     => [ 'view', 'edit' ],
		'properties'  => [
			'rendered'  => [
				'description' => 'The copyright string in HTML format, transformed for display',
				'type'        => 'string',
				'readonly'    => 'true',
				'context'     => [ 'view' ],
			],
			'plaintext' => [
				'description' => 'The copyright string as plain text, transformed for display',
				'type'        => 'string',
				'readonly'    => 'true',
				'context'     => [ 'view' ],
			],
			'user_id'   => [
				'description' => 'The user ID of the media items author (if set, it overrides any freeform credit)',
				'type'        => 'integer',
				'minimum'     => 0,
				'context'     => [ 'view', 'edit' ],
			],
			'freeform'  => [
				'description' => 'The copyright line itself (if not overridden by the `user_id`)',
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
			],
			'url'       => [
				'description' => 'A URL to link from the copyright information (overriding the default linkt to author pages)',
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => [ 'view', 'edit' ],
			],
			'flags'     => [
				'description' => 'A list of flags',
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'properties'  => [
					'nofollow' => [
						'description' => 'Indicates that `rel=nofollow` should be added to the copyright link',
						'type'        => 'boolean',
						'context'     => [ 'view', 'edit' ],
					],
				],
			],
		],
	];


	/**
	 * Start up enabled integrations.
	 */
	public function run() {
		\add_action( 'rest_api_init', [ $this, 'register_media_credit_fields' ] );
		\add_action( 'rest_api_init', [ $this, 'register_custom_routes' ] );
	}

	/**
	 * Registers the meta fields for use (not only) with the REST API.
	 */

	/**
	 * Registers the meta fields for use (not only) with the REST API.
	 */
	public function register_media_credit_fields() {
		\register_rest_field(
			'attachment',
			'media_credit',
			[
				'get_callback'    => [ $this, 'prepare_media_credit_fields' ],
				'update_callback' => [ $this, 'update_media_credit_fields' ],
				'auth_callback'   => [ $this, 'authorized_to_edit_media_credit' ],
				'schema'          => self::SCHEMA_MEDIA_CREDIT_FIELDS,
			]
		);
	}

	/**
	 * Registers our custom routes with the REST API.
	 */
	public function register_custom_routes() {
		\register_rest_route(
			self::NAMESPACE_V1,
			'/replace_in_content',
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'rest_filter_content' ],
				'args'     => [
					'attachment_id' => [
						'required'          => true,
						'type'              => 'integer',
						'minimum'           => 1,
					],
					'content'       => [
						'required'          => true,
					],
					'author_id'     => [
						'default'           => 0,
						'description'       => 'The user ID of the media items author (if set, it overrides any freeform credit)',
						'type'              => 'integer',
						'minimum'           => 0,
					],
					'freeform'      => [
						'default'           => '',
						'description'       => 'The copyright line itself (if not overridden by the `user_id`)',
						'type'              => 'string',
						'sanitize_callback' => [ $this, 'sanitize_text_field' ],
					],
					'url'           => [
						'default'           => '',
						'description'       => 'A URL to link from the copyright information (overriding the default linkt to author pages)',
						'type'              => 'string',
						'format'            => 'uri',
					],
					'nofollow'      => [
						'default'           => false,
						'description'       => 'Indicates that `rel=nofollow` should be added to the copyright link',
						'type'              => 'boolean',
					],
				],
			]
		);
	}

	/**
	 * Sanitizes a text field via the REST API.
	 *
	 * @param  mixed            $param   The parameter value.
	 * @param  \WP_REST_Request $request The REST request.
	 * @param  string           $key     The parameter name.
	 *
	 * @return string
	 */
	public function sanitize_text_field( $param, \WP_REST_Request $request, $key ) {
		return \sanitize_text_field( $param );
	}


	/**
	 * Prepares the field for output during a GET request.
	 *
	 * @param  array            $post        The post JSON object.
	 * @param  string           $field_name  The field name.
	 * @param  \WP_REST_Request $request     The REST request.
	 *
	 * @return array
	 */
	public function prepare_media_credit_fields( $post, $field_name, \WP_REST_Request $request ) {
		return $this->get_media_credit_json( $post['id'] );
	}

	/**
	 * Prepares the JSON data for the given attachment's media credit.
	 *
	 * @param  int $attachment_id The post ID of the media item.
	 *
	 * @return array
	 */
	protected function get_media_credit_json( $attachment_id ) {
		// Prepare helper objects.
		$post_object = \get_post( $attachment_id );
		$flags       = Template_Tags::get_media_credit_data( $post_object );

		// Return media credit data.
		return [
			'rendered'  => Template_Tags::get_media_credit_html( $post_object, true ),
			'plaintext' => Template_Tags::get_media_credit( $post_object, true ),
			'user_id'   => Template_Tags::get_wpuser_media_credit( $post_object ),
			'freeform'  => Template_Tags::get_freeform_media_credit( $post_object ),
			'url'       => Template_Tags::get_media_credit_url( $post_object ),
			'flags'     => [
				'nofollow' => ! empty( $flags['nofollow'] ),
			],
		];
	}

	/**
	 * Updates the media credit post meta data during a POST request.
	 *
	 * @param  array            $value       The new values for the media credit.
	 * @param  \WP_Post         $post        The post object.
	 * @param  string           $field_name  The field name.
	 * @param  \WP_REST_Request $request     The REST request.
	 *
	 * @return bool
	 */
	public function update_media_credit_fields( $value, \WP_Post $post, $field_name, \WP_REST_Request $request ) {
		$success  = true;
		$previous = $this->get_media_credit_json( $post->ID );

		$freeform   = isset( $value['freeform'] ) ? \wp_kses( $value['freeform'], [ 'a' => [ 'href', 'rel' ] ] ) : $previous['freeform'];
		$url        = isset( $value['url'] ) ? \esc_url_raw( $value['url'] ) : $previous['url'];
		$wp_user_id = isset( $value['user_id'] ) ? \absint( $value['user_id'] ) : $previous['user_id'];
		$nofollow   = isset( $value['flags']['nofollow'] ) ? \filter_var( $value['flags']['nofollow'], FILTER_VALIDATE_BOOLEAN ) : $previous['flags']['nofollow'];

		if ( isset( $value['url'] ) ) {
			// We need to update the credit URL.
			$success = $success && \update_post_meta( $post->ID, self::URL_POSTMETA_KEY, $url ); // insert '_media_credit_url' metadata field.
		}

		if ( isset( $value['flags']['nofollow'] ) ) {
			$flags             = Template_Tags::get_media_credit_data( $post );
			$flags['nofollow'] = $nofollow;
			$success           = $success && \update_post_meta( $post->ID, self::DATA_POSTMETA_KEY, $flags ); // insert '_media_credit_data' metadata field.
		}

		if ( isset( $value['freeform'] ) || isset( $value['user_id'] ) ) {
			if ( ! empty( $wp_user_id ) && \get_the_author_meta( 'display_name', (int) $wp_user_id ) === $freeform ) {
				// A valid WP user was selected, and the display name matches the free-form
				// the final conditional is necessary for the case when a valid user is selected, filling in the hidden
				// field, then free-form text is entered after that. if so, the free-form text is what should be used.
				$success = $success && \wp_update_post(
					[
						'ID'          => $post->ID,
						'post_author' => $wp_user_id,
					]
				);

				$success = $success && \delete_post_meta( $post->ID, self::POSTMETA_KEY ); // delete any residual metadata from a free-form field (as inserted below).
				$this->update_media_credit_in_post( $post->ID, '', $url );
			} else {
				// Free-form text was entered, insert postmeta with credit.
				// if free-form text is blank, insert a single space in postmeta.
				$freeform = $freeform ?: self::EMPTY_META_STRING;
				$success  = $success && \update_post_meta( $post->ID, self::POSTMETA_KEY, $freeform ); // insert '_media_credit' metadata field for image with free-form text.
				$this->update_media_credit_in_post( $post->ID, $freeform, $url );
			}
		}

		return $success;
	}

	/**
	 * Checks if the current user is authorized to edit the `media_credit` fields.
	 *
	 * @return bool
	 */
	public function authorized_to_edit_media_credit() {
		return \current_user_can( 'edit_posts' );
	}

	/**
	 * Filters the post content after editing media files (via REST).
	 *
	 * @param  \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_filter_content( \WP_REST_Request $request ) {

		// Retrieve the parameters.
		$params = $request->get_params();

		// Return the filtered post content in a response object.
		$response = new \WP_REST_Response(
			$this->filter_post_content(
				$params['content'],
				$params['attachment_id'],
				$params['author_id'],
				$params['freeform'],
				$params['url']
			)
		);

		return $response;
	}

	/**
	 * If the given media is attached to a post, edit the media-credit info in the attached (parent) post.
	 *
	 * @since 3.2.0 Unused parameter $wp_user removed.
	 *
	 * @param int|\WP_Post $post     Object of attachment containing all fields from get_post().
	 * @param string       $freeform Credit for attachment with freeform string. Empty if attachment should be credited to a user of this blog, as indicated by $wp_user above.
	 * @param string       $url      Credit URL for linking. Empty means default link for user of this blog, no link for freeform credit.
	 */
	private function update_media_credit_in_post( $post, $freeform = '', $url = '' ) {
		if ( is_int( $post ) ) {
			$post = get_post( $post, ARRAY_A );
		}

		if ( ! empty( $post['post_parent'] ) ) {
			$parent                 = get_post( $post['post_parent'], ARRAY_A );
			$parent['post_content'] = $this->filter_post_content( $parent['post_content'], $post['ID'], $post['post_author'], $freeform, $url );

			wp_update_post( $parent );
		}
	}

	/**
	 * Filter post content for changed media credits.
	 *
	 * @param string $content   The current post content.
	 * @param int    $image_id  The attachment ID.
	 * @param int    $author_id The author ID.
	 * @param string $freeform  The freeform credit.
	 * @param string $url       The credit URL. Optional. Default ''.
	 *
	 * @return string           The filtered post content.
	 */
	private function filter_post_content( $content, $image_id, $author_id, $freeform, $url = '' ) {
		preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );

		if ( ! empty( $matches ) ) {
			foreach ( $matches as $shortcode ) {
				if ( 'media-credit' === $shortcode[2] ) {
					$img              = $shortcode[5];
					$image_attributes = wp_get_attachment_image_src( $image_id );
					$image_filename   = $this->get_image_filename_from_full_url( $image_attributes[0] );

					// Ensure that $attr is an array.
					$attr = shortcode_parse_atts( $shortcode[3] );
					$attr = '' === $attr ? [] : $attr;

					if ( preg_match( '/src=".*' . $image_filename . '/', $img ) && preg_match( '/wp-image-' . $image_id . '/', $img ) ) {
						if ( $author_id > 0 ) {
							$attr['id'] = $author_id;
							unset( $attr['name'] );
						} else {
							$attr['name'] = $freeform;
							unset( $attr['id'] );
						}

						if ( ! empty( $url ) ) {
							$attr['link'] = $url;
						} else {
							unset( $attr['link'] );
						}

						$new_shortcode = '[media-credit';
						if ( isset( $attr['id'] ) ) {
							$new_shortcode .= ' id=' . $attr['id'];
							unset( $attr['id'] );
						} elseif ( isset( $attr['name'] ) ) {
							$new_shortcode .= ' name="' . $attr['name'] . '"';
							unset( $attr['name'] );
						}
						foreach ( $attr as $name => $value ) {
							$new_shortcode .= ' ' . $name . '="' . $value . '"';
						}
						$new_shortcode .= ']' . $img . '[/media-credit]';

						$content = str_replace( $shortcode[0], $new_shortcode, $content );
					}
				} elseif ( ! empty( $shortcode[5] ) && has_shortcode( $shortcode[5], 'media-credit' ) ) {
					$content = str_replace( $shortcode[5], $this->filter_post_content( $shortcode[5], $image_id, $author_id, $freeform, $url ), $content );
				}
			}
		}

		return $content;
	}

	/**
	 * Returns the filename of an image in the wp_content directory (normally, could be any dir really) given the full URL to the image, ignoring WP sizes.
	 * E.g.:
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-150x150.jpg, returns ParksTrip2010_100706_1487 (ignores size at end of string)
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-thumb.jpg, return ParksTrip2010_100706_1487-thumb
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-1.jpg, return ParksTrip2010_100706_1487-1
	 *
	 * @param  string $image Full URL to an image.
	 * @return string        The filename of the image excluding any size or extension, as given in the example above.
	 */
	private function get_image_filename_from_full_url( $image ) {
		$last_slash_pos = strrpos( $image, '/' );
		$image_filename = substr( $image, $last_slash_pos + 1, strrpos( $image, '.' ) - $last_slash_pos - 1 );
		$image_filename = preg_replace( '/(.*)-\d+x\d+/', '$1', $image_filename ); // drop "-{$width}x{$height}".

		return $image_filename;
	}
}
