/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var constants = {
        allCollectionsUrl: '/admin/api/collections?depth=0',
        singleCollectionUrl: '/admin/api/collections',
        singleMediaUrl: '/admin/api/media',
    },

    namespace = 'sulu.media.collections.',

    /**
     * listens on and changes the view to collections list
     * @event sulu.media.collections.list
     * @param noReload {Boolean} if false page reloads
     */
    ROUTE_TO_LIST = function() {
        return createEventName.call(this, 'list');
    },

    /**
     * listens on and deletes medias
     * @event sulu.media.collections.delete-media
     * @param ids {Array} array of ids of the media to delete
     * @param callback {Function} callback to execute after a media got deleted
     */
    DELETE_MEDIA = function() {
        return createEventName.call(this, 'delete-media');
    },

    /**
     * raised after a media got deleted
     * @event sulu.media.collections.media-deleted
     * @param id {Number|String} the id of the deleted media
     */
    SINGLE_MEDIA_DELETED = function() {
        return createEventName.call(this, 'media-deleted');
    },

    /**
     * emited after a collection entity got changed
     * @event sulu.media.collections.collection-changed
     * @param {Object} the changed collection object
     */
    COLLECTION_CHANGED = function() {
        return createEventName.call(this, 'collection-changed');
    },

    /** returns normalized event names */
    createEventName = function(postFix) {
        return namespace + postFix;
    };

    return {

        initialize: function() {
            this.bindCustomEvents();
            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'files') {
                this.renderFiles();
            } else {
                throw 'display type wrong';
            }
        },

        /**
         * Bind custom events concerning collections
         */
        bindCustomEvents: function() {
            // navigate to list view
            this.sandbox.on(ROUTE_TO_LIST.call(this), function(noReload) {
                this.sandbox.emit('sulu.router.navigate', 'media/collections', !noReload ? true : false , true);
            }, this);

            // delete media
            this.sandbox.on(DELETE_MEDIA.call(this), this.deleteMedia.bind(this));

            // collection title got changed
            this.sandbox.on('sulu.collection-list.collections.title-changed', function(collectionId, title) {
                this.saveCollection(collectionId, {title: title});
            }.bind(this));

            // collection title got changed
            this.sandbox.on('sulu.collection-list.collections.collection-added', function(title, color) {
                this.saveNewCollection({
                    title: title,
                    style: {
                        type: 'circle',
                        color: color
                    }
                });
            }.bind(this));

            // navigate to collection edit
            this.sandbox.on('sulu.media.collections.files', function(collectionId, tab) {
                // default tab is files
                tab = (!!tab) ? tab : 'files';
                this.sandbox.emit('sulu.router.navigate', 'media/collections/edit:'+ collectionId +'/' + tab , true, true);
            }.bind(this));
        },

        /**
         * Saves data for an existing collection
         * @param collectionId {Number|String} the collections identifier
         * @param data {Object} object with the data to update
         */
        saveCollection: function(collectionId, data) {
            this.sandbox.util.save(constants.singleCollectionUrl + '/' + collectionId, 'PUT', data).done(function(collection) {
                this.sandbox.emit(COLLECTION_CHANGED.call(this), collection);
            }.bind(this));
        },

        /**
         * Saves a completely new collection
         * @param data {Object} data of the new collection
         */
        saveNewCollection: function(data) {
            this.sandbox.util.save(constants.singleCollectionUrl, 'POST', data).done(function(collection) {
                this.sandbox.emit(COLLECTION_CHANGED.call(this), collection);
            }.bind(this));
        },

        /**
         * Deletes an array of media
         * @param mediaIds {Array} array of media ids
         * @param callback {Function} callback to execute after deleting a media
         */
        deleteMedia: function(mediaIds, callback) {
            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (confirmed === true) {

                    this.sandbox.util.foreach(mediaIds, function(id) {
                        this.sandbox.util.ajax({
                            url: constants.singleMediaUrl + '/' + id,
                            type: 'DELETE',
                            success: function() {
                                if (typeof callback === 'function') {
                                    callback(id);
                                } else {
                                    this.sandbox.emit(SINGLE_MEDIA_DELETED.call(this), id);
                                }
                            }.bind(this)
                        });
                    }.bind(this));

                }
            }.bind(this));
        },

        /**
         * Inserts a container and starts the collections list in it
         */
        renderList: function() {
            var $list = this.sandbox.dom.createElement('<div id="collections-list-container"/>');
            this.html($list);
            this.sandbox.util.load(constants.allCollectionsUrl).then(function(collections) {
                this.sandbox.start([
                    {
                        name: 'collections/components/list@sulumedia',
                        options: {
                            el: $list,
                            data: collections
                        }
                    }
                ]);
            }.bind(this));
        },

        /**
         * Inserts a container and starts the files-view of a single
         * collection in it
         */
        renderFiles: function() {
            var $files = this.sandbox.dom.createElement('<div id="collection-files-container"/>');
            this.html($files);
            this.sandbox.util.load(constants.singleCollectionUrl + '/' + this.options.id).then(function(collection) {
                this.sandbox.start([
                    {
                        name: 'collections/components/files@sulumedia',
                        options: {
                            el: $files,
                            activeTab: this.options.content,
                            data: collection
                        }
                    }
                ]);
            }.bind(this));
        }
    };
});
