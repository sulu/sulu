/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

/**
 * @class CSV-Export
 * @constructor
 */
define(['jquery', 'underscore', 'text!./form.html'], function($, _, form) {

    'use strict';

    var defaults = {
        options: {
            url: null, // url.csv to rest-resource
            urlParameter: {}
        },

        templates: {
            form: form
        },

        translations: {
            export: 'public.export',
            exportTitle: 'csv_export.export-title',
            delimiter: 'csv_export.delimiter',
            delimiterInfo: 'csv_export.delimiter-info',
            enclosure: 'csv_export.enclosure',
            enclosureInfo: 'csv_export.enclosure-info',
            escape: 'csv_export.escape',
            escapeInfo: 'csv_export.escape-info',
            newLine: 'csv_export.new-line',
            newLineInfo: 'csv_export.new-line-info'
        }
    };

    return {

        defaults: defaults,

        initialize: function() {
            this.render();
            this.startOverlay();
            this.bindCustomEvents();
        },

        /**
         * Set window location to download URL.
         */
        export: function() {
            var data = this.sandbox.form.getData(this.$form),
                parameter = $.extend(true, {}, this.options.urlParameter, data),
                encodedParameter = _.map(parameter, function(v, k) {
                    return k + '=' + v;
                }).join('&');

            window.location = this.options.url + '?' + encodedParameter;
        },

        /**
         * Render container for dialog and form.
         */
        render: function() {
            this.$container = $('<div/>');
            this.$form = $(this.templates.form({translations: this.translations}));

            this.$el.append(this.$container);
        },

        /**
         * Start overlay component.
         */
        startOverlay: function() {
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: this.$container,
                        openOnStart: true,
                        removeOnClose: true,
                        container: this.$el,
                        instanceName: 'csv-export',
                        slides: [{
                            title: this.translations.exportTitle,
                            data: this.$form,
                            buttons: [
                                {
                                    type: 'cancel',
                                    align: 'left'
                                },
                                {
                                    type: 'ok',
                                    align: 'right',
                                    text: this.translations.export
                                }
                            ],
                            okCallback: this.export.bind(this)
                        }]
                    }
                }
            ]);
        },

        /**
         * Bind events for overlay.
         *
         *  - close should stop this component.
         *  - open should start form components.
         */
        bindCustomEvents: function() {
            this.sandbox.once('husky.overlay.csv-export.opened', function() {
                this.sandbox.form.create(this.$form);
                this.sandbox.start(this.$form);
            }.bind(this));

            this.sandbox.once('husky.overlay.csv-export.closed', function() {
                this.sandbox.stop();
            }.bind(this));
        }
    };
});
