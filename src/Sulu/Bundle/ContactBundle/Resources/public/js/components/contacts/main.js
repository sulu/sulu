/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontact/models/contact',
    'sulucontact/models/title',
    'sulucontact/models/position',
    'sulucategory/model/category',
    'contactsutil/delete-dialog'
], function(Contact, Title, Position, Category, DeleteDialog) {

    'use strict';

    var constants = {
        datagridInstanceName: 'contacts'
    };

    return {

        initialize: function() {
            this.bindCustomEvents();
            this.bindSidebarEvents();

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'edit') {
                this.renderEdit();
            } else {
                throw 'display type wrong';
            }
        },

        bindCustomEvents: function() {
            // delete contact
            this.sandbox.on('sulu.contacts.contact.delete', function() {
                this.del();
            }, this);

            // save the current package
            this.sandbox.on('sulu.contacts.contacts.save', function(data, action) {
                this.save(data, action);
            }, this);

            // wait for navigation events
            this.sandbox.on('sulu.contacts.contacts.load', function(id) {
                this.load(id);
            }, this);

            // add new contact
            this.sandbox.on('sulu.contacts.contacts.new', function() {
                this.add();
            }, this);

            // delete selected contacts
            this.sandbox.on('sulu.contacts.contacts.delete', function(ids) {
                this.delContacts(ids);
            }, this);

            // load list view
            this.sandbox.on('sulu.contacts.contacts.list', function() {
                this.sandbox.emit('sulu.router.navigate', 'contacts/contacts');
            }, this);

            this.initializeDropDownListender(
                'title-select',
                'api/contact/titles');
            this.initializeDropDownListender(
                'position-select',
                'api/contact/positions');

            // handling documents
            this.sandbox.on('sulu.contacts.accounts.medias.save', this.saveDocuments.bind(this));
        },

        saveDocuments: function(contactId, newMediaIds, removedMediaIds, action) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');

            this.processAjaxForDocuments(newMediaIds, contactId, 'POST', action);
            this.processAjaxForDocuments(removedMediaIds, contactId, 'DELETE', action);
        },

        processAjaxForDocuments: function(mediaIds, contactId, type, action){

            var requests=[],
                medias=[],
                url;

            if(mediaIds.length > 0) {
                this.sandbox.util.each(mediaIds, function(index, id) {

                    if(type === 'DELETE') {
                        url = '/admin/api/contacts/' + contactId + '/medias/' + id;
                    } else if(type === 'POST') {
                        url = '/admin/api/contacts/' + contactId + '/medias';
                    }

                    requests.push(
                        this.sandbox.util.ajax({
                            url: url,
                            data: {mediaId: id},
                            type: type
                        }).fail(function() {
                                this.sandbox.logger.error("Error while saving documents!");
                        }.bind(this))
                    );
                    medias.push(id);
                }.bind(this));

                this.sandbox.util.when.apply(null, requests).then(function() {
                    if(type === 'DELETE') {
                        this.sandbox.emit('sulu.contacts.accounts.medias.removed', medias);
                    } else if(type === 'POST') {
                        this.sandbox.emit('sulu.contacts.accounts.medias.saved', medias);
                    }
                    this.afterSaveAction(action, contactId, false);
                }.bind(this));
            }
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
            this.contact.set(data);

            this.contact.get('categories').reset();
            this.sandbox.util.foreach(data.categories,function(id){
                var category = Category.findOrCreate({id: id});
                this.contact.get('categories').add(category);
            }.bind(this));

            this.contact.save(null, {
                // on success save contacts id
                success: function(response) {
                    var model = response.toJSON();
                    if (!!data.id) {
                        // TODO update address lists
                        this.sandbox.emit('sulu.contacts.contacts.saved', model);
                    }
                    this.afterSaveAction(action, model.id, !data.id);
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log('error while saving profile');
                }.bind(this)
            });
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

        load: function(id) {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/edit:' + id + '/details');
        },

        add: function() {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/add');
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

        renderList: function() {
            var $list = this.sandbox.dom.createElement('<div id="contacts-list-container"/>');
            this.html($list);
            this.sandbox.start([
                {name: 'contacts/list@sulucontact', options: { el: $list}}
            ]);
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
                            params: {type: 'contact'} //required for documents-tab
                        }
                    }]);
                };
            this.html($edit);

            if (!!this.options.id) {
                this.contact = new Contact({id: this.options.id});
                this.contact.fetch({
                    success: startComponent.bind(this),
                    error: function() {
                        this.sandbox.logger.log('error while fetching contact');
                    }.bind(this)
                });
            } else {
                startComponent.call(this, this.contact);
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
