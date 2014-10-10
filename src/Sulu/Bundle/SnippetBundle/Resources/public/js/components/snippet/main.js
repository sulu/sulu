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

    return {
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
                var data = this.content.toJSON();

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
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
                    this.model.destroy({
                        success: function() {
                            this.sandbox.emit('sulu.router.navigate', 'snippet/snippets');
                        }.bind(this)
                    });
                }
            }.bind(this));
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
                this.sandbox.emit('sulu.dialog.error.show', 'No snippets selected for Deletion');
                return;
            }
            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    ids.forEach(function(id) {
                        var snippet = new Snippet({id: id});
                        snippet.destroy({
                            success: function() {
                                this.sandbox.emit('husky.datagrid.record.remove', id);
                            }.bind(this)
                        });
                    }.bind(this));
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
});
