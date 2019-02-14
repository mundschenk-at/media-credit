<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2019 Peter Putzer.
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

// Apply credit width via style attribute.
$style = $width > 0 ? ' style="width: ' . (int) $width . 'px"' : '';

// Wrap output in <figure> if HTML5 is supported & the shortcode is a standalone one.
$wrap = $atts['standalone'] && $html5;

// Optional schema.org markup.
$schema_org        = '';
$figure_schema_org = '';
if ( ! empty( $this->settings['schema_org_markup'] ) ) {
	$schema_org        = ' itemprop="copyrightHolder"';
	$figure_schema_org = ' itemscope itemtype="http://schema.org/ImageObject"';

	if ( ! \preg_match( '/\bitemprop\s*=/S', $content ) ) {
		$content = \preg_replace( '/<img\b/S', '<img itemprop="contentUrl"', $content );
	}
}

$credit_line = \esc_html( $credit );
if ( $url ) {
	$credit_line = '<a href="' . \esc_url( $url ) . '"' . ( ! empty( $atts['nofollow'] ) ? ' rel="nofollow"' : '' ) . '>' . $credit_line . '</a>';
}
$credit_line .= \esc_html( $credit_suffix );

?>
<?php if ( $wrap ) : ?>
<figure class="wp-caption <?php echo \esc_attr( $atts['align'] ); ?>" <?php echo $style, $figure_schema_org; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
<?php endif; ?>
	<div class="media-credit-container <?php echo \esc_attr( $atts['align'] ); ?>" <?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span class="media-credit" <?php echo $schema_org; ?>><?php echo $credit_line; ?></span>
	</div>
<?php if ( $wrap ) : ?>
</figure>
<?php endif; ?>
