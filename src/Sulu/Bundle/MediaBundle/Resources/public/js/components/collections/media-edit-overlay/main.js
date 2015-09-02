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
            instanceName: '',
            locale: app.sandbox.sulu.user.locale
        },

        constants = {
            infoFormSelector: '#media-info',
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
         * Initializes the overlay component
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            if (!this.options.mediaIds) {
                this.sandbox.stop();
            }

            // for single edit
            this.media = null;

            // for multiple edit
            this.medias = null;
            this.$multiple = null;

            this.loadMediaAndRender();

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Show loading overlay and start loading media-data according to mediaIds in options
         */
        loadMediaAndRender: function() {
            this.startLoadingOverlay();
            this.loadMedias(this.options.mediaIds, this.options.locale).then(function(medias) {
                this.editMedia(medias);
            }.bind(this));
        },

        /**
         * Starts the loading overlay
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
                        title: this.sandbox.translate('sulu.media.edit.loading'),
                        data: $loader,
                        skin: 'wide',
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'media-edit.loading',
                        closeIcon: '',
                        okInactive: true,
                        propagateEvents: false
                    }
                }
            ]);
        },

        /**
         * Load media-data from server-api
         * @param mediaIds
         * @param locale
         * @returns {*}
         */
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
                            {title: this.sandbox.translate('public.info'), data: $info},
                            {title: this.sandbox.translate('sulu.media.history'), data: $versions}
                        ],
                        languageChanger: {
                            locales: this.sandbox.sulu.locales,
                            preSelected: this.options.locale
                        },
                        skin: 'wide',
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'media-edit',
                        propagateEvents: false,
                        okCallback: this.singleOkCallback.bind(this),
                        cancelCallback: this.sandbox.stop.bind(this)
                    }
                }
            ]);
        },

        /**
         * Validate single form-data and save if form-data is valid
         * @returns {boolean} false if form-data is not valid
         */
        singleOkCallback: function() {
            if (this.sandbox.form.validate(constants.infoFormSelector)) {
                this.saveSingleMedia();
            } else {
                return false;
            }
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

            this.sandbox.once('husky.dropzone.file-version.initialized', function() {
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
         * Handles the changing of the language in the single-edit overlay by saving current data
         * and restarting the overlay component with the new locale
         * @param locale
         */
        languageChangedSingle: function(locale) {
            this.saveSingleMedia().then(function() {
                this.sandbox.stop();
                OverlayManager.startEditMediaOverlay(this.sandbox._parent, this.options.mediaIds, locale);
            }.bind(this));
        },

        /**
         * Handles the upload of a image as new version
         * @param newMedia
         */
        newVersionUploadedHandler: function(newMedia) {
            if (!!newMedia[0]) {
                this.sandbox.emit('sulu.medias.media.saved', newMedia[0].id, newMedia[0]);
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-save-desc');

                this.sandbox.stop();
                OverlayManager.startEditMediaOverlay(this.sandbox._parent, this.options.mediaIds, this.options.locale);
            }
        },

        /**
         * Save the media if form-data is valid and something was changed
         */
        saveSingleMedia: function() {
            var promise = $.Deferred();
            // validate form data
            if (this.sandbox.form.validate(constants.infoFormSelector)) {
                var formData = this.sandbox.form.getData(constants.infoFormSelector),
                    mediaData = this.sandbox.util.extend(false, {}, this.media, formData);

                // check if form-data is different to source-media
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
         * Starts the dropzone for changing the file-version
         */
        startSingleDropzone: function() {
            this.sandbox.on('husky.dropzone.file-version.files-added', this.newVersionUploadedHandler.bind(this));

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
                        descriptionKey: 'sulu.media.upload-new-version',
                        instanceName: 'file-version',
                        maxFiles: 1
                    }
                }
            ]);
        },

        /**
         * Edits multiple media
         * @param medias {Array} array with the ids of the media to edit
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
                        title: this.sandbox.translate('sulu.media.multiple-edit.title'),
                        data: this.$multiple,
                        languageChanger: {
                            locales: this.sandbox.sulu.locales,
                            preSelected: this.options.locale
                        },
                        openOnStart: true,
                        removeOnClose: true,
                        closeIcon: '',
                        instanceName: 'media-multiple-edit',
                        propagateEvents: false,
                        okCallback: this.multipleOkCallback.bind(this),
                        cancelCallback: this.sandbox.stop.bind(this)
                    }
                }
            ]);
        },

        /**
         * Validate single form-data and save if form-data is valid
         * @returns {boolean} false if form-data is not valid
         */
        multipleOkCallback: function() {
            if (this.sandbox.form.validate(constants.multipleEditFormSelector)) {
                this.saveMultipleMedia();
            } else {
                return false;
            }
        },

        /**
         * Handles the changing of the language in the multiple-edit overlay by saving current data
         * and restarting the overlay component with the new locale
         * @param locale
         */
        languageChangedMultiple: function(locale) {
            this.saveMultipleMedia().then(function() {
                this.sandbox.stop();
                OverlayManager.startEditMediaOverlay(this.sandbox._parent, this.options.mediaIds, locale);
            }.bind(this));
        },

        /**
         * Binds events related to the multiple-edit overlay
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
                this.sandbox.dom.removeClass($elements, 'hidden');
            } else {
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
                this.sandbox.dom.removeClass($elements, 'hidden');
            } else {
                this.sandbox.dom.addClass($elements, 'hidden');
            }
            this.sandbox.emit('husky.overlay.media-multiple-edit.set-position');
        },

        /**
         * Saves the medias if form-data is valid and something was changed
         * @returns {Boolean} returns false if form is invalid, true if valid
         */
        saveMultipleMedia: function() {
            var promise = $.Deferred();

            // validate form data
            if (this.sandbox.form.validate(constants.multipleEditFormSelector)) {
                var formData = this.sandbox.form.getData(constants.multipleEditFormSelector),
                    requests = [];

                // loop through each media
                this.sandbox.util.foreach(this.medias, function(currentMedia, index) {
                    var newMedia = this.sandbox.util.extend(false, {}, currentMedia, formData.records[index]);
                    // check if form-data is different to source-media
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

        /**
         * Called when component gets destroyed
         */
        destroy: function() {
            this.sandbox.emit(CLOSED.call(this));
        }
    };
});
