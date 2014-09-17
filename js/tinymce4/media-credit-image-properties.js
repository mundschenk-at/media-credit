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
			mediaCreditName = model.get('mediaCreditName'),
			mediaCreditID = model.get('mediaCreditID'),
			mediaCreditBlock, link;

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
