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
    'jquery',
    'type/default',
    'form/util'
], function($, Default, Util) {

    'use strict';

    var handler = function(data, $el) {
            App.emit('sulu.content.changed');

            var $previous = $el.prev();
            if (-1 === data.indexOf('data-invalid="true"')) {
                $previous.hide();
            } else {
                $previous.show();
            }
        },

        changedHandler = function(data, $el) {
            App.emit('sulu.preview.update', $el, data, true);

            handler(data, $el);
        },

        focusoutHandler = function(data, $el) {
            App.emit('sulu.preview.update', $el, data);

            handler(data, $el);
        };

    return function($el, options) {
        var defaults = {
                instanceName: null
            },

            setValue = function(value) {
                Util.setValue(this.$el, this.getViewData.call(this, value));
            },

            subType = {
                initializeSub: function() {
                    // remove event with same name and register new one
                    App.off('husky.ckeditor.' + this.options.instanceName + '.changed', changedHandler);
                    App.on('husky.ckeditor.' + this.options.instanceName + '.changed', changedHandler);

                    // remove event with same name and register new one
                    App.off('husky.ckeditor.' + this.options.instanceName + '.focusout', focusoutHandler);
                    App.on('husky.ckeditor.' + this.options.instanceName + '.focusout', focusoutHandler);
                },

                needsValidation: function() {
                    return true;
                },

                setValue: function(value) {
                    setValue.call(this, value);

                    // show broken links
                    this.validate();
                },

                getModelData: function(value) {
                    return value.replace('data-invalid="true"', '');
                },

                validate: function() {
                    var response = $.ajax('/admin/markup/validate', {
                            type: "POST",
                            data: this.getValue(),
                            async: false
                        }),
                        data = response.responseJSON;

                    setValue.call(this, data.content);

                    var $previous = this.$el.prev();
                    if (!data.valid) {
                        $previous.show();
                    } else {
                        $previous.hide();
                    }

                    return true;
                }
            };

        return new Default($el, defaults, options, 'textEditor', subType);
    };
});
