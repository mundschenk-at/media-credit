(function( $, wp, _ ) {
	var MediaCreditImagePropertiesView, frame;

	if ( ! wp.media.events ) {
		return;
	}

	function addMediaCreditView( view ) {
		var mediaCreditView = new MediaCreditImagePropertiesView( { model: view.model } );

		view.on( 'post-render', function() {
			view.views.insert( view.$el.find('.advanced-image'), mediaCreditView.render().el );
		} );
	}

	wp.media.events.on( 'editor:image-edit', function( options ) {	
		if (options.metadata.mediaCreditName === '' && options.metadata.mediaCreditID !== '') {
			options.metadata.mediaCreditName = $mediaCredit['id'][options.metadata.mediaCreditID];
		}
	} );

	wp.media.events.on( 'editor:frame-create', function( options ) {
		frame = options.frame;
		frame.on( 'content:render:image-details', addMediaCreditView );
	} );

	wp.media.events.on( 'editor:image-update', function( options ) {
		var editor = options.editor,
			dom = editor.dom,
			image  = options.image,
			model = frame.content.get().model,
			align = model.get('align'),
			width = model.get('width'),
			credit,
			mediaCreditName = model.get('mediaCreditName'),
			mediaCreditID = model.get('mediaCreditID'),
			mediaCreditBlock,
			mediaCreditWrapper;

		/* Extract mediaCreditBlock in visual editor */
		if ( image.parentNode && image.parentNode.nodeName === 'A' ) {
			mediaCreditBlock = dom.getNext( image.parentNode, '.mceMediaCreditTemp' );			
		} else {
			mediaCreditBlock = dom.getNext( image, '.mceMediaCreditTemp' );;
		}
		
		if ($mediaCredit.id[mediaCreditID] !== mediaCreditName)
			mediaCreditID = '';

		credit = mediaCreditID ? ($mediaCredit.id[mediaCreditID] + $mediaCredit.separator + $mediaCredit.organization) : mediaCreditName;
		credit = credit.replace(/<[^>]+>(.*)<\/[^>]+>/g, '$1'); // basic sanitation
		
		if (mediaCreditBlock === null) {
			align = 'align' + ( align || 'none' ); 
			
			// create new representation for media-credit
			mediaCreditBlock = dom.create ('span', {
												'class': 'mceMediaCreditTemp mceNonEditable',
												'data-media-credit-id': mediaCreditID,
												'data-media-credit-name': mediaCreditName,
												'data-media-credit-align': align
											}, credit);
			
			if ( image.parentNode && image.parentNode.nodeName === 'A' ) {
				dom.insertAfter( mediaCreditBlock, image.parentNode );
			} else {
				dom.insertAfter( mediaCreditBlock, image );
			}
			
			if ( !dom.getParent( mediaCreditBlock, 'dl.wp-caption' ) ) {
				// standalone [media-credit]
				mediaCreditWrapper = dom.create( 'div', { 'class': 'mceMediaCreditOuterTemp ' + align,
					  									  'style': 'width: ' + (parseInt(width) + 10) + 'px' } );
				
				// swap existing parent with our new wrapper
				dom.insertAfter(mediaCreditWrapper, mediaCreditBlock.parentNode);
				dom.add(mediaCreditWrapper, mediaCreditBlock.parentNode);
				dom.remove(mediaCreditBlock.parentNode, true);
			}

		}
		
		dom.setAttrib(mediaCreditBlock, 'data-media-credit-name', mediaCreditName);
		dom.setAttrib(mediaCreditBlock, 'data-media-credit-id', mediaCreditID);
		dom.setHTML(mediaCreditBlock, credit);
	} );

	MediaCreditImagePropertiesView = wp.Backbone.View.extend( {
		className: 'media-credit-image-properties',
		template: wp.media.template('media-credit-image-properties'),

		initialize: function() {
			wp.Backbone.View.prototype.initialize.apply( this, arguments );
		},

		prepare: function() {
			var data = this.model.toJSON();
			//data.mediaCreditName = "foobar";
			//data.mediaCreditID = "-1";
			return data;
		},

		render: function() {
			wp.Backbone.View.prototype.render.apply( this, arguments );
			return this;
		}
	} );

})( jQuery, wp, _ );
