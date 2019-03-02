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

use Media_Credit\Core;

use Media_Credit\Tools\Shortcodes_Filter;

/**
 * Combines the WordPress REST API with Media Credit.
 *
 * @since 4.0.0
 */
class REST_API implements \Media_Credit\Component {

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
			'fancy'     => [
				'description' => 'The copyright string as "fancy" plain text, including the organization where applicable',
				'type'        => 'string',
				'readonly'    => 'true',
				'context'     => [ 'view' ],
			],
			'raw'       => [
				'description' => 'The raw data used for storing the copyright information',
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'properties'  => [
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
						'description' => 'A URL to link from the copyright information (overriding the default link to author pages)',
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
			],
		],
	];

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The shortcodes filter.
	 *
	 * @var Shortcodes_Filter
	 */
	private $shortcodes_filter;

	/**
	 * Creates a new instance of the REST API handler.
	 *
	 * @param Core              $core The core plugin API.
	 * @param Shortcodes_Filter $shortcodes_filter The shortcodes filter.
	 */
	public function __construct( Core $core, Shortcodes_Filter $shortcodes_filter ) {
		$this->core              = $core;
		$this->shortcodes_filter = $shortcodes_filter;
	}

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
	public function register_media_credit_fields() {
		\register_rest_field(
			'attachment',
			'media_credit',
			[
				'get_callback'    => [ $this, 'prepare_media_credit_fields' ],
				'update_callback' => [ $this, 'update_media_credit_fields' ],
				'auth_callback'   => [ $this->core, 'authorized_to_edit_media_credit' ],
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
	public function sanitize_text_field( $param, /* @scrutinizer ignore-unused */ \WP_REST_Request $request, /* @scrutinizer ignore-unused */ $key ) {
		return \sanitize_text_field( $param );
	}


	/**
	 * Prepares the field for output during a GET request.
	 *
	 * @param  array            $post        The post JSON object.
	 * @param  string           $field_name  The field name.
	 * @param  \WP_REST_Request $request     The REST request.
	 *
	 * @return array|void
	 */
	public function prepare_media_credit_fields( $post, /* @scrutinizer ignore-unused */ $field_name, /* @scrutinizer ignore-unused */ \WP_REST_Request $request ) {
		$attachment = \get_post( $post['id'] );
		if ( $attachment instanceof \WP_Post ) {
			return $this->core->get_media_credit_json( $attachment );
		}
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
	public function update_media_credit_fields( $value, \WP_Post $post, /* @scrutinizer ignore-unused */ $field_name, /* @scrutinizer ignore-unused */ \WP_REST_Request $request ) {
		if ( empty( $value['raw'] ) ) {
			return false;
		}

		// Save fields.
		$this->core->update_media_credit_json( $post, $value['raw'] );

		return true;
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
			$this->shortcodes_filter->update_changed_media_credits(
				$params['content'],
				$params['attachment_id'],
				$params['author_id'],
				$params['freeform'],
				$params['url'],
				$params['nofollow']
			)
		);

		return $response;
	}
}
