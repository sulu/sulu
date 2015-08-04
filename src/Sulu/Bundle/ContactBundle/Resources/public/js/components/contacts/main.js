/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/sulucontact/contact-manager',
    'services/sulucontact/contact-router',
    'sulucontact/models/contact',
    'sulucontact/models/title',
    'sulucontact/models/position',
    'sulucategory/model/category',
    'contactsutil/delete-dialog'
], function(ContactManager, ContactRouter, Contact, Title, Position, Category, DeleteDialog) {

    'use strict';

    var constants = {
        datagridInstanceName: 'contacts'
    };

    return {

        initialize: function() {
            this.bindCustomEvents();
            this.bindSidebarEvents();

            if (this.options.display === 'edit') {
                this.renderEdit();
            } else {
                throw 'display type wrong';
            }
        },

        bindCustomEvents: function() {
            // delete contact
            this.sandbox.on('sulu.contacts.contact.delete', this.del.bind(this)); // todo: contact-manager (delete dialog?)

            // save the current package
            this.sandbox.on('sulu.contacts.contacts.save', this.save.bind(this)); // todo: contact-manager / done

            // wait for navigation events
            this.sandbox.on('sulu.contacts.contacts.load', ContactRouter.toEdit.bind(this));

            // add new contact
            this.sandbox.on('sulu.contacts.contacts.new', ContactRouter.toAdd.bind(this));

            // delete selected contacts
            this.sandbox.on('sulu.contacts.contacts.delete', this.delContacts.bind(this)); // todo: contact-manager (delete dialog?)

            // load list view
            this.sandbox.on('sulu.contacts.contacts.list', ContactRouter.toList.bind(this));

            // handling documents
            this.sandbox.on('sulu.contacts.accounts.medias.save', this.saveDocuments.bind(this)); // todo: contact-manager / done

            this.initializeDropDownListender(   // todo: form-tab
                'title-select',
                'api/contact/titles');
            this.initializeDropDownListender(   // todo: form-tab
                'position-select',
                'api/contact/positions');
        },

        saveDocuments: function(contactId, newMediaIds, removedMediaIds, action) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
            ContactManager.saveDocuments(contactId, newMediaIds, removedMediaIds)
        },

        /**
         * Binds general sidebar events
         */
        bindSidebarEvents: function(){
            this.sandbox.dom.off('#sidebar');

            this.sandbox.dom.on('#sidebar', 'click', function(event) {
                var id = this.sandbox.dom.data(event.currentTarget,'id');
                this.sandbox.emit('sulu.contacts.contacts.load', id);
            }.bind(this), '#sidebar-contact-list');

            this.sandbox.dom.on('#sidebar', 'click', function(event) {
                var id = this.sandbox.dom.data(event.currentTarget,'id');
                this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/edit:' + id + '/details');
                this.sandbox.emit('husky.navigation.select-item','contacts/accounts');
            }.bind(this), '#main-account');
        },

        del: function() {
            DeleteDialog.show(this.sandbox, this.contact);
        },

        save: function(data, action) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');

            ContactManager.save(data).then(function(account) {
                if (!!data.id) {
                    this.sandbox.emit('sulu.contacts.contacts.saved', account);
                }
                this.afterSaveAction(action, account.id, !data.id);
            }.bind(this));
        },

        afterSaveAction: function(action, id, wasAdded) {
            if (action === 'back') {
                this.sandbox.emit('sulu.contacts.contacts.list');
            } else if (action == 'new') {
                this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/add', true, true);
            } else if (wasAdded) {
                this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/edit:' + id + '/details');
            }
        },

        delContacts: function(ids) {
            if (ids.length < 1) {
                this.sandbox.emit('sulu.dialog.error.show', 'No contacts selected for Deletion');
                return;
            }
            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    ids.forEach(function(id) {
                        var contact = new Contact({id: id});
                        contact.destroy({
                            success: function() {
                                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.record.remove', id);
                            }.bind(this)
                        });
                    }.bind(this));
                }
            }.bind(this));
        },

        renderEdit: function() {
            // load data and show form
            this.contact = new Contact();

            var $edit = this.sandbox.dom.createElement('<div id="contacts-edit-container"/>'),
                startComponent = function(model) {
                    this.sandbox.start([{
                        name: 'contacts/edit@sulucontact',
                        options: {
                            el: $edit,
                            data: model.toJSON(),
                            id: this.options.id,
                        }
                    }]);
                };
            this.html($edit);

            if (!!this.options.id) {
                ContactManager.load(this.options.id).then(function(contact) {
                        startComponent.call(this, contact);
                }.bind(this))
            } else {
                startComponent.call(this, new Contact());
            }
        },

        /**
         * Delete callback function for editable drop down
         * @param ids - ids to delete
         * @param instanceName
         */
        itemDeleted: function(ids, instanceName) {
            if (!!ids && ids.length > 0) {
                this.sandbox.util.each(ids, function(index, el) {
                    this.deleteItem(el, instanceName);
                }.bind(this));
            }
        },

        /**
         * delete elements
         * @param id
         * @param instanceName
         */
        deleteItem: function(id, instanceName) {
            if (instanceName === 'title-select') {
                this.deleteEntity(Title.findOrCreate({id: id}), instanceName);
            } else if (instanceName === 'position-select') {
                this.deleteEntity(Position.findOrCreate({id: id}), instanceName);
            }
        },

        /**
         * delete elements helper function
         * @param entity
         * @param instanceName
         */
        deleteEntity: function(entity, instanceName) {
            entity.destroy({
                error: function() {
                    this.sandbox.emit('husky.select.' + instanceName + '.revert');
                }.bind(this)
            });
        },

        /**
         * Save callback function for editable drop down
         * @param changedData - data to save
         * @param url - api url
         * @param instance - name of select instance
         */
        itemSaved: function(changedData, url, instance) {
            if (!!changedData && changedData.length > 0) {
                this.sandbox.util.save(
                    url,
                    'PATCH',
                    changedData)
                    .then(function(response) {
                        if (response.length > 0) {
                            var preselected = response[response.length - 1];

                            this.sandbox.emit(
                                instance + '.update',
                                response,
                                [preselected],
                                true,
                                true
                            );
                        } else {
                            this.sandbox.logger.error('No response from patch request');
                        }
                    }.bind(this)).fail(function(status, error) {
                        this.sandbox.emit(instance + '.revert');
                        this.sandbox.logger.error(status, error);
                    }.bind(this));
            }
        },

        /**
         * Register events for editable drop downs
         * @param instanceName
         * @param url
         */
        initializeDropDownListender: function(instanceName, url) {
            var instance = 'husky.select.' + instanceName;
            // Listen for changes in title selection drop down
            this.sandbox.on(instance + '.delete', function(data) {
                this.itemDeleted(data, instanceName);
            }.bind(this));
            this.sandbox.on(instance + '.save', function(data) {
                this.itemSaved(data, url, instance);
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

            // show warning dialog
            this.sandbox.emit('sulu.overlay.show-warning',
                'sulu.overlay.be-careful',
                'sulu.overlay.delete-desc',

                function() {
                    // cancel callback
                    callbackFunction(false);
                }.bind(this),

                function() {
                    // ok callback
                    callbackFunction(true);
                }.bind(this)
            );
        }
    };
});
