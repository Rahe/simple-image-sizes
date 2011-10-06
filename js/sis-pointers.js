jQuery(function() {
	if( getUserSetting('sis_medias_config_pointer') == false ) {
		jQuery('#menu-settings').pointer({
			content: sis_pointer.pointerMediasConfig,
			position: {
				my: 'right top',
				at: 'left top',
				offset: '0 -2'
			},
			arrow: {
				edge: 'left',
				align: 'top',
				offset: 10
			},
			close: function() {
				setUserSetting( 'sis_medias_config_pointer', true );
			}
		}).pointer('open');
	}
});