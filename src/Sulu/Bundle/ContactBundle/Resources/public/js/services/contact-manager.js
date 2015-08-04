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
        'sulumedia/model/media',
    ], function(Contact,
                Title,
                Position,
                Category) {

        'use strict';

        var instance = null;

        function ContactManager() {
            this.initialize();
        }

        ContactManager.prototype = {

            initialize: function() {
                this.sandbox = window.App; // TODO: inject context. find better solution
                this.contact = null;
            },

            /**
             * loads contact by id
             */
            load: function(contactId) {
                var promise = this.sandbox.data.deferred();

                this.contact = Contact.findOrCreate({id: contactId});

                this.contact.fetch({
                    success: function(model) {
                        this.contact = model;
                        promise.resolve(model);
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log('error while fetching contact');
                        promise.fail();
                    }.bind(this)
                });
                return promise;
            },

            /**
             * Saves a contact
             * @param data {Object} the account data to save
             * @returns promise
             */
            save: function(data) {
                var promise = this.sandbox.data.deferred();

                this.contact = Contact.findOrCreate({id: data.id});
                this.contact.set(data);

                this.contact.get('categories').reset();
                this.sandbox.util.foreach(data.categories, function(id) {
                    var category = Category.findOrCreate({id: id});
                    this.contact.get('categories').add(category);
                }.bind(this));

                this.contact.save(null, {
                    // on success save contacts id
                    success: function(response) {
                        var model = response.toJSON();
                        promise.resolve(model);
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.error('error while saving contact');
                        promise.fail();
                    }.bind(this)
                });

                return promise;
            },

            /**
             * Removes medias from an account
             * @param mediaIds Array of medias to delete
             * @param contactId The account to delete the medias from
             * @private
             */
            removeDocuments: function(mediaIds, contactId) {
                var requests = [],
                    promise = this.sandbox.data.deferred();
                if (!!mediaIds.length) {
                    this.sandbox.util.each(mediaIds, function(index, id) {
                        requests.push(
                            this.sandbox.util.ajax({
                                url: '/admin/api/contacts/' + contactId + '/medias/' + id,
                                data: {mediaId: id},
                                type: 'DELETE'
                            }).fail(function() {
                                this.sandbox.logger.error("Error while deleting documents!");
                            }.bind(this))
                        );
                    }.bind(this));

                    this.sandbox.util.when.apply(null, requests).then(function() {
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
             * @param contactId The account to add the medias to
             * @private
             */
            addDocuments: function(mediaIds, contactId) {
                var requests = [], promise = this.sandbox.data.deferred();
                if (!!mediaIds.length) {
                    this.sandbox.util.each(mediaIds, function(index, id) {
                        requests.push(
                            this.sandbox.util.ajax({
                                url: '/admin/api/contacts/' + contactId + '/medias',
                                data: {mediaId: id},
                                type: 'POST'
                            }).fail(function() {
                                this.sandbox.logger.error("Error while saving documents!");
                            }.bind(this))
                        );
                    }.bind(this));

                    this.sandbox.util.when.apply(null, requests).then(function() {
                        promise.resolve();
                    }.bind(this));

                } else {
                    promise.resolve();
                }
                return promise;
            },

            /**
             * Adds/Removes documents to or from an account
             * @param contactId Id of the account to save the media for
             * @param newMediaIds Array of media ids to add
             * @param removedMediaIds Array of media ids to remove
             */
            saveDocuments: function(contactId, newMediaIds, removedMediaIds) {
                var savePromise = this.sandbox.data.deferred(),

                    addPromise = this.addDocuments.call(this, newMediaIds, contactId),
                    removePromise = this.removeDocuments.call(this, removedMediaIds, contactId);

                this.sandbox.util.when(removePromise, addPromise).then(function() {
                    savePromise.resolve();
                }.bind(this));

                return savePromise;
            }
        };

        ContactManager.getInstance = function() {
            if (instance == null) {
                instance = new ContactManager();
            }
            return instance;
        }

        return ContactManager.getInstance();
    }
);
