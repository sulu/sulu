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

    var constants = {
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
            this.sandbox.sulu.triggerDeleteSuccessLabel('labels.success.category-delete-desc');
            this.render();
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.list-toolbar.add', this.addNewCategory.bind(this));
            this.sandbox.on('sulu.list-toolbar.delete', this.deleteSelected.bind(this));
        },

        /**
         * Renderes the component
         */
        render: function () {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/category/template/category/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'categoriesFields', '/admin/api/categories/fields',
                {
                    el: this.$find(constants.toolbarSelector),
                    template: 'default',
                    instanceName: this.instanceName,
                    inHeader: true
                },
                {
                    el: this.$find(constants.listSelector),
                    url: '/admin/api/categories?depth=0',
                    viewOptions: {
                        table: {
                            fullWidth: true,
                            selectItem: {
                                type: 'checkbox',
                                inFirstCell: true
                            },
                            childrenPropertyName: 'children',
                            icons: [
                                {
                                    column: 'name',
                                    icon: 'pencil',
                                    callback: this.editCategory.bind(this)
                                },
                                {
                                    column: 'name',
                                    icon: 'plus-circle',
                                    callback: this.addNewCategory.bind(this)
                                }
                            ]
                        }
                    }
                }
            );
        },

        /**
         * Navigates to the the form for adding a new category
         * @param parent
         */
        addNewCategory: function (parent) {
            this.sandbox.emit('sulu.category.categories.form-add', parent);
        },

        /**
         * Navigates to the form for editing an existing category
         * @param id
         */
        editCategory: function (id) {
            this.sandbox.emit('sulu.category.categories.form', id);
        },

        /**
         * Deletes all selected categories
         */
        deleteSelected: function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(categories) {
                this.sandbox.emit('sulu.category.categories.delete', categories, function(deletedId) {
                    this.sandbox.emit('husky.datagrid.record.remove', deletedId);
                }.bind(this), function() {
                    this.sandbox.emit('sulu.labels.success.show', 'labels.success.category-delete-desc', 'labels.success');
                }.bind(this));
            }.bind(this));
        }
    };
});
