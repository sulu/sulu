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
    'form/util',
    'services/husky/translator',
    'services/husky/util'
], function($, Default, Util, Translator, HuskyUtil) {

    'use strict';

    var handler = function(data, $el) {
            App.emit('sulu.content.changed');
            showErrors($el, data);
        },

        showErrors = function($element, data) {
            var $errorElements = $element.parent().find('.validate-error').children();
            $errorElements.hide();

            showError($element, data, 'unpublished');
            showError($element, data, 'removed');
        },

        showError = function($element, data, type) {
            if (-1 === data.indexOf('sulu:validation-state="' + type + '"')) {
                return;
            }

            var $errorElement = $element.parent().find('.' + type),
                match = data.match(new RegExp('sulu:validation-state="' + type + '"', 'g')),
                translateKey = 'content.text_editor.error.' + type + (1 === match.length ? '-single' : '-multiple');

            $errorElement.text(HuskyUtil.sprintf(Translator.translate(translateKey), match.length));

            $errorElement.show();
        },

        changedHandler = function(data, $el) {
            App.emit('sulu.preview.update', $el, data, true);

            handler(data, $el);
        },

        focusoutHandler = function(data, $el) {
            App.emit('sulu.preview.update', $el, data);

            showErrors($el, data);
        };

    return function($el, options) {
        var defaults = {
                instanceName: null
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
                    return false;
                },

                setValue: function(value) {
                    Util.setValue(this.$el, this.getViewData(value));

                    showErrors($el, value);
                }
            };

        return new Default($el, defaults, options, 'textEditor', subType);
    };
});
