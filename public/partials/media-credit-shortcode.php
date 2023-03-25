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
 * Required template variables:
 *
 * @var string     $content             The HTML content contained between
 *                                      [media-credit] and [/media-credit].
 * @var bool       $html5               A flag indicating that the theme supports
 *                                      HTML5 captions.
 * @var bool       $schema_org          A flag indicating that the schema.org information
 *                                      should be injected into the markup.
 * @var int        $width               The width in pixels.
 * @var callable   $inline_media_credit A helper to print the media credit line.
 * @var array      $atts {
 *     An array of shortcode attributes.
 *
 *     @type string $align      The alignment (left, right, center, none).
 *     @type bool   $standalone A flag indicating that this is a standalone
 *                              [media-credit] without a surrounding [caption].
 * }
 *
 * @phpstan-var array{align:string,standalone:bool} $atts
 */

// Apply credit width via style attribute.
$style = $width > 0 ? ' style="max-width: ' . (int) $width . 'px"' : '';

// Alignment class.
$align_class = "align{$atts['align']}";

// Wrap output in <figure> if HTML5 is supported & the shortcode is a standalone one.
$wrap = $atts['standalone'] && $html5;

// Optional schema.org markup.
$schema_org_figure     = '';
$schema_org_figcaption = '';
if ( $schema_org ) {
	$schema_org_figure     = ' itemscope itemtype="http://schema.org/ImageObject"';
	$schema_org_figcaption = ' itemprop="caption"';

	if ( ! \preg_match( '/\bitemprop\s*=/S', $content ) ) {
		$content = \preg_replace( '/<img\b/S', '<img itemprop="contentUrl"', $content );
	}
}

?>
<?php if ( $wrap ) : ?>
<figure class="wp-caption <?php echo \esc_attr( $align_class ); ?>" <?php echo $style, $schema_org_figure; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
<?php endif; ?>
	<?php if ( ! $html5 ) : ?>
		<div class="media-credit-container <?php echo \esc_attr( $align_class ); ?>" <?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $inline_media_credit( $atts, $schema_org ); ?>
		</div>
	<?php else : ?>
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<figcaption class="wp-caption-text" <?php echo $schema_org_figcaption; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php echo $inline_media_credit( $atts, $schema_org ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</figcaption>
	<?php endif; ?>
<?php if ( $wrap ) : ?>
</figure>
<?php endif; ?>
