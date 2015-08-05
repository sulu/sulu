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
        'sulucontact/models/contact',
        'sulucontact/models/title',
        'sulucontact/models/position',
        'sulucategory/model/category',
        'sulumedia/model/media',
    ], function(util,
                mediator,
                Contact,
                Title,
                Position,
                Category) {

        'use strict';

        var instance = null,

            /**
             * Removes medias from contact
             * @param mediaIds Array of medias to delete
             * @param contactId The contact to delete the medias from
             * @private
             */
            removeDocuments = function(mediaIds, contactId) {
                var requests = [],
                    promise = $.Deferred();
                if (!!mediaIds.length) {
                    util.each(mediaIds, function(index, id) {
                        requests.push(
                            util.ajax({
                                url: '/admin/api/contacts/' + contactId + '/medias/' + id,
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
             * Adds medias to an contact
             * @param mediaIds Array of medias to add
             * @param contactId The contact to add the medias to
             * @private
             */
            addDocuments = function(mediaIds, contactId) {
                var requests = [],
                    promise = $.Deferred();
                if (!!mediaIds.length) {
                    util.each(mediaIds, function(index, id) {
                        requests.push(
                            util.ajax({
                                url: '/admin/api/contacts/' + contactId + '/medias',
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
        function ContactManager() {
        }

        ContactManager.prototype = {

            /**
             * Load contact by given id
             * @param contactId
             * @returns promise
             */
            loadOrNew: function(contactId) {
                var promise = $.Deferred(),
                    contact;
                if (!contactId) {
                    contact = new Contact();
                    promise.resolve(contact.toJSON());
                } else {
                    contact = Contact.findOrCreate({id: contactId});
                    contact.fetch({
                        success: function(response) {
                            promise.resolve(response.toJSON());
                        }.bind(this),
                        error: function() {
                            promise.fail();
                        }.bind(this)
                    });
                }

                return promise;
            },

            /**
             * Save given contact data
             * @param data {Object} the contact data to save
             * @returns promise
             */
            save: function(data) {
                var promise = $.Deferred();
                var contact = Contact.findOrCreate({id: data.id});
                contact.set(data);

                contact.get('categories').reset();
                util.foreach(data.categories, function(id) {
                    var category = Category.findOrCreate({id: id});
                    contact.get('categories').add(category);
                }.bind(this));

                contact.save(null, {
                    // on success save contacts id
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
             * Adds/Removes documents to or from contact
             * @param contactId Id of the contact to save the media for
             * @param newMediaIds Array of media ids to add
             * @param removedMediaIds Array of media ids to remove
             */
            saveDocuments: function(contactId, newMediaIds, removedMediaIds) {
                var savePromise = $.Deferred(),

                    addPromise = addDocuments.call(this, newMediaIds, contactId),
                    removePromise = removeDocuments.call(this, removedMediaIds, contactId);

                $.when(removePromise, addPromise).then(function() {
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
