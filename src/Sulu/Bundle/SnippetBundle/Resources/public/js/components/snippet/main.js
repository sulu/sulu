/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulusnippet/model/snippet'
], function(Snippet) {

    'use strict';

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
            this.sandbox.on('sulu.snippets.snippet.load', function(id) {
                this.load(id);
            }, this);

            // add new
            this.sandbox.on('sulu.snippets.snippet.new', function() {
                this.add();
            }, this);

            // delete selected
            this.sandbox.on('sulu.snippets.snippet.delete', function(ids) {
                this.delSnippets(ids);
            }, this);

            // load list view
            this.sandbox.on('sulu.snippets.snippet.list', function() {
                this.sandbox.emit('sulu.router.navigate', 'snippet/snippets');
            }, this);
        },

        del: function() {
            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
                    this.snippet.destroy({
                        success: function() {
                            this.sandbox.emit('sulu.router.navigate', 'snippet/snippets');
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        save: function(data) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');
            this.snippet.set(data);

            this.snippet.save(null, {
                // on success save contacts id
                success: function(response) {
                    var model = response.toJSON();
                    if (!!data.id) {
                        this.sandbox.emit('sulu.snippets.snippet.saved', model);
                    } else {
                        this.sandbox.emit('sulu.router.navigate', 'snippets/snippet/edit:' + model.id + '/details');
                    }
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log('error while saving profile');
                }.bind(this)
            });
        },

        load: function(id) {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'snippets/snippet/edit:' + id + '/details');
        },

        add: function() {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'snippets/snippet/add');
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
