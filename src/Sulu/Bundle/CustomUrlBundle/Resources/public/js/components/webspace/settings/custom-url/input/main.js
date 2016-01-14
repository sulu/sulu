/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore'], function(_) {

    'use strict';

    // Tested base-domains:
    //  - *.sulu.io/test/*/{localization}
    //  - sulu.io/test/*/{localization}
    //  - *.sulu.io/test/*
    //  - *.sulu.io/test
    //  - sulu.io/test
    //  - sulu.io
    //  - sulu.io/*/*
    //  - *.sulu.io/*/*

    var defaults = {
        templates: {
            input: '<input type="text" data-index="<%=index%>" <% if (index === 0) { %>data-prefix="true"<% } else { %>data-suffix="true"<% } %>/>',
            text: '<span><%=text%></span>'
        },
        translations: {}
        },

        /**
         * Returns an array of domain parts.
         * If the domain part is empty an input should be displayed.
         *
         * @param {String} baseDomain
         *
         * @returns {Array}
         */
        parseBaseDomain = function(baseDomain) {
            var domainParts = baseDomain.split('*');

            // add a empty element between the items ('*') except the part is empty or the part before is empty
            for (var i = domainParts.length - (baseDomain.charAt(baseDomain.length - 1) === '*' ? 2 : 1); i >= 1; i = i - 2) {
                if (domainParts[i] !== '' && domainParts[i - 1] !== '') {
                    domainParts.splice(i, 0, '');
                }
            }

            var preparedBaseDomain = baseDomain.concat('/');

            // if no '*' exists in the right part of the domain appen a '/' to the last and add an empty element
            if (preparedBaseDomain.substring(preparedBaseDomain.indexOf('/')).indexOf('*') === -1) {
                domainParts[domainParts.length - 1] = domainParts[domainParts.length - 1].concat('/');

                // no * found in the right part of the domain
                domainParts.push('');
            }

            return domainParts;
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
            this.setDomData(this.$el.data('custom-url-data'));

            this.events.setBaseDomain(this.setBaseDomain.bind(this));

            this.$el.on('data-changed', function() {
                this.setBaseDomain(this.$el.data('custom-url-data'));
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

            this.domainParts = parseBaseDomain(this.baseDomain);
            this.html(_.map(this.domainParts, this.renderDomainPart.bind(this)))
        },

        /**
         * Renders a single domain part.
         * If the domain part is empty an input will be displayed.
         *
         * @param {String} domainPart
         * @param {Integer} index
         * @returns {String}
         */
        renderDomainPart: function(domainPart, index) {
            switch (domainPart) {
                case '':
                    return this.templates.input({index: index});
                default:
                    return this.templates.text({text: domainPart});
            }
        },

        setBaseDomain: function(baseDomain) {
            var data = this.getData();
            this.render(baseDomain);
            this.setDomData(data);
        },

        getData: function() {
            var prefix = $('input[data-prefix="true"]').val() || '',
                suffix = _.map($('input[data-suffix="true"]'), function(item) {
                    return $(item).val();
                });

            return {prefix: prefix, suffix: suffix};
        },

        setDomData: function(data) {
            $('input[data-prefix="true"]').val(data.prefix || '');
            _.map($('input[data-suffix="true"]'), function(item, index) {
                return $(item).val(data.suffix[index] || '');
            });

            this.$el.data('custom-url-data', this.getData());
        }
    }
});
