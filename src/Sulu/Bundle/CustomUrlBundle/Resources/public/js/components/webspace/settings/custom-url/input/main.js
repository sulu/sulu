/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore', 'jquery', 'services/husky/util'], function(_, $, util) {

    'use strict';

    var defaults = {
            templates: {
                input: '<input type="text" class="form-element<% if (!!prefix) { %> prefix" data-prefix="true"<% } else { %>" data-suffix="true"<% } %>/>'
            }
        },

        /**
         * Returns an array of domain parts.
         * If the domain part is empty an input should be displayed.
         *
         * @param {String} baseDomain
         *
         * @returns {String}
         */
        parseBaseDomain = function(baseDomain) {
            if (!baseDomain) {
                return [];
            }

            if (baseDomain.indexOf('*', baseDomain.length - 1) === -1) {
                baseDomain += '/*';
            }

            return baseDomain
                .replace(
                    /^([^/]*)(\*)([^/]+)(.*)$/,
                    '<span class="domain-part">$1</span>'
                    + this.templates.input({prefix: true})
                    + '<span class="domain-part">$3</span><span class="domain-part">$4</span>'
                )
                .replace(/\*/g, '</span>' + this.templates.input({prefix: false}) + '<span class="domain-part">');
        },

        /**
         * Namespace for events.
         *
         * @type {String}
         */
        eventNamespace = 'sulu.webspace-settings.custom-url.';

    return {

        defaults: defaults,

        events: {
            names: {
                setBaseDomain: {
                    postFix: 'set-base-domain',
                    type: 'on'
                }
            },
            namespace: eventNamespace
        },

        /**
         * Initializes the component.
         */
        initialize: function() {
            this.render(this.options.baseDomain);
            this.setDomData(this.$el.data('custom-url-data') || {});

            this.bindDomEvents();
            this.bindCustomEvents();
        },

        /**
         * Bind aura events.
         */
        bindCustomEvents: function() {
            this.events.setBaseDomain(this.setBaseDomain.bind(this));
        },

        /**
         * Bind events to dom-elements.
         */
        bindDomEvents: function() {
            this.$el.on('data-changed', function() {
                this.setDomData(this.$el.data('custom-url-data'));
            }.bind(this));

            this.$el.on('change', 'input', function() {
                this.$el.data('custom-url-data', this.getData());
            }.bind(this));
        },

        /**
         * Render base-domain input.
         *
         * @param {String} baseDomain
         */
        render: function(baseDomain) {
            this.baseDomain = baseDomain;

            if (this.baseDomain === null) {
                return;
            }

            this.html(parseBaseDomain.call(this, this.baseDomain));

            this.$find('.domain-part').each(function() {
                var text = $(this).text();

                if (text.length > 15) {
                    $(this).text(util.cropMiddle(text, 15));
                }
            });
        },

        /**
         * Set base-domain and rerender input with existing data.
         *
         * @param {String} baseDomain
         */
        setBaseDomain: function(baseDomain) {
            var data = this.getData();
            this.render(baseDomain);
            this.setDomData(data);
        },

        /**
         * Returns data from input elements.
         *
         * @returns {{prefix: String, suffix: Array}}
         */
        getData: function() {
            var prefix = $('input[data-prefix="true"]').val() || '',
                suffix = _.map($('input[data-suffix="true"]'), function(item) {
                    return $(item).val();
                });

            return {prefix: prefix, suffix: suffix};
        },

        /**
         * Set data to dom elements.
         *
         * @param {{prefix: String, suffix: Array}} data
         */
        setDomData: function(data) {
            $('input[data-prefix="true"]').val(data.prefix || '');
            _.each($('input[data-suffix="true"]'), function(item, index) {
                $(item).val(data.suffix[index] || '');
            });

            this.$el.data('custom-url-data', this.getData());
        }
    }
});
