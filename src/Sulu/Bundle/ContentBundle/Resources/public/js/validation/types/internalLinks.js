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
    'type/default'
], function(Default) {

    'use strict';

    var dataChangedHandler = function(data, $el) {
        App.emit('sulu.preview.update', $el, data);
        App.emit('sulu.content.changed');
    };

    return function($el, options) {
        var defaults = {},

            subType = {
                initializeSub: function() {
                    var dataChangedEvent = 'sulu.internal-links.' + options.instanceName + '.data-changed';

                    App.off(dataChangedEvent, dataChangedHandler);
                    App.on(dataChangedEvent, dataChangedHandler);
                },

                setValue: function(value) {
                    // FIXME: This is a dirty hack. (was a quickfix) This has to be done becuase the internal links data is handled differently within blocks
                    // In the one case the data is just an array (e.g. ['uuid1', 'uuid2']) in the other case its an object with ids (e.g. {ids: ['uuid1', 'uuid2']}
                    // The data needs to be handled the same way in blocks and everywhere else. If so the if else if statement above can be removed
                    if (!value.ids) {
                        value = {ids: value};
                    } else if (!!value.ids.ids) {
                        value = {ids: value.ids.ids};
                    }
                    App.dom.data($el, 'internal-links', value);
                },

                getValue: function() {
                    return App.dom.data($el, 'internal-links');
                },

                needsValidation: function() {
                    return false;
                },

                validate: function() {
                    return true;
                }
            };

        return new Default($el, defaults, options, 'internalLinks', subType);
    };
});
