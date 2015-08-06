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
            removeDocuments = function(contactId, mediaIds) {
                var requests = [],
                    promise = $.Deferred();
                if (!!mediaIds.length) {
                    util.each(mediaIds, function(index, id) {
                        requests.push(
                            util.ajax({
                                url: '/admin/api/contacts/' + contactId + '/medias/' + id,
                                data: {mediaId: id},
                                type: 'DELETE',

                                success: function() {
                                    mediator.emit('sulu.contacts.contacts.documents.removed', contactId, id);
                                }.bind(this)
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
            addDocuments = function(contactId, mediaIds) {
                var requests = [],
                    promise = $.Deferred();
                if (!!mediaIds.length) {
                    util.each(mediaIds, function(index, id) {
                        requests.push(
                            util.ajax({
                                url: '/admin/api/contacts/' + contactId + '/medias',
                                data: {mediaId: id},
                                type: 'POST',

                                success: function() {
                                    mediator.emit('sulu.contacts.contacts.documents.added', contactId, id);
                                }.bind(this)
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
                    mediator.emit('sulu.contacts.contacts.created');
                    promise.resolve(contact.toJSON());
                } else {
                    contact = Contact.findOrCreate({id: contactId});
                    contact.fetch({
                        success: function(response) {
                            mediator.emit('sulu.contacts.contacts.loaded', contactId);
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
             * Delete contact by given id
             * @param contactId
             * @returns promise
             */
            delete: function(contactId) {
                var promise = $.Deferred();
                var contact = Contact.findOrCreate({id: contactId});

                contact.destroy({
                    success: function() {
                        mediator.emit('sulu.contacts.contacts.deleted', contactId);
                        promise.resolve();
                    }.bind(this),
                    error: function() {
                        promise.fail();
                    }.bind(this)
                });

                return promise;
            },

            /**
             * Delete multiple contacts by array of given ids
             * @param contactIds of the accounts to remove
             * @returns promise
             */
            deleteMultiple: function(contactIds) {
                var requests=[],
                    promise = $.Deferred();

                if (!!contactIds.length) {
                    util.each(contactIds, function(index, id) {
                        requests.push(this.delete(id));
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
             * Save given contact data
             * @param data {Object} the contact data to save
             * @returns promise
             */
            save: function(data) {
                var promise = $.Deferred();
                var contact = Contact.findOrCreate({id: data.id});
                contact.set(data);

                if (!!data.categories) {
                    contact.get('categories').reset();
                    util.foreach(data.categories, function(id) {
                        var category = Category.findOrCreate({id: id});
                        contact.get('categories').add(category);
                    }.bind(this));
                }

                contact.save(null, {
                    success: function(response) {
                        mediator.emit('sulu.contacts.contacts.saved', response.toJSON.id);
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

                    addPromise = addDocuments.call(this, contactId, newMediaIds),
                    removePromise = removeDocuments.call(this, contactId, removedMediaIds);

                $.when(removePromise, addPromise).then(function() {
                    savePromise.resolve();
                }.bind(this));

                return savePromise;
            },

            /**
             * Deletes a contact-title with a given id
             * @param id The id of the contact-title to delete
             */
            deleteTitle: function(id) {
                var deletePromise = $.Deferred(),
                    title = Title.findOrCreate({id: id});
                title.destroy({
                    success: function() {
                        deletePromise.resolve();
                    }.bind(this)
                });
                return deletePromise;
            },

            /**
             * Saves an array of contact-titles
             * @param data The array of contact-titles to save
             */
            saveTitles: function(data) {
                var savePromise = $.Deferred();
                util.save('api/contact/titles', 'PATCH', data).then(function(response) {
                    savePromise.resolve(response);
                });
                return savePromise;
            },

            /**
             * Delete a position with a given id
             * @param id The id of the position to delete
             */
            deletePosition: function(id) {
                var deletePromise = $.Deferred(),
                    position = Position.findOrCreate({id: id});
                position.destroy({
                    success: function() {
                        deletePromise.resolve();
                    }.bind(this)
                });
                return deletePromise;
            },

            /**
             * Saves an array of positions
             * @param data The array of positions to save
             */
            savePositions: function(data) {
                var savePromise = $.Deferred();
                util.save('api/contact/positions', 'PATCH', data).then(function(response) {
                    savePromise.resolve(response);
                });
                return savePromise;
            }
        };

        ContactManager.getInstance = function() {
            if (instance === null) {
                instance = new ContactManager();
            }
            return instance;
        }

        return ContactManager.getInstance();
    }
);
