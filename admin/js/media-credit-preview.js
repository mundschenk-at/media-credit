/**
 * This file is part of Media Credit.
 *
 * Copyright 2015-2020 Peter Putzer.
 * Copyright 2010 Scott Bressler.
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
 * @file   This file handles autocomplete in the legacy media library list view.
 * @author Peter Putzer <github@mundschenk.at>
 * @author Scott Bressler
 * @since  0.5.1
 */

jQuery( function( $ ) {
	'use strict';

	/**
	 * Render HTML for standard media credit preview.
	 */
	function renderCreditExample() {
		var author = $( '#media-credit-preview a' ).clone().wrap( '<p>' ).parent().html();
		var separator = $( 'input[name=\'media_credit_settings[separator]\']' ).val();
		var organization = $( 'input[name=\'media_credit_settings[organization]\']' ).val();

		$( '#media-credit-preview' ).html( author + separator + organization );
	}

	/**
	 * Render HTML for the combined credits at the end a post.
	 */
	function renderCreditAtEndExample() {
		var author = $( '#media-credit-preview a' ).clone().wrap( '<p>' ).parent().html();
		var separator = $( 'input[name=\'media_credit_settings[separator]\']' ).val();
		var organization = $( 'input[name=\'media_credit_settings[organization]\']' ).val();
		var previewData = window.mediaCreditPreviewData || {

			// Default object if translated version is missing.
			pattern: 'Images courtesy of %2$s and %1$s',
			name1: 'Joe Smith',
			name2: 'Jane Doe',
			joiner: ', ',
		};

		$( '#media-credit-preview' ).html( previewData.pattern.replace( '%2$s', author + separator + organization + previewData.joiner + previewData.name2 ).replace( '%1$s', previewData.name1 ) );
	}

	/**
	 * Handle changes to the text fields.
	 */
	$( 'input[name^=\'media_credit_settings\']' ).keyup( function() {
		if ( ! $( 'input[name=\'media_credit_settings[credit_at_end]\']' ).prop( 'checked' ) ) {
			renderCreditExample();
		} else {
			renderCreditAtEndExample();
		}
	} );

	/**
	 * Handle changes to 'Display credits at the end' checkbox.
	 */
	$( 'input[name=\'media_credit_settings[credit_at_end]\']' ).change( function() {
		if ( this.checked ) {
			renderCreditAtEndExample();
		} else {
			renderCreditExample();
		}
	} );
} );
