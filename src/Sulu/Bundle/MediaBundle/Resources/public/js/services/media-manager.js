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
    'sulumedia/models/media'
], function(
    Util,
    Mediator,
    Media
) {

    'use strict';

    var instance = null,

        /**
         * Delete media by given id
         * @param mediaId media to delete
         * @returns {*}
         */
        deleteMedia = function(mediaId) {
            var promise = $.Deferred(),
                media = Media.findOrCreate({id: mediaId});

            media.destroy({
                success: function() {
                    Mediator.emit('sulu.medias.media.deleted', mediaId);
                    Mediator.emit('sulu.labels.success.show', 'labels.success.media-deleted-desc');
                    promise.resolve();
                }.bind(this),
                error: function() {
                    promise.reject();
                }.bind(this)
            });

            return promise;
        },

        /**
         * Move media to collection
         * @param mediaId media to move
         * @param collectionId collection to move media to
         * @param locale the locale for the return value of the collection
         * @returns {*}
         */
        moveMedia = function(mediaId, collectionId, locale) {
            var promise = $.Deferred();

            Util.save(
                '/admin/api/media/' + mediaId + '?action=move&destination=' + collectionId + '&locale=' + locale,
                'POST'
            ).done(function() {
                Mediator.emit('sulu.medias.media.moved', mediaId, collectionId);
                Mediator.emit('sulu.labels.success.show', 'labels.success.media-move-desc');
                promise.resolve();
            }.bind(this)).fail(function() {
                promise.reject();
            }.bind(this));


            return promise;
        },

        /**
         * Save given media data
         * @param data {Object} the media data to save
         * @returns promise
         */
        saveMedia = function(data) {
            var promise = $.Deferred();
            var media = Media.findOrCreate({id: data.id});
            media.set(data);

            media.save(null, {
                success: function(response) {
                    promise.resolve(response.toJSON());
                }.bind(this),
                error: function(context, jqXHR) {
                    promise.reject(jqXHR);
                }.bind(this)
            });

            return promise;
        };


    /** @constructor **/
    function MediaManager() {
    }

    MediaManager.prototype = {

        /**
         * Load media by given id.
         * @param {Integer} mediaId
         * @param {String} locale
         * @returns promise
         */
        loadOrNew: function(mediaId, locale) {
            var promise = $.Deferred(),
                media;
            if (!mediaId) {
                media = new Media();
                Mediator.emit('sulu.medias.media.created');
                promise.resolve(media.toJSON());
            } else {
                media = Media.findOrCreate({id: mediaId});
                media.clear();
                media.set({id: mediaId});

                media.fetch({
                    data: {
                        locale: locale
                    },
                    success: function(response) {
                        Mediator.emit('sulu.medias.media.loaded', mediaId);
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
         * Delete medias by given ids
         * @param mediaIds (Array)
         * @returns promise
         */
        delete: function(mediaIds) {
            if (!$.isArray(mediaIds)) {
                mediaIds = [mediaIds];
            }

            var requests = [],
                promise = $.Deferred();

            Util.each(mediaIds, function(index, id) {
                requests.push(deleteMedia(id));
            }.bind(this));

            $.when.apply(null, requests).done(function() {
                promise.resolve();
            }.bind(this)).fail(function() {
                promise.reject();
            }.bind(this));

            return promise;
        },

        /**
         * Save given media data and display labels
         * @param data {Object} the media data to save
         * @returns promise
         */
        save: function(data) {
            var promise = $.Deferred();

            saveMedia(data).done(function(media) {
                Mediator.emit('sulu.medias.media.saved', media.id, media);
                Mediator.emit('sulu.labels.success.show', 'labels.success.media-save-desc');
                promise.resolve(media);
            }.bind(this)).fail(function(xhr) {
                if (!xhr.status || xhr.status !== 403) {
                    Mediator.emit('sulu.labels.error.show');
                }
                promise.reject();
            }.bind(this));

            return promise;
        },

        /**
         * Move medias to a collection
         * @param mediaIds
         * @param collectionId
         * @param locale The locale for the return value of the media
         * @returns {*}
         */
        move: function(mediaIds, collectionId, locale) {
            if (!$.isArray(mediaIds)) {
                mediaIds = [mediaIds];
            }

            var requests = [],
                promise = $.Deferred();

            Util.each(mediaIds, function(index, mediaId) {
                requests.push(moveMedia(mediaId, collectionId, locale));
            }.bind(this));

            $.when.apply(null, requests).done(function() {
                promise.resolve();
            }.bind(this)).fail(function() {
                promise.reject();
            }.bind(this));

            return promise;
        }
    };

    MediaManager.getInstance = function() {
        if (instance === null) {
            instance = new MediaManager();
        }
        return instance;
    };

    return MediaManager.getInstance();
});
