/**
 * Image size model
 */
'use strict';
rahe.sis.models.Field = Backbone.Model.extend({
    defaults: {
        width: 0,
        height: 0,
        name: '',
        crop: false,
        show_insertion: false
    }
});