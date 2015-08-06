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
             * Delete contact by given id
             * @param contactId contact to delete
             * @returns {*}
             */
            deleteContact = function(contactId) {
                var promise = $.Deferred(),
                    contact = Contact.findOrCreate({id: contactId});

                contact.destroy({
                    success: function() {
                        mediator.emit('sulu.contacts.contact.deleted', contactId);
                        promise.resolve();
                    }.bind(this),
                    error: function() {
                        promise.fail();
                    }.bind(this)
                });

                return promise;
            },

            /**
             * Remove medias from an contact
             * @param mediaIds (Array) of medias to delete
             * @param contactId The contact to delete the medias from
             * @private
             */
            removeDocuments = function(contactId, mediaIds) {
                if (!$.isArray(mediaIds)) {
                    mediaIds = [mediaIds];
                }

                var requests = [],
                    promise = $.Deferred();

                util.each(mediaIds, function(index, id) {
                    requests.push(removeDocument(contactId, id));
                }.bind(this));

                $.when.apply(null, requests).then(function() {
                    promise.resolve();
                }.bind(this));

                return promise;
            },

            /**
             * Removes a media from an contact
             * @param mediaId media to delete
             * @param contactId The contact to delete the media from
             * @private
             */
            removeDocument = function(contactId, mediaId) {
                var promise = $.Deferred();

                util.ajax({
                    url: '/admin/api/contacts/' + contactId + '/medias/' + mediaId,
                    data: {mediaId: mediaId},
                    type: 'DELETE',

                    success: function() {
                        mediator.emit('sulu.contacts.contact.document.removed', contactId, mediaId);
                        promise.resolve();
                    }.bind(this)
                });

                return promise;
            },

            /**
             * Adds medias to an contact
             * @param mediaIds (Array) of medias to add
             * @param contactId The contact to add the medias to
             * @private
             */
            addDocuments = function(contactId, mediaIds) {
                if (!$.isArray(mediaIds)) {
                    mediaIds = [mediaIds];
                }

                var requests = [],
                    promise = $.Deferred();

                util.each(mediaIds, function(index, id) {
                    requests.push(addDocument(contactId, id));
                }.bind(this));

                $.when.apply(null, requests).then(function() {
                    promise.resolve();
                }.bind(this));

                return promise;
            },

            /**
             * Adds a media to an contact
             * @param mediaId media to add
             * @param contactId The contact to add the media to
             * @private
             */
            addDocument = function(contactId, mediaId) {
                var promise = $.Deferred();

                util.ajax({
                    url: '/admin/api/contacts/' + contactId + '/medias',
                    data: {mediaId: mediaId},
                    type: 'POST',

                    success: function() {
                        mediator.emit('sulu.contacts.contact.document.added', contactId, mediaId);
                        promise.resolve();
                    }.bind(this)
                });

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
                    mediator.emit('sulu.contacts.contact.created');
                    promise.resolve(contact.toJSON());
                } else {
                    contact = Contact.findOrCreate({id: contactId});
                    contact.fetch({
                        success: function(response) {
                            mediator.emit('sulu.contacts.contact.loaded', contactId);
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
             * Delete contacts by given id
             * @param contactIds (Array)
             * @returns promise
             */
            delete: function(contactIds) {
                if (!$.isArray(contactIds)) {
                    contactIds = [contactIds];
                }

                var requests = [],
                    promise = $.Deferred();

                util.each(contactIds, function(index, id) {
                    requests.push(deleteContact(id));
                }.bind(this));

                $.when.apply(null, requests).then(function() {
                    promise.resolve();
                }.bind(this));

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
                        mediator.emit('sulu.contacts.contact.saved', response.toJSON.id);
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
                var promise = $.Deferred(),
                    addPromise = addDocuments.call(this, contactId, newMediaIds),
                    removePromise = removeDocuments.call(this, contactId, removedMediaIds);

                $.when(removePromise, addPromise).then(function() {
                    promise.resolve();
                }.bind(this));

                return promise;
            },

            /**
             * Deletes a contact-title with a given id
             * @param titleId The id of the contact-title to delete
             */
            deleteTitle: function(titleId) {
                var deletePromise = $.Deferred(),
                    title = Title.findOrCreate({id: titleId});
                title.destroy({
                    success: function() {
                        mediator.emit('sulu.contacts.contacts.title.deleted', titleId);
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
                    mediator.emit('sulu.contacts.contacts.titles.saved');
                    savePromise.resolve(response);
                });
                return savePromise;
            },

            /**
             * Delete a position with a given id
             * @param positionId The id of the position to delete
             */
            deletePosition: function(positionId) {
                var deletePromise = $.Deferred(),
                    position = Position.findOrCreate({id: positionId});
                position.destroy({
                    success: function() {
                        mediator.emit('sulu.contacts.contacts.position.deleted', positionId);
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
                    mediator.emit('sulu.contacts.contacts.positions.saved');
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
