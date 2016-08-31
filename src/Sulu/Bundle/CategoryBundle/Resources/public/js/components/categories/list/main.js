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
        listSelector: '#categories-list',
        lastClickedCategorySettingsKey: 'categoriesLastClicked'
    };

    return {

        stickyToolbar: true,

        layout: {
            content: {
                width: 'max'
            }
        },

        header: function () {
            return {
                noBack: true,

                title: 'category.categories.title',
                underline: false,

                toolbar: {
                    buttons: {
                        add: {},
                        deleteSelected: {},
                        export: {
                            options: {
                                urlParameter: {
                                    flat: true
                                },
                                url: '/admin/api/categories.csv'
                            }
                        }
                    },
                    languageChanger: {
                        preSelected: this.options.locale
                    }
                }
            };
        },

        templates: ['/admin/category/template/category/list'],

        initialize: function () {
            this.locale = this.options.locale;

            this.sandbox.sulu.triggerDeleteSuccessLabel('labels.success.category-delete-desc');
            this.bindCustomEvents();
            this.render();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.language-changed', this.changeLanguage.bind(this));
            this.sandbox.on('husky.datagrid.item.click', this.saveLastClickedCategory.bind(this));
            this.sandbox.on('sulu.toolbar.add', this.addNewCategory.bind(this));
            this.sandbox.on('sulu.toolbar.delete', this.deleteSelected.bind(this));

            // checkbox clicked
            this.sandbox.on('husky.datagrid.number.selections', function(number) {
                var postfix = number > 0 ? 'enable' : 'disable';
                this.sandbox.emit('sulu.header.toolbar.item.' + postfix, 'deleteSelected', false);
            }.bind(this));
        },

        /**
         * Triggered when locale was changed.
         *
         * @param {{id}} localeItem
         */
        changeLanguage: function(localeItem) {
            this.locale = localeItem.id;
        },

        /**
         * Renderes the component
         */
        render: function () {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/category/template/category/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 
                'categories',
                '/admin/api/categories/fields?locale=' + this.locale,
                {
                    el: this.$find(constants.toolbarSelector),
                    template: 'default',
                    instanceName: this.instanceName
                },
                {
                    el: this.$find(constants.listSelector),
                    url: '/admin/api/categories?flat=true&sortBy=name&sortOrder=asc&locale=' + this.locale,
                    childrenPropertyName: 'hasChildren',
                    resultKey: 'categories',
                    searchFields: ['name'],
                    pagination: false,
                    actionCallback: this.editCategory.bind(this),
                    viewOptions: {
                        table: {
                            openChildId: this.sandbox.sulu.getUserSetting(constants.lastClickedCategorySettingsKey),
                            hideChildrenAtBeginning: false,
                            cropContents: false,
                            selectItem: {
                                type: 'checkbox',
                                inFirstCell: true
                            },
                            icons: [
                                {
                                    column: 'name',
                                    icon: 'plus-circle',
                                    callback: this.addNewCategory.bind(this)
                                }
                            ],
                            badges: [
                                {
                                    column: 'name',
                                    callback: function(item, badge) {
                                        if (item.defaultLocale === item.locale && item.locale !== this.locale) {
                                            badge.title = item.locale;

                                            return badge;
                                        }
                                    }.bind(this)
                                }
                            ],
                            cssClasses: [
                                {
                                    column: 'name',
                                    callback: function(item) {
                                        if (item.defaultLocale === item.locale && item.locale !== this.locale) {
                                            return 'row-gray';
                                        }
                                    }.bind(this)
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
            this.saveLastClickedCategory(parent);
            this.sandbox.emit('sulu.category.categories.form-add', parent);
        },

        /**
         * Navigates to the form for editing an existing category
         * @param id
         */
        editCategory: function (id) {
            this.saveLastClickedCategory(id);
            this.sandbox.emit('sulu.category.categories.form', id);
        },

        /**
         * Saves an id as the last click category in the user-settings
         * @param id {Number|String} the id of the category
         */
        saveLastClickedCategory: function(id) {
            if (!!id) {
                this.sandbox.sulu.saveUserSetting(constants.lastClickedCategorySettingsKey, id);
            }
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
