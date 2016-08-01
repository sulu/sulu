/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'config',
    'sulucategory/model/category',
    'sulucategory/collections/categories'
], function(Config, Category, Categories) {

    'use strict';

    var CATEGORIES_LOCALE = Config.get('sulu_category.user_settings.category_locale'),

        constants = {
            listContainerId: 'categories-list-container',
            editContainerId: 'categories-edit-container'
        },

        namespace = 'sulu.category.categories.',

        /**
         * listens on and navigates to category list
         * @event sulu.category.categories.list
         */
        NAVIGATE_CATEGORY_LIST = function() {
            return createEventName.call(this, 'list');
        },

        /**
         * listens on and navigates to category form
         * @event sulu.category.categories.form
         */
        NAVIGATE_CATEGORY_FORM = function() {
            return createEventName.call(this, 'form');
        },

        /**
         * listens on and navigates to category form for adding
         * @event sulu.category.categories.form-add
         */
        NAVIGATE_CATEGORY_FORM_ADD = function() {
            return createEventName.call(this, 'form-add');
        },

        /**
         * listens on and saves a category
         * @event sulu.category.categories.save
         * @param {Object} the data of the category to save
         */
        CATEGORY_SAVE = function() {
            return createEventName.call(this, 'save');
        },

        /**
         * listens on and deletes categories
         * @event sulu.category.categories.delete
         * @param {Array} an array of ids of the categories to delete
         * @param {Function} function to execute after every deleted category
         * @param {Function} function to execute after everything got deleted
         */
        CATEGORIES_DELETE = function() {
            return createEventName.call(this, 'delete');
        },

        /**
         * emited when a single category got deleted
         * @event sulu.category.categories.deleted
         * @param {String|Number} the id of the delted category
         */
        CATEGORY_DELETED = function() {
            return createEventName.call(this, 'deleted');
        },

        /**
         * emited when a single category got changed
         * @event sulu.category.categories.changed
         * @param {Object} the new category data
         */
        CATEGORY_CHANGED = function() {
            return createEventName.call(this, 'changed');
        },

        /** returns normalized event names */
        createEventName = function(postFix) {
            return namespace + postFix;
        };

    return {

        /**
         * Initializes the component
         */
        initialize: function() {
            this.categories = new Categories();
            this.locale = this.options.locale;

            this.bindCustomEvents();
            this.render();
        },

        /**
         * Helper function to get a category model
         * @param id {String|Number} id of the model
         * @returns {Object} the backbone model
         */
        getCategoryModel: function(id) {
            if (!!this.categories.get(id)) {
                return this.categories.get(id);
            } else {
                var model = new Category();
                if (!!id) {
                    model.set({id: id});
                }
                this.categories.push(model);
                return model;
            }
        },

        /**
         * Renderes the component
         */
        render: function() {
            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'edit') {
                this.renderEdit();
            } else {
                throw 'display type wrong';
            }
        },

        /**
         * Binds custom related events
         */
        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.language-changed', this.changeLanguage.bind(this));
            // navigate to category list
            this.sandbox.on(NAVIGATE_CATEGORY_LIST.call(this), this.navigateToList.bind(this));
            // navigate to category form
            this.sandbox.on(NAVIGATE_CATEGORY_FORM.call(this), this.navigateToForm.bind(this));
            // navigate to category form for adding
            this.sandbox.on(NAVIGATE_CATEGORY_FORM_ADD.call(this), this.navigateToAddForm.bind(this));
            // save a category
            this.sandbox.on(CATEGORY_SAVE.call(this), this.saveCategory.bind(this));
            // deletes more categories
            this.sandbox.on(CATEGORIES_DELETE.call(this), this.deleteCategories.bind(this));
        },

        /**
         * Saves data for an existing category
         * @param data {Object} object with the data to update
         * @param callback {Function} callback to call if collection has been saved
         */
        saveCategory: function(data, callback) {
            var category = this.getCategoryModel(data.id);
            category.set(data);

            category.save(null, {
                success: function(result) {
                    this.sandbox.emit(CATEGORY_CHANGED.call(this), result.toJSON());
                    callback(result.toJSON(), true);
                    this.sandbox.emit('sulu.header.saved', result.toJSON());
                }.bind(this),
                error: function(result, response) {
                    this.sandbox.logger.log('Error while saving category');
                    callback(response.responseJSON, false);
                }.bind(this)
            });
        },

        /**
         * Deletes an more categories
         * @param categoryIds {Array} array of category ids
         * @param callback {Function} callback to execute after a single category got deleted
         * @param finishedCallback {Function} callback to execute after everything got deleted
         */
        deleteCategories: function(categoryIds, callback, finishedCallback) {
            var category, count = 0;
            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (confirmed === true) {
                    this.sandbox.util.foreach(categoryIds, function(id) {
                        category = this.getCategoryModel(id);
                        category.destroy({
                            success: function() {
                                if (typeof callback === 'function') {
                                    callback(id);
                                } else {
                                    this.sandbox.emit(CATEGORY_DELETED.call(this), id);
                                }
                                count++;
                                if (count === categoryIds.length && typeof finishedCallback === 'function') {
                                    finishedCallback();
                                }
                            }.bind(this),
                            error: function() {
                                this.sandbox.logger.log('Error while deleting a single category');
                            }.bind(this)
                        });
                    }.bind(this));
                }
            }.bind(this));
        },

        /**
         * Navigates to the category list
         */
        navigateToList: function() {
            this.sandbox.emit('sulu.router.navigate', 'settings/categories', true, true);
        },

        /**
         * Navigates to the category form
         * @param categoryId {Number|String} the id of the category to edit
         * @param tab {String} the tab to route to
         */
        navigateToForm: function(categoryId, tab) {
            // default tab is details
            tab = (!!tab) ? tab : 'details';
            this.sandbox.emit('sulu.router.navigate', 'settings/categories/edit:' + categoryId + '/' + tab, true, true);
        },

        /**
         * Navigates to the category form for adding a new category
         * @param parentId {Number|String} of the parent-category
         * @param tab {String} the tab to route to
         */
        navigateToAddForm: function(parentId, tab) {
            // default tab is details
            tab = (!!tab) ? tab : 'details';
            var route = 'settings/categories/new';
            route = ((!!parentId) ? route + '/' + parentId : route) + '/' + tab;
            this.sandbox.emit('sulu.router.navigate', route, true, true);
        },

        /**
         * Changes the language of the category and emits a change event
         * @param language {Object} the language object with an id property
         */
        changeLanguage: function(language) {
            this.locale = language.id;
            this.sandbox.sulu.saveUserSetting(CATEGORIES_LOCALE, this.locale);

            if (this.options.display === 'list') {
                this.sandbox.emit('sulu.router.navigate', 'settings/categories/' + this.locale);
            } else {
                this.sandbox.emit('sulu.router.navigate', 'settings/categories/' + this.locale + '/edit:' + this.options.id + '/details');
            }
        },

        /**
         * Renders the list-component
         */
        renderList: function() {
            var $list = this.sandbox.dom.createElement('<div id="' + constants.listContainerId + '"/>');
            this.html($list);
            this.sandbox.start([
                {
                    name: 'categories/list@sulucategory',
                    options: {
                        el: $list,
                        locale: this.locale
                    }
                }
            ]);
        },

        /**
         * Fetches the category for a given locale
         * @param locale {String} The locale the fetch the category for
         * @param callback {Function} callback to execute
         */
        fetchCategory: function(locale, callback) {
            var category = this.getCategoryModel(this.options.id);
            if (!!locale) {
                category.set({locale: locale});
            }
            if (!!this.options.parent) {
                category.set({parent: this.options.parent});
            }

            if (!!category.get('id')) {
                category.fetch({
                    data: {locale: locale, flat: true},
                    success: function(result) {
                        callback(result.toJSON());
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log('Error while fetching a single category');
                    }.bind(this)
                });
            } else {
                callback(category.toJSON());
            }
        },

        /**
         * Renders the edit
         */
        renderEdit: function() {
            var action = function(data) {
                    this.sandbox.start([
                        {
                            name: 'categories/edit@sulucategory',
                            options: {
                                el: $form,
                                locale: this.locale,
                                data: data,
                                // TODO options parent is only set in case of 'add'. Parent should also be sent via the api
                                parent: this.options.parent || null
                            }
                        }
                    ]);
                }.bind(this),
                $form = this.sandbox.dom.createElement('<div id="' + constants.editContainerId + '"/>');
            this.html($form);
            this.fetchCategory(this.locale, action);
        }
    };
});
