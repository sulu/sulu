/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Handles content-teaser.
 *
 * @class ContentTeaser
 * @constructor
 */
define(['underscore'], function(_) {

    'use strict';

    var defaults = {
            options: {
                locale: null,
                webspaceKey: null,
                selectCallback: function(item) {
                }
            }
        },

        getColumnNavigationUrl = function() {
            var url = '/admin/api/nodes',
                urlParts = [
                    'language=' + this.options.locale,
                    'fields=title,order,published',
                    'webspace-nodes=all'
                ];

            if (!!this.options.webspaceKey) {
                urlParts.push('webspace=' + this.options.webspaceKey);
            }

            return url + '?' + urlParts.join('&');
        };

    return {
        defaults: defaults,

        initialize: function() {
            var $container = $('<div/>');
            this.$el.append($container);
            this.sandbox.start(
                [
                    {
                        name: 'column-navigation@husky',
                        options: {
                            el: $container,
                            url: getColumnNavigationUrl.call(this),
                            linkedName: 'linked',
                            typeName: 'type',
                            hasSubName: 'hasChildren',
                            instanceName: this.options.instanceName,
                            actionIcon: 'fa-plus-circle',
                            resultKey: this.options.resultKey,
                            showOptions: false,
                            responsive: false,
                            skin: 'fixed-height-small',
                            markable: true,
                            sortable: false,
                            premarkedIds: _.map(this.options.data, function(item) {
                                return item.id;
                            }),
                            actionCallback: function(item) {
                                this.options.selectCallback({type: 'content', id: item.id});
                            }.bind(this)
                        }
                    }
                ]
            );
        }
    };
});
