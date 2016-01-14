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

    var defaults = {
        options: {
            baseDomain: null
        },
        templates: {
            input: '<input type="text" data-index="<%=index%>"/>',
            text: '<span><%=text%></span>'
        },
        translations: {}
    };

    return {

        defaults: defaults,

        initialize: function() {
            this.render(this.options.baseDomain);
        },

        render: function(baseDomain) {
            this.baseDomain = baseDomain;

            if (this.baseDomain === null) {
                return;
            }

            this.domainParts = this.parseBaseDomain(this.baseDomain);

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

        /**
         * Returns an array of domain parts.
         * If the domain part is empty an input should be displayed.
         *
         * @param {String} baseDomain
         *
         * @returns {Array}
         */
        parseBaseDomain: function(baseDomain) {
            var domainParts = baseDomain.split('*');

            // add a empty element between the items ('*') except the part is empty or the part before is empty
            for (var i = domainParts.length - 1; i >= 1; i = i - 2) {
                if (domainParts[i] !== '' && domainParts[i - 1] !== '') {
                    domainParts.splice(i, 0, '');
                }
            }

            // if no '*' exists in the right part of the domain appen a '/' to the last and add an empty element
            if (baseDomain.substring(baseDomain.indexOf('/')).indexOf('*') === -1) {
                domainParts[domainParts.length - 1] = domainParts[domainParts.length - 1].concat('/');

                // no * found in the right part of the domain
                domainParts.push('');
            }

            return domainParts;
        }
    }
});
