// Functions for the regenerating of images
var rahe;
if ( ! rahe ) {
	rahe = {};
} else if ( typeof rahe !== 'object' ) {
	throw new Error( 'rahe already exists and not an object' );
}

if ( ! rahe.sis ) {
	rahe.sis = {};
} else if ( typeof rahe.sis !== 'object' ) {
	throw new Error( 'rahe.sis already exists and not an object' );
}

rahe.sis.featured = {
	messageZone: '',
	parent: '',
	element: '',
	init: function () {
		this.element = jQuery( '#sis_featured_regenerate' );
		this.parent = this.element.closest( '.inside' );
		this.nonce = this.element.attr( 'data-nonce' );
		this.id = jQuery( '#post_ID' ).val();

		this.parent.on( 'click', '#sis_featured_regenerate', function ( e ) {
			e.preventDefault();
			rahe.sis.featured.regenItem();
		} );
	},
	setMessage: function ( msg ) {
		// Display the message
		this.parent
			.find( '.sis_message' )
			.html( '<p>' + msg + '</p>' )
			.show();
	},
	regenItem: function () {
		var self = this;

		jQuery.ajax( {
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			cache: false,
			data: {
				action: 'sis_rebuild_featured',
				id: this.id,
				nonce: this.nonce,
			},
			beforeSend: function () {
				self.parent.find( '.spinner' ).addClass( 'is-active' );
				self.parent
					.find( '#sis_featured_regenerate' )
					.attr( 'disabled', 'disabled' );
			},
			success: function ( r ) {
				var message = '';
				// Check if error or a message in response
				if ( ! r.time || r.error || typeof r !== 'object' ) {
					message =
						typeof r !== 'object'
							? ( message = window.sis.phpError )
							: r.error;
				} else {
					message = window.sis.soloRegenerated.replace(
						'%s',
						r.time
					);
				}
				self.setMessage( message );

				self.parent.find( '.spinner' ).removeClass( 'is-active' );
				self.parent
					.find( '#sis_featured_regenerate' )
					.removeAttr( 'disabled' );
			},
		} );
	},
};

jQuery( function () {
	rahe.sis.featured.init();
} );
