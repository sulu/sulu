/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['config'], function(Config) {

    'use strict';

    var bindCustomEvents = function() {
        // add clicked
        this.sandbox.on('sulu.toolbar.add', function() {
            this.sandbox.emit('sulu.resource.filters.new');
        }.bind(this));

        // back button clicked
        this.sandbox.on('sulu.header.back', function() {
            var config = Config.get('suluresource.filters.type.' + this.options.type);
            if(config.routeToList) {
                this.sandbox.emit('sulu.router.navigate', config.routeToList);
            }
        }.bind(this));

        // delete clicked
        this.sandbox.on('sulu.toolbar.delete', function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.emit('sulu.resource.filters.delete', ids);
            }.bind(this));
        }.bind(this));

        // checkbox clicked
        this.sandbox.on('husky.datagrid.number.selections', function(number) {
            var postfix = number > 0 ? 'enable' : 'disable';
            this.sandbox.emit('sulu.header.toolbar.item.' + postfix, 'deleteSelected', false);
        }, this);
    };

    return {

        fullSize: {
            width: true
        },

        layout: {
            content: {
                width: 'max'
            }
        },

        header: function() {
            return {
                noBack: false,
                toolbar: {
                    buttons: {
                        add: {},
                        deleteSelected: {},
                    },
                    languageChanger: {
                        preSelected: this.options.locale
                    }
                }
            };
        },

        templates: ['/admin/resource/template/filter/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);
        },

        renderGrid: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/resource/template/filter/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this,
                'filterFields',
                '/admin/api/filters/fields?locale=' + this.options.locale,
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'filterSearch',
                    template: 'default'
                },
                {
                    el: this.sandbox.dom.find('#filter-list', this.$el),
                    url: '/admin/api/filters?locale=' + this.options.locale + '&flat=true&context='+this.options.type,
                    resultKey: 'filters',
                    searchInstanceName: 'filterSearch',
                    searchFields: ['name'],
                    actionCallback: function(id) {
                        this.sandbox.emit('sulu.resource.filters.edit', id)
                    }.bind(this)
                }
            );
        },

        /**
         * Renders the grid
         */
        render: function() {
            this.renderGrid();
        }
    };
});
