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

    var translations = {},
        defaultTranslations = {
            info: 'content.contents.settings.copy-locale.info',
            title: 'content.contents.settings.copy-locales.title',
            selectDefault: 'content.contents.settings.copy-locale.select-default',
            new: 'content.contents.settings.copy-locale.new',
            copy: 'content.contents.settings.copy-locale.copy',
            ok: 'content.contents.settings.copy-locale-overlay.ok'
        },

        templates = {
            openGhost: function() {
                return [
                    '<div class="copy-locale-overlay-content grid">',
                    '   <div class="grid-row">',
                    '       <p class="info">',
                    getTranslation.call(this, 'info'),
                    '       </p>',
                    '   </div>',
                    '   <div class="grid-row">',
                    '       <div class="custom-radio">',
                    '           <input type="radio" name="action" id="copy-locale-new" checked="checked"/>',
                    '           <span class="icon"></span>',
                    '       </div>',
                    '       <label for="copy-locale-new">',
                    getTranslation.call(this, 'new'),
                    '       </label>',
                    '   </div>',
                    '   <div class="grid-row">',
                    '       <div class="custom-radio">',
                    '           <input type="radio" name="action" id="copy-locale-copy"/>',
                    '           <span class="icon"></span>',
                    '       </div>',
                    '       <label for="copy-locale-copy">',
                    getTranslation.call(this, 'copy'),
                    '       </label>',
                    '       <div id="copy-locale-overlay-select" />',
                    '   </div>',
                    '</div>'
                ].join('');
            }
        },

        /**
         * Retrieves translation for given identifier.
         *
         * @param {String} id
         *
         * @return {String}
         */
        getTranslation = function(id) {
            return this.sandbox.translate(translations[id]);
        },

        /**
         * start a new overlay
         * @param {String} title the title of the overlay
         * @param {String} template template for the content
         * @param {Boolean} okButton
         * @param {undefined|String} instanceName
         * @param {undefined|function} okCallback
         * @param {undefined|function} cancelCallback
         */
        startOverlay = function(title, template, okButton, instanceName, okCallback, cancelCallback) {
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
                    text: getTranslation.call(this, 'ok')
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
                        skin: 'medium',
                        slides: [
                            {
                                title: title,
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
         * Starts a copy ghost overlay.
         *
         * @param {Object} item
         * @param {Object} newTranslations
         *
         * @returns {Object}
         */
        openGhost: function(item, newTranslations) {
            var def = this.sandbox.data.deferred();

            // overwrite translations
            translations = this.sandbox.util.extend(true, {}, defaultTranslations, newTranslations);

            startOverlay.call(
                this,
                getTranslation.call(this, 'title'),
                templates.openGhost.call(this),
                true,
                'copy-locale-overlay',
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
                        defaultLabel: getTranslation.call(this, 'selectDefault'),
                        data: item.concreteLanguages
                    }
                }
            ]);

            return def.promise();
        }
    };
});
