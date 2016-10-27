/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'services/sulucategory/category-manager', 'text!./form.html'], function($, CategoryManager, form) {

    'use strict';

    var defaults = {
        templates: {
            form: form,
            keywordsUrl: [
                '/admin/api/categories',
                '/<%= category %>',
                '/keywords',
                '<% if (typeof id !== "undefined") { %>/<%= id %><% } %>',
                '<% if (typeof postfix !== "undefined") { %><%= postfix %><% } %>',
                '?locale=<%= locale %>',
                '<% if (typeof ids !== "undefined") { %>&ids=<%= ids.join(",") %><% } %>',
                '<% if (typeof force !== "undefined") { %>&force=<%= force %><% } %>'
            ].join('')
        },
        translations: {
            name: 'public.name',
            key: 'public.key',
            yes: 'public.yes',
            no: 'public.no',
            description: 'public.description',
            categoryKey: 'sulu.category.category-key',
            keywords: 'sulu.category.keywords',
            keywordDeleteLabel: 'labels.success.delete-desc',
            keywordDeleteMessage: 'labels.success.delete-desc',
            conflictTitle: 'sulu.category.keyword_conflict.title',
            conflictMessage: 'sulu.category.keyword_conflict.message',
            conflictOverwrite: 'sulu.category.keyword_conflict.overwrite',
            conflictDetach: 'sulu.category.keyword_conflict.detach',
            mergeTitle: 'sulu.category.keyword_merge.title',
            mergeMessage: 'sulu.category.keyword_merge.message',
            categoryName: 'sulu.category.category-name',
            medias: 'sulu.category.medias'
        }
    };

    return {

        defaults: defaults,

        type: 'form-tab',

        /**
         * This function can be overwritten by the implementation to initialize the component.
         *
         * For best-practice the default implementation should be used.
         */
        tabInitialize: function() {
            // add clicked
            this.sandbox.on('sulu.toolbar.add', function() {
                this.sandbox.emit('husky.datagrid.record.add', {
                    id: '',
                    keyword: '',
                    locale: this.options.locale
                });
            }.bind(this));

            // resolve conflict
            this.sandbox.on('husky.datagrid.data.save.failed', function(jqXHR, textStatus, error, data) {
                this.handleFail(jqXHR, data);
            }.bind(this));

            this.sandbox.on('sulu.content.changed', function() {
                this.sandbox.emit('sulu.tab.dirty');
            }.bind(this));

            this.sandbox.emit('sulu.tab.initialize', this.name);
        },

        /**
         * This function can be overwritten by the implementation.
         */
        rendered: function() {
            if (!!this.data.id) {
                this.startKeywordList();
            }

            this.sandbox.emit('sulu.tab.rendered', this.name);
        },

        /**
         * This method function has to be overwritten by the implementation to convert the data from "options.data".
         *
         * @param {object} data
         */
        parseData: function(data) {
            this.data = data;
            if (!!this.data.id
                && this.data.defaultLocale === this.data.locale
                && this.data.locale !== this.options.locale
            ) {
                this.fallbackData = {locale: this.options.locale, name: this.data.name};
                this.data.name = null;
            }
            this.data.locale = this.options.locale;
        },

        /**
         * This method function has to be overwritten by the implementation to save the data.
         *
         * @param {object} data
         */
        save: function(data) {
            if (!!this.options.parent) {
                data.parent = this.options.parent;
            }

            CategoryManager.save(data, this.options.locale).then(this.saved.bind(this));
        },

        /**
         * This method function has to be overwritten by the implementation to generate the form-template.
         */
        getTemplate: function() {
            var placeholder = this.translations.categoryName;

            if (!!this.fallbackData) {
                placeholder = this.fallbackData.locale.toUpperCase() + ': ' + this.fallbackData.name;
            }

            return this.templates.form({
                placeholder: placeholder,
                translations: this.translations,
                keywords: !!this.data.id,
                locale: this.options.locale
            });
        },

        /**
         * This method function has to be overwritten by the implementation. It should return the id for the form.
         */
        getFormId: function() {
            return '#category-form';
        },

        /**
         * starts editable list for keywords.
         */
        startKeywordList: function() {
            this.sandbox.sulu.initListToolbarAndList.call(
                this,
                'keywords',
                this.templates.keywordsUrl({category: this.data.id, postfix: '/fields', locale: this.options.locale}),
                {
                    el: this.$find('#keywords-list-toolbar'),
                    template: this.sandbox.sulu.buttons.get({
                        add: {options: {position: 0}},
                        deleteSelected: {
                            options: {
                                position: 1,
                                callback: function() {
                                    this.deleteKeywordsConfirmation();
                                }.bind(this)
                            }
                        }
                    }),
                    parentTemplate: 'default',
                    listener: 'default'
                },
                {
                    el: this.$find('#keywords-list'),
                    url: this.templates.keywordsUrl({category: this.data.id, locale: this.options.locale}),
                    resultKey: 'keywords',
                    searchFields: ['keyword'],
                    saveParams: {locale: this.options.locale},
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

        /**
         * Ask for deleting the selected keywords.
         */
        deleteKeywordsConfirmation: function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                    if (!confirmed) {
                        return;
                    }

                    this.deleteKeywords(ids);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Deletes the selected keywords.
         */
        deleteKeywords: function(ids) {
            var url = this.templates.keywordsUrl({category: this.data.id, locale: this.options.locale, ids: ids});

            this.sandbox.util.save(url, 'DELETE').then(function() {
                for (var i = 0, length = ids.length; i < length; i++) {
                    this.sandbox.emit('husky.datagrid.record.remove', ids[i]);
                }

                this.sandbox.emit(
                    'sulu.labels.success.show',
                    this.translations.keywordDeleteMessage,
                    this.translations.keywordDeleteLabel
                );
            }.bind(this));
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
         * @param {Number} keywordId
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
         * @param {Number} keywordId
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
                        id: keywordId,
                        category: this.data.id,
                        locale: this.options.locale,
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
        }
    };
});
