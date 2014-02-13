/*
 * This file is part of the Husky Validation.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

define([
    'type/string',
    'form/util'
], function(String, Util) {

    'use strict';

    return function($el, options) {
        var defaults = {},

            subType = {
                initializeSub: function() {
                    this.sandbox.on('husky.ckeditor.changed', function(data, $el) {
                        App.emit('sulu.preview.update', $el.data('mapperProperty'), data);
                    }.bind(this));
                }
            };

        return new String($el, defaults, options, 'textEditor', subType);
    };
});
