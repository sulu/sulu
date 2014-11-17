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

    var CONTENT_LANGUAGE = 'contentLanguage';

    var BaseSnippet = function() {
    };

    var translationKeys = {
        deleteReferencedByFollowing: 'snippet.delete-referenced-by-following',
        deleteConfirmText: 'snippet.delete-confirm-text',
        deleteConfirmTitle: 'snippet.delete-confirm-title',
        deleteDoIt: 'snippet.delete-do-it',
        deleteNoSnippetsSelected: 'snippet.delete-no-snippets-selected'
    };

    BaseSnippet.prototype = {
        bindModelEvents: function() {
            // delete current
            this.sandbox.on('sulu.snippets.snippet.delete', function() {
                this.del();
            }, this);

            // save the current
            this.sandbox.on('sulu.snippets.snippet.save', function(data) {
                this.save(data);
            }, this);

            // wait for navigation events
            this.sandbox.on('sulu.snippets.snippet.load', function(id, language) {
                this.load(id, language);
            }, this);

            // add new
            this.sandbox.on('sulu.snippets.snippet.new', function(language) {
                this.add(language);
            }, this);

            // delete selected
            this.sandbox.on('sulu.snippets.snippets.delete', function(ids) {
                this.delSnippets(ids);
            }, this);

            // load list view
            this.sandbox.on('sulu.snippets.snippet.list', function() {
                this.sandbox.emit('sulu.router.navigate', 'snippet/snippets');
            }, this);

            // change language
            this.sandbox.on('sulu.header.toolbar.language-changed', function(item) {
                this.sandbox.sulu.saveUserSetting(CONTENT_LANGUAGE, item.localization);
                var data = this.model.toJSON();

                // if there is a index id this should be after reload
                if (this.options.id === 'index') {
                    data.id = this.options.id;
                }

                if (this.type === 'edit') {
                    this.sandbox.emit('sulu.snippets.snippet.load', data.id, item.localization);
                } else if (this.type === 'add') {
                    this.sandbox.emit('sulu.snippets.snippet.new', item.localization);
                } else {
                    this.sandbox.emit('sulu.snippets.snippet.list');
                }
            }, this);
        },

        del: function() {
            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    this.destroySnippet(this.model, function () {
                        this.sandbox.emit('sulu.router.navigate', 'snippet/snippets');
                    }.bind(this));

                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
                }
            }.bind(this));
        },

        destroySnippet: function (snippet, successCallback) {
            snippet.destroy({
                success: function() {
                    successCallback();
                }.bind(this),
                error: function (model, response) {
                    if (response.status == 409) {
                        this.referentialIntegrityDialog(snippet, response.responseJSON, successCallback);
                    }
                }.bind(this)
            });
        },

        referentialIntegrityDialog: function (snippet, data, successCallback) {
            var message = [];
            message.push(this.sandbox.translate(translationKeys.deleteReferencedByFollowing));
            message.push('');

            this.sandbox.util.foreach(data.structures, function (structure) {
                message.push(' - ' + structure.title);
            });

            message.push('');
            message.push(this.sandbox.translate(translationKeys.deleteConfirmText));

            var $element = $('<div/>');
            $('body').append($element);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $element,
                        title: this.sandbox.translate(translationKeys.deleteConfirmTitle),
                        message: message.join('<br/>'),
                        okDefaultText: this.sandbox.translate(translationKeys.deleteDoIt),
                        type: 'warning',
                        closeCallback: function() {
                        },
                        okCallback: function(data) {
                            snippet.destroy({
                                headers: {
                                    SuluForceRemove: true,
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

        save: function(data) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');
            if (!!this.template) {
                data.template = this.template;
            } else {
                var config = AppConfig.getSection('sulu-snippet');

                data.template = config.defaultType;
            }
            this.model.set(data);

            this.model.fullSave(this.template, this.options.language, this.state, {}, {
                // on success save contacts id
                success: function(response) {
                    var data = response.toJSON();
                    if (!!this.data.id) {
                        this.sandbox.emit('sulu.snippets.snippet.saved', data);
                    } else {
                        this.sandbox.emit('sulu.router.navigate', 'snippet/snippets/' + this.options.language + '/edit:' + data.id);
                    }
                }.bind(this),
                error: function() {
                    this.sandbox.emit('sulu.snippets.snippet.save-error');
                    this.sandbox.logger.log('error while saving profile');
                }.bind(this)
            });
        },

        load: function(id, language) {
            if (!language) {
                language = this.options.language;
            }

            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'snippet/snippets/' + language + '/edit:' + id);
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

            var deleteSnippetFromStack = function () {
                var id = ids.shift();
                if (id !== undefined) {
                    var snippet = new Snippet({id: id});
                    this.destroySnippet(snippet, function () {
                        this.sandbox.emit('husky.datagrid.record.remove', id);
                        deleteSnippetFromStack();
                    }.bind(this));
                }
            }.bind(this);

            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    deleteSnippetFromStack();
                }
            }.bind(this));
        },

        /**
         * @var ids - array of ids to delete
         * @var callback - callback function returns true or false if data got deleted
         */
        confirmDeleteDialog: function(callbackFunction) {
            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }
            // show dialog
            this.sandbox.emit('sulu.overlay.show-warning',
                'sulu.overlay.be-careful',
                'sulu.overlay.delete-desc',
                callbackFunction.bind(this, false),
                callbackFunction.bind(this, true)
            );
        }
    };

    return BaseSnippet;
});
