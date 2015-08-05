/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/husky/util',
    'services/husky/mediator',
    'sulucontact/models/account',
    'sulucontact/models/contact',
    'sulucontact/models/accountContact',
    'sulucontact/models/email',
    'sulucontact/models/emailType',
    'sulumedia/model/media',
    'sulucategory/model/category',
    'sulucategory/model/category'
], function(
    util,
    mediator,
    Account,
    Contact,
    AccountContact,
    Email,
    EmailType,
    Media,
    Category) {

    'use strict';

    var instance = null,

        /**
         * Removes medias from an account
         * @param mediaIds Array of medias to delete
         * @param accountId The account to delete the medias from
         * @private
         */
        removeDocuments = function(mediaIds, accountId) {
            var requests=[],
                promise = $.Deferred();
            if(!!mediaIds.length) {
                util.each(mediaIds, function(index, id) {
                    requests.push(
                        util.ajax({
                            url: '/admin/api/accounts/' + accountId + '/medias/' + id,
                            data: {mediaId: id},
                            type: 'DELETE'
                        })
                    );
                }.bind(this));
                $.when.apply(null, requests).then(function() {
                    promise.resolve();
                }.bind(this));
            } else {
                promise.resolve();
            }
            return promise;
        },

        /**
         * Adds medias to an account
         * @param mediaIds Array of medias to add
         * @param accountId The account to add the medias to
         * @private
         */
        addDocuments = function(mediaIds, accountId) {
            var requests=[],
                promise = $.Deferred();
            if(!!mediaIds.length) {
                util.each(mediaIds, function(index, id) {
                    requests.push(
                        util.ajax({
                            url: '/admin/api/accounts/' + accountId + '/medias',
                            data: {mediaId: id},
                            type: 'POST'
                        })
                    );
                }.bind(this));
                $.when.apply(null, requests).then(function() {
                    promise.resolve();
                }.bind(this));
            } else {
                promise.resolve();
            }
            return promise;
        };

    /** @constructor **/
    function AccountManager() {}

    AccountManager.prototype = {

        /**
         * Load or create account by given id
         * @param id
         * @returns promise
         */
        loadOrNew: function(id) {
            var promise = $.Deferred(),
                account;
            if (!id) {
                account = new Account();
                promise.resolve(account.toJSON());
            } else {
                account = Account.findOrCreate({id: id});
                account.fetch({
                    success: function() {
                        promise.resolve(account.toJSON());
                    }.bind(this),
                    error: function() {
                        promise.fail();
                    }.bind(this)
                });
            }

            return promise;
        },

        /**
         * Delete account by given id
         * @param accountId of the account to remove
         * @param removeContacts specifies if the associated contacts are removed too. default: false
         * @returns {*}
         */
        delete: function(accountId, removeContacts) {
            var promise = $.Deferred();
            var account = Account.findOrCreate({id: accountId});

            account.destroy({
                data: {removeContacts: !!removeContacts},
                processData: true,

                success: function() {
                    promise.resolve();
                }.bind(this),
                error: function() {
                    promise.fail();
                }.bind(this)
            });

            return promise;
        },

        /**
         * Save given account data
         * @param data {Object} the account data to save
         * @returns promise
         */
        save: function(data) {
            var promise = $.Deferred(),
            account = Account.findOrCreate({id: data.id});
            account.set(data);

            account.get('categories').reset();
            util.foreach(data.categories, function(categoryId){
                var category = Category.findOrCreate({id: categoryId});
                account.get('categories').add(category);
            }.bind(this));

            account.save(null, {
                success: function(response) {
                    promise.resolve(response.toJSON());
                }.bind(this),
                error: function() {
                    promise.fail();
                }.bind(this)
            });

            return promise;
        },

        /**
         * Removes multiple account-contacts
         * @param id The id of the account to delete the contacts from
         * @param ids {Array} the id's of the account-contacts to delete
         */
        removeAccountContacts: function(id, ids) {
            // show warning
            var account = Account.findOrCreate({id: id});
            mediator.emit('sulu.overlay.show-warning', 'sulu.overlay.be-careful', 'sulu.overlay.delete-desc', null, function() {
                // get ids of selected contacts
                var accountContact;
                util.foreach(ids, function(contactId) {
                    // set account and contact as well as  id to contacts id(so that request is going to be sent)
                    accountContact = AccountContact.findOrCreate({id: id, contact: Contact.findOrCreate({id: contactId}), account: account});
                    accountContact.destroy({
                        success: function() {
                            mediator.emit('sulu.contacts.accounts.contacts.removed', contactId);
                        }.bind(this)
                    });
                }.bind(this));
            }.bind(this));
        },

        /**
         * Save a new account-contact relationship
         * @param id The id of the account
         * @param contactId The id of the contact
         * @param position The position the contact has witih the account
         */
        addAccountContact: function(id, contactId, position) {
            var promise = $.Deferred(),
                account = Account.findOrCreate({id: id});
            var accountContact = AccountContact.findOrCreate({
                id: contactId,
                contact: Contact.findOrCreate({id: contactId}), account: account
            });

            if (!!position) {
                accountContact.set({position: position});
            }

            accountContact.save(null, {
                // on success save contacts id
                success: function(response) {
                    var model = response.toJSON();
                    promise.resolve(model);
                }.bind(this),
                error: function() {
                    promise.fail();
                }.bind(this)
            });

            return promise;
        },

        /**
         * Sets the main contact for an account
         * @param id The id of the account
         * @param contactId the id of the contact
         */
        setMainContact: function(id, contactId) {
            var promise = $.Deferred(),
                account = Account.findOrCreate({id: id});
            account.set({mainContact: Contact.findOrCreate({id: contactId})});
            account.save(null, {
                patch: true,
                success: function() {
                    promise.resolve();
                }.bind(this)
            });
            return promise;
        },

        /**
         * Adds/Removes documents to or from an account
         * @param accountId Id of the account to save the media for
         * @param newMediaIds Array of media ids to add
         * @param removedMediaIds Array of media ids to remove
         */
        saveDocuments: function(accountId, newMediaIds, removedMediaIds) {
            var savePromise = $.Deferred(),
                addPromise = addDocuments.call(this, newMediaIds, accountId),
                removePromise = removeDocuments.call(this, removedMediaIds, accountId);
            $.when(addPromise, removePromise).then(function() {
                savePromise.resolve();
            }.bind(this));
            return savePromise;
        },

        /**
         * Load delete-related information for account by given id
         * @param id
         * @returns promise
         */
        loadDeleteInfo: function(id) {
            var promise = $.Deferred();

            util.ajax({
                headers: {
                    'Content-Type': 'application/json'
                },

                type: 'GET',
                url: '/admin/api/accounts/' + id + '/deleteinfo',

                success: function(response) {
                    promise.resolve(response);
                }.bind(this),
            });

            return promise;
        },

        /**
         * Load delete-related information for deleting multiple accounts
         * @param ids of the accounts to delete
         * @returns promise
         */
        loadMultipleDeleteInfo: function(ids) {
            var promise = $.Deferred();

            util.ajax({
                headers: {
                    'Content-Type': 'application/json'
                },

                type: 'GET',
                url: '/admin/api/accounts/multipledeleteinfo',
                data: {ids: ids},

                success: function(response) {
                    promise.resolve(response);
                }.bind(this)
            });

            return promise;
        },
    };

    AccountManager.getInstance = function() {
        if (instance == null) {
            instance = new AccountManager();
        }
        return instance;
    }

    return AccountManager.getInstance();
});
