import 'jquery';
// Functions for the regenerating of images
var sis;
if ( ! sis ) {
	sis = {};
} else if ( typeof sis !== 'object' ) {
	throw new Error( 'sis already exists and not an object' );
}

jQuery( function () {
	'use strict';
	var bdy = jQuery( document.body ),
		SISAttachRegenerate = null,
		sis_obj = null;
	// Add action dinamycally
	jQuery( 'select[name="action"], select[name="action2"]' ).append(
		jQuery( '<option/>' )
			.attr( 'value', 'sis-regenerate' )
			.text( window.sis.regenerate )
	);

	// Regenerate one element
	bdy.on( 'click', '.sis-regenerate-one', function ( e ) {
		e.preventDefault();
		sis_obj = new SISAttachRegenerate( this );
	} );

	// On bulk actions
	jQuery( '#doaction, #doaction2' ).on( 'click', function ( e ) {
		if (
			jQuery( this ).parent().find( 'select' ).val() === 'sis-regenerate'
		) {
			// Get checked checkbocxes
			var els = jQuery(
				'#the-list .check-column input[type="checkbox"]:checked'
			)
				.closest( 'tr' )
				.find( '.sis-regenerate-one' );

			// Check there is any elements selected
			if ( els.length > 0 ) {
				// Stop default action
				e.preventDefault();

				// Make all the selected elements
				els.each( function ( i, el ) {
					sis_obj = new SISAttachRegenerate( this );
				} );
			}
		}
	} );

	// Function for regenerating the elements
	SISAttachRegenerate = function ( el ) {
		var regenerate = {
			list: {},
			parent: null,
			el: null,
			id: null,
			messageZone: '',
			init: function ( el ) {
				this.el = el;
				this.parent = el.closest( 'tr' );
				this.id = this.el.data( 'id' );
				this.list = { id: this.id, title: '' };
				this.messageZone = this.parent.find( '.title em' );

				if ( this.parent.find( '.title em' ).length === 0 ) {
					this.parent.find( '.title strong' ).after( '<em/>' );
				}

				this.messageZone = this.parent.find( '.title em' );

				if ( ! this.parent.hasClass( 'ajaxing' ) ) {
					this.regenItem();
				}
			},
			setMessage: function ( msg ) {
				// Display the message
				this.messageZone
					.html( ' - ' + msg )
					.addClass( 'updated' )
					.addClass( 'fade' )
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
						action: 'sis_rebuild_image',
						id: this.list.id,
						nonce: window.sis.regen_one,
					},
					beforeSend: function () {
						self.parent
							.addClass( 'ajaxing' )
							.find( '.sis-regenerate-one' )
							.hide()
							.end()
							.find( '.spinner' )
							.addClass( 'is-active' )
							.show();
						self.parent
							.find( 'a.sis-regenerate-one' )
							.closest( 'tr' )
							.fadeTo( 'fast', '0.3' );
					},
					success: function ( r ) {
						var message = '';
						// Check if error or a message in response
						if (
							! r.src ||
							! r.time ||
							r.error ||
							typeof r !== 'object'
						) {
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
						self.parent
							.removeClass( 'ajaxing' )
							.find( '.sis-regenerate-one' )
							.show()
							.end()
							.find( '.spinner' )
							.removeClass( 'is-active' )
							.hide();
						self.parent
							.find( 'a.sis-regenerate-one' )
							.closest( 'tr' )
							.fadeTo( 'fast', '1' );
					},
				} );
			},
		};

		// Launch regeneration
		regenerate.init( jQuery( el ) );
	};
} );
