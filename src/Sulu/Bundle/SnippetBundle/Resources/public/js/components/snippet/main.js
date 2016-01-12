/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulusnippet/model/snippet',
    'app-config'
], function(Snippet, AppConfig) {

    'use strict';

    var CONTENT_LANGUAGE = 'contentLanguage',

        BaseSnippet = function() {
        },

        translationKeys = {
            deleteReferencedByFollowing: 'snippet.delete-referenced-by-following',
            deleteConfirmText: 'snippet.delete-confirm-text',
            deleteConfirmDefaultText: 'snippet.delete-confirm-default-text',
            deleteConfirmTitle: 'snippet.delete-confirm-title',
            deleteDoIt: 'snippet.delete-do-it',
            deleteNoSnippetsSelected: 'snippet.delete-no-snippets-selected'
        },

        templates = {
            referentialIntegrityMessage: function(pageTitles, isDefault) {
                var message = [];

                if (pageTitles.length > 0) {
                    message.push('<p>', this.sandbox.translate(translationKeys.deleteReferencedByFollowing), '</p>');

                    message.push('<ul>');

                    this.sandbox.util.foreach(pageTitles, function(pageTitle) {
                        message.push('<li>', pageTitle, '</li>');
                    });

                    message.push('</ul>');
                }

                if (!!isDefault) {
                    message.push('<p>', this.sandbox.translate(translationKeys.deleteConfirmDefaultText), '</p>');
                }

                message.push('<p>', this.sandbox.translate(translationKeys.deleteConfirmText), '</p>');

                return message.join('');
            }
        };

    BaseSnippet.prototype = {
        bindModelEvents: function() {
            // delete current
            this.sandbox.on('sulu.snippets.snippet.delete', this.del, this);

            // save the current
            this.sandbox.on('sulu.snippets.snippet.save', this.save, this);

            // wait for navigation events
            this.sandbox.on('sulu.snippets.snippet.load', this.load, this);

            // add new
            this.sandbox.on('sulu.snippets.snippet.new', this.add, this);

            // delete selected
            this.sandbox.on('sulu.snippets.snippets.delete', this.delSnippets, this);

            // load list view
            this.sandbox.on('sulu.snippets.snippet.list', function(language) {
                var route = 'snippet/snippets';

                if (!!language) {
                    route += '/' + language;
                }

                this.sandbox.emit('sulu.router.navigate', route);
            }, this);

            // change language
            this.sandbox.on('sulu.header.language-changed', function(item) {
                this.sandbox.sulu.saveUserSetting(CONTENT_LANGUAGE, item.id);

                if (this.type === 'edit') {
                    var data = this.model.toJSON();
                    this.sandbox.emit('sulu.snippets.snippet.load', data.id, item.id);
                } else if (this.type === 'add') {
                    this.sandbox.emit('sulu.snippets.snippet.new', item.id);
                } else {
                    this.sandbox.emit('sulu.snippets.snippet.list', item.id);
                }
            }, this);
        },

        del: function() {
            this.sandbox.sulu.showDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    this.destroySnippet(this.model, function() {
                        this.sandbox.emit('sulu.router.navigate', 'snippet/snippets');
                    }.bind(this));

                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'settings');
                }
            }.bind(this));
        },

        destroySnippet: function(snippet, successCallback) {
            snippet.destroy({
                success: function() {
                    successCallback();
                }.bind(this),
                error: function(model, response) {
                    if (response.status == 409) {
                        this.referentialIntegrityDialog(snippet, response.responseJSON, successCallback);
                    }
                }.bind(this)
            });
        },

        referentialIntegrityDialog: function(snippet, data, successCallback) {
            var pageTitles = [];

            this.sandbox.util.foreach(data.structures, function(structure) {
                pageTitles.push(structure.title);
            });

            var $element = $('<div/>');
            $('body').append($element);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $element,
                        openOnStart: true,
                        title: this.sandbox.translate(translationKeys.deleteConfirmTitle),
                        message: templates.referentialIntegrityMessage.call(this, pageTitles, data.isDefault),
                        okDefaultText: this.sandbox.translate(translationKeys.deleteDoIt),
                        type: 'alert',
                        closeCallback: function() {
                        },
                        okCallback: function() {
                            snippet.destroy({
                                headers: {
                                    SuluForceRemove: true
                                },
                                success: function() {
                                    successCallback()
                                }.bind(this)
                            });
                        }.bind(this)
                    }
                }
            ]);
        },

        save: function(data, action) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
            if (!!this.template) {
                data.template = this.template;
            } else {
                var config = AppConfig.getSection('sulu-snippet');

                data.template = config.defaultType;
            }
            this.model.set(data);

            this.model.fullSave(this.options.language, null, {}, {
                // on success save contacts id
                success: function(response) {
                    var data = response.toJSON();
                    if (!!this.data.id) {
                        this.sandbox.emit('sulu.snippets.snippet.saved', data);
                    }
                    if (action === 'back') {
                        this.sandbox.emit('sulu.snippets.snippet.list');
                    } else if (action === 'new') {
                        this.sandbox.emit('sulu.router.navigate', 'snippet/snippets/' + this.options.language + '/add', true, true);
                    } else if (!this.data.id) {
                        this.sandbox.emit('sulu.router.navigate', 'snippet/snippets/' + this.options.language + '/edit:' + data.id);
                    }
                }.bind(this),
                error: function() {
                    this.sandbox.emit('sulu.snippets.snippet.save-error');
                    this.sandbox.logger.log('error while saving profile');
                }.bind(this)
            });
        },

        load: function(id, language, forceReload) {
            if (!language) {
                language = this.options.language;
            }

            // TODO: show loading icon
            this.sandbox.emit(
                'sulu.router.navigate',
                'snippet/snippets/' + language + '/edit:' + id,
                undefined, undefined,
                forceReload
            );
        },

        add: function(language) {
            if (!language) {
                language = this.options.language;
            }

            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'snippet/snippets/' + language + '/add');
        },

        delSnippets: function(ids) {
            if (ids.length < 1) {
                this.sandbox.emit('sulu.dialog.error.show', this.sandbox.translate(translationKeys.deleteNoSnippetsSelected));
                return;
            }

            var deleteSnippetFromStack = function() {
                var id = ids.shift();
                if (id !== undefined) {
                    var snippet = new Snippet({id: id});
                    this.destroySnippet(snippet, function() {
                        this.sandbox.emit('husky.datagrid.record.remove', id);
                        deleteSnippetFromStack();
                    }.bind(this));
                }
            }.bind(this);

            this.sandbox.sulu.showDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    deleteSnippetFromStack();
                }
            }.bind(this));
        },

        /**
         * Returns copy snippet from a given locale to a array of other locales url
         * @param {string} id
         * @param {string} src
         * @param {string[]} dest
         * @returns {string}
         */
        getCopyLocaleUrl: function(id, src, dest) {
            return [
                '/admin/api/snippets/', id, '?language=', src, '&dest=', dest, '&action=copy-locale'
            ].join('');
        }
    };

    return BaseSnippet;
});
