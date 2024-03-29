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

use Media_Credit\Settings;

/**
 * Required template variables:
 *
 * @var array $options      An array of plugin options.
 * @var array $preview_data {
 *     Strings used for generating the preview.
 *
 *     @type string $pattern The pattern string for credits with two names.
 *     @type string $name1   A male example name.
 *     @type string $name2   A female example name.
 *     @type string $joiner  The string used to join multiple image credits.
 * }
 *
 * @phpstan-var array{ separator: string, organization: string } $options
 * @phpstan-var array{ pattern: string, name1: string, name2: string, joiner: string } $preview_data
 */

$user        = \wp_get_current_user();
$user_credit = '<a href="' . \esc_url( \get_author_posts_url( $user->ID ) ) . '">' . \esc_html( $user->display_name ) . '</a>' . \esc_html( $options[ Settings::SEPARATOR ] . $options[ Settings::ORGANIZATION ] );

if ( ! empty( $options[ Settings::CREDIT_AT_END ] ) ) {
	$credit_html = \sprintf(
		$preview_data['pattern'],
		$preview_data['name1'],
		$user_credit . $preview_data['joiner'] . $preview_data['name2']
	);
} else {
	$credit_html = $user_credit;
}

?>
<p id="<?php echo \esc_attr( Settings::MEDIA_CREDIT_PREVIEW ); ?>" class="notice notice-info" aria-describedby="<?php echo \esc_attr( Settings::MEDIA_CREDIT_PREVIEW ); ?>-description">
	<?php echo \wp_kses( $credit_html, [ 'a' => [ 'href' ] ] ); ?>
</p>
<?php
