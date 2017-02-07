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
    'sulumedia/models/media'
], function(
    Util,
    Mediator,
    Contact,
    Title,
    Position,
    Category
) {

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
                    Mediator.emit('sulu.contacts.contact.deleted', contactId);
                    Mediator.emit('sulu.labels.success.show', 'contact.contacts.deleted');
                    promise.resolve();
                }.bind(this),
                error: function() {
                    promise.reject();
                }.bind(this)
            });

            return promise;
        },

        /**
         * Save given contact data
         * @param data {Object} the contact data to save
         * @returns promise
         */
        saveContact = function(data) {
            var promise = $.Deferred();
            var contact = Contact.findOrCreate({id: data.id});
            contact.set(data);

            if (!!data.categories) {
                contact.get('categories').reset();
                Util.foreach(data.categories, function(category) {
                    category = Category.findOrCreate({id: category});
                    contact.get('categories').add(category);
                }.bind(this));
            }

            contact.save(null, {
                success: function(response) {
                    promise.resolve(response.toJSON());
                }.bind(this),
                error: function(context, jqXHR) {
                    promise.reject(jqXHR);
                }.bind(this)
            });

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

            $.ajax({
                url: '/admin/api/contacts/' + contactId + '/medias/' + mediaId,
                type: 'DELETE',

                success: function() {
                    Mediator.emit('sulu.contacts.contact.document.removed', contactId, mediaId);
                    Mediator.emit('sulu.labels.success.show', 'contact.contacts.documents-removed');
                    promise.resolve();
                }.bind(this),
                error: function() {
                    promise.reject();
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
                Mediator.emit('sulu.contacts.contact.created');
                promise.resolve(contact.toJSON());
            } else {
                contact = Contact.findOrCreate({id: contactId});
                contact.fetch({
                    success: function(response) {
                        Mediator.emit('sulu.contacts.contact.loaded', contactId);
                        promise.resolve(response.toJSON());
                    }.bind(this),
                    error: function() {
                        promise.reject();
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

            Util.each(contactIds, function(index, id) {
                requests.push(deleteContact(id));
            }.bind(this));

            $.when.apply(null, requests).done(function() {
                promise.resolve();
            }.bind(this)).fail(function() {
                promise.reject();
            }.bind(this));

            return promise;
        },

        /**
         * Save given contact data and display labels
         * @param data {Object} the contact data to save
         * @returns promise
         */
        save: function(data) {
            var promise = $.Deferred();

            saveContact(data).done(function(contact) {
                Mediator.emit('sulu.contacts.contact.saved', contact.id);
                Mediator.emit('sulu.labels.success.show', 'contact.contacts.saved');
                promise.resolve(contact);
            }.bind(this)).fail(function(xhr) {
                if (!xhr.status || xhr.status !== 403) {
                    Mediator.emit('sulu.labels.error.show');
                }
                promise.reject();
            }.bind(this));

            return promise;
        },

        /**
         * Save given contact and display labels that avatar got saved
         * @param data {Object} the contact data to save
         * @returns promise
         */
        saveAvatar: function(data) {
            var promise = $.Deferred();

            saveContact(data).done(function(contact) {
                Mediator.emit('sulu.contacts.contact.avatar-saved', contact.id);
                Mediator.emit('sulu.labels.success.show', 'contact.contacts.avatar.saved');
                promise.resolve(contact);
            }.bind(this)).fail(function() {
                Mediator.emit('sulu.labels.error.show');
                promise.reject();
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
                    Mediator.emit('sulu.contacts.contacts.title.deleted', titleId);
                    deletePromise.resolve();
                }.bind(this),
                error: function() {
                    deletePromise.reject();
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
            Util.save('api/contact/titles', 'PATCH', data).done(function(response) {
                Mediator.emit('sulu.contacts.contacts.titles.saved');
                savePromise.resolve(response);
            }.bind(this)).fail(function() {
                savePromise.reject();
            }.bind(this));

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
                    Mediator.emit('sulu.contacts.contacts.position.deleted', positionId);
                    deletePromise.resolve();
                }.bind(this),
                error: function() {
                    deletePromise.reject();
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
            Util.save('api/contact/positions', 'PATCH', data).done(function(response) {
                Mediator.emit('sulu.contacts.contacts.positions.saved');
                savePromise.resolve(response);
            }.bind(this)).fail(function() {
                savePromise.reject();
            });
            return savePromise;
        },

        /**
         * Remove medias from a contact
         * @param mediaIds (Array) of medias to delete
         * @param contactId The contact to delete the medias from
         */
        removeDocuments: function(contactId, mediaIds) {
            if (!$.isArray(mediaIds)) {
                mediaIds = [mediaIds];
            }

            var requests = [],
                promise = $.Deferred();

            Util.each(mediaIds, function(index, id) {
                requests.push(removeDocument(contactId, id));
            }.bind(this));

            $.when.apply(null, requests).done(function() {
                promise.resolve();
            }.bind(this)).fail(function() {
                promise.reject();
            }.bind(this));

            return promise;
        },

        /**
         * Adds a media to a contact
         * @param mediaId media to add
         * @param contactId The contact to add the media to
         */
        addDocument: function(contactId, mediaId) {
            var promise = $.Deferred();

            $.ajax({
                url: '/admin/api/contacts/' + contactId + '/medias',
                data: {mediaId: mediaId},
                type: 'POST',

                success: function() {
                    Mediator.emit('sulu.contacts.contact.document.added', contactId, mediaId);
                    Mediator.emit('sulu.labels.success.show', 'contact.contacts.document-added');
                    promise.resolve();
                }.bind(this),
                error: function() {
                    promise.reject();
                }.bind(this)
            });

            return promise;
        },

        /**
         * Sets medias to a contact. Currently associated medias get replaced.
         * @param mediaIds medias to associate with the contact
         * @param contactId The contact to set the medias to
         */
        setDocuments: function(contactId, mediaIds) {
            var promise = $.Deferred();
            var medias = _.map(mediaIds, function (mediaId) { return {id: mediaId} });

            $.ajax({
                url: '/admin/api/contacts/' + contactId,
                contentType: 'application/json',
                data: JSON.stringify({medias: medias}),
                type: 'PATCH',

                success: function(response) {
                    Mediator.emit('sulu.contacts.contact.documents.saved', contactId, mediaIds);
                    Mediator.emit('sulu.labels.success.show', 'contact.contacts.documents-saved');
                    promise.resolve(response);
                }.bind(this),
                error: function() {
                    promise.reject();
                }.bind(this)
            });

            return promise;
        },

        /**
         * Returns documents specific data
         * @param contactId The contact
         * @returns {Object}
         */
        getDocumentsData: function(contactId) {
            return {
                listUrl: '/admin/api/contacts/' + contactId + '/medias?flat=true',
                fieldsKey: 'contactsDocumentsFields',
                fieldsUrl: '/admin/api/contacts/medias/fields'
            };
        }
    };

    ContactManager.getInstance = function() {
        if (instance === null) {
            instance = new ContactManager();
        }
        return instance;
    };

    return ContactManager.getInstance();
});
