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
 */
define([
    'config',
    './cropping-slide',
    './focus-point-slide',
    'services/sulumedia/media-manager',
    'services/sulumedia/file-icons',
    'services/sulumedia/image-editor',
    'text!./info.html',
    'text!./copyright.html',
    'text!./versions.html',
    'text!./preview.html',
    'text!./formats.html',
    'text!./categories.html'
], function(
    config,
    croppingSlide,
    focusPointSlide,
    mediaManager,
    fileIcons,
    imageEditor,
    infoTemplate,
    copyrightTemplate,
    versionsTemplate,
    previewTemplate,
    formatsTemplate,
    categoriesTemplate
) {

    'use strict';

    var namespace = 'sulu.media-edit.',

        defaults = {
            instanceName: '',
            startingSlide: 'edit',
            formats: []
        },

        constants = {
            thumbnailFormat: '260x',
            formSelector: '#media-form',
            multipleEditFormSelector: '#media-multiple-edit',
            fileDropzoneSelector: '#file-version-change',
            previewDropzoneSelector: '#preview-image-change',
            multipleEditDescSelector: '.media-description',
            multipleEditTagsSelector: '.media-tags',
            descriptionCheckboxSelector: '#show-descriptions',
            tagsCheckboxSelector: '#show-tags',
            previewSelector: '.media-edit-preview-image',
            previewLoaderSelector: '.media-edit-preview-loader',
            previewLoaderHintSelector: '.media-edit-preview-loader .hint',
            previewImgSelector: '.media-edit-preview-image img',
            singleEditClass: 'single-edit',
            multiEditClass: 'multi-edit',
            loadingClass: 'loading',
            loaderClass: 'media-edit-loader',
            resetPreviewActionClass: 'media-reset-preview-action',
            previewImageLoaderDuration: 3000,
            singleOverlaySkin: 'large',
            multipleOverlaySkin: 'medium',
            formatTabId: 'media-formats'
        },

        imageFormats = config.get('sulu-media')['formats'],

        nonInternalImageFormats = Object.keys(imageFormats).reduce(function(previous, current) {
            if (!imageFormats[current].internal) {
                previous[current] = imageFormats[current];
            }

            return previous;
        }, {}),

        resetPreviewUrl = function(id) {
            return '/admin/api/media/' + id + '/preview?locale=' + this.options.locale;
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

        /**
         * starts a loader with the passed text in the preview image
         * @event sulu.media-edit.preview.loading
         */
        LOADING_PREVIEW = function() {
            return createEventName.call(this, 'preview.loading');
        },

        /**
         * triggers the format tab to be updated with new URLs
         */
        UPDATE_FORMATS = function() {
            return createEventName.call(this, 'formats.update');
        },

        /** returns normalized event names */
        createEventName = function(postFix) {
            return namespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        };

    return {

        templates: [
            '/admin/media/template/media/multiple-edit'
        ],

        /**
         * Initializes the overlay component
         */
        initialize: function() {
            var formatCount;

            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {
                locale: this.sandbox.sulu.getDefaultContentLocale()
            }, defaults, this.options);

            if (!this.options.mediaIds) {
                throw new Error('media-ids are not defined');
            }

            this.imageFormats = nonInternalImageFormats;

            formatCount = this.options.formats.length;
            if (formatCount > 0) {
                this.imageFormats = Object.keys(nonInternalImageFormats).reduce(function (previous, current) {
                    for (var i = 0; i < formatCount; ++i) {
                        if (this.options.formats[i] === nonInternalImageFormats[current].key) {
                            previous[current] = nonInternalImageFormats[current];
                            return previous;
                        }
                    }

                    return previous;
                }.bind(this), {});
            }

            this.options.previewInitialized = false;

            // for single edit
            this.media = null;

            this.loaderTimeout = null;

            // for multiple edit
            this.medias = null;
            this.$multiple = null;

            this.startLoadingOverlay(this.options.mediaIds.length > 1);
            this.loadMedias(this.options.mediaIds, this.options.locale).then(function(medias) {
                this.editMedia(medias);
            }.bind(this));

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Starts the loading overlay
         */
        startLoadingOverlay: function(multiple) {
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
                        skin: !!multiple ? constants.multipleOverlaySkin : constants.singleOverlaySkin,
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
                var request = mediaManager.loadOrNew(mediaId, locale);
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
         */
        editSingleMedia: function(media) {
            var $container = this.sandbox.dom.createElement('<div class="' + constants.singleEditClass + '" id="media-form"/>'),
                $editActionSelect, startingSlide = 0;

            this.media = media;
            this.sandbox.dom.append(this.$el, $container);
            this.bindSingleOverlayEvents();

            var infoTab = $(this.renderInfoTab()),
                previewTab = this.renderPreviewTab(),
                tabs = [
                    {title: this.sandbox.translate('public.info'), data: infoTab},
                    {title: this.sandbox.translate('sulu.media.licence'), data: this.renderCopyrightTab()}
                ];

            if (previewTab) {
                tabs.push(
                    {
                        title: this.sandbox.translate('sulu.media.preview-tab'),
                        data: previewTab
                    }
                );
            }

            tabs.push(
                {
                    title: this.sandbox.translate('sulu.media.formats'),
                    data: $('<div id="' + constants.formatTabId + '">' + this.renderFormatsTab() + '</div>')
                }
            );

            tabs.push(
                {
                    title: this.sandbox.translate('sulu.media.categories'),
                    data: this.renderCategoriesTab()
                }
            );

            tabs.push(
                {
                    title: this.sandbox.translate('sulu.media.history'),
                    data: this.renderVersionTab()
                }
            );

            if (this.media.type.name === 'image') {
                $editActionSelect = $('<div class="edit-action-select"/>');
                croppingSlide.initialize(this.$el, this.sandbox, this.media, this.imageFormats, function() {
                    this.sandbox.emit('husky.overlay.media-edit.slide-to', 0);
                }.bind(this));
                focusPointSlide.initialize(this.sandbox, this.media, function() {
                    this.sandbox.emit('husky.overlay.media-edit.slide-to', 0);
                }.bind(this));
                startingSlide = (this.options.startingSlide === 'crop') ? 1 : startingSlide;
            }

            this.removePlaceholderOnImgLoad(infoTab, fileIcons.getByMimeType(this.media.mimeType));

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'media-edit',
                        skin: constants.singleOverlaySkin,
                        startingSlide: startingSlide,
                        supportKeyInput: false,
                        slides: [
                            {
                                title: this.media.title,
                                subTitle: this.sandbox.util.cropMiddle(
                                    this.media.mimeType + ', ' + this.sandbox.util.formatBytes(this.media.size),
                                    32
                                ),
                                tabs: tabs,
                                languageChanger: {
                                    locales: this.sandbox.sulu.locales,
                                    preSelected: this.options.locale
                                },
                                panelContent: $editActionSelect,
                                propagateEvents: false,
                                okCallback: this.singleOkCallback.bind(this),
                                cancelCallback: function() {
                                    this.sandbox.stop();
                                }.bind(this),
                                buttons: [
                                    {
                                        type: 'cancel',
                                        inactive: false,
                                        text: 'public.cancel',
                                        align: 'left'
                                    },
                                    {
                                        classes: 'just-text ' + constants.resetPreviewActionClass,
                                        inactive: false,
                                        text: 'sulu.media.reset-preview-image',
                                        align: 'center',
                                        callback: function() {
                                            this.resetPreviewImage.call(this);
                                            return false;
                                        }.bind(this)
                                    },
                                    {
                                        type: 'ok',
                                        inactive: false,
                                        text: 'public.ok',
                                        align: 'right'
                                    }
                                ]
                            },
                            croppingSlide.getSlideDefinition(),
                            focusPointSlide.getSlideDefinition()
                        ]
                    }
                }
            ]).then(function() {
                if (this.media.type.name === 'image') {
                    this.startEditActionSelect($editActionSelect);
                    croppingSlide.start();
                    focusPointSlide.start();
                }
            }.bind(this));
        },

        /**
         * Renders the content for the info tab
         * @returns {Element|*|Object}
         */
        renderInfoTab: function() {
            return this.sandbox.dom.createElement(_.template(infoTemplate, {
                media: this.media,
                translate: this.sandbox.translate,
                icon: fileIcons.getByMimeType(this.media.mimeType),
                thumbnailFormat: constants.thumbnailFormat
            }));
        },

        /**
         * Renders the content for the copyright tab
         */
        renderCopyrightTab: function() {
            return _.template(copyrightTemplate, {
                media: this.media,
                translate: this.sandbox.translate
            });
        },

        /**
         * Renders the content for the formats tab
         */
        renderFormatsTab: function() {
            var formatUrls = Object.keys(nonInternalImageFormats).reduce(function(previous, current) {
                    previous[current] = this.media.thumbnails[current];

                    return previous;
                }.bind(this), {}),

                formatTitles = Object.keys(nonInternalImageFormats).reduce(function(previous, current) {
                    var format = nonInternalImageFormats[current];

                    previous[current] = null;

                    if (!!format && format.meta && format.meta.title) {
                        previous[current] = format.meta.title[this.sandbox.sulu.user.locale];
                    }

                    return previous;
                }.bind(this), {});

            return _.template(formatsTemplate, {
                media: this.media,
                formatUrls: formatUrls,
                formatTitles: formatTitles,
                domain: window.location.protocol + '//' + window.location.host,
                translate: this.sandbox.translate
            });
        },

        /**
         * Renders the content for the preview tab
         */
        renderPreviewTab: function() {
            if (this.media.type.name === 'image') {
                return;
            }

            return _.template(previewTemplate, {
                media: this.media,
                translate: this.sandbox.translate
            });
        },

        /**
         * Renders the content for the categories tab
         */
        renderCategoriesTab: function() {
            return _.template(categoriesTemplate, {
                categoryLocale: this.options.locale,
                media: this.media,
                translate: this.sandbox.translate
            });
        },

        renderVersionTab: function() {
            return _.template(versionsTemplate, {
                media: this.media,
                translate: this.sandbox.translate
            });
        },

        /**
         * Starts the edit action select which provides actions
         * to navigate to the cropping slide.
         *
         * @param {Object} $element The dom element to start the select in
         */
        startEditActionSelect: function($element) {
            var config = {
                data: [
                    {
                        name: this.sandbox.translate('sulu-media.crop'),
                        callback: function() {
                            this.sandbox.emit('husky.overlay.media-edit.slide-to', 1);
                        }.bind(this)
                    },
                    {
                        name: this.sandbox.translate('sulu-media.focus-point'),
                        callback: function() {
                            this.sandbox.emit('husky.overlay.media-edit.slide-to', 2);
                        }.bind(this)
                    }
                ]
            };

            if (imageEditor.editingIsPossible()) {
                config.data.push({
                    name: this.sandbox.translate('sulu-media.edit-original'),
                    callback: function() {
                        this.sandbox.sulu.showConfirmationDialog({
                            title: 'sulu-media.external-server-title',
                            description: 'sulu-media.external-server-description',
                            callback: function(confirmed) {
                                if (!confirmed) {
                                    return;
                                }

                                imageEditor.editImage(this.media.url).then(this.setNewVersionByUrl.bind(this));
                            }.bind(this)
                        });
                    }.bind(this)
                });
            }

            this.sandbox.start([{
                name: 'select@husky',
                options: this.sandbox.util.extend(true, {}, {
                    el: $element,
                    defaultLabel: this.sandbox.translate('sulu-media.edit-image'),
                    instanceName: 'edit-action-select',
                    fixedLabel: true,
                    skin: 'white-border',
                    icon: 'paint-brush',
                    repeatSelect: true
                }, config)
            }]);
        },

        /**
         * Handles the load process of a preview image. The image gets hidden
         * and displayed when completely loaded. When the loading has finished
         * the placeholder gets removed.
         *
         * @param container
         * @param placeholderClass
         */
        removePlaceholderOnImgLoad: function(container, placeholderClass) {
            var $img = $(container).find(constants.previewImgSelector);
            if (!!$img.length) {
                $img.hide();
                $img.load(function() {
                    $img.show();
                    $img.parent().removeClass(placeholderClass);
                }.bind(this));
            }
        },

        /**
         * Validate single form-data and save if form-data is valid
         * @returns {boolean} false if form-data is not valid
         */
        singleOkCallback: function() {
            if (this.sandbox.form.validate(constants.formSelector)) {
                this.saveSingleMedia();
                this.sandbox.stop();
            } else {
                return false;
            }
        },

        /**
         * Binds events related to the single-edit overlay
         */
        bindSingleOverlayEvents: function() {
            this.sandbox.once('husky.overlay.media-edit.opened', function() {
                this.sandbox.form.create(constants.formSelector).initialized.then(function() {
                    this.sandbox.form.setData(constants.formSelector, this.media).then(function() {
                        this.sandbox.start(constants.formSelector);
                        this.sandbox.dom.addClass($('.' + constants.resetPreviewActionClass), 'hide');
                        this.startSingleDropzone();
                    }.bind(this));
                }.bind(this));
            }.bind(this));

            this.sandbox.once('husky.overlay.media-edit.initialized', function() {
                this.sandbox.emit('husky.overlay.media-edit.loading.close');
            }.bind(this));

            this.sandbox.once('husky.overlay.media-edit.opened', function() {
                this.clipboard = this.sandbox.clipboard.initialize('.fa-clipboard');
                this.sandbox.emit('husky.overlay.alert.close');
            }.bind(this));

            // change language (single-edit)
            this.sandbox.on('husky.tabs.overlaymedia-edit.item.select', function(tab) {
                var $resetPreviewButton = $('.' + constants.resetPreviewActionClass);

                if (tab.$el[0].id === 'media-preview') {
                    if (!this.options.previewInitialized) {
                        this.startPreviewDropzone();
                        this.options.previewInitialized = true;
                    }
                    this.sandbox.dom.removeClass($resetPreviewButton, 'hide');
                } else {
                    this.sandbox.dom.addClass($resetPreviewButton, 'hide');
                }
            }.bind(this));

            // initialize preview image upload tab
            this.sandbox.once('husky.overlay.media-edit.initialized', function() {
                this.sandbox.emit('husky.overlay.media-edit.loading.close');
            }.bind(this));

            // change language (single-edit)
            this.sandbox.on(
                'husky.overlay.media-edit.language-changed', this.languageChangedSingle.bind(this)
            );

            this.sandbox.dom.on(this.$el, 'click', function(e) {
                var $target = $(e.currentTarget),
                    $item = $target.parents('.media-edit-link'),
                    $info = $target.siblings('.media-edit-copied');

                $item.addClass('highlight-animation');
                $target.hide();
                $info.show();

                _.delay(function($target, $item, $info) {
                    $item.removeClass('highlight-animation');
                    $info.hide();
                    $target.show();
                }, 2000, $target, $item, $info);
            }.bind(this), '.fa-clipboard');

            this.sandbox.on('husky.dropzone.file-version.uploading', function() {
                this.sandbox.emit('husky.overlay.alert.close');
            }.bind(this));

            this.sandbox.on('husky.dropzone.file-version.files-added', this.newVersionUploadedHandler.bind(this));
            this.sandbox.on('husky.dropzone.preview-image.files-added', this.previewImageChangeHandler.bind(this));

            this.sandbox.on(LOADING_PREVIEW.call(this), this.startPreviewImageLoader.bind(this));
            this.sandbox.on(UPDATE_FORMATS.call(this), this.updateFormatsTab.bind(this));
        },

        /**
         * Removes events related to the single-edit overlay
         */
        unbindSingleOverlayEvents: function() {
            this.sandbox.off('husky.overlay.media-edit.language-changed');
            this.sandbox.off('husky.tabs.overlaymedia-edit.item.select');
            this.sandbox.off('husky.dropzone.file-version.files-added');
            this.sandbox.off('husky.dropzone.preview-image.files-added');
        },

        /**
         * Handles the changing of the language in the single-edit overlay by saving current data
         * and restarting the overlay component with the new locale
         * @param locale
         */
        languageChangedSingle: function(locale) {
            this.saveSingleMedia().then(function() {
                croppingSlide.destroy();
                this.sandbox.stop(this.$find('*'));
                this.options.locale = locale;

                this.unbindSingleOverlayEvents();

                this.initialize();
            }.bind(this));
        },

        /**
         * Sets the new file version for a media by a given url. Shows
         * a confirmation dialog to the user to confirm the change.
         *
         * @param {String} newMediaUrl The url to the new media
         */
        setNewVersionByUrl: function(newMediaUrl) {
            this.sandbox.sulu.showConfirmationDialog({
                title: 'sulu-media.new-version-will-be-created',
                description: 'sulu-media.new-version-will-be-created-description',
                callback: function(confirmed) {
                    if (!confirmed) {
                        return;
                    }

                    this.sandbox.emit('husky.overlay.alert.show-loader');
                    this.sandbox.emit(
                        'husky.dropzone.file-version.add-image',
                        newMediaUrl,
                        this.media.mimeType,
                        this.media.name
                    );

                    return false;
                }.bind(this)
            });
        },

        /**
         * Handles the upload of a image as new version
         * @param newMedia
         */
        newVersionUploadedHandler: function(newMedia) {
            if (!!newMedia[0]) {
                this.sandbox.emit('sulu.medias.media.saved', newMedia[0].id, newMedia[0]);
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-save-desc');

                croppingSlide.destroy();
                this.sandbox.stop(this.$find('*'));

                this.unbindSingleOverlayEvents();

                this.initialize();
            }
        },

        /**
         * Handles the reset and rerendering of preview images
         */
        previewImageChangeHandler: function() {
            var mediaId = this.media.id;

            mediaManager.loadOrNew(mediaId, this.options.locale).then(function(media) {
                this.sandbox.emit('sulu.medias.media.saved', media.id, media);
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-save-desc');

                croppingSlide.destroy();
                this.sandbox.stop(this.$find('*'));

                this.unbindSingleOverlayEvents();
                this.initialize();
            }.bind(this));
        },

        /**
         * Rerenders the format tab, to update the URLs.
         */
        updateFormatsTab: function() {
            mediaManager.loadOrNew(this.media.id, this.options.locale).then(function(media) {
                this.media = media;
                $('#' + constants.formatTabId).html(this.renderFormatsTab());
            }.bind(this));
        },

        /**
         * Start a loader in the preview image with a text hint.
         *
         * @param hint {String}
         */
        startPreviewImageLoader: function(hint) {
            this.showPreviewImageLoader(hint);

            this.sandbox.once('husky.overlay.media-edit.slide-to', function() {
                this.loaderTimeout = setTimeout(function() {
                    this.hidePreviewImageLoader();
                    this.loaderTimeout = null;
                }.bind(this), constants.previewImageLoaderDuration);
            }, this);
        },

        /**
         * Shows the loader for the preview image.
         *
         * @param {String} hint
         */
        showPreviewImageLoader: function(hint) {
            $(constants.previewSelector).hide();
            $(constants.previewLoaderSelector).show();

            $(constants.previewLoaderHintSelector).text(hint);
        },

        /**
         * Hide the loader for the preview image.
         */
        hidePreviewImageLoader: function() {
            $(constants.previewSelector).show();
            $(constants.previewLoaderSelector).hide();

            $(constants.previewLoaderHintSelector).text('');
        },

        /**
         * Save the media if form-data is valid and something was changed
         */
        saveSingleMedia: function() {
            var promise = $.Deferred();
            // validate form data
            if (this.sandbox.form.validate(constants.formSelector)) {
                var formData = this.sandbox.form.getData(constants.formSelector),
                    mediaData = this.sandbox.util.extend(false, {}, this.media, formData);

                // check if form-data is different to source-media
                if (JSON.stringify(this.media) !== JSON.stringify(mediaData)) {
                    mediaManager.save(mediaData).then(function() {
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
            this.sandbox.start([
                {
                    name: 'dropzone@husky',
                    options: {
                        el: constants.fileDropzoneSelector,
                        maxFilesize: config.get('sulu-media').maxFilesize,
                        url: '/admin/api/media/' + this.media.id + '?action=new-version&locale=' + this.options.locale,
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
         * Starts the dropzone for changing the preview image
         */
        startPreviewDropzone: function() {
            this.sandbox.start([
                {
                    name: 'dropzone@husky',
                    options: {
                        el: constants.previewDropzoneSelector,
                        maxFilesize: config.get('sulu-media').maxFilesize,
                        url: '/admin/api/media/' + this.media.id + '/preview?locale=' + this.options.locale,
                        method: 'POST',
                        paramName: 'previewImage',
                        showOverlay: false,
                        skin: 'overlay',
                        titleKey: '',
                        descriptionKey: 'sulu.media.upload-new-preview',
                        instanceName: 'preview-image',
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
            this.$multiple = this.sandbox.dom.createElement(
                this.renderTemplate('/admin/media/template/media/multiple-edit')
            );
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
                        skin: constants.multipleOverlaySkin,
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
                        cancelCallback: function() {
                            this.sandbox.stop();
                        }.bind(this)
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
                this.sandbox.stop();
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
                this.sandbox.stop(this.$find('*'));
                this.options.locale = locale;

                this.unbindMultipleOverlayEvents();

                this.initialize();
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
            this.sandbox.on(
                'husky.overlay.media-multiple-edit.language-changed', this.languageChangedMultiple.bind(this)
            );
        },

        /**
         * Binds events related to the multiple-edit overlay
         */
        unbindMultipleOverlayEvents: function() {
            this.sandbox.off('husky.overlay.media-multiple-edit.language-changed');
        },

        /**
         * Toggles the descriptions in the multiple-edit element
         */
        toggleDescriptions: function() {
            var checked = this.sandbox.dom.is(
                    this.sandbox.dom.find(constants.descriptionCheckboxSelector, this.$multiple), ':checked'
                ),
                $elements = this.sandbox.dom.find(constants.multipleEditDescSelector, this.$multiple);
            if (checked === true) {
                this.sandbox.dom.removeClass($elements, 'hidden');
            } else {
                this.sandbox.dom.addClass($elements, 'hidden');
            }
        },

        /**
         * Toggles the tag-components in the multiple-edit element
         */
        toggleTags: function() {
            var checked = this.sandbox.dom.is(
                    this.sandbox.dom.find(constants.tagsCheckboxSelector, this.$multiple), ':checked'
                ),
                $elements = this.sandbox.dom.find(constants.multipleEditTagsSelector, this.$multiple);
            if (checked === true) {
                this.sandbox.dom.removeClass($elements, 'hidden');
            } else {
                this.sandbox.dom.addClass($elements, 'hidden');
            }
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
                        requests.push(mediaManager.save(newMedia));
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
         * Removes current preview image and sets default video thumbnail
         */
        resetPreviewImage: function() {
            var mediaId = this.media.id;

            $.ajax({
                url: resetPreviewUrl(mediaId),
                type: 'DELETE',
                success: function() {
                    this.previewImageChangeHandler.call(this);
                }.bind(this)
            });
        },

        /**
         * Called when component gets destroyed
         */
        destroy: function() {
            if (!!this.loaderTimeout) {
                clearTimeout(this.loaderTimeout);
                this.loaderTimeout = null;
            }

            this.sandbox.emit(CLOSED.call(this));
        }
    };
});
