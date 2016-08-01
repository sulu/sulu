/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Handles teaser selection.
 *
 * @class TeaserSelection
 * @constructor
 */
define(['underscore'], function(_) {

    'use strict';

    var defaults = {
            options: {
                eventNamespace: 'sulu.teaser-selection',
                resultKey: 'teasers',
                dataAttribute: 'teaser-selection',
                dataDefault: {},
                hidePositionElement: true,
                hideConfigButton: true,
                webspaceKey: null,
                locale: null,
                navigateEvent: 'sulu.router.navigate',
                idKey: 'teaserId',
                types: {},
                presentAs: [],
                translations: {
                    noContentSelected: 'sulu-content.teaser.no-teaser',
                    add: 'sulu-content.teaser.add-teaser'
                }
            },
            templates: {
                url: '/admin/api/teasers?ids=<%= ids.join(",") %>',
                contentItem: '<% if (!!media) { %><span class="image"><img src="<%= media %>"/></span><% } %><span class="value"><%= title %></span>',
                presentAsButton: '<span class="fa-eye present-as teaser-selection icon right border"><span class="selected-text"></span><span class="dropdown-toggle"></span></span>'
            }
        },

        renderDropdown = function() {
            var $container = $('<div/>');
            this.$addButton.parent().append($container);
            this.$addButton.append('<span class="dropdown-toggle teaser-selection"/>');

            this.sandbox.start([
                {
                    name: 'dropdown@husky',
                    options: {
                        el: $container,
                        data: _.map(this.options.types, function(item, name) {
                            return _.extend({id: name, name: name}, item);
                        }),
                        valueName: 'title',
                        trigger: this.$addButton,
                        triggerOutside: true,
                        clickCallback: addByType.bind(this)
                    }
                }
            ]);
        },

        renderPresentAs = function() {
            var $presentAsButton = $(this.templates.presentAsButton()),
                $presentAsText = $presentAsButton.find('.selected-text'),
                $container = $('<div/>'),
                presentAs = this.getData().presentAs || '';

            $presentAsButton.insertAfter(this.$addButton);
            this.$addButton.parent().append($container);

            _.each(this.options.presentAs, function(item) {
                if (item.id === presentAs) {
                    $presentAsText.text(item.name);
                    return false;
                }
            });

            this.sandbox.start([
                {
                    name: 'dropdown@husky',
                    options: {
                        el: $container,
                        instanceName: this.options.instanceName,
                        data: this.options.presentAs,
                        alignment: 'right',
                        trigger: $presentAsButton,
                        triggerOutside: true,
                        clickCallback: function(item) {
                            $presentAsText.text(item.name);

                            this.setData(_.extend(this.getData(), {presentAs: item.id}));
                        }.bind(this)
                    }
                }
            ]);
        },

        addByType = function(type) {
            var $container = $('<div class="teaser-selection"/>'),
                $componentContainer = $('<div/>'),
                data = this.getData().items || [];

            this.$el.append($container);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        instanceName: this.options.instanceName,
                        openOnStart: true,
                        removeOnClose: true,
                        cssClass: 'type-' + type.name,
                        slides: [
                            {
                                title: this.sandbox.translate(type.title),
                                data: $componentContainer,
                                okCallback: function() {
                                    var newData = this.getData();
                                    newData.items = data;

                                    this.setData(newData);
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]);

            this.sandbox.once('husky.overlay.' + this.options.instanceName + '.initialized', function() {
                this.sandbox.start([
                    {
                        name: type.component,
                        options: _.extend(
                            {
                                el: $componentContainer,
                                locale: this.options.locale,
                                webspaceKey: this.options.webspaceKey,
                                instanceName: this.options.instanceName,
                                type: type.name,
                                data: _.fsrc/Sulu/Component/Content/SimpleContentType.phpilter(data, function(item) {
                                    return item['type'] === type.name;
                                }),
                                selectCallback: function(item) {
                                    data.push(item);
                                },
                                deselectCallback: function(item) {
                                    data = _.without(data,  _.findWhere(data, item));
                                }
                            },
                            type.componentOptions
                        )
                    }
                ]);
            }.bind(this));
        };

    return {
        type: 'itembox',

        defaults: defaults,

        initialize: function() {
            this.render();
            renderDropdown.call(this);
            renderPresentAs.call(this);
        },

        getUrl: function(data) {
            var ids = _.map(data.items || [], function(item) {
                return item.type + ';' + item.id;
            });

            return this.templates.url({ids: ids});
        },

        getItemContent: function(item) {
            return this.templates.contentItem(item);
        },

        sortHandler: function(ids) {
            this.setData(ids, false);
        },

        removeHandler: function(id) {
            var data = this.getData().items || [],
                idParts = id.split(';');

            for (var i = -1, length = data.length; ++i < length;) {
                if (idParts[0] === data[i].type && idParts[1] === data[i].id) {
                    data.splice(i, 1);
                    break;
                }
            }

            this.setData(data, false);
        }
    };
});
