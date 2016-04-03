// Functions for the regenerating of images
var rahe;
if (!rahe) {
	rahe = {};
} else if (typeof rahe !== "object") {
	throw new Error('rahe already exists and not an object');
}

if (!rahe.sis) {
	rahe.sis = {};
} else if (typeof rahe.sis !== "object") {
	throw new Error('rahe.sis already exists and not an object');
}

rahe.sis.regenerate = {
	post_types: [],
	thumbnails: [],
	total: 0,
	cur: 0,
	timeScript: [],
	dateScript: '',
	percent: '',
	percentText: null,
	progress: null,
	messageZone: null,
	sisZone: null,
	time: null,
	timeZone: null,
	buttonRegenerate: null,
	errorZone: null,
	errorMessages: null,
	thumb: null,
	thumbImg: null,
	init: function () {
		this.sisZone = jQuery('.sis');
		this.percentText = jQuery('#sis_progress-percent');
		this.progress = jQuery('.progress');
		this.messageZone = this.sisZone.find(".regenerate_message");
		this.time = this.sisZone.find(".time");
		this.timeZone = this.time.find("p span.time_message");
		this.buttonRegenerate = jQuery("#ajax_thumbnail_rebuild");
		this.errorZone = jQuery('#error_messages');
		this.errorMessages = this.errorZone.find('ul.messages');
		this.thumb = this.sisZone.find('.thumb');
		this.thumbImg = this.sisZone.find('.thumb-img');
	},
	getThumbnails: function () {
		var self = this,
			inputs = jQuery('input.thumbnails:checked');

		// Get the checked thumbnails inputs
		if (inputs.length != jQuery('input.thumbnails[type="checkbox"]').length) {
			inputs.each(function (i) {
				self.thumbnails.push(this.value);
			});
		}
	},
	getPostTypes: function () {
		var self = this,
			inputs = jQuery('input.post_types:checked');

		// Get the checked post Types inputs
		if (inputs.length != jQuery('input.post_types[type="checkbox"]').length) {
			inputs.each(function () {
				self.post_types.push(this.value);
			});
		}
	},
	setMessage: function (msg) {
		// Display the message
		this.messageZone.html("<p>" + msg + "</p>").addClass('updated').addClass('fade').show();
	},
	setTimeMessage: function (msg) {
		this.timeZone.html(msg);
	},
	checkStartRegenerating: function () {
		if (jQuery('.notSaved').size() > 0) {
			var confirmation = confirm(sis.notSaved);

			// Delete if ok else not delete
			if (confirmation == true) {
				this.startRegenerating();
			} else {
				return false;
			}
		} else {
			this.startRegenerating();
		}
	},
	startRegenerating: function () {
		var self = this,
			wp_nonce = jQuery('input.getList').val();

		// Get the humbnails and post types
		self.getThumbnails();
		self.getPostTypes();

		this.dateScript = new Date();
		// Start ajax
		jQuery.ajax({
			url: ajaxurl,
			type: "POST",
			dataType: 'json',
			data: {
				action: 'sis_get_list',
				post_types: self.post_types,
				nonce: wp_nonce
			},
			beforeSend: function () {

				// Disable the button
				self.buttonRegenerate.attr("disabled", true);
				// Display the message
				self.setMessage(sis.reading);

			},
			success: function (r) {

				if (typeof r !== 'object') {
					self.reInit();
					self.setMessage(sis.phpError);
					return false;
				}

				self.time.show();

				// Eval the response
				self.total = r.total;

				// Set the current to 0
				self.curr = 0;

				// Display the progress Bar
				self.progress.show().parent().show();

				// Start Regenerating
				self.regenItem();
			}
		});
	},
	regenItem: function () {
		var self = this,
			wp_nonce = jQuery('input.regen').val();

		// If the list is empty display the message of emptyness and reinitialize the form
		if (this.total == 0 || _.isUndefined(this.total)) {
			this.reInit();
			this.setMessage(sis.noMedia);
			return false;
		}

		// If we have finished the regeneration display message and init again
		if (this.curr >= this.total) {
			var now = new Date();
			this.reInit();
			this.setMessage(sis.done + this.curr + ' ' + sis.messageRegenerated + sis.startedAt + ' ' + this.dateScript.getHours() + ":" + this.dateScript.getMinutes() + ":" + this.dateScript.getSeconds() + sis.finishedAt + ' ' + now.getHours() + ":" + now.getMinutes() + ":" + now.getSeconds());
			return;
		}

		// Set the message of current image regenerating
		this.setMessage(sis.regenerating + ( this.curr + 1 ) + sis.of + this.total);
		this.setMessage(sis.regenerating + ( this.curr + 1 ) + sis.of + this.total);

		jQuery.ajax({
			url: ajaxurl,
			type: "POST",
			dataType: 'json',
			data: {
				action: 'sis_rebuild_images',
				offset: this.curr,
				thumbnails: this.thumbnails,
				nonce: wp_nonce
			},
			beforeSend: function () {
				// Calculate the percentage of regeneration
				self.percent = ( self.curr / self.total ) * 100;

				// Change the progression
				self.progress.find('.bar').width(self.percent + '%')
					.find('.percent').html(self.percent + '%');

				// Change the text of progression
				self.percentText.removeClass('hidden').html(Math.round(self.percent) + "%");
			},
			success: function (r) {
				// Check if error or a message in response
				if (( !r.src || !r.time ) || r.error || typeof r !== 'object') {
					var message = '';
					if (typeof r !== 'object') {
						message = sis.phpError;
					} else {
						message = r.error
					}

					self.errorZone.addClass('error message');
					self.errorMessages.prepend('<li>' + message + '</li>');
				} else {

					// Append a message if needed
					if (r.message) {
						self.time.find('ul.messages').prepend('<li>' + r.message + '</li>');
					}

					// Display the image
					self.thumb.show();

					// Change his attribute
					self.thumbImg.attr("src", r.src);

					// Actual time
					var dateEnd = new Date(),
						curDate = new Date(),
						num = 0,
						sum = 0,
						i = 0,
						ave = 0,
						time = '';

					// Add the regenerating time to the array
					self.timeScript.push(parseFloat(r.time.replace(',', '.'), 10));

					// Get the number of elements in array
					num = self.timeScript.length;

					// Make the sum of the times
					for (i; i < num; i++) {
						sum += self.timeScript[i];
					}

					// Make the average value of the regenerating time
					ave = sum / num;

					// Round the value in miliseconds and add 25% or error
					t = Math.round(( ( ave * self.total ) * 1000 ));

					// Set the predicted time
					dateEnd.setTime(self.dateScript.getTime() + t);

					// Get the difference between the two dates
					time = self.s2t(Math.abs(( dateEnd.getTime() - curDate.getTime() )) / 1000);

					// Set the message in the notice box
					self.setTimeMessage(dateEnd.getHours() + ":" + dateEnd.getMinutes() + ":" + dateEnd.getSeconds() + sis.or + time + sis.beforeEnd);
				}

				// Inscrease the counter and regene the next item
				self.curr++;
				self.regenItem();
			}
		});

	},
	s2t: function (secs) {
		var secs = secs % 86400,
			t = new Date(1970, 0, 1),
			s = 0;

		t.setSeconds(secs);
		s = t.toTimeString().substr(0, 8);
		if (secs > 86399) {
			s = Math.floor(( t - Date.parse("1/1/70") ) / 3600000) + s.substr(2);
		}
		return s;
	},
	reInit: function () {
		// Re initilize the form
		this.buttonRegenerate.removeAttr("disabled");
		this.thumb.hide();
		this.progress.hide();
		this.percentText.addClass('hidden');
	}
};

rahe.sis.sizes = {
	i: 0,
	add: function (e, el) {
		e.preventDefault();

		// Create the template
		var elTr = rahe.sis.template('new_size');
		elTr = elTr({
			size_id: this.i,
			validate: sis.validate
		});

		// Add the form for editing
		jQuery(el).closest('tr').before(elTr);

		// Inscrease the identifier
		this.i++;
	},
	register: function (e, el) {
		// Stop propagation
		e.preventDefault();

		// Get name and id
		var name = jQuery(el).closest('tr').children('th').find('input').val(),
			id = jQuery(el).closest('tr').children('th').find('input').attr('id'),

		// Get the number of elements with this name
			checkPresent = jQuery(el).closest('tbody').find('input[value="' + name + '"]').length;

		// Check if not basic size or already present, display message
		if (name == 'thumbnail' || name == "medium" || name == "large") {
			alert(sis.notOriginal);
			return false;
		} else if (checkPresent != 0) {
			alert(sis.alreadyPresent);
			return false;
		}

		var row = rahe.sis.template('new_size_row');

		row = row({
			size: sis.size,
			size_name: name,
			maximumWidth: sis.maximumWidth,
			maximumHeight: sis.maximumHeight,
			customName: sis.customName,
			crop: sis.crop,
			crop_positions: sis.crop_positions,
			show: sis.show,
			deleteImage: sis.deleteImage,
			validateButton: sis.validateButton
		});

		// Add the row to the current list
		jQuery('#' + id).closest('tr').html(row);
	},
	deleteSize: function (e, el) {
		e.preventDefault();
		// Check if user want to delete or not
		var confirmation = confirm(sis.confirmDelete);

		// Delete if ok else not delete
		if (confirmation == true) {
			// Remove from the list and the array
			jQuery(el).closest('tr').remove();
			this.ajaxUnregister(el);
		}
	},
	getPhp: function (e, el) {
		e.preventDefault();
		// Get parent element
		var parent = jQuery(el).closest('tr');

		jQuery.ajax({
			url: ajaxurl,
			type: "POST",
			data: {action: "sis_get_sizes"},
			beforeSend: function () {
				// Remove classes of status
				parent.removeClass('addPending');
				parent.addClass('addPending');
			},
			success: function (result) {
				// Add the classes for the status
				jQuery('#get_php').nextAll('code').html('<br />' + result).show().css({'display': 'block'});
				parent.removeClass('addPending');
			}
		});
	},
	ajaxRegister: function (e, el) {
		e.preventDefault();

		// Get the vars
		var self = this,
			parentTable = jQuery(el).closest('table'),
			timer,
			wp_nonce = jQuery('.addSize').val(),
			parent = jQuery(el).closest('tr'),
			n = parent.find('input[name="image_name"]').val(),
			c = parent.find('select.crop').val(),
			s = parent.find('input.show').val(),
			cn = parent.find('input.n').val(),
			h = 0,
			w = 0;
		s = ( s == false || s == undefined ) ? false : true;
		w = parseInt(parent.find('input.w').val());
		h = parseInt(parent.find('input.h').val());

		if (!parentTable.hasClass('ajaxing')) {
			jQuery.ajax({
				url: ajaxurl,
				type: "POST",
				dataType: 'json',
				data: {
					action: "sis_add_size",
					width: w,
					height: h,
					crop: c,
					name: n,
					show: s,
					customName: cn,
					nonce: wp_nonce
				},
				beforeSend: function () {
					// Remove status and set pending
					parent.removeClass();
					parent.addClass('addPending');
					parentTable.addClass('ajaxing');
				},
				success: function (result) {
					// Set basic class and remove pending
					var classTr = '';
					parent.removeClass();
					parentTable.removeClass('ajaxing');

					// Check the result for the different messages
					if (result == 0) {
						classTr = 'errorAdding';
					} else if (result == 2) {
						classTr = 'notChangedAdding';

						// add/update to the array with the status class
						self.addToArray(n, w, h, c, classTr);
					} else {
						classTr = 'successAdding';

						// add/update to the array with the status class
						self.addToArray(n, w, h, c, classTr);
					}

					// Add the new sizes values for checking of changed or not
					parent.find('input.h').attr({base_h: h});
					parent.find('input.w').attr({base_w: w});
					parent.find('select.c').attr({base_c: c});
					parent.find('input.s').attr({base_s: s});

					// Add the generated class
					parent.addClass(classTr);
					parent.find('td').removeClass("notSaved");

					// Change the button text
					parent.find('.add_size').removeClass('validate_size').hide().children('.ui-button-text').text(sis.update);

					clearTimeout(timer);
					// Remove classes after 3 seconds
					timer = setTimeout(function () {
						parent.removeClass('errorAdding notChangedAdding successAdding');
					}, 2 * 1000);
				}
			});
		}
	},
	ajaxUnregister: function (el) {
		// Get name and self object
		var self = this,
			n = jQuery(el).closest('tr').find('input[name="image_name"]').val(),
			wp_nonce = jQuery(el).closest('tr').find('input.deleteSize').val();

		// Make the ajax call
		jQuery.ajax({
			url: ajaxurl,
			type: "POST",
			data: {action: "sis_remove_size", name: n, nonce: wp_nonce},
			success: function (result) {
				self.removeFromArray(el);
			}
		});
	},
	addToArray: function (n, w, h, c, s) {
		// Get the row for editing or updating
		var testRow = jQuery('#sis-' + n),
			newRow = '',
			timer;

		// Get the right newRow, updating or adding ?
		if (testRow.length != 0) {
			newRow = testRow.closest('tr');
		} else {
			newRow = jQuery('#sis-regen .wrapper > table#sis_sizes > tbody > tr:first').clone().attr('id', 'sis-' + n);
		}

		c = !_.isUndefined(sis.crop_positions[c]) ? sis.crop_positions[c] : sis.fl;

		// Set the datas with the given datas
		newRow.find('th > label').attr('for', n)
			.end()
			.find('input.thumbnails').val(n).attr('id', n)
			.end()
			.find('th:nth-child(2) > label').text(n)
			.end()
			.find('th:nth-child(3) > label').text(w + 'px')
			.end()
			.find('th:nth-child(4) > label').text(h + 'px')
			.end()
			.find('th:nth-child(5) > label').text(c);

		// If new then add the row
		if (testRow.length == 0) {
			newRow.appendTo('#sis-regen .wrapper > table#sis_sizes > tbody');
		}

		// Remove the previous status classes and add the status class
		newRow.removeClass('errorAdding notChangedAdding successAdding').addClass(s);

		clearTimeout(timer);
		// Remove the statuses classes
		timer = setTimeout(function () {
			newRow.removeClass('errorAdding notChangedAdding successAdding');
		}, 3 * 1000);
	},
	removeFromArray: function (el) {
		// get the name
		var n = jQuery(el).closest('tr').find('input[name=image_name]').val();

		// Remove the given name from the array
		jQuery('#sis-' + n).remove();
	},
	displayChange: function (el) {
		var el = jQuery(el),
			parent = el.closest('tr');

		// Check not new size
		if (parent.hasClass('new_size')) {
			return false;
		}

		var h_el = parent.find('input.h'),
			w_el = parent.find('input.w'),
			c_el = parent.find('select.c'),
			s_el = parent.find('input.s'),
			n_el = parent.find('input.n'),

			h = h_el.val(),
			w = w_el.val(),
			c = c_el.val(),
			s = s_el.val(),
			n = n_el.val(),

			base_h = h_el.attr('base_h'),
			base_w = w_el.attr('base_w'),
			base_c = c_el.attr('base_c'),
			base_s = s_el.attr('base_s'),
			base_n = n_el.attr('base_n');


		base_c = base_c == '0' ? false : true;
		base_s = base_s == '0' ? false : true;

		if (h != base_h || w != base_w || c != base_c || s != base_s || n != base_n) {
			el.closest('td').addClass('notSaved').find('.add_size').css('display', 'inline-block');
		} else {
			el.closest('td').removeClass('notSaved').find('.add_size').css('display', 'none');
		}
	}
};

rahe.sis.template = _.memoize(function (id) {
	var compiled,
		options = {
			evaluate: /<#([\s\S]+?)#>/g,
			interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
			escape: /\{\{([^\}]+?)\}\}(?!\})/g,
			variable: 'data'
		};

	return function (data) {
		compiled = compiled || _.template(jQuery('#sis-' + id).html(), null, options);
		return compiled(data);
	};
});

jQuery(function () {
	rahe.sis.regenerate.init();
	var bodyContent = jQuery('#wpbody-content');
	// Regeneration listener
	jQuery('#ajax_thumbnail_rebuild').click(function () {
		rahe.sis.regenerate.checkStartRegenerating();
	});

	// Add size button listener
	bodyContent.on('click', '#add_size', function (e) {
			rahe.sis.sizes.add(e, this);
		})

		// Registering a new size listener
		.on('click', '.add_size_name', function (e) {
			rahe.sis.sizes.register(e, this);
		})

		// Delete and Adding buttons
		.on('click', '.delete_size', function (e) {
			rahe.sis.sizes.deleteSize(e, this);
		})
		.on('click', '.add_size', function (e) {
			rahe.sis.sizes.ajaxRegister(e, this);
		})

		.on('click keyup change', '.h,.w,.c,.s,.n', function (e) {
			rahe.sis.sizes.displayChange(this);
		})

		// Seup the getphp
		.on('click', '#get_php', function (e) {
			rahe.sis.sizes.getPhp(e, this)
		});
	jQuery('#get_php').nextAll('code').hide();

	jQuery(".add_size").hide();
});

// Functions for the regenerating of images
var sis;
if (!sis) {
	sis = {};
} else if (typeof sis !== "object") {
	throw new Error('sis already exists and not an object');
}

jQuery(function () {
	'use strict';
	var bdy = jQuery(document.body), SISAttachRegenerate = null, sis_obj = null;
	// Add action dinamycally
	jQuery('select[name="action"], select[name="action2"]').append(
		jQuery('<option/>').attr('value', 'sis-regenerate').text(sis.regenerate)
	);

	// Regenerate one element
	bdy.on('click', '.sis-regenerate-one', function (e) {
		e.preventDefault();
		sis_obj = new SISAttachRegenerate(this);
	});

	// On bulk actions
	jQuery('#doaction, #doaction2').on('click', function (e) {
		if (jQuery(this).parent().find('select').val() === 'sis-regenerate') {
			// Get checked checkbocxes
			var els = jQuery('#the-list .check-column input[type="checkbox"]:checked').closest('tr').find('.sis-regenerate-one');

			// Check there is any elements selected
			if (els.length > 0) {

				// Stop default action
				e.preventDefault();

				// Make all the selected elements
				els.each(function (i, el) {
					sis_obj = new SISAttachRegenerate(this);
				});
			}
		}
	});

	// Function for regenerating the elements
	SISAttachRegenerate = function (el) {
		var regenerate = {
			list: {},
			parent: null,
			el: null,
			id: null,
			messageZone: '',
			init: function (el) {
				this.el = el;
				this.parent = el.closest('tr');
				this.id = this.el.data('id');
				this.list = {'id': this.id, 'title': ''};
				this.messageZone = this.parent.find('.title em');

				if (this.parent.find('.title em').length === 0) {
					this.parent.find('.title strong').after('<em/>');
				}

				this.messageZone = this.parent.find('.title em');

				if (!this.parent.hasClass('ajaxing')) {
					this.regenItem();
				}
			},
			setMessage: function (msg) {
				// Display the message
				this.messageZone.html(' - ' + msg).addClass('updated').addClass('fade').show();
			},
			regenItem: function () {
				var self = this;

				jQuery.ajax({
					url: ajaxurl,
					type: "POST",
					dataType: 'json',
					cache: false,
					data: {
						action: 'sis_rebuild_image',
						id: this.list.id,
						nonce: sis.regen_one
					},
					beforeSend: function () {
						self.parent.addClass('ajaxing').find('.sis-regenerate-one').hide().end().find('.spinner').addClass('is-active').show();
						self.parent.find('a.sis-regenerate-one').closest('tr').fadeTo('fast', '0.3');
					},
					success: function (r) {
						var message = '';
						// Check if error or a message in response
						if (( !r.src || !r.time ) || r.error || typeof r !== 'object') {
							message = typeof r !== 'object' ? message = sis.phpError : r.error;
						} else {
							message = sis.soloRegenerated.replace('%s', r.time);
						}
						self.setMessage(message);
						self.parent.removeClass('ajaxing').find('.sis-regenerate-one').show().end().find('.spinner').removeClass('is-active').hide();
						self.parent.find('a.sis-regenerate-one').closest('tr').fadeTo('fast', '1');
					}
				});
			}
		};

		// Launch regeneration
		regenerate.init(jQuery(el));
	};
});

// Functions for the regenerating of images
var rahe;
if( !rahe ) {
    rahe = {};
} else if( typeof rahe !== "object" ) {
    throw new Error( 'rahe already exists and not an object' );
}

if( !rahe.sis ) {
    rahe.sis = {};
} else if( typeof rahe.sis !== "object" ) {
    throw new Error( 'rahe.sis already exists and not an object' );
}

rahe.sis.featured = {
    messageZone : '',
    parent : '',
    element : '',
    init : function() {
        this.element = jQuery( '#sis_featured_regenerate' );
        this.parent = this.element.closest( '.inside' );
        this.nonce = this.element.attr( 'data-nonce' );
        this.id = jQuery( '#post_ID' ).val();

        this.parent.on( 'click', '#sis_featured_regenerate', function ( e ) {
            e.preventDefault();
            rahe.sis.featured.regenItem();
        } );
    },
    setMessage : function( msg ) {
        // Display the message
		this.parent.find( '.sis_message' ).html( "<p>" + msg + "</p>" ).show();
    },
    regenItem : function( ) {
    var self = this;

    jQuery.ajax( {
        url: ajaxurl,
        type: "POST",
        dataType: 'json',
        cache: false,
        data: {
            action : 'sis_rebuild_featured',
            id : this.id,
            nonce : this.nonce
        },
        beforeSend : function() {
            self.parent.find('.spinner').addClass( 'is-active');
            self.parent.find( '#sis_featured_regenerate' ).attr( 'disabled', 'disabled' );
        },
        success: function( r ) {
            var message ='';
            // Check if error or a message in response
            if( !r.time || r.error || typeof r !== 'object' ) {
                message = typeof r !== 'object' ? message = sis.phpError : r.error ;
            } else {
                message = sis.soloRegenerated.replace( '%s', r.time );
            }
			console.log(message);
            self.setMessage( message );

            self.parent.find('.spinner').removeClass( 'is-active');
            self.parent.find( '#sis_featured_regenerate' ).removeAttr( 'disabled' );
        }
    });
}
};

jQuery(function () {
    rahe.sis.featured.init();
} );

//# sourceMappingURL=app.js.map