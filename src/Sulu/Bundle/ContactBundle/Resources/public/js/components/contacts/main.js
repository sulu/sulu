/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['sulucontact/model/contact'], function(Contact) {

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

//        getModel: function(id) {
//            // FIXME: fixed challenge Cannot instantiate more than one Backbone.RelationalModel with the same id per type!
//            var packageModel = Contact.findOrCreate(id);
//            if (!packageModel) {
//                packageModel = new Contact({
//                    id: id
//                });
//            }
//            return packageModel;
//        },

        renderList: function() {

           this.sandbox.start([
                {name: 'contacts/components/list@sulucontact', options: { el: this.$el}}
            ]);

            // wait for navigation events
           this.sandbox.on('sulu.contacts.contacts.load', function(id) {
               this.sandbox.emit('husky.header.button-state', 'loading-add-button');
               this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/edit:' + id);
            }, this);

            // add new contact
           this.sandbox.on('sulu.contacts.contacts.new', function() {
               this.sandbox.emit('husky.header.button-state', 'loading-add-button');
               this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/add');
            }, this);

            // delete selected contacts
           this.sandbox.on('sulu.contacts.contacts.delete', function(ids) {
                if (ids.length<1) {
                    this.sandbox.emit('sulu.dialog.error.show', 'No contacts selected for Deletion');
                    return;
                }
                this.confirmDeleteDialog(function(wasConfirmed) {
                    if (wasConfirmed) {
                       this.sandbox.emit('husky.header.button-state', 'loading-add-button');
                        ids.forEach(function(id) {
                            //var contact = this.getModel(id);
                            var contact = new Contact({id:id});
                            contact.destroy({
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
            var contact;
            if (!!this.options.id) {
                contact = new Contact({id:this.options.id});
                //contact = this.getModel(this.options.id);
                contact.fetch({
                    success: function(model) {
                       this.sandbox.start([
                            {name: 'contacts/components/form@sulucontact', options: { el: this.$el, data: model.toJSON()}}
                        ]);
                    }.bind(this),
                    error: function() {
                       this.sandbox.logger.log("error while fetching contact");
                    }.bind(this)
                });
            } else {
                contact = new Contact();
                this.sandbox.start([
                    {name: 'contacts/components/form@sulucontact', options: { el: this.$el, data: contact.toJSON()}}
                ]);
            }

            // delete contact
            this.sandbox.on('sulu.contacts.contacts.delete', function(id) {
                this.confirmDeleteDialog(function(wasConfirmed) {
                    if(wasConfirmed) {
                        //contact = this.getModel(id);
                        this.sandbox.emit('husky.header.button-state', 'loading-delete-button');
                        contact.destroy({
                            success: function() {
                               this.sandbox.emit('sulu.router.navigate', 'contacts/contacts');
                            }.bind(this)
                        });
                    }
                }.bind(this));
            }, this);

            // save contact
           this.sandbox.on('sulu.contacts.contacts.save',function(data) {
               this.sandbox.emit('husky.header.button-state', 'loading-save-button');
                contact.set(data);
                contact.save(null, {
                    // on success save contacts id
                    success: function(response) {
                        var model = response.toJSON();
                        this.sandbox.emit('husky.header.button-state', 'standard');
                        this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/edit:' + model.id);
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
        confirmDeleteDialog : function(callbackFunction) {
            // check if callback is a function
            if ( !!callbackFunction && typeof(callbackFunction)!=='function') { throw 'callback is not a function'; }

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
                if (!!callbackFunction) { callbackFunction(true); }
           }.bind(this));

            // cancel
           this.sandbox.once('husky.dialog.cancel', function() {
               this.sandbox.emit('husky.dialog.hide');
                if (!!callbackFunction) { callbackFunction(false); }
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
                    'displayOption':'link',
                    'action':'contacts/contacts'
                },
                'hasSub': 'true',
                'displayOption':'content',
                //TODO id mandatory?
                'sub': {
                    'items': []
                }
            };

            if (!!id) {
                navigation.sub.items.push({
                    'title': 'Details',
                    'action': 'contacts/contacts/edit:' + cssId+ '/details',
                    'hasSub': false,
                    'type': 'content',
                    'selected': true,
                    'id': 'contacts-details-' + cssId
                });

                navigation.sub.items.push({
                    'title': 'Permissions',
                    'action': 'contacts/contacts/edit:' + cssId + '/permissions',
                    'hasSub': false,
                    'type': 'content',
                    'id': 'contacts-permission-' + cssId
                });

                navigation.sub.items.push({
                    'title': 'Settings',
                    'action': 'contacts/contacts/edit:' + cssId + '/settings',
                    'hasSub': false,
                    'type': 'content',
                    'id': 'contacts-settings-' + cssId
                });
            }

            return navigation;
        }

    };
});
