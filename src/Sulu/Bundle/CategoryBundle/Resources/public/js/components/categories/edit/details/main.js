/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!./form.html'], function(form) {

    'use strict';

    var defaults = {
            options: {
                data: {},
                instanceName: 'category',
                newCategoryTitle: 'sulu.category.new-category'
            },
            templates: {
                form: form,
                keywordsUrl: '/admin/api/categories/<%= category %>/keywords<% if (typeof id !== "undefined") { %>/<%= id %><% } %><% if (typeof postfix !== "undefined") { %><%= postfix %><% } %>?locale=<%= locale %><% if (typeof ids !== "undefined") { %>&ids=<%= ids.join(",") %><% } %><% if (typeof force !== "undefined") { %>&force=<%= force %><% } %>'
            },
            translations: {
                name: 'public.name',
                key: 'public.key',
                yes: 'public.yes',
                no: 'public.no',
                categoryKey: 'sulu.category.category-key',
                keywords: 'sulu.category.keywords',
                keywordDeleteLabel: 'labels.success.delete-desc',
                keywordDeleteMessage: 'labels.success.delete-desc',
                conflictTitle: 'sulu.category.keyword_conflict.title',
                conflictMessage: 'sulu.category.keyword_conflict.message',
                conflictOverwrite: 'sulu.category.keyword_conflict.overwrite',
                conflictDetach: 'sulu.category.keyword_conflict.detach',
                mergeTitle: 'sulu.category.keyword_merge.title',
                mergeMessage: 'sulu.category.keyword_merge.message'
            }
        },

        constants = {
            detailsFromSelector: '#category-form',
            lastClickedCategorySettingsKey: 'categoriesLastClicked'
        };

    return {

        defaults: defaults,

        layout: {},

        /**
         * Initializes the collections list
         */
        initialize: function() {
            this.saved = true;
            this.locale = this.options.locale;

            this.prepareData(this.options.data);

            this.bindCustomEvents();
            this.render();

            if (!!this.data.id) {
                this.sandbox.sulu.saveUserSetting(constants.lastClickedCategorySettingsKey, this.data.id);
            }
        },

        /**
         * Prepare the data with fallbacks.
         */
        prepareData: function(data) {
            this.data = data;
            if (this.data.defaultLocale === this.data.locale && this.data.locale !== this.locale) {
                this.fallbackData = {locale: this.data.locale, name: this.data.name};
                this.data.name = null;
            }
            this.data.locale = this.locale;
        },

        /**
         * Binds custom related events
         */
        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.category.categories.list');
            }.bind(this));

            this.sandbox.on('sulu.toolbar.save', this.saveDetails.bind(this));
            this.sandbox.on('sulu.toolbar.delete', this.deleteCategory.bind(this));

            // add clicked
            this.sandbox.on('sulu.toolbar.add', function() {
                this.sandbox.emit('husky.datagrid.record.add', {
                    id: '',
                    keyword: '',
                    locale: this.locale
                });
            }.bind(this));

            // resolve conflict
            this.sandbox.on('husky.datagrid.data.save.failed', function(jqXHR, textStatus, error, data) {
                this.handleFail(jqXHR, data);
            }.bind(this));
        },

        /**
         * Renderes the details tab
         */
        render: function() {
            var placeholder = this.sandbox.translate('sulu.category.category-name');

            if (!!this.fallbackData) {
                placeholder = this.fallbackData.locale.toUpperCase() + ': ' + this.fallbackData.name;
            }

            this.sandbox.dom.html(
                this.$el,
                this.templates.form({
                    placeholder: placeholder,
                    translations: this.translations,
                    keywords: !!this.options.data.id
                })
            );
            this.sandbox.form.create(constants.detailsFromSelector);
            this.sandbox.form.setData(constants.detailsFromSelector, this.data).then(function() {
                this.bindDomEvents();

                if (!!this.options.data.id) {
                    this.startKeywordList();
                }
            }.bind(this));
        },

        /**
         * starts editable list for keywords.
         */
        startKeywordList: function() {
            this.sandbox.sulu.initListToolbarAndList.call(
                this,
                'keywords',
                this.templates.keywordsUrl({category: this.options.data.id, postfix: '/fields', locale: this.locale}),
                {
                    el: this.$find('#keywords-list-toolbar'),
                    template: this.sandbox.sulu.buttons.get({
                        add: {options: {position: 0}},
                        deleteSelected: {
                            options: {
                                position: 1, callback: function() {
                                    this.deleteKeywords();
                                }.bind(this)
                            }
                        }
                    }),
                    parentTemplate: 'default',
                    listener: 'default'
                },
                {
                    el: this.$find('#keywords-list'),
                    url: this.templates.keywordsUrl({category: this.options.data.id, locale: this.locale}),
                    resultKey: 'keywords',
                    searchFields: ['keyword'],
                    saveParams: {locale: this.locale},
                    contentFilters: {
                        categoryTranslationCount: function(content) {
                            return content > 1 ? this.translations.yes : this.translations.no;
                        }.bind(this)
                    },
                    viewOptions: {
                        table: {
                            editable: true,
                            validation: true
                        },
                        dropdown: {
                            limit: 100
                        }
                    }
                },
                'keywords'
            );
        },

        handleFail: function(jqXHR, data) {
            if (jqXHR.status === 409 && jqXHR.responseJSON.code === 2002) {
                this.handleConflict(data.id, data.keyword);
            } else if (jqXHR.status === 409 && jqXHR.responseJSON.code === 2001) {
                this.resolveConflict('merge', data.id, data.keyword);
            }
        },

        /**
         * Handle conflicting response.
         *
         * @param {Integer} keywordId
         * @param {String} keyword
         */
        handleConflict: function(keywordId, keyword) {
            var $container = this.sandbox.dom.createElement('<div/>');
            this.$el.append($container);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        cssClass: 'alert',
                        removeOnClose: true,
                        openOnStart: true,
                        instanceName: 'warning',
                        slides: [
                            {
                                title: this.translations.conflictTitle,
                                message: this.translations.conflictMessage,
                                okCallback: function() {
                                    this.resolveConflict('overwrite', keywordId, keyword);
                                }.bind(this),
                                buttons: [
                                    {
                                        text: this.translations.conflictOverwrite,
                                        type: 'ok',
                                        align: 'right'
                                    },
                                    {
                                        text: this.translations.conflictDetach,
                                        align: 'center',
                                        callback: function() {
                                            this.resolveConflict('detach', keywordId, keyword);
                                            this.sandbox.emit('husky.overlay.warning.close');
                                        }.bind(this)
                                    },
                                    {
                                        type: 'cancel',
                                        align: 'left'
                                    }
                                ]
                            }
                        ]
                    }
                }
            ]);
        },

        /**
         * Resolves conflict.
         *
         * @param {String} type
         * @param {Integer} keywordId
         * @param {Object} keyword
         */
        resolveConflict: function(type, keywordId, keyword) {
            var data = {
                id: keywordId,
                keyword: keyword
            };

            this.sandbox.util.save(
                this.templates.keywordsUrl(
                    {
                        category: this.options.data.id,
                        id: keywordId,
                        locale: this.locale,
                        force: type
                    }
                ),
                'PUT',
                data
            ).then(function(newData) {
                if (newData.id !== data.id) {
                    this.sandbox.emit('husky.datagrid.record.remove', data.id);
                    this.sandbox.emit('husky.datagrid.record.add', newData);
                } else {
                    this.sandbox.emit('husky.datagrid.records.change', data);
                }
            }.bind(this)).fail(function(jqXHR) {
                this.handleFail(jqXHR, data);
            }.bind(this));
        },

        /**
         * Binds DOM-Events for the details tab
         */
        bindDomEvents: function() {
            // activate save-button on key input
            this.sandbox.dom.on(constants.detailsFromSelector, 'change keyup', function() {
                if (this.saved === true) {
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
                    this.saved = false;
                }
            }.bind(this), 'input:not(.editable-input)');
        },

        /**
         * Deletes the current category
         */
        deleteCategory: function() {
            if (!!this.data.id) {
                this.sandbox.emit('sulu.category.categories.delete', [this.data.id], null, function() {
                    this.sandbox.sulu.unlockDeleteSuccessLabel();
                    this.sandbox.emit('sulu.category.categories.list');
                }.bind(this));
            }
        },

        /**
         * Deletes the selected keywords.
         */
        deleteKeywords: function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                    if (confirmed === true) {
                        this.sandbox.util.save(
                            this.templates.keywordsUrl({
                                category: this.options.data.id,
                                locale: this.locale,
                                ids: ids
                            }),
                            'DELETE'
                        ).then(function() {
                            for (var i = 0, length = ids.length; i < length; i++) {
                                this.sandbox.emit('husky.datagrid.record.remove', ids[i]);
                            }

                            this.sandbox.emit(
                                'sulu.labels.success.show',
                                this.translations.keywordDeleteMessage,
                                this.translations.keywordDeleteLabel
                            );
                        }.bind(this));
                    }
                }.bind(this));
            }.bind(this));
        },

        /**
         * Saves the details-tab
         */
        saveDetails: function(action) {
            if (this.sandbox.form.validate(constants.detailsFromSelector)) {
                var data = this.sandbox.form.getData(constants.detailsFromSelector);
                this.data = this.sandbox.util.extend(true, {}, this.data, data);
                this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
                this.sandbox.emit('sulu.category.categories.save', this.data, this.savedCallback.bind(this, !this.data.id, action));
            }
        },

        /**
         * Method which gets called after the save-process has finished
         * @param {Boolean} toEdit if true the form will be navigated to the edit-modus
         * @param {String} action 'new', 'back' or 'edit
         * @param {Object} result the saved category model or the error model
         * @param {Boolean} success to trigger success callback, false to trigger error callback
         */
        savedCallback: function(toEdit, action, result, success) {
            if (success === true) {
                this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
                this.saved = true;
                if (action === 'back') {
                    this.sandbox.emit('sulu.category.categories.list');
                } else if (action === 'new') {
                    this.sandbox.emit('sulu.category.categories.form-add', this.options.parent);
                } else if (toEdit === true) {
                    this.sandbox.emit('sulu.category.categories.form', result.id);
                }
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.category-save-desc', 'labels.success');
            } else {
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
                if (result.code === 1) {
                    this.sandbox.emit('sulu.labels.error.show', 'labels.error.category-unique-key', 'labels.error');
                } else {
                    this.sandbox.emit('sulu.labels.error.show', 'labels.success.category-save-error', 'labels.error');
                }
            }
        }
    };
});
