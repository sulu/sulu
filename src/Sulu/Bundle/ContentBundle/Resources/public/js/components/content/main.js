/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontent/model/content',
    'text!/admin/content/navigation/content'
], function(Content, ContentNavigation) {

    'use strict';

    return {

        initialize: function() {
            this.bindCustomEvents();

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.renderForm();
            } else {
                throw 'display type wrong';
            }
        },

        bindCustomEvents: function() {
            // delete contact
            this.sandbox.on('sulu.content.contents.delete', function() {
                this.del();
            }, this);

            // save the current package
            this.sandbox.on('sulu.content.contents.save', function(data, parent) {
                this.save(data);
            }, this);

            // wait for navigation events
            this.sandbox.on('sulu.content.contents.load', function(id) {
                this.load(id);
            }, this);

            // add new contact
            this.sandbox.on('sulu.content.contents.new', function() {
                this.add();
            }, this);

            // delete selected contacts
            this.sandbox.on('sulu.content.contents.delete', function(ids) {
                this.delContents(ids);
            }, this);
        },

        del: function() {
           // TODO Delete
        },

        save: function(data) {
            this.sandbox.emit('husky.header.button-state', 'loading-save-button');
            this.content.set(data);

            // TODO select template
            this.content.saveTemplate(null, 'overview', this.options.parent, {
                // on success save contacts id
                success: function(response) {
                    var model = response.toJSON();
                    if (!!this.options.id) {
                        this.sandbox.emit('sulu.content.contents.saved', model._embedded[0].id);
                    } else {
                        this.sandbox.emit('sulu.router.navigate', 'content/contents/edit:' + model._embedded[0].id + '/details');
                    }
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving profile");
                }.bind(this)
            });
        },

        load: function(id) {
            this.sandbox.emit('husky.header.button-state', 'loading-add-button');
            this.sandbox.emit('sulu.router.navigate', 'content/contents/edit:' + id + '/details');
        },

        add: function() {
            this.sandbox.emit('husky.header.button-state', 'loading-add-button');
            this.sandbox.emit('sulu.router.navigate', 'content/contents/add');
        },

        delContents: function(ids) {

            if (ids.length < 1) {
                this.sandbox.emit('sulu.dialog.error.show','No contents selected for deletion!');
                return;
            }

            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    this.sandbox.emit('husky.header.button-state', 'loading-add-button');
                    ids.forEach(function(id) {
                        var content = new Content({id: id});
                        content.destroy({
                            success: function() {
                                this.sandbox.emit('husky.datagrid.row.remove', id);
                            }.bind(this),
                            error: function(){
                               // TODO error message
                            }
                        });
                    }.bind(this));
                    this.sandbox.emit('husky.header.button-state', 'standard');
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
            this.sandbox.emit('sulu.dialog.confirmation.show', {
                content: {
                    title: "Be careful!",
                    content: "<p>The operation you are about to do will delete data.<br/>This is not undoable!</p><p>Please think about it and accept or decline.</p>"
                },
                footer: {
                    buttonCancelText: "Don't do it",
                    buttonSubmitText: "Do it, I understand"
                },
                callback: {
                    submit: function() {
                        this.sandbox.emit('husky.dialog.hide');
                        if (!!callbackFunction) {
                            callbackFunction(true);
                        }
                    }.bind(this),
                    cancel: function() {
                        this.sandbox.emit('husky.dialog.hide');
                        if (!!callbackFunction) {
                            callbackFunction(false);
                        }
                    }.bind(this)
                }
            });
        },

        renderList: function() {
            this.sandbox.start([
                {name: 'content/components/list@sulucontent', options: { el: this.$el}}
            ]);
        },

        renderForm: function() {

            this.sandbox.sulu.navigation.getContentTabs(ContentNavigation,this.options.id);

            // load data and show form
            this.content = new Content();
            if (!!this.options.id) {
                this.content = new Content({id: this.options.id});
                this.content.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'content/components/form@sulucontent', options: { el: this.$el, data: model.toJSON()}}
                        ]);
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                    }.bind(this)
                });
            } else {
                this.sandbox.start([
                    {name: 'content/components/form@sulucontent', options: { el: this.$el, data: this.content.toJSON()}}
                ]);
            }

        }
    };
});
