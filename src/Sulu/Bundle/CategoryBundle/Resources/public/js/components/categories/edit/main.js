/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'jquery',
    'config',
    'services/sulucategory/category-manager',
    'services/sulucategory/category-router'
], function($, Config, CategoryManager, CategoryRouter) {

    'use strict';

    var CATEGORIES_LOCALE = Config.get('sulu_category.user_settings.category_locale');

    return {

        collaboration: function() {
            if (!this.data || !this.data.id) {
                return;
            }

            return {
                id: this.data.id,
                type: 'categories'
            };
        },

        header: function() {
            return {
                title: this.data.title,

                tabs: {
                    url: '/admin/content-navigations?alias=category',
                    options: {
                        data: function() {
                            return this.sandbox.util.extend(false, {}, this.data);
                        }.bind(this),
                        locale: this.options.locale
                    },
                    componentOptions: {
                        values: this.data
                    }
                },
                toolbar: {
                    buttons: {
                        save: {
                            parent: 'saveWithOptions'
                        },
                        edit: {
                            options: {
                                dropdownItems: {
                                    delete: {}
                                }
                            }
                        }
                    },
                    languageChanger: {
                        preSelected: this.options.locale
                    }
                }
            };
        },

        loadComponentData: function() {
            if (!this.options.id) {
                return {};
            }

            return CategoryManager.load(this.options.id, this.options.locale);
        },

        initialize: function() {
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.back', this.toList.bind(this));
            this.sandbox.on('sulu.tab.dirty', this.enableSave.bind(this));
            this.sandbox.on('sulu.toolbar.save', this.save.bind(this));
            this.sandbox.on('sulu.toolbar.delete', this.delete.bind(this));
            this.sandbox.on('sulu.tab.data-changed', this.setData.bind(this));
            this.sandbox.on('sulu.header.language-changed', this.changeLanguage.bind(this));
        },

        toList: function() {
            CategoryRouter.toList(this.options.locale);
        },

        toNew: function(locale, content, parent) {
            CategoryRouter.toNew(
                (locale || this.options.locale),
                (content || 'details'),
                (parent || this.options.parent)
            );
        },

        toEdit: function(id, locale, content) {
            CategoryRouter.toEdit((locale || this.options.locale), id, (content || 'details'));
        },

        save: function(action) {
            this.loadingSave();

            this.saveTab().then(function(data) {
                this.afterSave(action, data);
            }.bind(this));
        },

        delete: function() {
            if (!this.options.id) {
                return;
            }

            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (!confirmed) {
                    return;
                }

                CategoryManager.delete(this.options.id, this.options.locale).then(function() {
                    this.sandbox.sulu.unlockDeleteSuccessLabel();
                    CategoryRouter.toList(this.options.locale);
                }.bind(this));
            }.bind(this));
        },

        setData: function(data) {
            this.data = data;
        },

        saveTab: function() {
            var promise = $.Deferred();

            this.sandbox.once('sulu.tab.saved', function(savedData) {
                this.setData(savedData);
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.category-save-desc', 'labels.success');

                promise.resolve(savedData);
            }.bind(this));

            this.sandbox.emit('sulu.tab.save');

            return promise;
        },

        enableSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
        },

        loadingSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
        },

        changeLanguage: function(language) {
            var locale = language.id;
            this.sandbox.sulu.saveUserSetting(CATEGORIES_LOCALE, locale);

            if (!!this.options.id) {
                this.toEdit(this.options.id, locale, this.options.content);
            } else {
                this.toNew(locale, this.options.content);
            }
        },

        afterSave: function(action, data) {
            this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
            this.sandbox.emit('sulu.header.saved', data);

            if (action === 'back') {
                this.toList();
            } else if (action === 'new') {
                this.toNew();
            } else if (!this.options.id) {
                this.toEdit(data.id);
            }
        }
    };
});
