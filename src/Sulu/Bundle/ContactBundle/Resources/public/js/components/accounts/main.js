/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['sulucontact/model/account'], function(Account) {

    'use strict';

    return {

        initialize: function() {
            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.renderForm();
            } else {
                throw 'display type wrong';
            }
        },

        getModel: function(id) {
            // FIXME: fixed challenge Cannot instantiate more than one Backbone.RelationalModel with the same id per type!
            var packageModel = Account.findOrCreate(id);
            if (!packageModel) {
                packageModel = new Account({
                    id: id
                });
            }
            return packageModel;
        },

        renderList: function() {

            this.sandbox.start([
                {name: 'accounts/components/list@sulucontact', options: { el: this.$el}}
            ]);

            // wait for navigation events
            this.sandbox.on('sulu.contacts.accounts.load', function(id) {
                this.sandbox.emit('husky.header.button-state', 'loading-add-button');
                this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/edit:' + id);
            }, this);

            // add new contact
            this.sandbox.on('sulu.contacts.accounts.new', function() {
                this.sandbox.emit('husky.header.button-state', 'loading-add-button');
                this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/add');
            }, this);

            // delete selected contacts
            this.sandbox.on('sulu.contacts.accounts.delete', function(ids) {
                if (ids.length < 1) {
                    this.sandbox.emit('sulu.dialog.error.show', 'No contacts selected for Deletion');
                    return;
                }
                this.confirmDeleteDialog(function(wasConfirmed) {
                    if (wasConfirmed) {
                        this.sandbox.emit('husky.header.button-state', 'loading-add-button');
                        ids.forEach(function(id) {
                            var account = this.getModel(id);
                            account.destroy({
                                success: function() {
                                    this.sandbox.emit('husky.datagrid.row.remove', id);
                                }.bind(this)
                            });
                        }.bind(this));
                        this.sandbox.emit('husky.header.button-state', 'standard');
                    }
                }.bind(this));
            }, this);

        },

        renderForm: function() {

            // show navigation submenu
            this.sandbox.emit('navigation.item.column.show', {
                data: this.getTabs(this.options.id)
            });

            // load data and show form
            var account;
            if (!!this.options.id) {
                account = this.getModel(this.options.id);
                account.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'accounts/components/form@sulucontact', options: { el: this.$el, data: model.toJSON()}}
                        ]);
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                    }.bind(this)
                });
            } else {
                account = new Account();
                this.sandbox.start([
                    {name: 'accounts/components/form@sulucontact', options: { el: this.$el, data: account.toJSON()}}
                ]);
            }

            // delete contact
            this.sandbox.on('sulu.contacts.accounts.delete', function(id) {
                this.confirmDeleteDialog(function(wasConfirmed) {
                    if (wasConfirmed) {
                        account = this.getModel(id);
                        this.sandbox.emit('husky.header.button-state', 'loading-delete-button');
                        account.destroy({
                            success: function() {
                                this.sandbox.emit('sulu.router.navigate', 'contacts/accounts');
                            }.bind(this)
                        });
                    }
                }.bind(this));
            }, this);

            // save contact
            this.sandbox.on('sulu.contacts.accounts.save', function(data) {
                if (!!data.id) {
                    account = this.getModel(data.id);
                } else {
                    account = new Account();
                }
                this.sandbox.emit('husky.header.button-state', 'loading-save-button');
                account.set(data);
                account.save(null, {
                    // on success save contacts id
                    success: function(response) {
                        var model = response.toJSON();
                        this.sandbox.emit('husky.header.button-state', 'standard');
                        this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/edit:' + model.id);
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while saving profile");
                    }.bind(this)
                });
            }, this);
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
                }
            });

            // submit -> delete
            this.sandbox.once('husky.dialog.submit', function() {
                this.sandbox.emit('husky.dialog.hide');
                if (!!callbackFunction) {
                    callbackFunction(true);
                }
            }.bind(this));

            // cancel
            this.sandbox.once('husky.dialog.cancel', function() {
                this.sandbox.emit('husky.dialog.hide');
                if (!!callbackFunction) {
                    callbackFunction(false);
                }
            }.bind(this));
        },


        // Navigation
        getTabs: function(id) {
            //TODO Simplify this task for bundle developer?
            var cssId = id || 'new',

            // TODO translate
                navigation = {
                    'title': 'Contact',
                    'header': {
                        'displayOption': 'link',
                        'action': 'contacts/accounts'
                    },
                    'hasSub': 'true',
                    'displayOption': 'content',
                    //TODO id mandatory?
                    'sub': {
                        'items': []
                    }
                };

            if (!!id) {
                navigation.sub.items.push({
                    'title': 'Details',
                    'action': 'contacts/accounts/edit:' + cssId + '/details',
                    'hasSub': false,
                    'type': 'content',
                    'selected': true,
                    'id': 'contacts-details-' + cssId
                });
            }

            return navigation;
        }

    };
});
