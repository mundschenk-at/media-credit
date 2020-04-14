/**
 * This file is part of Media Credit.
 *
 * Copyright (C) 2014-2020 Peter Putzer.
 * Copyright (C) 1999-2017 Ephox Corp.
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
 * ***
 *
 * This file includes work covered by the following copyright and
 * permission notice:
 *
 * Plugin.js
 *
 * Released under LGPL License.
 * Copyright (c) 1999-2017 Ephox Corp. All rights reserved
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 *
 * ***
 *
 * @file   This file handles autocomplete in the legacy media library list view.
 * @author Peter Putzer <github@mundschenk.at>
 * @author Ephox Corp.
 * @since  0.5.1
 */

( function( tinymce ) {
	'use strict';

	tinymce.PluginManager.add( 'noneditable', function( editor ) {
		var editClass, nonEditClass, nonEditableRegExps,
			contentEditableAttrName = 'contenteditable';

		function hasClass( checkClassName ) {
			return function( node ) {
				return -1 !== ( ' ' + node.attr( 'class' ) + ' ' ).indexOf( checkClassName );
			};
		}

		function convertRegExpsToNonEditable( e ) {
			var i = nonEditableRegExps.length,
				content = e.content,
				cls = tinymce.trim( nonEditClass );

			function replaceMatchWithSpan( match ) {
				var args = arguments,
					index = args[args.length - 2];
				var prevChar = 0 < index ? content.charAt( index - 1 ) : '';

				// Is value inside an attribute then don't replace
				if ( '"' === prevChar ) {
					return match;
				}

				// Is value inside a contentEditable="false" tag
				if ( '>' === prevChar ) {
					var findStartTagIndex = content.lastIndexOf( '<', index ); // eslint-disable-line vars-on-top
					if ( -1 !== findStartTagIndex ) {
						var tagHtml = content.substring( findStartTagIndex, index ); // eslint-disable-line vars-on-top
						if ( -1 !== tagHtml.indexOf( 'contenteditable="false"' ) ) {
							return match;
						}
					}
				}

				return (
					'<span class="' + cls + '" data-mce-content="' + editor.dom.encode( args[0] ) + '">' +
            editor.dom.encode( 'string' === typeof args[1] ? args[1] : args[0] ) + '</span>'
				);
			}

			// Don't replace the variables when raw is used for example on undo/redo
			if ( 'raw' === e.format ) {
				return;
			}

			while ( i-- ) {
				content = content.replace( nonEditableRegExps[i], replaceMatchWithSpan );
			}

			e.content = content;
		}

		editClass = ' ' + tinymce.trim( editor.getParam( 'noneditable_editable_class', 'mceEditable' ) ) + ' ';
		nonEditClass = ' ' + tinymce.trim( editor.getParam( 'noneditable_noneditable_class', 'mceNonEditable' ) ) + ' ';

		var hasEditClass = hasClass( editClass ); // eslint-disable-line vars-on-top
		var hasNonEditClass = hasClass( nonEditClass ); // eslint-disable-line vars-on-top

		nonEditableRegExps = editor.getParam( 'noneditable_regexp' );
		if ( nonEditableRegExps && ! nonEditableRegExps.length ) {
			nonEditableRegExps = [ nonEditableRegExps ];
		}

		editor.on( 'PreInit', function() {
			if ( nonEditableRegExps ) {
				editor.on( 'BeforeSetContent', convertRegExpsToNonEditable );
			}

			editor.parser.addAttributeFilter( 'class', function( nodes ) {
				var i = nodes.length,
					node;

				while ( i-- ) {
					node = nodes[i];

					if ( hasEditClass( node ) ) {
						node.attr( contentEditableAttrName, 'true' );
					} else if ( hasNonEditClass( node ) ) {
						node.attr( contentEditableAttrName, 'false' );
					}
				}
			} );

			editor.serializer.addAttributeFilter( contentEditableAttrName, function( nodes ) {
				var i = nodes.length,
					node;

				while ( i-- ) {
					node = nodes[i];
					if ( ! hasEditClass( node ) && ! hasNonEditClass( node ) ) {
						continue;
					}

					if ( nonEditableRegExps && node.attr( 'data-mce-content' ) ) {
						node.name = '#text';
						node.type = 3;
						node.raw = true;
						node.value = node.attr( 'data-mce-content' );
					} else {
						node.attr( contentEditableAttrName, null );
					}
				}
			} );
		} );
	} );
}( window.tinymce ) );
