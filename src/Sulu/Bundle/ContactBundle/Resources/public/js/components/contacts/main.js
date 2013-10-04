/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontact/model/contact',
    'text!/contact/navigation/content'
], function(Contact, contentNavigation) {

    'use strict';

    console.log('contentNavigation',contentNavigation);



    // FIXME: remove function for hiding global vars
    return (function() {

        var sandbox;

        return {

            initialize: function() {
                sandbox = this.sandbox;
                if (this.options.display === 'list') {
                    this.renderList();
                } else if (this.options.display === 'form') {
                    this.renderForm();
                } else {
                    throw 'display type wrong';
                }
            },

            renderList: function() {

                sandbox.start([
                    {name: 'contacts/components/list@sulucontact', options: { el: this.$el}}
                ]);

                // wait for navigation events
                sandbox.on('sulu.contacts.contacts.load', function(id) {
                    sandbox.emit('husky.header.button-state', 'loading-add-button');
                    sandbox.emit('sulu.router.navigate', 'contacts/contacts/edit:' + id);
                }, this);

                // add new contact
                sandbox.on('sulu.contacts.contacts.new', function(id) {
                    sandbox.emit('husky.header.button-state', 'loading-add-button');
                    sandbox.emit('sulu.router.navigate', 'contacts/contacts/add');
                }, this);

                // delete selected contacts
                sandbox.on('sulu.contacts.contacts.delete', function(ids) {
                    if (ids.length < 1) {
                        sandbox.emit('sulu.dialog.error.show', 'No contacts selected for Deletion');
                        return;
                    }
                    this.confirmDeleteDialog(function(wasConfirmed) {
                        if (wasConfirmed) {
                            sandbox.emit('husky.header.button-state', 'loading-add-button');
                            ids.forEach(function(id) {
                                var contact = new Contact({id: id});
                                contact.destroy({
                                    success: function() {
                                        sandbox.emit('husky.datagrid.row.remove', id);
                                    }
                                });
                            });
                            sandbox.emit('husky.header.button-state', 'standard');
                        }
                    });
                });

            },


            renderForm: function() {

                // show navigation submenu

                sandbox.emit('navigation.item.column.show', {
                    data: this.getTabs(this.options.id)
                });


                // load data and show form
                var contact = new Contact();
                if (!!this.options.id) {
                    contact.set({id: this.options.id});
                    contact.fetch({
                        success: function(model) {
                            sandbox.start([
                                {name: 'contacts/components/form@sulucontact', options: { el: this.$el, data: model.toJSON()}}
                            ]);
                        }.bind(this),
                        error: function() {
                            sandbox.logger.log("error while fetching contact");
                        }
                    });
                } else {
                    sandbox.start([
                        {name: 'contacts/components/form@sulucontact', options: { el: this.$el, data: contact.toJSON()}}
                    ]);
                }

                // delete contact
                sandbox.on('sulu.contacts.contacts.delete', function() {
                    this.confirmDeleteDialog(function(wasConfirmed) {
                        if (wasConfirmed) {
                            sandbox.emit('husky.header.button-state', 'loading-delete-button');
                            contact.destroy({
                                success: function() {
                                    sandbox.emit('sulu.router.navigate', 'contacts/contacts');
                                }
                            });
                        }
                    });
                }, this);

                // save contact
                sandbox.on('sulu.contacts.contacts.save', function(data) {
                    sandbox.emit('husky.header.button-state', 'loading-save-button');
                    contact.set(data);
                    contact.save(null, {
                        // on success save contacts id
                        success: function(response) {
                            sandbox.emit('husky.header.button-state', 'standard');
                            sandbox.emit('sulu.contacts.contacts.saved', response.id);
                        }.bind(this),
                        error: function() {
                            sandbox.logger.log("error while saving profile");
                        }
                    });
                });
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
                sandbox.emit('sulu.dialog.confirmation.show', {
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
                sandbox.once('husky.dialog.submit', function() {
                    sandbox.emit('husky.dialog.hide');
                    if (!!callbackFunction) {
                        callbackFunction(true);
                    }
                });

                // cancel
                sandbox.once('husky.dialog.cancel', function() {
                    sandbox.emit('husky.dialog.hide');
                    if (!!callbackFunction) {
                        callbackFunction(false);
                    }
                });
            },


            // TODO: this function must be globally available (for every related main function)
            // Navigation
            getTabs: function(id) {
                //TODO Simplify this task for bundle developer?
                var cssId = id || 'new',

                // TODO translate
                    navigation = {
                        'title': 'Contact',
                        'header': {
                            'title': 'Contact'
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
                        'action': 'contacts/contacts/edit:' + cssId + '/details',
                        'hasSub': false,
                        'type': '' +
                            '',
                        'id': 'contacts-details-' + cssId
                    });
                }

                var contents = JSON.parse(contentNavigation);

                for (var i = 0; i<contents.length; i++) {
                    // contact must be set before optional tabs can be opened
                    if (!!id) {
                        console.log("element",contents[i]);
                        navigation.sub.items.push({
                            'title': contents[i].title,
                            'action': 'contacts/contacts/edit:' + cssId + '/' + contents[i].url,
                            'hasSub': false,
                            'type': 'content',
                            'id': 'contacts-'+contents[i].url+'-' + cssId
                        });
                    }
                }

                return navigation;
            }
        }

    })();
});
