/**
 * Image size model
 */
'use strict';

// Deps
var Backbone = require( 'backbone' );

module.exports = Backbone.Model.extend({
    defaults: {
        width: 0,
        height: 0,
        name: '',
        crop: false,
        show_insertion: false
    }
});