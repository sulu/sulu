/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

define([], function() {

    'use strict';

    var defaults = {
            breadcrumbs: []
        },

        templates = {
            breadcrumb: [
                '<span class="breadcrumb">',
                '<% if (!!icon) { %><span class="<%= icon %> icon"></span><% } %><%= title %><span class="fa-chevron-right separator"></span>',
                '</span>'
            ].join('')
        };

    return {
        events: {
            names: {
                breadcrumbClicked: {
                    postFix: 'breadcrumb-clicked'
                }
            },
            namespace: 'sulu.breadcrumbs.'
        },

        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.options.breadcrumbs.forEach(function(breadcrumbData) {
                var $breadcrumb = $(this.sandbox.util.template(templates.breadcrumb, {
                    title: this.sandbox.translate(breadcrumbData.title),
                    icon: breadcrumbData.icon
                }));

                $breadcrumb.on('click', function() {
                    this.events.breadcrumbClicked(breadcrumbData);
                }.bind(this));

                this.$el.append($breadcrumb);
            }.bind(this));
        }
    };
});
