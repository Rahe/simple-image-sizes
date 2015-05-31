rahe.sis.tools = {
    uniqid: function (prefix, more_entropy) {
        // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +    revised by: Kankrelune (http://www.webfaktory.info/)
        // %        note 1: Uses an internal counter (in php_js global) to avoid collision
        // *     example 1: uniqid();
        // *     returns 1: 'a30285b160c14'
        // *     example 2: uniqid('foo');
        // *     returns 2: 'fooa30285b1cd361'
        // *     example 3: uniqid('bar', true);
        // *     returns 3: 'bara20285b23dfd1.31879087'
        if (typeof prefix == 'undefined') {
            prefix = "";
        }

        var retId;
        var formatSeed = function (seed, reqWidth) {
            seed = parseInt(seed, 10).toString(16);
            // to hex str
            if (reqWidth < seed.length) {// so long we split
                return seed.slice(seed.length - reqWidth);
            }
            if (reqWidth > seed.length) {// so short we pad
                return Array(1 + ( reqWidth - seed.length )).join('0') + seed;
            }
            return seed;
        };

        // BEGIN REDUNDANT
        if (!this.php_js) {
            this.php_js = {};
        }
        // END REDUNDANT
        if (!this.php_js.uniqidSeed) {// init seed with big random int
            this.php_js.uniqidSeed = Math.floor(Math.random() * 0x75bcd15);
        }
        this.php_js.uniqidSeed++;

        retId = prefix;
        // start with prefix, add current milliseconds hex string
        retId += formatSeed(parseInt(new Date().getTime() / 1000, 10), 8);
        retId += formatSeed(this.php_js.uniqidSeed, 5);
        // add seed hex string
        if (more_entropy) {
            // for more entropy we add a float lower to 10
            retId += ( Math.random() * 10 ).toFixed(8).toString();
        }

        return retId;
    },
    selected: function (value, check) {
        "use strict";
        return rahe.sis.tools.checked_selected_helper(value, check, 'selected');
    },
    checked: function (value, check) {
        "use strict";
        return rahe.sis.tools.checked_selected_helper(value, check, 'checked');
    },
    checked_selected_helper: function (helper, current, type) {
        "use strict";
        return ( helper === current ) ? type + '="' + type + '"' : '';
    },
    remove_accents: function (value) {
        // thanks to https://gist.github.com/richardsweeney/5317392 for this code!
        var replace = {
            'ä': 'a',
            'à': 'a',
            'æ': 'a',
            'å': 'a',
            'ö': 'o',
            'ø': 'o',
            'é': 'e',
            'ë': 'e',
            'ü': 'u',
            'ó': 'o',
            'ő': 'o',
            'ú': 'u',
            'è': 'e',
            'á': 'a',
            'ű': 'u',
            'í': 'i',
            ' ': '_',
            '\'': '_',
            ',': '_',
            '"': '_',
            'ç': 'c',
            'ù': 'u'
        };

        _.each(replace, function (v, k) {
            var regex = new RegExp(k, 'g');
            value = value.replace(regex, v);
        });
        return value.toLowerCase();
    },
    template: _.memoize(function (id) {
        var compiled;

        return function (data) {
            compiled = compiled || _.template(jQuery('#wc-acf-' + id).html() || '');
            return compiled(data);
        };
    })
};