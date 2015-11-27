/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @class MediaPlayVideoOverlay
 * Class which shows overlays for playing videos
 * @constructor
 */
define([
    'services/sulumedia/media-manager'
], function(MediaManager) {

    'use strict';

    var defaults = {
            options: {
                instanceName: 'play-video-overlay'
            },
            templates: {
                video: [
                    '<div class="media-play-video-container">',
                    '   <video id="<%= playVideoId %>" controls>',
                    '       <source src="<%= videoUrl %>" />',
                    '   </video>',
                    '</div>'
                ].join('')
            }
        },

        constants = {
            loadingClass: 'loading',
            loaderClass: 'media-play-video-loader',
            playVideoId: 'media-play-video-id',
            eventNamespace: 'sulu.media-play-video.'
        };

    return {

        defaults: defaults,

        events: {
            names: {
                initialized: {
                    postFix: 'initialized'
                },
                closed: {
                    postFix: 'closed'
                }
            },
            namespace: constants.eventNamespace
        },

        /**
         * Initializes the overlay component
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            if (!this.options.videoId) {
                throw new Error('videoId not defined');
            }

            this.startLoadingOverlay();
            this.loadVideo(this.options.videoId, this.options.locale).then(function(video) {
                this.startVideoOverlay(video);
            }.bind(this));

            this.events.initialized();
        },

        /**
         * Starts the loading overlay
         */
        startLoadingOverlay: function() {
            var $container = this.sandbox.dom.createElement('<div class="' + constants.loadingClass + '"/>'),
                $loader = this.sandbox.dom.createElement('<div class="' + constants.loaderClass + '" />');

            this.sandbox.dom.append(this.$el, $container);
            this.sandbox.once('husky.overlay.media-play-video.loading.opened', function() {
                this.sandbox.start([
                    {
                        name: 'loader@husky',
                        options: {
                            el: $loader,
                            size: '100px',
                            color: '#cccccc'
                        }
                    }
                ]);
            }.bind(this));
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        skin: 'wide',
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'media-play-video.loading',
                        closeIcon: '',
                        slides: [{
                            title: this.sandbox.translate('sulu.media.play-video.loading'),
                            data: $loader,
                            okInactive: true,
                            buttons: [
                                {
                                    type: 'cancel',
                                    inactive: false,
                                    text: 'public.cancel',
                                    align: 'left'
                                }
                            ]
                        }]
                    }
                }
            ]);
        },

        /**
         * Load video-data from server-api
         * @param videoId
         * @param locale
         * @returns video
         */
        loadVideo: function(videoId, locale) {
            var promise = $.Deferred(),
                video,
                request;

            request = MediaManager.loadOrNew(videoId, locale);

            request.then(function(video) {
                promise.resolve(video);
            }.bind(this));

            return promise;
        },

        /**
         * Starts the actual overlay for displaying video
         */
        startVideoOverlay: function(video) {
            var videoUrl = video.url + '&inline=1',
                $container,
                $data;

            $container = this.sandbox.dom.createElement(
                '<div class="' + constants.singleEditClass + '" id="media-form"/>'
            );

            $data = this.templates.video(
                {
                    videoUrl: videoUrl,
                    playVideoId: constants.playVideoId
                }
            );

            this.sandbox.dom.append(this.$el, $container);
            this.bindVideoOverlayEvents();
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        skin: 'wide',
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'media-play-video',
                        propagateEvents: false,
                        slides: [{
                            title: video.title,
                            data: $data,
                            okInactive: true,
                            buttons: [
                                {
                                    type: 'cancel',
                                    inactive: false,
                                    text: 'public.cancel',
                                    align: 'left'
                                }
                            ],
                            cancelCallback: function () {
                                this.sandbox.stop();
                            }.bind(this)
                        }]
                    }
                }
            ]);
        },

        /**
         * Binds events related to the video-play overlay
         */
        bindVideoOverlayEvents: function() {
            this.sandbox.once('husky.overlay.media-play-video.opened', function() {
                var videoElement = document.getElementById(constants.playVideoId);
                videoElement.autoplay = true;
            }.bind(this));

            this.sandbox.once('husky.overlay.media-play-video.initialized', function() {
                this.sandbox.emit('husky.overlay.media-play-video.loading.close');
            }.bind(this));
        },

        /**
         * Called when component gets destroyed
         */
        destroy: function() {
            this.events.closed();
        }
    };
});
