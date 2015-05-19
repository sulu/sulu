/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['config', 'filtersutil/header'], function(Config, HeaderUtil) {

    'use strict';

    var bindCustomEvents = function() {
        // add clicked
        this.sandbox.on('sulu.list-toolbar.add', function() {
            this.sandbox.emit('sulu.resource.filters.new');
        }.bind(this));

        // delete clicked
        this.sandbox.on('sulu.list-toolbar.delete', function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.emit('sulu.resource.filters.delete', ids);
            }.bind(this));
        }.bind(this));
    };

    return {
        view: true,

        fullSize: {
            width: true
        },

        layout: {
            content: {
                width: 'max',
                leftSpace: false,
                rightSpace: false
            }
        },

        header: function() {
            return {
                title: 'resource.filter',
                noBack: true
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
            this.sandbox.sulu.initListToolbarAndList.call(this, 'filterFields', '/admin/api/filters/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'filterSearch',
                    parentTemplate: 'default',
                    inHeader: true
                },
                {
                    el: this.sandbox.dom.find('#filter-list', this.$el),
                    url: '/admin/api/filters?flat=true&context='+this.options.type,
                    resultKey: 'filters',
                    searchInstanceName: 'filterSearch',
                    searchFields: ['name'],
                    viewOptions: {
                        table: {
                            fullWidth: true
                        }
                    }
                }
            );
        },

        /**
         * Renders the grid and the header information
         */
        render: function() {
            this.renderGrid();
            this.setHeaderInformation();
        },

        /**
         * Sets header information like title and breadcrumb
         */
        setHeaderInformation: function() {
            HeaderUtil.setTitle(this.sandbox, null);
            HeaderUtil.setBreadCrumb(this.sandbox, this.options.type, null);
        }
    };
});
