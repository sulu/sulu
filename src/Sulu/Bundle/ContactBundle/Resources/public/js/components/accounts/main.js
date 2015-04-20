/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontact/model/account',
    'sulucontact/model/contact',
    'sulucontact/model/accountContact',
    'accountsutil/header',
    'sulucontact/model/email',
    'sulucontact/model/emailType',
    'sulumedia/model/media',
    'sulucategory/model/category',
    'accountsutil/delete-dialog'
], function(
    Account,
    Contact,
    AccountContact,
    AccountsUtilHeader,
    Email,
    EmailType,
    Media,
    Category,
    DeleteDialog) {

    'use strict';

    return {

        initialize: function() {
            this.bindCustomEvents();
            this.bindSidebarEvents();
            this.account = null;

            this.renderByDisplay();
        },

        renderByDisplay: function() {
            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.renderForm().then(this.setHeader.bind(this));
            } else if (this.options.display === 'contacts') {
                this.renderComponent(
                    'accounts/components/',
                    this.options.display,
                    'accounts-form-container', {}
                ).then(this.setHeader.bind(this));
            } else if (this.options.display === 'documents') {
                this.renderComponent(
                    '',
                    this.options.display,
                    'documents-form',
                    {type: 'account'}
                ).then(this.setHeader.bind(this));
            } else {
                throw 'display type wrong';
            }
        },

        setHeader: function() {
            AccountsUtilHeader.setHeader.call(this, this.account);
        },

        bindCustomEvents: function() {
            // delete contact
            this.sandbox.on('sulu.contacts.account.delete', this.del.bind(this));

            // save the current package
            this.sandbox.on('sulu.contacts.accounts.save', this.save.bind(this));

            // wait for navigation events
            this.sandbox.on('sulu.contacts.accounts.load', this.load.bind(this));

            // wait for navigation events
            this.sandbox.on('sulu.contacts.contact.load', this.loadContact.bind(this));

            // add new contact
            this.sandbox.on('sulu.contacts.accounts.new', this.add.bind(this));

            // delete selected contacts
            this.sandbox.on('sulu.contacts.accounts.delete', this.delAccounts.bind(this));

            // adds a new accountContact Relation
            this.sandbox.on('sulu.contacts.accounts.contact.save', this.addAccountContact.bind(this));

            // removes accountContact Relation
            this.sandbox.on('sulu.contacts.accounts.contacts.remove', this.removeAccountContacts.bind(this));

            // set main contact
            this.sandbox.on('sulu.contacts.accounts.contacts.set-main', this.setMainContact.bind(this));

            // load list view
            this.sandbox.on('sulu.contacts.accounts.list', function(type, noReload) {
                this.sandbox.emit(
                    'sulu.router.navigate', 'contacts/accounts',
                    !noReload,
                    true,
                    true
                );
            }, this);

            // handling documents
            this.sandbox.on('sulu.contacts.accounts.medias.save', this.saveDocuments.bind(this));

            // receive form of address values via template
            this.sandbox.on('sulu.contacts.set-types', function(types) {
                this.formOfAddress = types.formOfAddress;
                this.emailTypes = types.emailTypes;
            }.bind(this));

            // pass them on to the contact tab when fully loaded
            this.sandbox.on('sulu.contacts.accounts.contacts.initialized', function() {
                this.sandbox.emit('sulu.contacts.accounts.set-form-of-address', this.formOfAddress);
            }.bind(this));

            // add a new contact
            this.sandbox.on('sulu.contacts.accounts.new.contact', this.createNewContact.bind(this));
        },

        /**
         * adds a new contact and assigns the current account to it
         */
        createNewContact: function(data) {
            var contact = new Contact(data);
            contact.set('emails', [
                new Email({
                    email: data.email,
                    emailType: EmailType.findOrCreate({id: this.emailTypes[0].id})
                })
            ]);
            contact.save(null, {
                success: function(response) {
                    var model = response.toJSON();
                    this.sandbox.emit('sulu.contacts.accounts.contact.created', model);
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving a new contact");
                }.bind(this)
            });
        },

        saveDocuments: function(accountId, newMediaIds, removedMediaIds) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');

            this.sandbox.logger.warn('newMediaIds',newMediaIds);
            this.sandbox.logger.warn('removedMediaIds',removedMediaIds);

            this.processAjaxForDocuments(newMediaIds, accountId, 'POST');
            this.processAjaxForDocuments(removedMediaIds, accountId, 'DELETE');
        },

        processAjaxForDocuments: function(mediaIds, accountId, type){

            var requests=[],
                medias=[],
                url;

            if(mediaIds.length > 0) {
                this.sandbox.util.each(mediaIds, function(index, id) {

                    if(type === 'DELETE') {
                        url = '/admin/api/accounts/' + accountId + '/medias/' + id;
                    } else if(type === 'POST') {
                        url = '/admin/api/accounts/' + accountId + '/medias';
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
                        this.sandbox.logger.warn(medias);
                        this.sandbox.emit('sulu.contacts.contacts.medias.removed', medias);
                    } else if(type === 'POST') {
                        this.sandbox.logger.warn(medias);
                        this.sandbox.emit('sulu.contacts.contacts.medias.saved', medias);
                    }
                }.bind(this));
            }
        },

        /**
         * Binds general sidebar events
         */
        bindSidebarEvents: function() {
            this.sandbox.dom.off('#sidebar');

            this.sandbox.dom.on('#sidebar', 'click', function(event) {
                var id = this.sandbox.dom.data(event.currentTarget, 'id');
                this.sandbox.emit('sulu.contacts.accounts.load', id);
            }.bind(this), '#sidebar-accounts-list');

            this.sandbox.dom.on('#sidebar', 'click', function(event) {
                var id = this.sandbox.dom.data(event.currentTarget, 'id');
                this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/edit:' + id + '/details');
                this.sandbox.emit('husky.navigation.select-item', 'contacts/contacts');
            }.bind(this), '#main-contact');
        },

        /**
         * loads contact by id
         */
        getAccount: function(id) {
            this.account = new Account({id: id});
            this.account.fetch({
                success: function(model) {
                    this.account = model;
                    this.dfdAccount.resolve();
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log('error while fetching contact');
                }.bind(this)
            });
        },

        // sets main contact
        setMainContact: function(id) {
            // set mainContact
            this.account.set({mainContact: Contact.findOrCreate({id: id})});
            this.account.save(null, {
                patch: true,
                success: function() {
                    // TODO: show success label
                }.bind(this)
            });
        },

        addAccountContact: function(id, position) {
            // set id to contacts id;
            var accountContact = AccountContact.findOrCreate({
                id: id,
                contact: Contact.findOrCreate({id: id}), account: this.account});

            if (!!position) {
                accountContact.set({position: position});
            }

            accountContact.save(null, {
                // on success save contacts id
                success: function(response) {
                    var model = response.toJSON();
                    this.sandbox.emit('sulu.contacts.accounts.contact.saved', model);
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving contact");
                }.bind(this)
            });
        },

        /**
         * removes mulitple AccountContacts
         * @param ids
         */
        removeAccountContacts: function(ids) {
            // show warning
            this.sandbox.emit('sulu.overlay.show-warning', 'sulu.overlay.be-careful', 'sulu.overlay.delete-desc', null, function() {
                // get ids of selected contacts
                var accountContact;
                this.sandbox.util.foreach(ids, function(id) {
                    // set account and contact as well as  id to contacts id(so that request is going to be sent)
                    accountContact = AccountContact.findOrCreate({id: id, contact: Contact.findOrCreate({id: id}), account: this.account});
                    accountContact.destroy({
                        success: function() {
                            this.sandbox.emit('sulu.contacts.accounts.contacts.removed', id);
                        }.bind(this),
                        error: function() {
                            this.sandbox.logger.log("error while deleting AccountContact");
                        }.bind(this)
                    });
                }.bind(this));
            }.bind(this));
        },

        // show confirmation and delete account
        del: function() {
            DeleteDialog.showForSingle(this.sandbox, this.account, this.options.id);
        },

        // saves an account
        save: function(data) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');

            this.account.set(data);

            this.account.get('categories').reset();
            this.sandbox.util.foreach(data.categories,function(id){
                var category = Category.findOrCreate({id: id});
                this.account.get('categories').add(category);
            }.bind(this));

            this.account.save(null, {
                // on success save contacts id
                success: function(response) {
                    var model = response.toJSON();
                    if (!!data.id) {
                        this.sandbox.emit('sulu.contacts.accounts.saved', model);
                    } else {
                        this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/edit:' + model.id + '/details');
                    }
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving profile");
                }.bind(this)
            });
        },

        load: function(id) {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/edit:' + id + '/details');
        },

        loadContact: function(id) {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'contacts/contacts/edit:' + id + '/details');
        },

        add: function() {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'contacts/accounts/add');
        },

        delAccounts: function(ids) {
            if (ids.length < 1) {
                // TODO: translations
                this.sandbox.emit('sulu.overlay.show-error', 'sulu.overlay.delete-no-items');
                return;
            }
            this.showDeleteConfirmation(ids);
        },

        renderList: function() {
            var $list = this.sandbox.dom.createElement('<div id="accounts-list-container"/>');
            this.html($list);
            this.sandbox.start([
                {
                    name: 'accounts/components/list@sulucontact',
                    options: {
                        el: $list
                    }
                }
            ]);
        },

        /**
         * Adds a container with the given id and starts a component with the given name in it
         * @param path path to component
         * @param componentName
         * @param containerId
         * @param params additional params
         * @returns {*}
         * @param namespace
         */
        renderComponent: function(path, componentName, containerId, params, namespace) {
            var $form = this.sandbox.dom.createElement('<div id="' + containerId + '"/>'),
                dfd = this.sandbox.data.deferred();

            this.html($form);

            if (!!this.options.id) {
                this.account = new Account({id: this.options.id});
                this.account.fetch({
                    success: function(model) {
                        this.account = model;
                        this.sandbox.start([
                            {
                                name: path + componentName + '@' + (!!namespace ? namespace : 'sulucontact'),
                                options: {
                                    el: $form,
                                    data: model.toJSON(),
                                    params: !!params ? params : {}
                                }
                            }
                        ]);
                        dfd.resolve();
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                        dfd.reject();
                    }.bind(this)
                });
            }
            return dfd.promise();
        },

        renderForm: function() {
            // load data and show form
            this.account = new Account();

            var accTypeId,
                $form = this.sandbox.dom.createElement('<div id="accounts-form-container"/>'),
                dfd = this.sandbox.data.deferred();
            this.html($form);

            if (!!this.options.id) {
                this.account = new Account({id: this.options.id});
                this.account.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'accounts/components/form@sulucontact', options: { el: $form, data: model.toJSON()}}
                        ]);
                        dfd.resolve();
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                        dfd.reject();
                    }.bind(this)
                });
            } else {
                this.renderCreateForm(dfd, $form);
            }
            return dfd.promise();
        },

        renderCreateForm: function(dfd, $form) {
            this.sandbox.start([
                {name: 'accounts/components/form@sulucontact', options: {el: $form, data: this.account.toJSON()}}
            ]);

            dfd.resolve();
        },

        showDeleteConfirmation: function(ids) {
            if (ids.length === 0) {
                return;
            } else if (ids.length === 1) {
                // if only one account was selected - get related sub-companies and contacts (and show the first 3 ones)
                //this.confirmSingleDeleteDialog(ids[0], callbackFunction);
                DeleteDialog.showForSingle(this.sandbox, Account.findOrCreate({id:ids[0]}), ids[0], true)
            } else {
                // if multiple accounts were selected, get related sub-companies and show simplified message
                //this.confirmMultipleDeleteDialog(ids, callbackFunction);
                DeleteDialog.showForMultiple(this.sandbox, ids);
            }
        }
    };
});
