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
define(function () {

    'use strict';

    var namespace = 'sulu.media-edit.',

        defaults = {
            infoKey: 'public.info',
            multipleEditTitle: 'sulu.media.multiple-edit.title',
            instanceName: ''
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
            multiEditClass: 'multi-edit'
        },

        /**
         * listens on and shows an overlay to edit the media for a given id
         * @event sulu.media-edit.edit
         * @param media {Object} the media model to edit
         */
        EDIT = function () {
            return createEventName.call(this, 'edit');
        },

        /**
         * raised if the media-edit overlay got closed
         * @event sulu.media-edit.closed
         */
        CLOSED = function () {
            return createEventName.call(this, 'closed');
        },

        /** returns normalized event names */
        createEventName = function (postFix) {
            return namespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        };

    return {

        templates: [
            '/admin/media/template/media/info',
            '/admin/media/template/media/multiple-edit'
        ],

        /**
         * Initializes the collections list
         */
        initialize: function () {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.bindCustomEvents();
            this.sandbox.dom.width(this.$el, 0);
            this.sandbox.dom.height(this.$el, 0);

            // for single edit
            this.media = null;
            // for multiple edit
            this.medias = null;

            // stores info-tab html
            this.$info = null;
            // stores the multiple edit-form
            this.$multiple = null;
        },

        /**
         * Bind custom-related events
         */
        bindCustomEvents: function () {
            this.sandbox.on(EDIT.call(this), this.editMedia.bind(this));

            // emit finished event if overlay gets closed
            this.sandbox.on('husky.overlay.media-edit.closed', function () {
                this.sandbox.emit(CLOSED.call(this));
            }.bind(this));

            // change language (single-edit)
            this.sandbox.on('husky.overlay.media-edit.language-changed', this.languageChangedSingle.bind(this));

            // change language (multi-edit)
            this.sandbox.on('husky.overlay.media-multiple-edit.language-changed', this.languageChangedMultiple.bind(this));
        },

        /**
         * Shows an overlay to edit media
         * @param media {Object|Array} the media model or an array with media models
         */
        editMedia: function (media) {
            if (this.sandbox.dom.isArray(media)) {
                this.editMultipleMedia(media);
            } else {
                this.editSingleMedia(media);
            }
        },

        /**
         * Handles the changing of the language in the single-edit overlay
         * @param locale
         */
        languageChangedSingle: function (locale) {
            this.sandbox.emit('sulu.media.collections.reload-single-media',
                this.media.id, {locale: locale},
                function (media) {
                    this.media = media;
                    this.sandbox.form.setData(constants.infoFormSelector, this.media);
                }.bind(this)
            );
        },

        /**
         * Handles the changing of the language in the single-edit overlay
         * @param locale
         */
        languageChangedMultiple: function(locale) {
            this.sandbox.emit('sulu.media.collections.reload-media',
                this.medias, {locale: locale},
                function(medias) {
                    this.medias = medias;
                    var descriptionVisible = this.sandbox.dom.is(
                        constants.multipleEditFormSelector + ' ' + constants.multipleEditDescSelector, ':visible'
                    ),
                        tagsVisible = this.sandbox.dom.is(
                            constants.multipleEditFormSelector + ' ' + constants.multipleEditTagsSelector, ':visible'
                        );

                    this.sandbox.stop(constants.multipleEditFormSelector + ' *');
                    this.sandbox.form.setData(constants.multipleEditFormSelector, {
                        records: this.medias
                    }).then(function() {
                            this.sandbox.start(constants.multipleEditFormSelector);
                            if (descriptionVisible === true) {
                                this.sandbox.dom.show(
                                    constants.multipleEditFormSelector + ' ' + constants.multipleEditDescSelector
                                );
                            }
                            if (tagsVisible) {
                                this.sandbox.dom.show(
                                    constants.multipleEditFormSelector + ' ' + constants.multipleEditTagsSelector
                                );
                            }
                        }.bind(this));
                }.bind(this)
            );
        },

        /**
         * Edits a single media
         * @param media {Object} the id of the media to edit
         */
        editSingleMedia: function (media) {
            this.media = media;
            this.$info = this.sandbox.dom.createElement(this.renderTemplate('/admin/media/template/media/info', {
                media: this.media
            }));
            this.startSingleOverlay();
        },

        /**
         * Edits multiple media
         * @param medias {Array} array with the ids of the media to edit
         */
        editMultipleMedia: function (medias) {
            this.medias = medias;
            this.$multiple = this.sandbox.dom.createElement(this.renderTemplate('/admin/media/template/media/multiple-edit'));
            this.bindMultipleEditDomEvents();
            this.startMultipleEditOverlay();
        },

        /**
         * Starts the actual overlay for single-edit
         */
        startSingleOverlay: function () {
            var $container = this.sandbox.dom.createElement('<div class="'+ constants.singleEditClass +'"/>');
            this.sandbox.dom.append(this.$el, $container);
            this.bindSingleOverlayEvents();
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        title: this.media.title,
                        tabs: [
                            {title: this.sandbox.translate(this.options.infoKey), data: this.$info}
                        ],
                        languageChanger: {
                            locales: ['en', 'de'],
                            preSelected: this.media.locale
                        },
                        openOnStart: true,
                        instanceName: 'media-edit',
                        propagateEvents: false,
                        okCallback: function() {
                            this.changeSingleModel();
                        }.bind(this)
                    }
                }
            ]);
        },

        /**
         * Binds events related to the single-edit overlay
         */
        bindSingleOverlayEvents: function() {
            this.sandbox.once('husky.overlay.media-edit.opened', function () {
                this.sandbox.form.create(constants.infoFormSelector);
                this.sandbox.form.setData(constants.infoFormSelector, this.media).then(function() {
                    this.sandbox.start(constants.infoFormSelector);
                    this.startDropzone();
                }.bind(this));
            }.bind(this));

            this.sandbox.once('husky.dropzone.file-version-'+ this.media.id +'.initialized', function() {
                this.sandbox.emit('husky.overlay.media-edit.set-position');
            }.bind(this));

            this.sandbox.once('husky.auto-complete-list.media-info-'+ this.media.id +'.initialized', function() {
                this.sandbox.emit('husky.overlay.media-edit.set-position');
            }.bind(this));

            this.sandbox.on('husky.auto-complete-list.media-info-'+ this.media.id +'.item-added', function() {
                this.sandbox.emit('husky.overlay.media-edit.set-position');
            }.bind(this));
        },

        /**
         * Starts the actual overlay for multiple-edit
         */
        startMultipleEditOverlay: function () {
            var $container = this.sandbox.dom.createElement('<div class="'+ constants.multiEditClass +'"/>');
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
                            locales: ['en', 'de'],
                            preSelected: this.medias[0].locale
                        },
                        openOnStart: true,
                        draggable: false,
                        propagateEvents: false,
                        closeIcon: false,
                        instanceName: 'media-multiple-edit',
                        okCallback: this.changeMultipleModel.bind(this),
                        closeCallback: function() {
                            this.sandbox.stop(constants.multipleEditFormSelector + ' *');
                        }.bind(this)
                    }
                }
            ]);
        },

        /**
         * Binds events related to the single-edit overlay
         */
        bindMultipleOverlayEvents: function() {
            this.sandbox.once('husky.overlay.media-multiple-edit.opened', function () {
                this.sandbox.form.create(constants.multipleEditFormSelector).initialized.then(function () {
                    this.sandbox.form.setData(constants.multipleEditFormSelector, {
                        records: this.medias
                    }).then(function () {
                            this.sandbox.start(constants.multipleEditFormSelector);
                            this.sandbox.emit('husky.overlay.media-multiple-edit.set-position');
                        }.bind(this));
                }.bind(this));
            }.bind(this));
            this.sandbox.once('husky.overlay.media-multiple-edit.closed', function() {
                this.sandbox.stop('.' + constants.multiEditClass);
            }.bind(this));
        },

        /**
         * Binds Dom Events on the multiple-edit element
         */
        bindMultipleEditDomEvents: function () {
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
        },

        /**
         * Toggles the descriptions in the multiple-edit element
         */
        toggleDescriptions: function () {
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
         * Starts the dropzone for changeing the file-version
         */
        startDropzone: function () {
            // replace the current media with the new one if a fileversion got uploaded
            this.sandbox.off('husky.dropzone.file-version-' + this.media.id + '.files-added', this.filesAddedHandler);
            this.sandbox.on('husky.dropzone.file-version-' + this.media.id + '.files-added', this.filesAddedHandler, this);

            this.sandbox.start([
                {
                    name: 'dropzone@husky',
                    options: {
                        el: constants.dropzoneSelector,
                        url: '/admin/api/media/' + this.media.id,
                        method: 'POST',
                        paramName: 'fileVersion',
                        showOverlay: false,
                        skin: 'small',
                        titleKey: 'sulu.upload.small-dropzone-title',
                        instanceName: 'file-version-' + this.media.id,
                        maxFiles: 1
                    }
                }
            ]);
        },

        filesAddedHandler: function(newMedia) {
            this.media = newMedia[0];
            this.sandbox.emit('sulu.media.collections.save-media', this.media, null, true);
            this.savedCallback();
        },

        /**
         * Maps the overlay inputs back on a single model
         * @returns {Boolean} returns false if form is invalid, true if valid
         */
        changeSingleModel: function () {
            if (this.sandbox.form.validate(constants.infoFormSelector)) {
                var data = this.sandbox.form.getData(constants.infoFormSelector);
                this.media = this.sandbox.util.extend(false, {}, this.media, data);
                this.sandbox.emit('sulu.media.collections.save-media', this.media, this.savedCallback.bind(this));
                this.media = null;
                return true;
            } else {
                return false;
            }
        },

        /**
         * Maps the overlay input back on multiple models
         * @returns {Boolean} returns false if form is invalid, true if valid
         */
        changeMultipleModel: function () {
            if (this.sandbox.form.validate(constants.multipleEditFormSelector)) {
                var data = this.sandbox.form.getData(constants.multipleEditFormSelector);
                this.sandbox.util.foreach(this.medias, function(singleMedia, index) {
                    this.medias[index] = this.sandbox.util.extend(false, {}, singleMedia, data.records[index]);
                }.bind(this));
                this.sandbox.emit('sulu.media.collections.save-media', this.medias, this.savedCallback.bind(this));
                this.medias = null;
                return true;
            } else {
                return false;
            }
        },

        /**
         * Method to use for as a callback when one or more medias got saved
         */
        savedCallback: function () {
            this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-save-desc', 'labels.success');
        }
    };
});
