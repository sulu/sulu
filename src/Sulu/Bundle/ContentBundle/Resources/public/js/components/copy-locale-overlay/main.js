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
            copyLocales: function(item) {
                var template = [
                    '<div class="copy-locales-overlay-content">',
                    '   <label>',
                    this.sandbox.translate('content.contents.settings.copy-locales.copy-from'),
                    '   </label>',
                    '   <div class="grid m-top-10">',
                    '       <div class="grid-row">',
                    '       <div id="copy-locales-select" class="grid-col-6"/>',
                    '   </div>',
                    '</div>',
                    '<h2 class="divider m-top-20">',
                    this.sandbox.translate('content.contents.settings.copy-locales.target'),
                    '</h2>',
                    '<p class="info">',
                    '   * ', this.sandbox.translate('content.contents.settings.copy-locales.info'),
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

        copyLocale = function(id, src, dest, successCallback, errorCallback) {
            var url = [
                '/admin/api/nodes/', id, '?webspace=', this.options.webspace,
                '&language=', src, '&dest=', dest.join(','), '&action=copy-locale'
            ].join('');

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
        };

    return {
        copyLocale: function(id, src, dest, successCallback, errorCallback) {
            copyLocale.call(this, id, src, dest, successCallback, errorCallback);
        },

        startCopyLocalesOverlay: function() {
            var $element = this.sandbox.dom.createElement('<div class="overlay-container"/>'),
                languages = [],
                currentLocaleText = this.sandbox.translate('content.contents.settings.copy-locales.current-language'),
                deselectHandler = function(item) {
                    var id = 'copy-locales-to-' + item;

                    // enable checkbox and label
                    this.sandbox.dom.prop('#' + id, 'disabled', '');
                    this.sandbox.dom.removeClass('label[for="' + id + '"]', 'disabled');
                }.bind(this),
                selectHandler = function(item) {
                    var id = 'copy-locales-to-' + item;

                    // disable checkbox and label
                    this.sandbox.dom.prop('#' + id, 'disabled', 'disabled');
                    this.sandbox.dom.addClass('label[for="' + id + '"]', 'disabled');
                }.bind(this);

            this.sandbox.dom.append(this.$el, $element);

            this.sandbox.util.foreach(this.data.concreteLanguages, function(locale) {
                languages.push({
                    id: locale,
                    name: locale + (locale === this.options.language ? ' (' + currentLocaleText + ')' : '')
                });
            }.bind(this));

            this.sandbox.on('husky.select.copy-locale-to.deselected.item', deselectHandler);
            this.sandbox.on('husky.select.copy-locale-to.selected.item', selectHandler);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        openOnStart: true,
                        removeOnClose: true,
                        el: $element,
                        container: this.$el,
                        instanceName: 'copy-locales',
                        skin: 'wide',
                        slides: [
                            {
                                title: this.sandbox.translate('content.contents.settings.copy-locales.title'),
                                data: templates.copyLocales.call(this, this.data),
                                buttons: [
                                    {
                                        type: 'cancel',
                                        align: 'right'
                                    },
                                    {
                                        type: 'ok',
                                        text: this.sandbox.translate('content.contents.settings.copy-locales.ok'),
                                        align: 'left'
                                    }
                                ],
                                okCallback: function() {
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

                                    this.sandbox.off('husky.select.copy-locale-to.deselected.item', deselectHandler);
                                    this.sandbox.off('husky.select.copy-locale-to.selected.item', selectHandler);
                                    copyLocale.call(this, this.data.id, src[0], dest);

                                    // define data and overwrite data.id if startpage (index) - for correct redirect
                                    var data = this.data;
                                    if (this.options.id === 'index') {
                                        data.id = this.options.id;
                                    }

                                    this.load(data, this.options.webspace, this.options.language, true);
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]);

            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: '#copy-locales-select',
                        instanceName: 'copy-locale-to',
                        defaultLabel: this.sandbox.translate('content.contents.settings.copy-locales.default-label'),
                        preSelectedElements: [this.options.language],
                        data: languages
                    }
                }
            ]);
        }
    };
});
