/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Handles teaser-types which uses a list to select.
 *
 * @class ListTeaser
 * @constructor
 */
define(['underscore'], function(_) {

    'use strict';

    var defaults = {
        options: {
            locale: null,
            url: '',
            resultKey: null,
            searchFields: [],
            matchings: [],
            selectCallback: function(item) {
            }
        },
        templates: {
            skeleton: [
                '<div class="grid">',
                '   <div class="grid-row search-row">',
                '       <div class="grid-col-8"/>',
                '       <div class="grid-col-4 teaser-selection-search"/>',
                '   </div>',
                '   <div class="grid-row">',
                '       <div class="grid-col-12 teaser-selection-list"/>',
                '   </div>',
                '</div>'
            ].join('')
        }
    };

    return {
        defaults: defaults,

        initialize: function() {
            var $container = $(this.templates.skeleton());
            this.$el.append($container);

            this.sandbox.start([
                {
                    name: 'search@husky',
                    options: {
                        el: '.teaser-selection-search',
                        appearance: 'white small',
                        instanceName: this.options.instanceName + '-teaser-search'
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: '.teaser-selection-list',
                        instanceName: 'teaser-selection',
                        url: this.options.url,
                        preselected: _.map(this.options.data, function(item) {
                            return item.id;
                        }),
                        resultKey: this.options.resultKey,
                        sortable: false,
                        columnOptionsInstanceName: '',
                        clickCallback: function(item) {
                            this.sandbox.emit('husky.datagrid.teaser-selection.toggle.item', item);
                        }.bind(this),
                        selectedCounter: true,
                        searchInstanceName: this.options.instanceName + '-teaser-search',
                        searchFields: this.options.searchFields,
                        paginationOptions: {
                            dropdown: {
                                limit: 20
                            }
                        },
                        matchings: this.options.matchings
                    }
                }
            ]);

            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.datagrid.teaser-selection.item.select', function(id) {
                this.options.selectCallback({type: this.options.type, id: id});
            }.bind(this));
            this.sandbox.on('husky.datagrid.teaser-selection.item.deselect', function(id) {
                this.options.deselectCallback({type: this.options.type, id: id});
            }.bind(this));
        }
    };
});
