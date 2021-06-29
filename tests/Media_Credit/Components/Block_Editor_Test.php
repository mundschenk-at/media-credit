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

namespace Media_Credit\Tests\Media_Credit\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Media_Credit\Tests\TestCase;

use Media_Credit\Components\Block_Editor;

use Media_Credit\Core;
use Media_Credit\Settings;

/**
 * Media_Credit\Components\Block_Editor unit test.
 *
 * @coversDefaultClass \Media_Credit\Components\Block_Editor
 * @usesDefaultClass \Media_Credit\Components\Block_Editor
 *
 * @uses ::__construct
 */
class Block_Editor_Test extends TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Block_Editor
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		Functions\when( '__' )->returnArg();

		$this->core = m::mock( Core::class );

		$this->sut = m::mock( Block_Editor::class, [ $this->core ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$core = m::mock( Core::class );

		$sut = m::mock( Block_Editor::class )->makePartial();
		$sut->__construct( $core );

		$this->assert_attribute_same( $core, 'core', $sut );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Filters\expectAdded( 'render_block' )->once()->with( [ $this->sut, 'add_media_credit_to_image_blocks' ], m::type( 'int' ), m::type( 'int' ) );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::add_media_credit_to_image_blocks.
	 *
	 * @covers ::add_media_credit_to_image_blocks
	 */
	public function test_add_media_credit_to_image_blocks_no_schema_org() {
		// Input data.
		$block_content = '<figure>Fake image block<figcaption>My caption</figcaption></figure>';
		$attachment_id = 42;
		$block         = [
			'blockName' => 'core/image',
			'attrs'     => [
				'id' => $attachment_id,
			],
			'foo'       => 'bar',
		];

		// Intermediary data.
		$schema_org             = false;
		$settings               = [
			Settings::CREDIT_AT_END     => false,
			Settings::SCHEMA_ORG_MARKUP => $schema_org,
		];
		$attachment             = m::mock( \WP_Post::class );
		$rendered_credit        = 'HTML credit line';
		$credit_json            = [
			'rendered' => $rendered_credit,
			'foo'      => 'bar',
		];
		$wrapped_credit         = 'Wrapped HTML credit';
		$injected_block_content = '<figure>Filtered image block<figcaption>My caption & credit</figcaption></figure>';

		// Expected result.
		$result = $injected_block_content;

		$this->core->shouldReceive( 'get_settings' )->once()->withNoArgs()->andReturn( $settings );

		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );

		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $credit_json );
		$this->core->shouldReceive( 'wrap_media_credit_markup' )->once()->with( $rendered_credit, $schema_org )->andReturn( $wrapped_credit );
		$this->sut->shouldReceive( 'inject_credit_into_caption' )->once()->with( $block_content, $wrapped_credit )->andReturn( $injected_block_content );

		$this->assertSame( $result, $this->sut->add_media_credit_to_image_blocks( $block_content, $block ) );
	}

	/**
	 * Tests ::add_media_credit_to_image_blocks.
	 *
	 * @covers ::add_media_credit_to_image_blocks
	 */
	public function test_add_media_credit_to_image_blocks_with_schema_org() {
		// Input data.
		$block_content = '<figure>Fake image block<figcaption>My caption</figcaption></figure>';
		$attachment_id = 42;
		$block         = [
			'blockName' => 'core/image',
			'attrs'     => [
				'id' => $attachment_id,
			],
			'foo'       => 'bar',
		];

		// Intermediary data.
		$schema_org             = true;
		$settings               = [
			Settings::CREDIT_AT_END     => false,
			Settings::SCHEMA_ORG_MARKUP => $schema_org,
		];
		$attachment             = m::mock( \WP_Post::class );
		$rendered_credit        = 'HTML credit line';
		$credit_json            = [
			'rendered' => $rendered_credit,
			'foo'      => 'bar',
		];
		$wrapped_credit         = 'Wrapped HTML credit';
		$injected_block_content = '<figure>Filtered image block<figcaption>My caption & credit</figcaption></figure>';

		// Expected result.
		$result = '<figure itemscope itemtype="http://schema.org/ImageObject">Filtered image block<figcaption itemprop="caption">My caption & credit</figcaption></figure>';

		$this->core->shouldReceive( 'get_settings' )->once()->withNoArgs()->andReturn( $settings );

		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );

		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $credit_json );
		$this->core->shouldReceive( 'wrap_media_credit_markup' )->once()->with( $rendered_credit, $schema_org )->andReturn( $wrapped_credit );
		$this->sut->shouldReceive( 'inject_credit_into_caption' )->once()->with( $block_content, $wrapped_credit )->andReturn( $injected_block_content );
		$this->core->shouldReceive( 'maybe_add_schema_org_markup_to_figure' )->once()->with( $injected_block_content )->andReturn( $result );

		$this->assertSame( $result, $this->sut->add_media_credit_to_image_blocks( $block_content, $block ) );
	}

	/**
	 * Tests ::add_media_credit_to_image_blocks.
	 *
	 * @covers ::add_media_credit_to_image_blocks
	 */
	public function test_add_media_credit_to_image_blocks_invalid_block() {
		// Input data.
		$block_content = '<figure>Fake image block<figcaption>My caption</figcaption></figure>';
		$attachment_id = 42;
		$block         = [
			'blockName' => 'core/paragraph',
			'attrs'     => [
				'id' => $attachment_id,
			],
			'foo'       => 'bar',
		];

		// Intermediary data.
		$schema_org = true;
		$settings   = [
			Settings::CREDIT_AT_END     => false,
			Settings::SCHEMA_ORG_MARKUP => $schema_org,
		];

		// Expected result.
		$result = $block_content;

		$this->core->shouldReceive( 'get_settings' )->once()->withNoArgs()->andReturn( $settings );

		Functions\expect( 'get_post' )->never();

		$this->core->shouldReceive( 'get_media_credit_json' )->never();
		$this->core->shouldReceive( 'wrap_media_credit_markup' )->never();
		$this->sut->shouldReceive( 'inject_credit_into_caption' )->never();
		$this->core->shouldReceive( 'maybe_add_schema_org_markup_to_figure' )->never();

		$this->assertSame( $result, $this->sut->add_media_credit_to_image_blocks( $block_content, $block ) );
	}

	/**
	 * Tests ::add_media_credit_to_image_blocks.
	 *
	 * @covers ::add_media_credit_to_image_blocks
	 */
	public function test_add_media_credit_to_image_blocks_credit_at_end() {
		// Input data.
		$block_content = '<figure>Fake image block<figcaption>My caption</figcaption></figure>';
		$attachment_id = 42;
		$block         = [
			'blockName' => 'core/image',
			'attrs'     => [
				'id' => $attachment_id,
			],
			'foo'       => 'bar',
		];

		// Intermediary data.
		$schema_org = true;
		$settings   = [
			Settings::CREDIT_AT_END     => true,
			Settings::SCHEMA_ORG_MARKUP => $schema_org,
		];

		// Expected result.
		$result = $block_content;

		$this->core->shouldReceive( 'get_settings' )->once()->withNoArgs()->andReturn( $settings );

		Functions\expect( 'get_post' )->never();

		$this->core->shouldReceive( 'get_media_credit_json' )->never();
		$this->core->shouldReceive( 'wrap_media_credit_markup' )->never();
		$this->sut->shouldReceive( 'inject_credit_into_caption' )->never();
		$this->core->shouldReceive( 'maybe_add_schema_org_markup_to_figure' )->never();

		$this->assertSame( $result, $this->sut->add_media_credit_to_image_blocks( $block_content, $block ) );
	}

	/**
	 * Tests ::add_media_credit_to_image_blocks.
	 *
	 * @covers ::add_media_credit_to_image_blocks
	 */
	public function test_add_media_credit_to_image_blocks_missing_attachment_id() {
		// Input data.
		$block_content = '<figure>Fake image block<figcaption>My caption</figcaption></figure>';
		$block         = [
			'blockName' => 'core/image',
			'attrs'     => [],
			'foo'       => 'bar',
		];

		// Intermediary data.
		$schema_org = true;
		$settings   = [
			Settings::CREDIT_AT_END     => false,
			Settings::SCHEMA_ORG_MARKUP => $schema_org,
		];

		// Expected result.
		$result = $block_content;

		$this->core->shouldReceive( 'get_settings' )->once()->withNoArgs()->andReturn( $settings );

		Functions\expect( 'get_post' )->never();

		$this->core->shouldReceive( 'get_media_credit_json' )->never();
		$this->core->shouldReceive( 'wrap_media_credit_markup' )->never();
		$this->sut->shouldReceive( 'inject_credit_into_caption' )->never();
		$this->core->shouldReceive( 'maybe_add_schema_org_markup_to_figure' )->never();

		$this->assertSame( $result, $this->sut->add_media_credit_to_image_blocks( $block_content, $block ) );
	}

	/**
	 * Tests ::add_media_credit_to_image_blocks.
	 *
	 * @covers ::add_media_credit_to_image_blocks
	 */
	public function test_add_media_credit_to_image_blocks_invalid_attachment_id() {
		// Input data.
		$block_content = '<figure>Fake image block<figcaption>My caption</figcaption></figure>';
		$attachment_id = 42;
		$block         = [
			'blockName' => 'core/image',
			'attrs'     => [
				'id' => $attachment_id,
			],
			'foo'       => 'bar',
		];

		// Intermediary data.
		$schema_org = true;
		$settings   = [
			Settings::CREDIT_AT_END     => false,
			Settings::SCHEMA_ORG_MARKUP => $schema_org,
		];

		// Expected result.
		$result = $block_content;

		$this->core->shouldReceive( 'get_settings' )->once()->withNoArgs()->andReturn( $settings );

		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturnNull();

		$this->core->shouldReceive( 'get_media_credit_json' )->never();
		$this->core->shouldReceive( 'wrap_media_credit_markup' )->never();
		$this->sut->shouldReceive( 'inject_credit_into_caption' )->never();
		$this->core->shouldReceive( 'maybe_add_schema_org_markup_to_figure' )->never();

		$this->assertSame( $result, $this->sut->add_media_credit_to_image_blocks( $block_content, $block ) );
	}

	/**
	 * Tests ::add_media_credit_to_image_blocks.
	 *
	 * @covers ::add_media_credit_to_image_blocks
	 */
	public function test_add_media_credit_to_image_blocks_invalid_json() {
		// Input data.
		$block_content = '<figure>Fake image block<figcaption>My caption</figcaption></figure>';
		$attachment_id = 42;
		$block         = [
			'blockName' => 'core/image',
			'attrs'     => [
				'id' => $attachment_id,
			],
			'foo'       => 'bar',
		];

		// Intermediary data.
		$schema_org  = true;
		$settings    = [
			Settings::CREDIT_AT_END     => false,
			Settings::SCHEMA_ORG_MARKUP => $schema_org,
		];
		$attachment  = m::mock( \WP_Post::class );
		$credit_json = [
			'foo' => 'bar',
		];

		// Expected result.
		$result = $block_content;

		$this->core->shouldReceive( 'get_settings' )->once()->withNoArgs()->andReturn( $settings );

		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );

		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $credit_json );
		$this->core->shouldReceive( 'wrap_media_credit_markup' )->never();
		$this->sut->shouldReceive( 'inject_credit_into_caption' )->never();
		$this->core->shouldReceive( 'maybe_add_schema_org_markup_to_figure' )->never();

		$this->assertSame( $result, $this->sut->add_media_credit_to_image_blocks( $block_content, $block ) );
	}

	/**
	 * Tests ::inject_credit_into_caption.
	 *
	 * @covers ::inject_credit_into_caption
	 */
	public function test_inject_credit_into_caption_no_figcaption() {
		// Input data.
		$block_content = '<figure>Fake image block</figure>';
		$credit        = '<span>Wrapped Credit</span>';

		// Expected result.
		$result = '<figure>Fake image block<figcaption><span>Wrapped Credit</span></figcaption></figure>';

		Filters\expectApplied( 'media_credit_block_editor_caption' )->once()->with( m::type( 'string' ), m::type( 'string' ), $credit, $block_content )->andReturnFirstArg();

		$this->assertSame( $result, $this->sut->inject_credit_into_caption( $block_content, $credit ) );
	}

	/**
	 * Tests ::inject_credit_into_caption.
	 *
	 * @covers ::inject_credit_into_caption
	 */
	public function test_inject_credit_into_caption_existing_figcaption() {
		// Input data.
		$block_content = '<figure>Fake image block<figcaption>My caption</figcaption></figure>';
		$credit        = '<span>Wrapped Credit</span>';

		// Expected result.
		$result = '<figure>Fake image block<figcaption><span>Wrapped Credit</span> My caption</figcaption></figure>';

		Filters\expectApplied( 'media_credit_block_editor_caption' )->once()->with( m::type( 'string' ), m::type( 'string' ), $credit, $block_content )->andReturnFirstArg();

		$this->assertSame( $result, $this->sut->inject_credit_into_caption( $block_content, $credit ) );
	}
}
