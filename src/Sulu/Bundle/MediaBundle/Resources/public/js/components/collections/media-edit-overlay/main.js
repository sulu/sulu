/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @class MediaEdit
 * Class which shows overlays for editing media models
 * @constructor
 *
 **/
define(['services/sulumedia/media-manager', 'services/sulumedia/overlay-manager'], function(MediaManager, OverlayManager) {

    'use strict';

    var namespace = 'sulu.media-edit.',

        defaults = {
            infoKey: 'public.info',
            versionsKey: 'sulu.media.history',
            multipleEditTitle: 'sulu.media.multiple-edit.title',
            loadingTitle: 'sulu.media.edit.loading',
            instanceName: ''
        },

        constants = {
            infoFormSelector: '#media-info',
            versionsFormSelector: '#media-versions',
            multipleEditFormSelector: '#media-multiple-edit',
            dropzoneSelector: '#file-version-change',
            multipleEditDescSelector: '.media-description',
            multipleEditTagsSelector: '.media-tags',
            descriptionCheckboxSelector: '#show-descriptions',
            tagsCheckboxSelector: '#show-tags',
            singleEditClass: 'single-edit',
            multiEditClass: 'multi-edit',
            loadingClass: 'loading',
            loaderClass: 'media-edit-loader'
        },

        /**
         * raised when the overlay get closed
         * @event sulu.media-edit.closed
         */
        CLOSED = function() {
            return createEventName.call(this, 'closed');
        },

        /**
         * raised when component is initialized
         * @event sulu.media-edit.closed
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        },

        /** returns normalized event names */
        createEventName = function(postFix) {
            return namespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        };

    return {

        templates: [
            '/admin/media/template/media/info',
            '/admin/media/template/media/versions',
            '/admin/media/template/media/multiple-edit'
        ],

        /**
         * Initializes the collections list
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // for single edit
            this.media = null;

            // for multiple edit
            this.medias = null;
            this.$multiple = null;

            this.loadMediaAndRender();

            this.sandbox.emit(INITIALIZED.call(this));
        },

        loadMediaAndRender: function() {
            this.startLoadingOverlay();
            this.loadMedias(this.options.mediaIds, this.options.locale).then(function(medias) {
                this.editMedia(medias);
            }.bind(this));
        },

        /**
         * Starts the loading overlay in hidden state
         */
        startLoadingOverlay: function() {
            var $container = this.sandbox.dom.createElement('<div class="' + constants.loadingClass + '"/>'),
                $loader = this.sandbox.dom.createElement('<div class="' + constants.loaderClass + '" />');

            this.sandbox.dom.append(this.$el, $container);
            this.sandbox.once('husky.overlay.media-edit.loading.opened', function() {
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
                        title: this.sandbox.translate(this.options.loadingTitle),
                        data: $loader,
                        skin: 'wide',
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'media-edit.loading',
                        closeIcon: '',
                        okInactive: true
                    }
                }
            ]);
        },

        loadMedias: function(mediaIds, locale) {
            var requests = [],
                promise = $.Deferred(),
                medias = [];

            mediaIds.forEach(function(mediaId) {
                var request = MediaManager.loadOrNew(mediaId, locale);
                requests.push(request);

                request.then(function(media) {
                    medias.push(media);
                }.bind(this));
            }.bind(this));

            $.when.apply(null, requests).then(function() {
                promise.resolve(medias);
            }.bind(this));

            return promise;
        },

        /**
         * Shows an overlay to edit media
         * @param medias {Object|Array} the media model or an array with media models
         */
        editMedia: function(medias) {
            if (medias.length === 1) {
                this.editSingleMedia(medias[0]);
            } else if (medias.length > 1) {
                this.editMultipleMedia(medias);
            }
        },

        /**
         * Edits a single media
         * @param media {Object} the id of the media to edit
         * @param {Array} locales
         */
        editSingleMedia: function(media) {
            this.media = media;
            var $info = this.sandbox.dom.createElement(this.renderTemplate('/admin/media/template/media/info', {
                media: this.media
            }));
            var $versions = this.sandbox.dom.createElement(this.renderTemplate('/admin/media/template/media/versions', {
                media: this.media
            }));
            this.startSingleOverlay($info, $versions);
        },

        /**
         * Starts the actual overlay for single-edit
         */
        startSingleOverlay: function($info, $versions) {
            var $container = this.sandbox.dom.createElement('<div class="' + constants.singleEditClass + '"/>');
            this.sandbox.dom.append(this.$el, $container);
            this.bindSingleOverlayEvents();
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        title: this.media.title,
                        tabs: [
                            {title: this.sandbox.translate(this.options.infoKey), data: $info},
                            {title: this.sandbox.translate(this.options.versionsKey), data: $versions}
                        ],
                        languageChanger: {
                            locales: this.sandbox.sulu.locales,
                            preSelected: this.options.locale
                        },
                        skin: 'wide',
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'media-edit',
                        okCallback: function() {
                            if (this.sandbox.form.validate(constants.infoFormSelector)) {
                                this.saveSingleMedia();
                            } else {
                                return false;
                            }
                        }.bind(this),
                        cancelCallback: function() {
                            this.sandbox.stop();
                        }.bind(this)
                    }
                }
            ]);
        },

        /**
         * Binds events related to the single-edit overlay
         */
        bindSingleOverlayEvents: function() {
            this.sandbox.once('husky.overlay.media-edit.opened', function() {
                this.sandbox.form.create(constants.infoFormSelector).initialized.then(function() {
                    this.sandbox.form.setData(constants.infoFormSelector, this.media).then(function() {
                        this.sandbox.start(constants.infoFormSelector);
                        this.startSingleDropzone();
                    }.bind(this));
                }.bind(this));
            }.bind(this));

            this.sandbox.once('husky.overlay.media-edit.initialized', function() {
                this.sandbox.emit('husky.overlay.media-edit.loading.close');
            }.bind(this));

            this.sandbox.once('husky.dropzone.file-version-' + this.media.id + '.initialized', function() {
                this.sandbox.emit('husky.overlay.media-edit.set-position');
            }.bind(this));

            this.sandbox.once('husky.auto-complete-list.media-info-' + this.media.id + '.initialized', function() {
                this.sandbox.emit('husky.overlay.media-edit.set-position');
            }.bind(this));

            this.sandbox.on('husky.auto-complete-list.media-info-' + this.media.id + '.item-added', function() {
                this.sandbox.emit('husky.overlay.media-edit.set-position');
            }.bind(this));

            // change language (single-edit)
            this.sandbox.once('husky.overlay.media-edit.language-changed', this.languageChangedSingle.bind(this));
        },

        /**
         * Handles the changing of the language in the single-edit overlay
         * @param locale
         */
        languageChangedSingle: function(locale) {
            this.saveSingleMedia().then(function() {
                this.sandbox.stop();
                OverlayManager.startEditMediaOverlay(this.sandbox._parent, this.options.mediaIds, locale);
            }.bind(this));
        },

        filesAddedHandler: function(newMedia) {
            if (!!newMedia[0]) {
                this.sandbox.emit('sulu.medias.media.saved', newMedia[0].id, newMedia[0]);
                this.sandbox.stop();
                OverlayManager.startEditMediaOverlay(this.sandbox._parent, this.options.mediaIds, this.options.locale);
            }
        },

        /**
         * Maps the overlay inputs back on a single model
         * @returns {Boolean} returns false if form is invalid, true if valid
         */
        saveSingleMedia: function() {
            var promise = $.Deferred();
            if (this.sandbox.form.validate(constants.infoFormSelector)) {
                var formData = this.sandbox.form.getData(constants.infoFormSelector),
                    mediaData = this.sandbox.util.extend(false, {}, this.media, formData);

                if (JSON.stringify(this.media) !== JSON.stringify(mediaData)) {
                    MediaManager.save(mediaData).then(function() {
                        promise.resolve();
                    });
                }
                else {
                    promise.resolve();
                }
            } else {
                promise.resolve();
            }
            return promise;
        },

        /**
         * Starts the dropzone for changeing the file-version
         */
        startSingleDropzone: function() {
            // replace the current media with the new one if a fileversion got uploaded
            this.sandbox.on('husky.dropzone.file-version-' + this.media.id + '.files-added', this.filesAddedHandler.bind(this));

            this.sandbox.start([
                {
                    name: 'dropzone@husky',
                    options: {
                        el: constants.dropzoneSelector,
                        url: '/admin/api/media/' + this.media.id + '?action=new-version',
                        method: 'POST',
                        paramName: 'fileVersion',
                        showOverlay: false,
                        skin: 'overlay',
                        titleKey: '',
                        descriptionKey: 'sulu.media.upload-new-version', //todo: add translation
                        instanceName: 'file-version-' + this.media.id,
                        maxFiles: 1
                    }
                }
            ]);
        },

        /**
         * Edits multiple media
         * @param medias {Array} array with the ids of the media to edit
         * @param {Array} locales
         */
        editMultipleMedia: function(medias) {
            this.medias = medias;
            this.$multiple = this.sandbox.dom.createElement(this.renderTemplate('/admin/media/template/media/multiple-edit'));
            this.startMultipleEditOverlay();
        },

        /**
         * Starts the actual overlay for multiple-edit
         */
        startMultipleEditOverlay: function() {
            var $container = this.sandbox.dom.createElement('<div class="' + constants.multiEditClass + '"/>');
            this.sandbox.dom.append(this.$el, $container);
            this.bindMultipleOverlayEvents();
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        title: this.sandbox.translate(this.options.multipleEditTitle),
                        data: this.$multiple,
                        languageChanger: {
                            locales: this.sandbox.sulu.locales,
                            preSelected: this.options.locale
                        },
                        openOnStart: true,
                        removeOnClose: true,
                        closeIcon: '',
                        instanceName: 'media-multiple-edit',
                        okCallback: function() {
                            if (this.sandbox.form.validate(constants.multipleEditFormSelector)) {
                                this.saveMultipleMedia();
                            } else {
                                return false;
                            }
                        }.bind(this),
                        cancelCallback: function() {
                            this.sandbox.stop();
                        }.bind(this)
                    }
                }
            ]);
        },


        /**
         * Handles the changing of the language in the singleedit overlay
         * @param locale
         */
        languageChangedMultiple: function(locale) {
            this.saveMultipleMedia().then(function() {
                this.sandbox.stop();
                OverlayManager.startEditMediaOverlay(this.sandbox._parent, this.options.mediaIds, locale);
            }.bind(this));
        },

        /**
         * Binds events related to the single-edit overlay
         */
        bindMultipleOverlayEvents: function() {
            this.sandbox.once('husky.overlay.media-multiple-edit.opened', function() {
                this.sandbox.form.create(constants.multipleEditFormSelector).initialized.then(function() {
                    this.sandbox.form.setData(constants.multipleEditFormSelector, {
                        records: this.medias
                    }).then(function() {
                        this.sandbox.start(constants.multipleEditFormSelector);
                        this.sandbox.emit('husky.overlay.media-multiple-edit.set-position');
                    }.bind(this));
                }.bind(this));
            }.bind(this));

            this.sandbox.once('husky.overlay.media-multiple-edit.initialized', function() {
                this.sandbox.emit('husky.overlay.media-edit.loading.close');
            }.bind(this));

            // toggle all descriptions on click on related checkbox
            this.sandbox.dom.on(
                this.sandbox.dom.find(constants.descriptionCheckboxSelector, this.$multiple),
                'change',
                this.toggleDescriptions.bind(this)
            );
            // toggle all tag-components on click on related checkbox
            this.sandbox.dom.on(
                this.sandbox.dom.find(constants.tagsCheckboxSelector, this.$multiple),
                'change',
                this.toggleTags.bind(this)
            );

            // change language (multi-edit)
            this.sandbox.on('husky.overlay.media-multiple-edit.language-changed', this.languageChangedMultiple.bind(this));
        },

        /**
         * Toggles the descriptions in the multiple-edit element
         */
        toggleDescriptions: function() {
            var checked = this.sandbox.dom.is(this.sandbox.dom.find(constants.descriptionCheckboxSelector, this.$multiple), ':checked'),
                $elements = this.sandbox.dom.find(constants.multipleEditDescSelector, this.$multiple);
            if (checked === true) {
                this.sandbox.dom.show($elements);
                this.sandbox.dom.removeClass($elements, 'hidden');
            } else {
                this.sandbox.dom.hide($elements);
                this.sandbox.dom.addClass($elements, 'hidden');
            }
            this.sandbox.emit('husky.overlay.media-multiple-edit.set-position');
        },

        /**
         * Toggles the tag-components in the multiple-edit element
         */
        toggleTags: function() {
            var checked = this.sandbox.dom.is(this.sandbox.dom.find(constants.tagsCheckboxSelector, this.$multiple), ':checked'),
                $elements = this.sandbox.dom.find(constants.multipleEditTagsSelector, this.$multiple);
            if (checked === true) {
                this.sandbox.dom.show($elements);
                this.sandbox.dom.removeClass($elements, 'hidden');
            } else {
                this.sandbox.dom.hide($elements);
                this.sandbox.dom.addClass($elements, 'hidden');
            }
            this.sandbox.emit('husky.overlay.media-multiple-edit.set-position');
        },

        /**
         * Maps the overlay input back on multiple models
         * @returns {Boolean} returns false if form is invalid, true if valid
         */
        saveMultipleMedia: function() {
            var promise = $.Deferred();

            if (this.sandbox.form.validate(constants.multipleEditFormSelector)) {
                var formData = this.sandbox.form.getData(constants.multipleEditFormSelector),
                    requests = [];

                this.sandbox.util.foreach(this.medias, function(currentMedia, index) {
                    var newMedia = this.sandbox.util.extend(false, {}, currentMedia, formData.records[index]);
                    if (JSON.stringify(currentMedia) !== JSON.stringify(newMedia)) {
                        requests.push(MediaManager.save(newMedia));
                    }
                }.bind(this));

                $.when.apply(null, requests).then(function() {
                    promise.resolve();
                }.bind(this));
            } else {
                promise.resolve();
            }

            return promise;
        },

        destroy: function() {
            this.sandbox.emit(CLOSED.call(this));
        }
    };
});
