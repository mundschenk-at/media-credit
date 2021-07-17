<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2021 Peter Putzer.
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
 * @package mundschenk-at/media-credit/tests
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Media_Credit\Tests\Media_Credit\Tools;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Media_Credit\Tools\Shortcodes_Filter;

use Media_Credit\Tests\TestCase;

/**
 * Media_Credit\Tools\Shortcodes_Filter unit test.
 *
 * @since 4.2.0
 *
 * @coversDefaultClass \Media_Credit\Tools\Shortcodes_Filter
 * @usesDefaultClass \Media_Credit\Tools\Shortcodes_Filter
 */
class Shortcodes_Filter_Test extends TestCase {
	/**
	 * Content for testing the filters.
	 *
	 * @var string
	 */
	const CONTENT = <<<HTML
[caption id="attachment_13762" align="alignright" width="150"]

[media-credit name="Dodo" link="https://example.org" align="alignright" width="150"]<a href="https://example.org/wp-content/uploads/2019/02/19-02-02-18-25-37-0030.jpg"><img class="size-thumbnail wp-image-13762" src="https://example.org/wp-content/uploads/2019/02/19-02-02-18-25-37-0030-150x150.jpg" alt="Some picture" width="150" height="150" /></a>[/media-credit] This is a foobar![/caption]

Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.

[media-credit id="2" link="https://example.net/another/url" align="alignleft" width="150"]<a href="https://example.org/wp-content/uploads/2016/10/another-picture.png"><img class="size-thumbnail wp-image-13163 alignleft" src="https://example.org/wp-content/uploads/2016/10/another-image-150x150.png" alt="Another picture" width="150" height="150" /></a>[/media-credit]

Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
HTML;

	const MEDIA_CREDIT_REGEX =
		// phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
		'\\['                                // Opening bracket.
		. '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]].
		. '(media-credit)'                     // 2: Shortcode name.
		. '(?![\\w-])'                       // Not followed by word character or hyphen.
		. '('                                // 3: Unroll the loop: Inside the opening shortcode tag.
		.     '[^\\]\\/]*'                   // Not a closing bracket or forward slash.
		.     '(?:'
		.         '\\/(?!\\])'               // A forward slash not followed by a closing bracket.
		.         '[^\\]\\/]*'               // Not a closing bracket or forward slash.
		.     ')*?'
		. ')'
		. '(?:'
		.     '(\\/)'                        // 4: Self closing tag...
		.     '\\]'                          // ...and closing bracket.
		. '|'
		.     '\\]'                          // Closing bracket.
		.     '(?:'
		.         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags.
		.             '[^\\[]*+'             // Not an opening bracket.
		.             '(?:'
		.                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag.
		.                 '[^\\[]*+'         // Not an opening bracket.
		.             ')*+'
		.         ')'
		.         '\\[\\/\\2\\]'             // Closing shortcode tag.
		.     ')?'
		. ')'
		. '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]].
		// phpcs:enable

	/**
	 * The system-under-test.
	 *
	 * @var Shortcodes_Filter
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		// Create system-under-test.
		$this->sut = m::mock( Shortcodes_Filter::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Test ::update_changed_media_credits.
	 *
	 * @covers ::update_changed_media_credits
	 */
	public function test_update_changed_media_credits() {
		// Input data.
		$image_id  = 13762;
		$author_id = 5;
		$freeform  = '';
		$url       = 'https://example.net/my/url';
		$nofollow  = true;

		// Intermediary data.
		$image_basename = '19\-02\-02\-18\-25\-37\-0030';
		$attr           = [
			'name'  => 'Dodo',
			'link'  => 'https://example.org',
			'align' => 'alignright',
			'width' => '150',
		];
		$updated        = [
			'id'       => $author_id,
			'name'     => $freeform,
			'link'     => $url,
			'nofollow' => $nofollow,
		];

		// Expected result.
		$updated_content = 'CONTENT WITH UPDATED SHORTCODES';

		$this->sut->shouldReceive( 'get_quoted_image_basename' )->once()->with( $image_id, '/' )->andReturn( $image_basename );

		Functions\expect( 'get_shortcode_regex' )->once()->with( [ 'media-credit' ] )->andReturn( self::MEDIA_CREDIT_REGEX );

		$this->sut->shouldReceive( 'parse_shortcode_attributes' )->once()->with( m::type( 'string' ) )->andReturn( $attr );
		$this->sut->shouldReceive( 'update_shortcode' )
			->once()
			->with( self::CONTENT, m::pattern( '/\[media-credit.*\[\/media-credit\]/' ), m::type( 'string' ), $attr, $updated )
			->andReturn( $updated_content );

		$this->assertSame( $updated_content, $this->sut->update_changed_media_credits( self::CONTENT, $image_id, $author_id, $freeform, $url, $nofollow ) );
	}

	/**
	 * Test ::update_changed_media_credits.
	 *
	 * @covers ::update_changed_media_credits
	 */
	public function test_update_changed_media_credits_invalid_basename() {
		// Input data.
		$image_id  = 13762;
		$author_id = 5;
		$freeform  = '';
		$url       = 'https://example.net/my/url';
		$nofollow  = true;

		$this->sut->shouldReceive( 'get_quoted_image_basename' )->once()->with( $image_id, '/' )->andReturn( false );

		Functions\expect( 'get_shortcode_regex' )->never();

		$this->sut->shouldReceive( 'parse_shortcode_attributes' )->never();
		$this->sut->shouldReceive( 'update_shortcode' )->never();

		$this->assertSame( self::CONTENT, $this->sut->update_changed_media_credits( self::CONTENT, $image_id, $author_id, $freeform, $url, $nofollow ) );
	}

	/**
	 * Provides data for testing update_shortcode.
	 *
	 * @return array
	 */
	public function provide_update_shortcode_data() {
		return [
			[
				[
					'id'       => 4711,
					'name'     => 'Totally Invalid Double Credit',
					'link'     => 'https://foo.bar/some/url',
					'foo'      => 'bar',
					'nofollow' => false,
				],
				[
					'id'       => 5,
					'name'     => '',
					'link'     => 'https://example.net/my/url',
					'nofollow' => true,
				],
				'id=5 link="https://example.net/my/url" foo="bar" nofollow="1"',
			],
			[
				[
					'id'       => 4711,
					'name'     => 'Totally Invalid Double Credit',
					'link'     => 'https://foo.bar/some/url',
					'foo'      => 'bar',
					'nofollow' => false,
				],
				[
					'id'       => 0,
					'name'     => 'Foobar',
					'link'     => '',
					'nofollow' => true,
				],
				'name="Foobar" foo="bar"',
			],
			[
				[
					'id'       => 4711,
					'name'     => 'Totally Invalid Double Credit',
					'link'     => 'https://foo.bar/some/url',
					'foo'      => 'bar',
					'nofollow' => true,
				],
				[
					'id'       => 5,
					'name'     => '',
					'link'     => 'https://example.net/my/url',
					'nofollow' => false,
				],
				'id=5 link="https://example.net/my/url" foo="bar"',
			],
		];
	}

	/**
	 * Test ::update_shortcode.
	 *
	 * @covers ::update_shortcode
	 *
	 * @dataProvider provide_update_shortcode_data
	 *
	 * @param  array  $attr    The current shortcode attributes.
	 * @param  array  $updated The updated shortcode attributes.
	 * @param  string $result  The new shortcode attribute string as part of the
	 *                         expected result.
	 */
	public function test_update_shortcode( array $attr, $updated, $result ) {
		// Input data.
		$content   = 'fake content [MEDIACREDIT] more fake content';
		$shortcode = '[MEDIACREDIT]';
		$img       = '<img src="fake/image"/>';

		// Expected result.
		$result = "fake content [media-credit {$result}]{$img}[/media-credit] more fake content";

		$this->assertSame( $result, $this->sut->update_shortcode( $content, $shortcode, $img, $attr, $updated ) );
	}

	/**
	 * Test ::parse_shortcode_attributes.
	 *
	 * @covers ::parse_shortcode_attributes
	 */
	public function test_parse_shortcode_attributes() {
		// Input data.
		$attributes = 'fa="ke" attr=\'str\' ing';

		// Intermediary data.
		$parsed_attr = [
			'fa'   => 'ke',
			'attr' => 'str',
			'ing',
		];

		// Expected result.
		$result = $parsed_attr;

		Functions\expect( 'shortcode_parse_atts' )->once()->with( $attributes )->andReturn( $parsed_attr );

		$this->assertSame( $result, $this->sut->parse_shortcode_attributes( $attributes ) );
	}

	/**
	 * Test ::parse_shortcode_attributes even when shortcode_parse_atts false to return
	 * meaningful results.
	 *
	 * @covers ::parse_shortcode_attributes
	 */
	public function test_parse_shortcode_attributes_string() {
		// Input data.
		$attributes = 'fa="ke" attr=\'str\' ing';

		// Intermediary data.
		$parsed_attr = 'fakeattrstring';

		// Expected result.
		$result = [];

		Functions\expect( 'shortcode_parse_atts' )->once()->with( $attributes )->andReturn( $parsed_attr );

		$this->assertSame( $result, $this->sut->parse_shortcode_attributes( $attributes ) );
	}

	/**
	 * Provides data for testing ::get_quoted_image_basename,
	 *
	 * @return array
	 */
	public function provide_get_quoted_image_basename_data() {
		return [
			[ [ 'https://example.org/wp-content/uploads/2019/02/19-02-02-18-25-37-0030-150x150.jpg' ], '19\-02\-02\-18\-25\-37\-0030' ],
			[ [ 'https://example.org/wp-content/uploads/2019/02/19-02-02-18-25-37-0030.jpg' ], '19\-02\-02\-18\-25\-37\-0030' ],
			[ [ 'https://example.org/wp-content/uploads/2016/10/another-image-300x300.png' ], 'another\-image' ],
			[ [ 1 => 'foo' ], false ],
			[ [], false ],
		];
	}

	/**
	 * Test ::get_quoted_image_basename.
	 *
	 * @covers ::get_quoted_image_basename
	 *
	 * @dataProvider provide_get_quoted_image_basename_data
	 *
	 * @param  array|bool  $src    The return value of wp_get_attachment_image_src.
	 * @param  string|bool $result The expected result.
	 */
	public function test_get_quoted_image_basename( $src, $result ) {
		// Input data.
		$image_id  = 4711;
		$delimiter = '@';

		Functions\expect( 'wp_get_attachment_image_src' )->once()->with( $image_id )->andReturn( $src );
		Functions\expect( 'wp_basename' )->atMost()->once()->with( m::type( 'string' ) )->andReturnUsing(
			function( $url ) {
				return \basename( $url );
			}
		);

		$this->assertSame( $result, $this->sut->get_quoted_image_basename( $image_id, $delimiter ) );
	}
}
