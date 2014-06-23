/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function () {

    'use strict';

    var defaults = {},

        constants = {
            toolbarSelector: '#list-toolbar-container',
            listSelector: '#categories-list'
        };

    return {

        view: true,

        fullSize: {
            width: true
        },

        header: function () {
            return {
                title: 'category.categories.title',
                noBack: true,

                breadcrumb: [
                    {title: 'navigation.settings'},
                    {title: 'category.categories.title'}
                ]
            };
        },

        templates: ['/admin/category/template/category/list'],

        initialize: function () {
            this.render();
        },

        render: function () {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/category/template/category/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'categoriesFields', '/admin/api/categories/fields',
                {
                    el: this.$find(constants.toolbarSelector),
                    template: 'defaultEditable',
                    instanceName: this.instanceName,
                    inHeader: true
                },
                {
                    el: this.$find(constants.listSelector),
                    url: '/admin/api/categories',
                    viewOptions: {
                        table: {
                            fullWidth: true
                        }
                    }
                }
            );
        }
    };
});
