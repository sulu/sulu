/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var templates = {
            openGhost: function() {
                return [
                    '<div class="copy-locale-overlay-content grid">',
                    '   <div class="grid-row">',
                    '       <p class="info">',
                    this.sandbox.translate('content.contents.settings.copy-locale.info'),
                    '       </p>',
                    '   </div>',
                    '   <div class="grid-row">',
                    '       <div class="custom-radio">',
                    '           <input type="radio" name="action" id="copy-locale-new" checked="checked"/>',
                    '           <span class="icon"></span>',
                    '       </div>',
                    '       <label for="copy-locale-new">',
                    this.sandbox.translate('content.contents.settings.copy-locale.new'),
                    '       </label>',
                    '   </div>',
                    '   <div class="grid-row">',
                    '       <div class="custom-radio">',
                    '           <input type="radio" name="action" id="copy-locale-copy"/>',
                    '           <span class="icon"></span>',
                    '       </div>',
                    '       <label for="copy-locale-copy">',
                    this.sandbox.translate('content.contents.settings.copy-locale.copy'),
                    '       </label>',
                    '       <div id="copy-locale-overlay-select" />',
                    '   </div>',
                    '</div>'
                ].join('');
            }
        },

        /**
         * start a new overlay
         * @param {String} titleKey translation key
         * @param {String} template template for the content
         * @param {Boolean} okButton
         * @param {undefined|String} instanceName
         * @param {undefined|function} okCallback
         * @param {undefined|function} cancelCallback
         */
        startOverlay = function(titleKey, template, okButton, instanceName, okCallback, cancelCallback) {
            if (!instanceName) {
                instanceName = 'node';
            }

            var $element = this.sandbox.dom.createElement('<div class="overlay-container"/>'),
                buttons = [
                    {
                        type: 'cancel',
                        align: 'left'
                    }
                ];
            this.sandbox.dom.append(this.$el, $element);

            if (!!okButton) {
                buttons.push({
                    type: 'ok',
                    align: 'right',
                    text: this.sandbox.translate('content.contents.settings.' + instanceName + '.ok')
                });
            }

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        openOnStart: true,
                        removeOnClose: true,
                        cssClass: 'node',
                        el: $element,
                        container: this.$el,
                        instanceName: instanceName,
                        skin: 'wide',
                        slides: [
                            {
                                title: this.sandbox.translate(titleKey),
                                data: template,
                                buttons: buttons,
                                okCallback: okCallback,
                                cancelCallback: cancelCallback
                            }
                        ]
                    }
                }
            ]);
        };

    return {
        /**
         * starts a copy ghost overlay
         * @param {Object} item
         * @returns {Object}
         */
        openGhost: function(item) {
            var def = this.sandbox.data.deferred();

            startOverlay.call(
                this,
                'content.contents.settings.copy-locale.title',
                templates.openGhost.call(this), true, 'copy-locale-overlay',
                function() {
                    var copy = this.sandbox.dom.prop('#copy-locale-copy', 'checked'),
                        src = this.sandbox.dom.data('#copy-locale-overlay-select', 'selectionValues');

                    if (copy && (!src || src.length === 0)) {
                        return false;
                    }

                    def.resolve(copy, src[0]);
                }.bind(this),
                function() {
                    def.reject();
                }
            );

            this.sandbox.once('husky.select.copy-locale-to.selected.item', function() {
                this.sandbox.dom.prop('#copy-locale-copy', 'checked', true)
            }.bind(this));

            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: '#copy-locale-overlay-select',
                        instanceName: 'copy-locale-to',
                        defaultLabel: this.sandbox.translate('content.contents.settings.copy-locale.select-default'),
                        data: item.concreteLanguages
                    }
                }
            ]);

            return def.promise();
        }
    };
});
