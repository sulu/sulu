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
    'sulumedia/models/collection',
], function(
        Util,
        Mediator,
        Collection
) {

    'use strict';

    var instance = null,

        /**
         * Save given collection data
         * @param data {Object} the contact data to save
         * @returns promise
         */
        saveCollection = function(data) {
            var promise = $.Deferred();
            var collection = Collection.findOrCreate({id: data.id});
            collection.set(data);

            collection.save(null, {
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
         * Delete collection by given id
         * @param collectionId collection to delete
         * @returns {*}
         */
        deleteCollection = function(collectionId) {
            var promise = $.Deferred(),
                collection = Collection.findOrCreate({id: collectionId});

            collection.destroy({
                success: function() {
                    Mediator.emit('sulu.medias.collection.deleted', collectionId);
                    Mediator.emit('sulu.labels.success.show', 'labels.success.collection-deleted-desc');
                    promise.resolve();
                }.bind(this),
                error: function() {
                    promise.reject();
                }.bind(this)
            });

            return promise;
        };


    /** @constructor **/
    function CollectionManager() {
    }

    CollectionManager.prototype = {

        /**
         * Load collection by given id
         * @param collectionId
         * @returns promise
         */
        loadOrNew: function(collectionId, locale) {
            var promise = $.Deferred(),
                collection;
            if (!collectionId) {
                collection = new Collection();
                Mediator.emit('sulu.medias.collection.created');
                promise.resolve(collection.toJSON());
            } else {
                collection = Collection.findOrCreate({id: collectionId});
                collection.fetch({
                    data: {locale: locale},
                    success: function(response) {
                        Mediator.emit('sulu.medias.collection.loaded', collectionId);
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
         * Save given collection data and display labels
         * @param data {Object} the collection data to save
         * @returns promise
         */
        save: function(data) {
            var promise = $.Deferred();

            saveCollection(data).done(function(collection) {
                Mediator.emit('sulu.medias.collection.saved', collection.id, collection);
                Mediator.emit('sulu.labels.success.show', 'labels.success.collection-save-desc');
                promise.resolve(collection);
            }.bind(this)).fail(function(xhr) {
                if (!xhr.status || xhr.status !== 403) {
                    Mediator.emit('sulu.labels.error.show');
                }
                promise.reject();
            }.bind(this));

            return promise;
        },

        /**
         * Delete collections by given ids
         * @param collectionIds (Array)
         * @returns promise
         */
        delete: function(collectionIds) {
            if (!$.isArray(collectionIds)) {
                collectionIds = [collectionIds];
            }

            var requests = [],
                promise = $.Deferred();

            Util.each(collectionIds, function(index, id) {
                requests.push(deleteCollection(id));
            }.bind(this));

            $.when.apply(null, requests).done(function() {
                promise.resolve();
            }.bind(this)).fail(function() {
                promise.reject();
            });

            return promise;
        },

        /**
         * Move collection into given parentCollection
         * @param collectionId
         * @param parentCollectionId
         * @returns {*}
         */
        move: function(collectionId, parentCollectionId, locale) {
            var promise = $.Deferred();

            var url = '/admin/api/collections/' + collectionId + '?action=move&locale=' + locale;
            url = (!!parentCollectionId) ? url + '&destination=' + parentCollectionId : url;

            Util.save(url, 'POST')
                .done(function() {
                    Mediator.emit('sulu.medias.collection.moved', collectionId, parentCollectionId);
                    Mediator.emit('sulu.labels.success.show', 'labels.success.collection-move-desc');
                    promise.resolve();
                }.bind(this)).fail(function() {
                    promise.reject();
                }.bind(this));

            return promise;
        }
    };

    CollectionManager.getInstance = function() {
        if (instance === null) {
            instance = new CollectionManager();
        }
        return instance;
    };

    return CollectionManager.getInstance();
});
