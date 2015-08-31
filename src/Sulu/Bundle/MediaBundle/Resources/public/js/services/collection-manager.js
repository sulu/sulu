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
    ], function(util,
                mediator,
                Collection) {

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
                    error: function() {
                        promise.fail();
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
            loadOrNew: function(collectionId) {
                var promise = $.Deferred(),
                    collection;
                if (!collectionId) {
                    collection = new Collection();
                    mediator.emit('sulu.medias.collection.created');
                    promise.resolve(collection.toJSON());
                } else {
                    collection = Collection.findOrCreate({id: collectionId});
                    collection.fetch({
                        success: function(response) {
                            mediator.emit('sulu.medias.collection.loaded', collectionId);
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
             * Save given collection data and display labels
             * @param data {Object} the collection data to save
             * @returns promise
             */
            save: function(data) {
                var promise = $.Deferred();

                saveCollection(data).then(function(collection) {
                    mediator.emit('sulu.medias.collection.saved', collection.id);
                    mediator.emit('sulu.labels.success.show', 'labels.success.collection-save-desc');
                    promise.resolve(collection);
                }.bind(this)).fail(function() {
                    mediator.emit('sulu.labels.error.show');
                    promise.fail();
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
    }
);
