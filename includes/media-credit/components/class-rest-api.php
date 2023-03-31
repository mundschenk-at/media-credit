<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2023 Peter Putzer.
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

use WP_REST_Server;

use Media_Credit\Core;
use Media_Credit\Tools\Shortcodes_Filter;

/**
 * Combines the WordPress REST API with Media Credit.
 *
 * @since 4.0.0
 * @since 4.2.0 The public method sanitize_text_field has been deprecated.
 *
 * @phpstan-import-type MediaCreditJSONRaw from Core
 * @phpstan-type MediaCreditRESTRequest array{content: string, attachment_id: int, author_id: int, freeform: string, url: string, nofollow: bool}
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
	 * @var array<string,mixed>
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
	 *
	 * @return void
	 */
	public function run() {
		\add_action( 'rest_api_init', [ $this, 'register_media_credit_fields' ] );
		\add_action( 'rest_api_init', [ $this, 'register_custom_routes' ] );
	}

	/**
	 * Registers the meta fields for use (not only) with the REST API.
	 *
	 * @return void
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
	 *
	 * @return void
	 */
	public function register_custom_routes() {
		\register_rest_route(
			self::NAMESPACE_V1,
			'/replace_in_content',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_filter_content' ],
				'permission_callback' => '__return_true',
				'args'                => [
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
						'sanitize_callback' => 'sanitize_text_field',
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
	 * @deprecated 4.2.0 There is no need to wrap the sanitize_text_field function.
	 *
	 * @since  4.2.0 Unused parameters $request and $key removed.
	 *
	 * @param  mixed $param The parameter value.
	 *
	 * @return string
	 */
	public function sanitize_text_field( $param ) {
		\_deprecated_function( __FUNCTION__, '4.2.0', 'sanitize_text_field' );

		return \sanitize_text_field( \strval( $param ) );
	}


	/**
	 * Prepares the field for output during a GET request.
	 *
	 * @since  4.2.0 Unused Parameters $field_name and $request removed.
	 *
	 * @param  mixed[] $post The post JSON object.
	 *
	 * @return mixed[]|void
	 *
	 * @phpstan-param array{id?:int} $post
	 */
	public function prepare_media_credit_fields( $post ) {
		$attachment = ! empty( $post['id'] ) ? \get_post( $post['id'] ) : null;
		if ( ! empty( $attachment ) ) {
			return $this->core->get_media_credit_json( $attachment );
		}
	}

	/**
	 * Updates the media credit post meta data during a POST request.
	 *
	 * @since  4.2.0 Unused Parameters $field_name and $request removed.
	 *
	 * @param  mixed[]  $value The new values for the media credit.
	 * @param  \WP_Post $post  The post object.
	 *
	 * @return bool
	 *
	 * @phpstan-param array{raw?: MediaCreditJSONRaw} $value
	 */
	public function update_media_credit_fields( $value, \WP_Post $post ) {
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
	 *
	 * @phpstan-param \WP_REST_Request<MediaCreditRESTRequest> $request
	 */
	public function rest_filter_content( \WP_REST_Request $request ) {

		// Retrieve the parameters.
		$params = $request->get_params();

		// Return the filtered post content in a response object.
		return new \WP_REST_Response(
			$this->shortcodes_filter->update_changed_media_credits(
				$params['content'],
				$params['attachment_id'],
				$params['author_id'],
				$params['freeform'],
				$params['url'],
				$params['nofollow']
			)
		);
	}
}
