/**
 * Adapted from http://core.svn.wordpress.org/trunk/wp-includes/js/tinymce/plugins/wpeditimage/plugin.js
 */

/* global tinymce */
tinymce.PluginManager.add( 'mediacredit', function( editor ) {
    var floatingToolbar, serializer, 
        DOM = tinymce.DOM, 
        settings = editor.settings, 
        Factory = tinymce.ui.Factory, 
        each = tinymce.each, 
        iOS = tinymce.Env.iOS, 
        toolbarIsHidden = true, 
        editorWrapParent = tinymce.$( '#postdivrich' ); 

    function isPlaceholder( node ) { 
        return !! ( editor.dom.getAttrib( node, 'data-mce-placeholder' ) || editor.dom.getAttrib( node, 'data-mce-object' ) ); 
    } 

	editor.addButton( 'wp_img_remove', { 
        tooltip: 'Remove', 
        icon: 'dashicon dashicons-no', 
        onclick: function() { 
            removeImage( editor.selection.getNode() ); 
        } 
    } ); 
 
    editor.addButton( 'wp_img_edit', { 
        tooltip: 'Edit ', // trailing space is needed, used for context 
        icon: 'dashicon dashicons-edit', 
        onclick: function() { 
            editImage( editor.selection.getNode() ); 
        } 
    } ); 

	each( { 
        alignleft: 'Align left', 
        aligncenter: 'Align center', 
        alignright: 'Align right', 
        alignnone: 'No alignment' 
    }, function( tooltip, name ) { 
        var direction = name.slice( 5 ); 
 
        editor.addButton( 'wp_img_' + name, { 
            tooltip: tooltip, 
            icon: 'dashicon dashicons-align-' + direction, 
            cmd: 'alignnone' === name ? 'wpAlignNone' : 'Justify' + direction.slice( 0, 1 ).toUpperCase() + direction.slice( 1 ), 
                    onPostRender: function() {
		            	var self = this; 
		 
		                editor.on( 'NodeChange', function( event ) { 
		                    var node; 
		 
		                    // Don't bother. 
		                    if ( event.element.nodeName !== 'IMG' ) { 
		                        return; 
		                    } 
		 
		                    node = editor.dom.getParent( event.element, '.wp-caption' ) || event.element; 
		 
		                    if ( 'alignnone' === name ) { 
		                        self.active( ! /\balign(left|center|right)\b/.test( node.className ) ); 
		                    } else { 
		                        self.active( editor.dom.hasClass( node, name ) ); 
		                    } 
		                } ); 
		            } 
        } ); 
    } ); 

    function toolbarConfig() { 
        var toolbarItems = [], 
            buttonGroup; 
 
        each( [ 'wp_img_alignleft', 'wp_img_aligncenter', 'wp_img_alignright', 'wp_img_alignnone', 'wp_img_edit', 'wp_img_remove' ], function( item ) { 
            var itemName; 
 
            function bindSelectorChanged() { 
                var selection = editor.selection; 
 
                if ( item.settings.stateSelector ) { 
                    selection.selectorChanged( item.settings.stateSelector, function( state ) { 
                        item.active( state ); 
                    }, true ); 
                } 
 
                if ( item.settings.disabledStateSelector ) { 
                    selection.selectorChanged( item.settings.disabledStateSelector, function( state ) { 
                        item.disabled( state ); 
                    } ); 
                } 
            } 
 
            if ( item === '|' ) { 
                buttonGroup = null; 
            } else { 
                if ( Factory.has( item ) ) { 
                    item = { 
                        type: item 
                    }; 
 
                    if ( settings.toolbar_items_size ) { 
                        item.size = settings.toolbar_items_size; 
                    } 
 
                    toolbarItems.push( item ); 
 
                    buttonGroup = null; 
                } else { 
                    if ( ! buttonGroup ) { 
                        buttonGroup = { 
                            type: 'buttongroup', 
                            items: [] 
                        }; 
 
                        toolbarItems.push( buttonGroup ); 
                    } 
 
                    if ( editor.buttons[ item ] ) { 
                        itemName = item; 
                        item = editor.buttons[ itemName ]; 
 
                        if ( typeof item === 'function' ) { 
                            item = item(); 
                        } 
 
                        item.type = item.type || 'button'; 
 
                        if ( settings.toolbar_items_size ) { 
                            item.size = settings.toolbar_items_size; 
                        } 
 
                        item = Factory.create( item ); 
                        buttonGroup.items.push( item ); 
 
                        if ( editor.initialized ) { 
                            bindSelectorChanged(); 
                        } else { 
                            editor.on( 'init', bindSelectorChanged ); 
                        } 
                    } 
                } 
            } 
        } ); 
 
        return { 
            type: 'panel', 
            layout: 'stack', 
            classes: 'toolbar-grp inline-toolbar-grp wp-image-toolbar', 
            ariaRoot: true, 
            ariaRemember: true, 
            items: [ 
                { 
                    type: 'toolbar', 
                    layout: 'flow', 
                    items: toolbarItems 
                } 
            ] 
        }; 
    }

    floatingToolbar = Factory.create( toolbarConfig() ).renderTo( document.body ).hide(); 

	floatingToolbar.reposition = function() { 
        var top, left, minTop, className, 
            windowPos, adminbar, mceToolbar, boundary, 
            boundaryMiddle, boundaryVerticalMiddle, spaceTop, 
            spaceBottom, windowWidth, toolbarWidth, toolbarHalf, 
            iframe, iframePos, iframeWidth, iframeHeigth, 
            toolbarNodeHeight, verticalSpaceNeeded, 
            toolbarNode = this.getEl(), 
            buffer = 5, 
            margin = 8, 
            adminbarHeight = 0, 
            imageNode = editor.selection.getNode(); 
 
        if ( ! imageNode || imageNode.nodeName !== 'IMG' ) { 
            return this; 
        } 
 
        windowPos = window.pageYOffset || document.documentElement.scrollTop; 
        adminbar = tinymce.$( '#wpadminbar' )[0]; 
        mceToolbar = tinymce.$( '.mce-tinymce .mce-toolbar-grp' )[0]; 
        boundary = imageNode.getBoundingClientRect(); 
        boundaryMiddle = ( boundary.left + boundary.right ) / 2; 
        boundaryVerticalMiddle = ( boundary.top + boundary.bottom ) / 2; 
        spaceTop = boundary.top; 
        spaceBottom = iframeHeigth - boundary.bottom; 
        windowWidth = window.innerWidth; 
        toolbarWidth = toolbarNode.offsetWidth; 
        toolbarHalf = toolbarWidth / 2; 
        iframe = editor.getContentAreaContainer().firstChild; 
        iframePos = DOM.getPos( iframe ); 
        iframeWidth = iframe.offsetWidth; 
        iframeHeigth = iframe.offsetHeight; 
        toolbarNodeHeight = toolbarNode.offsetHeight; 
        verticalSpaceNeeded = toolbarNodeHeight + margin + buffer; 
 
        if ( iOS ) { 
            top = boundary.top + iframePos.y + margin; 
        } else { 
            if ( spaceTop >= verticalSpaceNeeded ) { 
                className = ' mce-arrow-down'; 
                top = boundary.top + iframePos.y - toolbarNodeHeight - margin; 
            } else if ( spaceBottom >= verticalSpaceNeeded ) { 
                className = ' mce-arrow-up'; 
                top = boundary.bottom + iframePos.y; 
            } else { 
                top = buffer; 
 
                if ( boundaryVerticalMiddle >= verticalSpaceNeeded ) { 
                    className = ' mce-arrow-down'; 
                } else { 
                    className = ' mce-arrow-up'; 
                } 
            } 
        } 
 
        // Make sure the image toolbar is below the main toolbar. 
        if ( mceToolbar ) { 
            minTop = DOM.getPos( mceToolbar ).y + mceToolbar.clientHeight; 
        } else { 
            minTop = iframePos.y; 
        } 
 
        // Make sure the image toolbar is below the adminbar (if visible) or below the top of the window. 
        if ( windowPos ) { 
            if ( adminbar && adminbar.getBoundingClientRect().top === 0 ) { 
                adminbarHeight = adminbar.clientHeight; 
            } 
 
            if ( windowPos + adminbarHeight > minTop ) { 
                minTop = windowPos + adminbarHeight; 
            } 
        } 
 
        if ( top && minTop && ( minTop + buffer > top ) ) { 
            top = minTop + buffer; 
            className = ''; 
        } 
 
        left = boundaryMiddle - toolbarHalf; 
        left += iframePos.x; 
 
        if ( toolbarWidth >= windowWidth ) { 
            className += ' mce-arrow-full'; 
            left = 0; 
        } else if ( ( left < 0 && boundary.left + toolbarWidth > windowWidth ) || 
            ( left + toolbarWidth > windowWidth && boundary.right - toolbarWidth < 0 ) ) { 
 
            left = ( windowWidth - toolbarWidth ) / 2; 
        } else if ( left < iframePos.x ) { 
            className += ' mce-arrow-left'; 
            left = boundary.left + iframePos.x; 
        } else if ( left + toolbarWidth > iframeWidth + iframePos.x ) { 
            className += ' mce-arrow-right'; 
            left = boundary.right - toolbarWidth + iframePos.x; 
        } 
 
        if ( ! iOS ) { 
            toolbarNode.className = toolbarNode.className.replace( / ?mce-arrow-[\w]+/g, '' ); 
            toolbarNode.className += className; 
        } 
 
        DOM.setStyles( toolbarNode, { 'left': left, 'top': top } ); 
 
        return this; 
    }; 

    if ( iOS ) { 
        // Safari on iOS fails to select image nodes in contentEditoble mode on touch/click. 
        // Select them again. 
        editor.on( 'click', function( event ) { 
            if ( event.target.nodeName === 'IMG' ) { 
                var node = event.target; 
 
                window.setTimeout( function() { 
                    editor.selection.select( node ); 
                }, 200 ); 
            } else { 
                floatingToolbar.hide(); 
            } 
        }); 
    } 

    editor.on( 'nodechange', function( event ) { 
        var delay = iOS ? 350 : 100; 
 
        if ( event.element.nodeName !== 'IMG' || isPlaceholder( event.element ) ) { 
            floatingToolbar.hide(); 
            return; 
        } 
 
        setTimeout( function() { 
            var element = editor.selection.getNode(); 
 
            if ( element.nodeName === 'IMG' && ! isPlaceholder( element ) ) { 
                if ( floatingToolbar._visible ) { 
                    floatingToolbar.reposition(); 
                } else { 
                    floatingToolbar.show(); 
                } 
            } else { 
                floatingToolbar.hide(); 
            } 
        }, delay ); 
    } ); 

    function hide() { 
        if ( ! toolbarIsHidden ) { 
            floatingToolbar.hide(); 
        }
    }
    
    floatingToolbar.on( 'show', function() { 
        toolbarIsHidden = false; 
 
        if ( this._visible ) { 
            this.reposition(); 
            DOM.addClass( this.getEl(), 'mce-inline-toolbar-grp-active' ); 
        }
    } );

    floatingToolbar.on( 'hide', function() { 
        toolbarIsHidden = true; 
        DOM.removeClass( this.getEl(), 'mce-inline-toolbar-grp-active' ); 
    } );

    floatingToolbar.on( 'keydown', function( event ) { 
        if ( event.keyCode === 27 ) { 
            hide(); 
            editor.focus(); 
        } 
    } ); 
    
    DOM.bind( window, 'resize scroll', function() { 
        if ( ! toolbarIsHidden && editorWrapParent.hasClass( 'wp-editor-expand' ) ) { 
            hide(); 
        } 
    }); 
 	
    editor.on( 'init', function() { 
        editor.dom.bind( editor.getWin(), 'scroll', hide ); 
    }); 

    editor.on( 'blur hide', hide ); 

    // 119 = F8 
    editor.shortcuts.add( 'Alt+119', '', function() { 
        var node = floatingToolbar.find( 'toolbar' )[0]; 
 
        if ( node ) { 
            node.focus( true ); 
        } 
    });
    
	function parseShortcode( content ) {
		
		var result;
		
		/*
		 * Handle [media-credit] wrapped in [caption]
		 */
		result = content.replace( /(?:<p>)?\[(?:wp_)?caption([^\]]+)\]([\s\S]+?)\[\/(?:wp_)?caption\](?:<\/p>)?/g, function( a, b, c ) {
			//var id, cls, w, cap, img, width,
            var id, align, classes, caption, img, width, 

				trim = tinymce.trim;

            align = b.match( /align=['"]([^'"]*)['"] ?/ ); 
            if ( align ) { 
            	b = b.replace( align[0], '' ); 
            } 
 
            classes = b.match( /class=['"]([^'"]*)['"] ?/ ); 
            if ( classes ) { 
                b = b.replace( classes[0], '' ); 
            } 
 
            width = b.match( /width=['"]([0-9]*)['"] ?/ ); 
            if ( width ) { 
                b = b.replace( width[0], '' ); 
			}
			
			c = trim( c );
			img = c.match(/((?:\[media-credit[^\]]+\]\s*)(?:<a [^>]+>)?<img [^>]+>(?:<\/a>)?(?:\s*\[\/media-credit\])?)([\s\S]*)/i);
			img = img !== null ? img : c.match( /((?:<a [^>]+>)?<img [^>]+>(?:<\/a>)?)([\s\S]*)/i ); // alternative match if there is no media-credit shortcode
			
			if ( img && img[2] ) {
				caption = trim( img[2] ); 
				img = trim( img[1] );
			} else {
				// old captions shortcode style
				caption = trim( b ).replace( /caption=['"]/, '' ).replace( /['"]$/, '' );
				img = c;
			}

			img = parseMediaCreditShortcode(img);
			
			id = ( id && id[1] ) ? id[1].replace( /[<>&]+/g,  '' ) : '';
			align = ( align && align[1] ) ? align[1] : 'alignnone';
			classes = ( classes && classes[1] ) ? ' ' + classes[1].replace( /[<>&]+/g,  '' ) : ''; 
				
			if ( ! width && img ) { 
                width = img.match( /width=['"]([0-9]*)['"]/ ); 
            } 
 
            if ( width && width[1] ) { 
                width = width[1]; 
            } 
 
            if ( ! width || ! caption ) { 
				return c;
			}

			width = parseInt( width, 10 ); 
			if ( ! editor.getParam( 'wpeditimage_html5_captions' ) ) {
				width += 10;
			}

			return '<div class="mceTemp"><dl id="'+ id +'" class="wp-caption '+ align + classes +'" style="width: '+ width +'px">' +
				'<dt class="wp-caption-dt">'+ img +'</dt><dd class="wp-caption-dd">'+ caption +'</dd></dl></div>';
		});
		
		/*
		 * Handle all other occurences of [media-credit]
		 */
		result = parseMediaCreditShortcode( result, true );
		
		return result;
	}
	
	function parseMediaCreditShortcode( content, standalone ) {
		standalone = (typeof standalone == 'undefined' ? false : standalone);
		var pattern;
		
		if (standalone) {
			pattern = /(?:<p>)?\[media-credit([^\]]+)\]([\s\S]+?)\[\/media-credit\](?:<\/p>)?/g;
		} else {
			pattern = /\[media-credit([^\]]+)\]([\s\S]+?)\[\/media-credit\]/g;
		}
		
		return content.replace(pattern, function(a,b,c){

			var id, align, w, img, width, out,
				trim = tinymce.trim;

			id = b.match( /id=['"]?([0-9]+)['"]? ?/ );
			if ( id ) {
				b = b.replace( id[0], '' );
			}

			align = b.match( /align=['"]([^'"]*)['"] ?/ );
			if ( align ) {
				b = b.replace( align[0], '' );
			}

			w = b.match( /width=['"]([0-9]*)['"] ?/ );
			if ( w ) {
				b = b.replace( w[0], '' );
			}

			//name = b.match(/name=['"]([^'"]*)['"] ?/i);
			name = trim( b ).replace( /name=['"]/, '' ).replace( /['"]$/, '' );
			
			if ( name ) {
				b = b.replace( name[0], '' );
			}
			
			c = trim( c );
			img = c.match( /((?:<a [^>]+>)?<img [^>]+>(?:<\/a>)?)([\s\S]*)/i );
				
			if ( img && img[1] ) {
				img = trim( img[1] );
			}
			
			id = ( id && id[1] ) ? id[1] : '';
			align = ( align && align[1] ) ? align[1] : 'alignnone';

			if ( ! w && img ) {
				w = img.match( /width=['"]([0-9]*)['"]/ );
			}

			if ( w && w[1] ) {
				w = w[1];
			}

			if ( ! w || ! (name || id) ) {
				return c;
			}

			width = parseInt( w, 10 );
			credit = name ? name : ($mediaCredit.id[id] + $mediaCredit.separator + $mediaCredit.organization);
			credit = credit.replace(/<[^>]+>(.*)<\/[^>]+>/g, '$1'); // basic sanitation


			out = img + wp.html.string({
				tag: 'span',
				content: credit,
				attrs: {
					'class': 'mceMediaCreditTemp mceNonEditable',
					'data-media-credit-id': id,
					'data-media-credit-name': name,
					'data-media-credit-align': align
				}
			});
			
			if (standalone) {
				out = wp.html.string({
					tag: 'div',
					content: out,
					attrs: {
						'class': 'mceMediaCreditOuterTemp ' + align,
						style: 'width: ' + (width + 10) + 'px'
					}
				});
			}
			
			return out;
			
		});
	}

	function getShortcode( content ) {
		var result;
		
		/*
		 * Handle media-credits inside captions
		 */
		result = content.replace( /<div (?:id="attachment_|class="mceTemp)[^>]*>([\s\S]+?)<\/div>/g, function( a, b ) {

			var out = '';

			if ( b.indexOf('<img ') === -1 ) {
				// Broken caption. The user managed to drag the image out?
				// Try to return the caption text as a paragraph.
				out = b.match( /<dd [^>]+>([\s\S]+?)<\/dd>/i );

				if ( out && out[1] ) {
					return '<p>' + out[1] + '</p>';
				}

				return '';
			}

			out = b.replace( /\s*<dl ([^>]+)>\s*<dt [^>]+>([\s\S]+?)<\/dt>\s*<dd [^>]+>([\s\S]*?)<\/dd>\s*<\/dl>\s*/gi, function( a, b, c, caption ) {
				var id, classes, align, width;

				width = c.match( /width="([0-9]*)"/ ); 
                width = ( width && width[1] ) ? width[1] : ''; 
 
                if ( ! width || ! caption ) { 
					return c;
				}

				id = b.match( /id="([^"]*)"/ );
				id = ( id && id[1] ) ? id[1] : '';

				classes = b.match( /class="([^"]*)"/ ); 
                classes = ( classes && classes[1] ) ? classes[1] : ''; 
             		 
                align = classes.match( /align[a-z]+/i ) || 'alignnone'; 
                classes = classes.replace( /wp-caption ?|align[a-z]+ ?/gi, '' ); 
             		 
                if ( classes ) { 
                	classes = ' class="' + classes + '"'; 
             	} 
             		 
                caption = caption.replace( /\r\n|\r/g, '\n' ).replace( /<[a-zA-Z0-9]+( [^<>]+)?>/g, function( a ) { 
					// no line breaks inside HTML tags
					return a.replace( /[\r\n\t]+/, ' ' );
				});

				// convert remaining line breaks to <br>
				caption = caption.replace( /\s*\n\s*/g, '<br />' );
				
				c = getMediaCreditShortcode(c);				
				
				return '[caption id="' + id + '" align="' + align + '" width="' + width + '"' + classes + ']' + c + ' ' + caption + '[/caption]'; 

			});

			if ( out.indexOf('[caption') === -1 ) {
				// the caption html seems broken, try to find the image that may be wrapped in a link
				// and may be followed by <p> with the caption text.
				out = b.replace( /[\s\S]*?((?:<a [^>]+>)?<img [^>]+>(?:<\/a>)?)(<p>[\s\S]*<\/p>)?[\s\S]*/gi, '<p>$1</p>$2' );
			}

			return out;
		});
		
		/*
		 * Handle all other media-credits 
		 */
		result = getMediaCreditShortcode( result, true );
		
		console.log("RECONSTRUCTED: ", result);
		
		return result;
	}
	
	function getMediaCreditShortcode( content, standalone ) {
		standalone = (typeof standalone == 'undefined' ? false : standalone);
		var pattern = /((?:<a [^>]+>)?<img [^>]+>(?:<\/a>)?)<span class="mceMediaCreditTemp[^"]*" ([^>]*)>([\s\S]+?)<\/span>/g;
		
		if (standalone) {
			pattern = /<div class="mceMediaCreditOuterTemp[^"]*"[^>]*>((?:<a [^>]+>)?<img [^>]+>(?:<\/a>)?)<span class="mceMediaCreditTemp[^"]*" ([^>]*)>([\s\S]+?)<\/span><\/div>/g;
		}
		
		console.log("Reconstructing media credit ...");
		console.log("CONTENT: ", content);
		
		return content.replace( pattern , function( a, b, c, d) {			
			var out = '', id, name, w, align, 
				trim = tinymce.trim;

			console.log("A: ", a);
			console.log("B: ", b);
			console.log("C: ", c);
			console.log("D: ", d);
			
			if ( b.indexOf('<img ') === -1 ) {
				// Broken credit. The user managed to drag the image out?
				// Try to return the credit text as a paragraph.
				return '<p>' + d + '</p>';
			}
		
			w = b.match( /width="([0-9]*)"/ );
			w = ( w && w[1] ) ? w[1] : '';
		
			id = parseAttribute( c, 'data-media-credit-id', '[0-9]+', true );
			align = parseAttribute( c, 'data-media-credit-align', '[^\'"]*', false );
			name = _.unescape(parseAttribute( c, 'data-media-credit-name', '[^"]*', false ));
						
			if ( ! w || ! (name || id) ) {
				return b;
			}
			
			credit = id ? ('id='+id) : ('name="'+name+'"');
			out = '[media-credit ' + credit + ' align="' + align +'" width="' + w + '"]'+ b +'[/media-credit]';
			
			// correct newlines for standalone credits - [caption] has special handling in /wp-admin/js/editor.js for this case
//			if ( standalone ) {
//				out += '\n\n';
//			}
			
			if ( out.indexOf('[media-credit') !== 0 ) {
				// the caption html seems broken, try to find the image that may be wrapped in a link
				// and may be followed by <p> with the caption text.
				out = b.replace( /[\s\S]*?((?:<a [^>]+>)?<img [^>]+>(?:<\/a>)?)(<p>[\s\S]*<\/p>)?[\s\S]*/gi, '<p>$1</p>$2' );
			}

			return out;
		});
	}

	/*
	 * content - the snippet to parse
	 * attr - the name of the attribute
	 * pattern - a regexp for the result
	 * unquoted - whether quotes are necessary (default)
	 */
	function parseAttribute( content, attr, pattern, unquoted ) {
		var result = null;
		unquoted = (typeof unquoted == 'undefined' ? false : unquoted);
				
		if (unquoted) {
			search_pattern = new RegExp(attr + '=(' + pattern + ') ?') ;
			result = content.match( search_pattern );
		}
		
		if (!result) {
			search_pattern =  new RegExp(attr + '="(' + pattern + ')" ?');
			result = content.match( search_pattern );
		}

		if (!result) {
			search_pattern = new RegExp(attr + "='(" + pattern + ")' ?");
			result = content.match( search_pattern );
		}

		result = ( result && result[1] ) ? result[1] : '';
				
		return result;
	}
	
	function extractImageData( imageNode ) {
		var classes, extraClasses, metadata, captionBlock, caption, link, width, height, mediaCreditBlock,
			captionClassName = [], 
			dom = editor.dom,
			isIntRegExp = /^\d+$/;

		// default attributes
		metadata = {
			attachment_id: false,
			size: 'custom',
			caption: '',
			align: 'none',
			extraClasses: '',
			link: false,
			linkUrl: '',
			linkClassName: '',
			linkTargetBlank: false,
			linkRel: '',
			title: '',
			mediaCreditName: '',
			mediaCreditID: '',
		};

		metadata.url = dom.getAttrib( imageNode, 'src' );
		metadata.alt = dom.getAttrib( imageNode, 'alt' );
		metadata.title = dom.getAttrib( imageNode, 'title' );

		width = dom.getAttrib( imageNode, 'width' );
		height = dom.getAttrib( imageNode, 'height' );

		if ( ! isIntRegExp.test( width ) || parseInt( width, 10 ) < 1 ) {
			width = imageNode.naturalWidth || imageNode.width;
		}

		if ( ! isIntRegExp.test( height ) || parseInt( height, 10 ) < 1 ) {
			height = imageNode.naturalHeight || imageNode.height;
		}

		metadata.customWidth = metadata.width = width;
		metadata.customHeight = metadata.height = height;

		classes = tinymce.explode( imageNode.className, ' ' );
		extraClasses = [];

		tinymce.each( classes, function( name ) {

			if ( /^wp-image/.test( name ) ) {
				metadata.attachment_id = parseInt( name.replace( 'wp-image-', '' ), 10 );
			} else if ( /^align/.test( name ) ) {
				metadata.align = name.replace( 'align', '' );
			} else if ( /^size/.test( name ) ) {
				metadata.size = name.replace( 'size-', '' );
			} else {
				extraClasses.push( name );
			}

		} );

		metadata.extraClasses = extraClasses.join( ' ' );
		metadata.captionClassName = captionClassName.join( ' ' );
		
		// Extract caption
		captionBlock = dom.getParents( imageNode, '.wp-caption' );

		if ( captionBlock.length ) {
			captionBlock = captionBlock[0];

			classes = captionBlock.className.split( ' ' );
			tinymce.each( classes, function( name ) {
				if ( /^align/.test( name ) ) {
					metadata.align = name.replace( 'align', '' );
	            } else if ( name && name !== 'wp-caption' ) { 
            		captionClassName.push( name ); 
				}
			} );

			caption = dom.select( 'dd.wp-caption-dd', captionBlock );
			if ( caption.length ) {
				caption = caption[0];

				metadata.caption = editor.serializer.serialize( caption )
					.replace( /<br[^>]*>/g, '$&\n' ).replace( /^<p>/, '' ).replace( /<\/p>$/, '' );
			}
		}
		
		// Extract linkTo
		if ( imageNode.parentNode && imageNode.parentNode.nodeName === 'A' ) {
			link = imageNode.parentNode;
			metadata.linkUrl = dom.getAttrib( link, 'href' );
			metadata.linkTargetBlank = dom.getAttrib( link, 'target' ) === '_blank' ? true : false;
			metadata.linkRel = dom.getAttrib( link, 'rel' );
			metadata.linkClassName = link.className;
		}

		// Extract media-credit
		if ( link ) {
			mediaCreditBlock = dom.getNext( link, '.mceMediaCreditTemp' );			
		} else {
			mediaCreditBlock = dom.getNext( imageNode, '.mceMediaCreditTemp' );
		}
		
		if (mediaCreditBlock) {
			metadata.align = (metadata.align && metadata.align != 'none' ) ? metadata.align : dom.getAttrib(mediaCreditBlock, 'data-media-credit-align', '').replace( 'align', '' );
			metadata.mediaCreditName =  dom.getAttrib(mediaCreditBlock, 'data-media-credit-name', '');
			metadata.mediaCreditID =  dom.getAttrib(mediaCreditBlock, 'data-media-credit-id', '');
		}
		
		return metadata;
	}

	function hasTextContent( node ) {
		return node && !! ( node.textContent || node.innerText );
	}
	
    // Verify HTML in captions 
    function verifyHTML( caption ) { 
        if ( ! caption || ( caption.indexOf( '<' ) === -1 && caption.indexOf( '>' ) === -1 ) ) { 
            return caption; 
        } 
 
        if ( ! serializer ) { 
            serializer = new tinymce.html.Serializer( {}, editor.schema ); 
        } 
 
        return serializer.serialize( editor.parser.parse( caption, { forced_root_block: false } ) ); 
    } 

	function updateImage( imageNode, imageData ) {
		var classes, className, node, html, parent, wrap, linkNode,
			captionNode, dd, dl, id, attrs, linkAttrs, width, height, align,
			mediaCreditNode, mediaCreditWrapper, 
			dom = editor.dom;

		classes = tinymce.explode( imageData.extraClasses, ' ' );

		if ( ! classes ) {
			classes = [];
		}

		// setup nodes for later checks
		if ( imageNode.parentNode && imageNode.parentNode.nodeName === 'A' && ! hasTextContent( imageNode.parentNode ) ) {
			node = imageNode.parentNode;
		} else {
			node = imageNode;
		}
		mediaCreditNode = dom.getNext( node, '.mceMediaCreditTemp' );			
				
		// set alignment if there is no caption
		if ( ! imageData.caption ) {
			if (mediaCreditNode) {
				dom.setAttrib( mediaCreditNode, 'data-media-credit-align', 'align' + imageData.align );
			} else {
				classes.push( 'align' + imageData.align );
			}
		}

		if ( imageData.attachment_id ) {
			classes.push( 'wp-image-' + imageData.attachment_id );
			if ( imageData.size && imageData.size !== 'custom' ) {
				classes.push( 'size-' + imageData.size );
			}
		}

		width = imageData.width;
		height = imageData.height;

		if ( imageData.size === 'custom' ) {
			width = imageData.customWidth;
			height = imageData.customHeight;
		}

		attrs = {
			src: imageData.url,
			width: width || null,
			height: height || null,
			alt: imageData.alt,
			title: imageData.title || null,
			'class': classes.join( ' ' ) || null
		};

		dom.setAttribs( imageNode, attrs );

		linkAttrs = {
			href: imageData.linkUrl,
			rel: imageData.linkRel || null,
			target: imageData.linkTargetBlank ? '_blank': null,
			'class': imageData.linkClassName || null
		};

		if ( imageNode.parentNode && imageNode.parentNode.nodeName === 'A' && ! hasTextContent( imageNode.parentNode ) ) {
			// Update or remove an existing link wrapped around the image
			if ( imageData.linkUrl ) {
				dom.setAttribs( imageNode.parentNode, linkAttrs );
			} else {
				dom.remove( imageNode.parentNode, true );
			}
		} else if ( imageData.linkUrl ) {
			if ( linkNode = dom.getParent( imageNode, 'a' ) ) {
				// The image is inside a link together with other nodes,
				// or is nested in another node, move it out
				dom.insertAfter( imageNode, linkNode );
			}

			// Add link wrapped around the image
			linkNode = dom.create( 'a', linkAttrs );
			imageNode.parentNode.insertBefore( linkNode, imageNode );
			linkNode.appendChild( imageNode );
		}

		captionNode = editor.dom.getParent( imageNode, '.mceTemp' );

		if ( imageData.caption ) {
			imageData.caption = verifyHTML( imageData.caption ); 

			id = imageData.attachment_id ? 'attachment_' + imageData.attachment_id : null;
			align = 'align' + ( imageData.align || 'none' ); 
			className = 'wp-caption ' + align; 
			
			if ( imageData.captionClassName ) { 
				className += ' ' + imageData.captionClassName.replace( /[<>&]+/g,  '' ); 
			} 

			// set alignment for nested media-credit if necessary
			if (mediaCreditNode) {
				dom.setAttrib( mediaCreditNode, 'data-media-credit-align', align );
			}
			
			if ( ! editor.getParam( 'wpeditimage_html5_captions' ) ) {
				width = parseInt( width, 10 );
				width += 10;
			}

			if ( captionNode ) {
				dl = dom.select( 'dl.wp-caption', captionNode );

				if ( dl.length ) {
					dom.setAttribs( dl, {
						id: id,
						'class': className,
						style: 'width: ' + width + 'px'
					} );
				}

				dd = dom.select( '.wp-caption-dd', captionNode );

				if ( dd.length ) {
					dom.setHTML( dd[0], imageData.caption );
				}

			} else {
				id = id ? 'id="'+ id +'" ' : '';
				
				// should create a new function for generating the caption markup
				html =  '<dl ' + id + 'class="' + className +'" style="width: '+ width +'px">' +
	                '<dt class="wp-caption-dt"></dt><dd class="wp-caption-dd">'+ imageData.caption +'</dd></dl>'; 
				
				wrap = dom.create( 'div', { 'class': 'mceTemp' }, html );

				if ( (parent = dom.getParent( node, 'p' )) || 
					 (parent = dom.getParent( node, '.mceMediaCreditOuterTemp' )) ) {
					parent.parentNode.insertBefore( wrap, parent );
					
					// prevent duplicate children
					dom.remove(node); 
					if (mediaCreditNode) {
						dom.remove( mediaCreditNode );
					}					
					
					// cleanup
					if ( dom.isEmpty( parent ) ) {
						dom.remove( parent );
					}
				} else {
					node.parentNode.insertBefore( wrap, node );
				}
				
				node = editor.$( wrap ).find( 'dt.wp-caption-dt' ).append( node ); 
				
				if (mediaCreditNode) {
					node.append(mediaCreditNode);
				}
			}
		} else if ( captionNode ) {
			// Remove the caption wrapper and place the image in new media-credit wrapper or a new paragraph
			mediaCreditNode = dom.getNext( node, '.mceMediaCreditTemp' );
			
			if (mediaCreditNode) {
				align = 'align' + ( imageData.align || 'none' ); 

				parent = dom.create( 'div', { 'class': 'mceMediaCreditOuterTemp ' + align,
											  'style': 'width: ' + (parseInt(width) + 10) + 'px' } );
			} else {
				parent = dom.create( 'p' );
			}
			captionNode.parentNode.insertBefore( parent, captionNode );
			parent.appendChild( node );
			if (mediaCreditNode) {
				parent.appendChild( mediaCreditNode );
			}
			
			dom.remove( captionNode );
		} else {
			// no caption data, just update the media-credit wrapper
			mediaCreditWrapper = dom.getParent( mediaCreditNode, '.mceMediaCreditOuterTemp' );
			
			if ( mediaCreditWrapper ) {
				align = 'align' + ( imageData.align || 'none' ); 
				mediaCreditWrapper.className = mediaCreditWrapper.className.replace( / ?align(left|center|right|none)/g, ' ' ) + align; 
				dom.setAttrib( mediaCreditWrapper, 'style', 'width: ' + (width + 10) + 'px' );
			}
		}

        if ( wp.media.events ) {
                wp.media.events.trigger( 'editor:image-update', {
                        editor: editor,
                        metadata: imageData,
                        image: imageNode
                } );
        }

		editor.nodeChanged();
	}

	function editImage( img ) {
		var frame, callback;

		if ( typeof wp === 'undefined' || ! wp.media ) {
			editor.execCommand( 'mceImage' );
			return;
		}

		
        metadata = extractImageData(img);

		// Manipulate the metadata by reference that is fed into
		// the PostImage model used in the media modal
		wp.media.events.trigger('editor:image-edit', {
			editor : editor,
			metadata : metadata,
			image : img
		});

		frame = wp.media({
			frame: 'image',
			state: 'image-details',
			metadata: metadata
		} );

		wp.media.events.trigger( 'editor:frame-create', { frame: frame } );
		
		callback = function( imageData ) {
			editor.focus();
			editor.undoManager.transact( function() {
				updateImage( img, imageData );
			} );
			frame.detach();
		};

		frame.state('image-details').on( 'update', callback );
		frame.state('replace-image').on( 'replace', callback );
		frame.on( 'close', function() {
			editor.focus();
			frame.detach();
		});

		frame.open();
	}

	function removeImage( node ) {
		var wrap;

		if ( node.nodeName === 'DIV' && ( editor.dom.hasClass( node, 'mceTemp' ) || editor.dom.hasClass( node, 'mceMediaCreditOuterTemp' ) ) ) {
			wrap = node;
		} else if ( node.nodeName === 'IMG' || node.nodeName === 'DT' || node.nodeName === 'A' ) {
			wrap = editor.dom.getParent( node, 'div.mceTemp' ) || editor.dom.getParent( node, 'div.mceMediaCreditOuterTemp' );
		}

		if ( wrap ) {
			if ( wrap.nextSibling ) {
				editor.selection.select( wrap.nextSibling );
			} else if ( wrap.previousSibling ) {
				editor.selection.select( wrap.previousSibling );
			} else {
				editor.selection.select( wrap.parentNode );
			}

			editor.selection.collapse( true );
			editor.dom.remove( wrap );
		} else {
			editor.dom.remove( node );
		}
		editor.nodeChanged();
		editor.undoManager.add(); 
	}

	editor.on( 'init', function() {
		var dom = editor.dom,
			captionClass = editor.getParam( 'wpeditimage_html5_captions' ) ? 'html5-captions' : 'html4-captions';

		dom.addClass( editor.getBody(), captionClass );

		// Add caption field to the default image dialog
		editor.on( 'wpLoadImageForm', function( event ) {
			if ( editor.getParam( 'wpeditimage_disable_captions' ) ) {
				return;
			}

			var captionField = {
				type: 'textbox',
				flex: 1,
				name: 'caption',
				minHeight: 60,
				multiline: true,
				scroll: true,
				label: 'Image caption'
			};

			event.data.splice( event.data.length - 1, 0, captionField );
		});

		// Fix caption parent width for images added from URL
		editor.on( 'wpNewImageRefresh', function( event ) {
			var parent, captionWidth;

			if ( parent = dom.getParent( event.node, 'dl.wp-caption' ) ) {
				if ( ! parent.style.width ) {
					captionWidth = parseInt( event.node.clientWidth, 10 ) + 10;
					captionWidth = captionWidth ? captionWidth + 'px' : '50%';
					dom.setStyle( parent, 'width', captionWidth );
				}
			}
		});

		editor.on( 'wpImageFormSubmit', function( event ) {
			var data = event.imgData.data,
				imgNode = event.imgData.node,
				caption = event.imgData.caption,
				captionId = '',
				captionAlign = '',
				captionWidth = '',
				wrap, parent, node, html, imgId;

			// Temp image id so we can find the node later
			data.id = '__wp-temp-img-id';
			// Cancel the original callback
			event.imgData.cancel = true;

			if ( ! data.style ) {
				data.style = null;
			}

			if ( ! data.src ) {
				// Delete the image and the caption
				if ( imgNode ) {
					if ( wrap = dom.getParent( imgNode, 'div.mceTemp' ) ) {
						dom.remove( wrap );
					} else if ( imgNode.parentNode.nodeName === 'A' ) {
						dom.remove( imgNode.parentNode );
					} else {
						dom.remove( imgNode );
					}

					editor.nodeChanged();
				}
				return;
			}

			if ( caption ) {
				caption = caption.replace( /\r\n|\r/g, '\n' ).replace( /<\/?[a-zA-Z0-9]+( [^<>]+)?>/g, function( a ) {
					// No line breaks inside HTML tags
					return a.replace( /[\r\n\t]+/, ' ' );
				});

				// Convert remaining line breaks to <br>
				caption = caption.replace( /(<br[^>]*>)\s*\n\s*/g, '$1' ).replace( /\s*\n\s*/g, '<br />' );
				caption = verifyHTML( caption ); 
			}

			if ( ! imgNode ) {
				// New image inserted
				html = dom.createHTML( 'img', data );

				if ( caption ) {
					node = editor.selection.getNode();

					if ( data.width ) {
						captionWidth = parseInt( data.width, 10 );

						if ( ! editor.getParam( 'wpeditimage_html5_captions' ) ) {
							captionWidth += 10;
						}

						captionWidth = ' style="width: ' + captionWidth + 'px"';
					}

					html = '<dl class="wp-caption alignnone"' + captionWidth + '>' +
						'<dt class="wp-caption-dt">'+ html +'</dt><dd class="wp-caption-dd">'+ caption +'</dd></dl>';

					if ( node.nodeName === 'P' ) {
						parent = node;
					} else {
						parent = dom.getParent( node, 'p' );
					}

					if ( parent && parent.nodeName === 'P' ) {
						wrap = dom.create( 'div', { 'class': 'mceTemp' }, html );
						dom.insertAfter( wrap, parent );
						editor.selection.select( wrap );
						editor.nodeChanged();

						if ( dom.isEmpty( parent ) ) {
							dom.remove( parent );
						}
					} else {
						editor.selection.setContent( '<div class="mceTemp">' + html + '</div>' );
					}
				} else {
					editor.selection.setContent( html );
				}
			} else {
				// Edit existing image

				// Store the original image id if any
				imgId = imgNode.id || null;
				// Update the image node
				dom.setAttribs( imgNode, data );
				wrap = dom.getParent( imgNode, 'dl.wp-caption' );

				if ( caption ) {
					if ( wrap ) {
						if ( parent = dom.select( 'dd.wp-caption-dd', wrap )[0] ) {
							parent.innerHTML = caption;
						}
					} else {
						if ( imgNode.className ) {
							captionId = imgNode.className.match( /wp-image-([0-9]+)/ );
							captionAlign = imgNode.className.match( /align(left|right|center|none)/ );
						}

						if ( captionAlign ) {
							captionAlign = captionAlign[0];
							imgNode.className = imgNode.className.replace( /align(left|right|center|none)/g, '' );
						} else {
							captionAlign = 'alignnone';
						}

						captionAlign = ' class="wp-caption ' + captionAlign + '"';

						if ( captionId ) {
							captionId = ' id="attachment_' + captionId[1] + '"';
						}

						captionWidth = data.width || imgNode.clientWidth;

						if ( captionWidth ) {
							captionWidth = parseInt( captionWidth, 10 );

							if ( ! editor.getParam( 'wpeditimage_html5_captions' ) ) {
								captionWidth += 10;
							}

							captionWidth = ' style="width: '+ captionWidth +'px"';
						}

						if ( imgNode.parentNode && imgNode.parentNode.nodeName === 'A' ) {
							//html = dom.getOuterHTML( imgNode.parentNode );
							node = imgNode.parentNode;
						} else {
							//html = dom.getOuterHTML( imgNode );
							node = imgNode;
						}

						html = '<dl ' + captionId + captionAlign + captionWidth + '>' +
                            '<dt class="wp-caption-dt"></dt><dd class="wp-caption-dd">'+ caption +'</dd></dl>'; 
	 
	                        wrap = dom.create( 'div', { 'class': 'mceTemp' }, html ); 
	 
	                        if ( parent = dom.getParent( node, 'p' ) ) { 
	                            parent.parentNode.insertBefore( wrap, parent ); 


							if ( dom.isEmpty( parent ) ) {
								dom.remove( parent );
							}
						} else {
                            node.parentNode.insertBefore( wrap, node ); 
                        } 
 
                        editor.$( wrap ).find( 'dt.wp-caption-dt' ).append( node ); 
					}
				} else {
					if ( wrap ) {
						// Remove the caption wrapper and place the image in new paragraph
						if ( imgNode.parentNode.nodeName === 'A' ) {
							html = dom.getOuterHTML( imgNode.parentNode );
						} else {
							html = dom.getOuterHTML( imgNode );
						}

						parent = dom.create( 'p', {}, html );
						dom.insertAfter( parent, wrap.parentNode );
						editor.selection.select( parent );
						editor.nodeChanged();
						dom.remove( wrap.parentNode );
					}
				}
			}

			imgNode = dom.get('__wp-temp-img-id');
			dom.setAttrib( imgNode, 'id', imgId );
			event.imgData.node = imgNode;
		});

		editor.on( 'wpLoadImageData', function( event ) {
			var parent,
				data = event.imgData.data,
				imgNode = event.imgData.node;

			if ( parent = dom.getParent( imgNode, 'dl.wp-caption' ) ) {
				parent = dom.select( 'dd.wp-caption-dd', parent )[0];

				if ( parent ) {
					data.caption = editor.serializer.serialize( parent )
						.replace( /<br[^>]*>/g, '$&\n' ).replace( /^<p>/, '' ).replace( /<\/p>$/, '' );
				}
			}
		});

		dom.bind( editor.getDoc(), 'dragstart', function( event ) {
			var node = editor.selection.getNode();

			// Prevent dragging images out of the caption elements
			if ( node.nodeName === 'IMG' && dom.getParent( node, '.wp-caption' ) ) {
				event.preventDefault();
			}
		});

		// Prevent IE11 from making dl.wp-caption resizable
		if ( tinymce.Env.ie && tinymce.Env.ie > 10 ) {
			// The 'mscontrolselect' event is supported only in IE11+
			dom.bind( editor.getBody(), 'mscontrolselect', function( event ) {
				if ( event.target.nodeName === 'IMG' && dom.getParent( event.target, '.wp-caption' ) ) {
					// Hide the thick border with resize handles around dl.wp-caption
					editor.getBody().focus(); // :(
				} else if ( event.target.nodeName === 'DL' && dom.hasClass( event.target, 'wp-caption' ) ) {
					// Trigger the thick border with resize handles...
					// This will make the caption text editable.
					event.target.focus();
				}
			});
		}
	});

	editor.on( 'ObjectResized', function( event ) {
        var node = event.target, mediaCreditNode;
        	
	    if ( node.nodeName === 'IMG' ) {
	            editor.undoManager.transact( function() {
				    var parent, width,
						node = event.target,
						dom = editor.dom;
				
					if ( node.nodeName === 'IMG' ) {
						node.className = node.className.replace( /\bsize-[^ ]+/, '' );
				
						if ( parent = dom.getParent( node, '.wp-caption' ) ) {
							width = event.width || dom.getAttrib( node, 'width' );
				
							if ( width ) {
								width = parseInt( width, 10 );
				
								if ( ! editor.getParam( 'wpeditimage_html5_captions' ) ) {
									width += 10;
								}
				
								dom.setStyle( parent, 'width', width + 'px' );
							}
						}
					}
				});
	    }
	});
	
	editor.on( 'BeforeExecCommand', function( event ) {
		var node, p, DL, align, replacement,
			cmd = event.command,
			dom = editor.dom,
			mediaCreditNode;

		if ( cmd === 'mceInsertContent' ) {
			// When inserting content, if the caret is inside a caption create new paragraph under
			// and move the caret there
			if ( (node = dom.getParent( editor.selection.getNode(), 'div.mceTemp' )) ||
				 (node = dom.getParent( editor.selection.getNode(), 'div.mceMediaCreditOuterTemp' )) ) {
				p = dom.create( 'p' );
				dom.insertAfter( p, node );
				editor.selection.setCursorLocation( p, 0 );
				editor.nodeChanged();

			}
		} else if ( cmd === 'JustifyLeft' || cmd === 'JustifyRight' || cmd === 'JustifyCenter' || cmd === 'wpAlignNone' ) {
			node = editor.selection.getNode();
            align = 'align' + cmd.slice( 7 ).toLowerCase(); 
            DL = editor.dom.getParent( node, '.wp-caption' ); 
 
            if ( node.nodeName !== 'IMG' && ! DL ) { 
                return; 
            } 
 
            node = DL || node; 
    		mediaCreditNode = dom.getNext( dom.getParent(node, 'a' ), '.mceMediaCreditTemp' ) || dom.select( '.mceMediaCreditTemp', node );			

            if ( editor.dom.hasClass( node, align ) ) { 
                replacement = ' alignnone'; 
            } else { 
                replacement = ' ' + align; 
            } 
 
            node.className = node.className.replace( / ?align(left|center|right|none)/g, '' ) + replacement; 
			
            // set alignment for nested media-credit if necessary
			if (mediaCreditNode) {
				dom.setAttrib( mediaCreditNode, 'data-media-credit-align', align );
				
				var parent = dom.getParent(mediaCreditNode, 'div.mceMediaCreditOuterTemp');
				if (parent) {
					// also update container alignment for visual presentation in stand-alone case
					parent.className = parent.className.replace( / ?align(left|center|right|none)/g, '' ) + replacement; 
				}
			}
            
            editor.nodeChanged(); 
            event.preventDefault(); 
 
            if ( floatingToolbar ) { 
                floatingToolbar.reposition(); 
			}
            
            editor.fire( 'ExecCommand', { 
                command: cmd, 
                ui: event.ui, 
                value: event.value 
            } ); 
		}
	});

	editor.on( 'keydown', function( event ) {
		var node, wrap, P, spacer,
			selection = editor.selection,
			keyCode = event.keyCode,
			dom = editor.dom;
			VK = tinymce.util.VK;
		
		if ( keyCode === VK.ENTER ) {
			// When pressing Enter inside a caption move the caret to a new parapraph under it
			node = selection.getNode();
			wrap = dom.getParent( node, 'div.mceTemp' );

			if ( !wrap ) {
				wrap = dom.getParent( node, 'div.mceMediaCreditOuterTemp' );
			}
			
			if ( wrap ) {
				dom.events.cancel( event ); // Doesn't cancel all :(

				// Remove any extra dt and dd cleated on pressing Enter...
				tinymce.each( dom.select( 'dt, dd', wrap ), function( element ) {
					if ( dom.isEmpty( element ) ) {
						dom.remove( element );
					}
				});

				spacer = tinymce.Env.ie && tinymce.Env.ie < 11 ? '' : '<br data-mce-bogus="1" />';
				P = dom.create( 'p', null, spacer );

				if ( node.nodeName === 'DD' ) {
					dom.insertAfter( P, wrap );
				} else {
					wrap.parentNode.insertBefore( P, wrap );
				}

				editor.nodeChanged();
				selection.setCursorLocation( P, 0 );
			}
		} else if ( keyCode === VK.DELETE || keyCode === VK.BACKSPACE ) {
			node = selection.getNode();

			if ( node.nodeName === 'DIV' && (dom.hasClass( node, 'mceTemp' ) || dom.hasClass( node, 'mceMediaCreditOuterTemp' ))) {
				wrap = node;
			} else if ( node.nodeName === 'IMG' || node.nodeName === 'DT' || node.nodeName === 'A' ) {
				wrap = dom.getParent( node, 'div.mceTemp' ) || editor.dom.getParent( node, 'div.mceMediaCreditOuterTemp' );
			}

			if ( wrap ) {
				dom.events.cancel( event );
				removeImage( node );
				return false;
			}
		}
	});

	// After undo/redo FF seems to set the image height very slowly when it is set to 'auto' in the CSS. 
	// This causes image.getBoundingClientRect() to return wrong values and the resize handles are shown in wrong places. 
	// Collapse the selection to remove the resize handles. 
	if ( tinymce.Env.gecko ) { 
		editor.on( 'undo redo', function() { 
			if ( editor.selection.getNode().nodeName === 'IMG' ) { 
				editor.selection.collapse(); 
			} 
		}); 
	}
	
	editor.wpSetImgCaption = function( content ) {
		return parseShortcode( content );
	};

	editor.wpGetImgCaption = function( content ) {
		return getShortcode( content );
	};

	editor.on( 'BeforeSetContent', function( event ) {
		if ( event.format !== 'raw' ) {
			event.content = editor.wpSetImgCaption( event.content );
		}
	});

	editor.on( 'PostProcess', function( event ) {
		if ( event.get ) {
			event.content = editor.wpGetImgCaption( event.content );
		}
	});

	return {
		_do_shcode: parseShortcode,
		_get_shcode: getShortcode
	};
});