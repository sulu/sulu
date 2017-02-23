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
            copyFrom: 'content.contents.settings.copy-locales.copy-from',
            target: 'content.contents.settings.copy-locales.target',
            info: 'content.contents.settings.copy-locales.info',
            title: 'content.contents.settings.copy-locales.title',
            ok: 'content.contents.settings.copy-locales.ok',
            defaultLabel: 'content.contents.settings.copy-locales.default-label',
            currentLanguage: 'content.contents.settings.copy-locales.current-language'
        },

        templates = {
            copyLocales: function(item) {
                var template = [
                    '<div class="copy-locales-overlay-content">',
                    '   <label>',
                    getTranslation.call(this, 'copyFrom'),
                    '   </label>',
                    '   <div class="grid m-top-10">',
                    '       <div class="grid-row">',
                    '       <div id="copy-locales-select" class="grid-col-6"/>',
                    '   </div>',
                    '</div>',
                    '<h2 class="divider m-top-20">',
                    getTranslation.call(this, 'target'),
                    '</h2>',
                    '<p class="info">',
                    '   * ', getTranslation.call(this, 'info'),
                    '</p>',
                    '<div class="copy-locales-to-container m-bottom-20 grid">'
                ], i = 0;

                this.sandbox.util.foreach(this.localizations, function(locale) {
                    if (i % 2 === 0) {
                        template.push((i > 0 ? '</div>' : '') + '<div class="grid-row">');
                    }
                    template.push(templates.copyLocalesCheckbox.call(this, locale.title, item));
                    i++;
                }.bind(this));

                template.push('</div>');
                template.push('</div>');

                return template.join('');
            },

            copyLocalesCheckbox: function(locale, item) {
                var concreteLanguages = [],
                    currentLocale;

                // object to array
                for (var i in this.data.concreteLanguages) {
                    if (this.data.concreteLanguages.hasOwnProperty(i)) {
                        concreteLanguages.push(this.data.concreteLanguages[i]);
                    }
                }

                currentLocale = (
                    locale === this.options.language &&
                    concreteLanguages.indexOf(locale) >= 0
                );

                return [
                    '<div class="grid-col-3">',
                    '   <div class="custom-checkbox">',
                    '       <input type="checkbox"',
                    '              id="copy-locales-to-', locale, '"',
                    '              name="copy-locales-to" class="form-element" value="', locale, '"',
                    (currentLocale ? ' disabled="disabled"' : ''), '/>',
                    '       <span class="icon"></span>',
                    '   </div>',
                    '   <label for="copy-locales-to-', locale, '" class="', (currentLocale ? 'disabled' : ''), '">',
                    locale, concreteLanguages.indexOf(locale) < 0 ? ' *' : '',
                    '   </label>',
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
         * private method to copy locale from src language to dest languages for given id
         * @param {String} id
         * @param {String} src
         * @param {String[]} dest
         * @param {function} successCallback
         * @param {function} errorCallback
         */
        copyLocale = function(id, src, dest, successCallback, errorCallback) {
            var url = this.getCopyLocaleUrl(id, src, dest.join(','));

            this.sandbox.util.save(url, 'POST', {})
                .then(function(data) {
                    if (!!successCallback && typeof successCallback === 'function') {
                        successCallback(data);
                    }
                }.bind(this))
                .fail(function(jqXHR, textStatus, error) {
                    if (!!errorCallback && typeof errorCallback === 'function') {
                        errorCallback(error);
                    }
                }.bind(this));
        },

        /**
         * callback for overlay
         * @param {Object} def
         */
        okCallback = function(def) {
            var src = this.sandbox.dom.data('#copy-locales-select', 'selection'),
                $dest = this.sandbox.dom.find(
                    '.copy-locales-to-container input:checked:not(input[disabled="disabled"])'
                ),
                dest = [];

            this.sandbox.util.foreach($dest, function($item) {
                dest.push(this.sandbox.dom.val($item));
            }.bind(this));

            if (!src || src.length === 0 || dest.length === 0) {
                return false;
            }

            this.sandbox.off('husky.select.copy-locale-to.deselected.item', deselectHandler.bind(this));
            this.sandbox.off('husky.select.copy-locale-to.selected.item', selectHandler.bind(this));
            copyLocale.call(this, this.data.id, src[0], dest);

            def.resolve(dest);
        },

        /**
         * starts overlay
         * @param $element
         * @param {Object} def
         */
        startOverlay = function($element, def) {
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        openOnStart: true,
                        removeOnClose: true,
                        el: $element,
                        container: this.$el,
                        instanceName: 'copy-locales',
                        skin: 'medium',
                        slides: [
                            {
                                title: getTranslation.call(this, 'title'),
                                data: templates.copyLocales.call(this, this.data),
                                buttons: [
                                    {
                                        type: 'cancel',
                                        align: 'left'
                                    },
                                    {
                                        type: 'ok',
                                        text: getTranslation.call(this, 'ok'),
                                        align: 'right'
                                    }
                                ],
                                okCallback: function() {
                                    okCallback.call(this, def);
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]);
        },

        /**
         * start select in overlay
         * @param {String[]} languages
         */
        startSelect = function(languages) {
            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: '#copy-locales-select',
                        instanceName: 'copy-locale-to',
                        defaultLabel: getTranslation.call(this, 'defaultLabel'),
                        preSelectedElements: [this.options.language],
                        data: languages
                    }
                }
            ]);
        },

        /**
         * handler for select
         * @param {String} item
         */
        deselectHandler = function(item) {
            var id = 'copy-locales-to-' + item;

            // enable checkbox and label
            this.sandbox.dom.prop('#' + id, 'disabled', '');
            this.sandbox.dom.removeClass('label[for="' + id + '"]', 'disabled');
        },

        /**
         * handler for deselect
         * @param {String} item
         */
        selectHandler = function(item) {
            var id = 'copy-locales-to-' + item;

            // disable checkbox and label
            this.sandbox.dom.prop('#' + id, 'disabled', 'disabled');
            this.sandbox.dom.addClass('label[for="' + id + '"]', 'disabled');
        };

    return {
        /**
         * public method to copy locale from src language to dest languages for given id
         * @param {String} id
         * @param {String} src
         * @param {String[]} dest
         * @param {function} successCallback
         * @param {function} errorCallback
         */
        copyLocale: function(id, src, dest, successCallback, errorCallback) {
            copyLocale.call(this, id, src, dest, successCallback, errorCallback);
        },

        /**
         * Start overlay with selects to choose src and dest languages.
         *
         * @param {Object} newTranslations
         *
         * @return {*}
         */
        startCopyLocalesOverlay: function(newTranslations) {
            // overwrite translations
            translations = this.sandbox.util.extend(true, {}, defaultTranslations, newTranslations);

            var def = this.sandbox.data.deferred(),
                $element = this.sandbox.dom.createElement('<div class="overlay-container"/>'),
                languages = [],
                currentLocaleText = getTranslation.call(this, 'currentLanguage');

            this.sandbox.dom.append(this.$el, $element);

            this.sandbox.util.foreach(this.data.concreteLanguages, function(locale) {
                languages.push({
                    id: locale,
                    name: locale + (locale === this.options.language ? ' (' + currentLocaleText + ')' : '')
                });
            }.bind(this));

            this.sandbox.on('husky.select.copy-locale-to.deselected.item', deselectHandler.bind(this));
            this.sandbox.on('husky.select.copy-locale-to.selected.item', selectHandler.bind(this));

            startOverlay.call(this, $element, def);
            startSelect.call(this, languages);

            return def.promise();
        }
    };
});
