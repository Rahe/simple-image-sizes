jQuery( function(){
	jQuery( '.sis-regenerate-one' ).click( function( e ) {
		e.preventDefault();
		var regenerate = {
			list : '',
			percent : '' ,
			el : '',
			id : '',
			messageZone : '',
			init: function( el ) {
				this.el = jQuery( el );
				id = jQuery( el ).attr( 'id' );
				this.id = id.replace( 'post-', '' );
				
				this.list = { 'id' : this.id, 'title' : 'titre' };
				
				if( this.el.find('.title em').size() == 0 )
					this.el.find('.title strong').after('<em/>');
				this.messageZone = this.el.find('.title em');
				
				if( !this.el.hasClass( 'ajaxing' ) )
					this.regenItem();
			},
			setMessage : function( msg ) {
				// Display the message
				this.messageZone.html( ' - '+ msg ).addClass( 'updated' ).addClass( 'fade' ).show();
			},
			regenItem : function( ) {
				var _self = this;
		
				jQuery.ajax( {
					url: sis.ajaxUrl,
					type: "POST",
					dataType: 'json',
					data: "action=sis_ajax_thumbnail_rebuild&do=regen&id=" + this.list.id,
					beforeSend : function() {
						_self.el.fadeTo( 'fast' ,'0.2' ).addClass('ajaxing');
					},
					success: function( r ) {
						var message ='';
						// Check if error or a message in response
						if( ( !r.src || !r.time ) || r.error || typeof r !== 'object' ) {
							if( typeof r !== 'object' )
								message = sis.phpError;
							else 
								message = r.error
						} else {
							message = sis.soloRegenerated.replace( '%s', r.time );
						}
						_self.setMessage( message );
						_self.el.fadeTo( 'fast' ,'1' ).removeClass('ajaxing');
					}
				});
		
			}
		}
		regenerate.init( jQuery( this ).closest( 'tr' ) );
	});
});