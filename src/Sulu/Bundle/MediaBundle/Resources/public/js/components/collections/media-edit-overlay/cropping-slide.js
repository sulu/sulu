/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @class CroppingSlide
 * Class which handles the cropping-slide in the media-edit overlay.
 * Note that this is not an aura-component but a simple require-js dependency.
 * @constructor
 */
define([
    'services/sulumedia/user-settings-manager',
    'services/sulumedia/format-manager',
    'text!./cropping.html'
], function(UserSettingsManager, FormatManager, elementTemplate) {

    'use strict';

    /**
     * Handles the selection of a dropdown item in the formats-dropdown
     *
     * @param {Object} dropdownItem The clicked dropdown item
     */
    var formatsDropdownHandler = function(dropdownItem) {
        this.selectedFormat = this.formats[dropdownItem.id];

        // Reset the buttons in the toolbar
        this.sandbox.emit('husky.toolbar.cropping-' + this.media.id + '.item.disable', 'save', false);
        if (!!this.selectedFormat.options) {
            this.sandbox.emit('husky.toolbar.cropping-' + this.media.id + '.item.enable', 'remove', false);
        } else {
            this.sandbox.emit('husky.toolbar.cropping-' + this.media.id + '.item.disable', 'remove', false);
        }

        // If a crop is not possible in a format inform the user through a label
        if (!FormatManager.cropPossibleInInFormat(
            this.selectedFormat.scale.x,
            this.selectedFormat.scale.y,
            this.imageWidth,
            this.imageHeight)
        ) {
            showTooSmallImageLabel.call(this);
        } else {
            this.sandbox.emit('husky.label.too-small-image-' + this.media.id + '.vanish');
        }

        // Update the area selection to the new format
        this.sandbox.emit(
            'sulu.area-selection.cropping-' + this.media.id + '.set-area-guide-dimensions',
            this.selectedFormat.scale.x,
            this.selectedFormat.scale.y,
            getSelectionDataFromFormat.call(this, this.selectedFormat)
        );
    },

    /**
     * Handles the click on the toolbar save-button
     */
    saveClickHandler = function() {
        this.sandbox.emit('husky.toolbar.cropping-' + this.media.id + '.item.loading', 'save');
        var format = {
            key: this.selectedFormat.key,
            options: {
                cropX: this.$el.find('.area-selection').data('area').x,
                cropY: this.$el.find('.area-selection').data('area').y,
                cropWidth: this.$el.find('.area-selection').data('area').width,
                cropHeight: this.$el.find('.area-selection').data('area').height
            }
        };
        FormatManager.saveFormat(this.media.id, this.sandbox.sulu.user.locale, format)
            .then(postFormatChangeAction.bind(this))
            .fail(function() {
                this.sandbox.emit('husky.toolbar.cropping-' + this.media.id + '.item.disable', 'save', false);
            }.bind(this));
    },

    /**
     * Handles the click on the toolbar remove-button
     */
    removeClickHandler = function() {
        this.sandbox.emit('husky.toolbar.cropping-' + this.media.id + '.item.loading', 'remove');
        var format = {
            key: this.selectedFormat.key,
            options: {}
        };
        FormatManager.saveFormat(this.media.id, this.sandbox.sulu.user.locale, format)
            .then(postFormatChangeAction.bind(this))
            .fail(function() {
                this.sandbox.emit('husky.toolbar.cropping-' + this.media.id + '.item.disable', 'remove', false);
            }.bind(this));
    },

    /**
     * Gets called after a format has been updated on the server
     *
     * @param {Object} format The format data
     */
    postFormatChangeAction = function(format) {
        var formatHasOptions = !!format.options,
            newImageSrc = this.media.thumbnails[format.key] + '&t=' + (new Date().getTime());

        // Update the local data
        this.formats[format.key] = format;
        this.selectedFormat = format;

        // Renew the source of all images displaying the changed image
        $('img[src="' + this.media.thumbnails[format.key] + '"]').each(function(key, img) {
            img.src = newImageSrc;
        });

        this.media.thumbnails[format.key] = newImageSrc;
        this.sandbox.emit('sulu.medias.media.saved', this.media.id, this.media);
        this.sandbox.emit('husky.label.invalid-crop-' + this.media.id + '.vanish');
        this.sandbox.emit('husky.label.invalid-crops-' + this.media.id + '.vanish');

        // Update the toolbar buttons and dropdowns
        if (!formatHasOptions) {
            this.sandbox.emit('husky.toolbar.cropping-' + this.media.id + '.item.disable', 'remove', true);
            this.sandbox.emit('husky.toolbar.cropping-' + this.media.id + '.item.disable', 'save', false);
            this.sandbox.emit('sulu.labels.success.show', 'sulu-media.crop-remove-success');
        } else {
            this.sandbox.emit('husky.toolbar.cropping-' + this.media.id + '.item.disable', 'save', true);
            this.sandbox.emit('husky.toolbar.cropping-' + this.media.id + '.item.enable', 'remove', false);
            this.sandbox.emit('sulu.labels.success.show', 'sulu-media.crop-save-success');
        }
        this.sandbox.emit('husky.toolbar.cropping-' + this.media.id + '.items.set', 'formats', getFormatDropdownItems.call(this));

        // Update the area selection to show the formats returned by the server
        this.sandbox.emit(
            'sulu.area-selection.cropping-' + this.media.id + '.set-area-guide-dimensions',
            this.selectedFormat.scale.x,
            this.selectedFormat.scale.y,
            getSelectionDataFromFormat.call(this, format)
        );
    },

    /**
     * Starts the area-selection component with the image of the passed media.
     */
    startAreaSelection = function() {
        var whenAreaSelectionLoaded = $.Deferred();

        this.sandbox.start([{
            name: 'area-selection@sulumedia',
            options: {
                el: this.$el.find('.area-selection'),
                instanceName: 'cropping-' + this.media.id,
                image: this.media.url
            }
        }]);

        this.sandbox.once(
            'sulu.area-selection.cropping-' + this.media.id + '.initialized',
            afterAreaSelectionInitializedHandler.bind(this, whenAreaSelectionLoaded)
        );

        return whenAreaSelectionLoaded;
    },

    /**
     * Gets called after the area selection component has been initialized. The function selects
     * the initial format to display and resolves a given promise when everything has been finished.
     *
     * @param {Object} promise
     * @param {Number} imageWidth
     * @param {Number} imageHeight
     */
    afterAreaSelectionInitializedHandler = function(promise, imageWidth, imageHeight) {
        this.imageWidth = imageWidth;
        this.imageHeight = imageHeight;

        // Now that the image height and width are set, set the guide-dimensions on the cropping-area
        this.selectedFormat = getFirstCroppableFormat.call(this);
        if (!!this.selectedFormat) {
            this.sandbox.emit(
                'sulu.area-selection.cropping-' + this.media.id + '.set-area-guide-dimensions',
                this.selectedFormat.scale.x,
                this.selectedFormat.scale.y,
                getSelectionDataFromFormat.call(this, this.selectedFormat)
            );
        } else {
            showTooSmallImageLabel.call(this);
        }

        promise.resolve();
    },

    /**
     * Displays a label, which notifies the user that
     * the image is too small to define a crop in any format.
     */
    showTooSmallImageLabel = function() {
        var $element = $('<div class="too-small"/>');
        this.sandbox.stop(this.$el.find('.label-container .too-small'));
        this.$el.find('.label-container').append($element);

        this.sandbox.start([{
            name: 'label@husky',
            options: {
                el: $element,
                type: 'WARNING',
                instanceName: 'too-small-image-' + this.media.id,
                title: 'sulu-media.crop-not-possible',
                autoVanish: false,
                description: 'sulu-media.image-too-small',
                additionalLabelClasses: 'small',
                hasClose: false
            }
        }]);
    },

    /**
     * Displays a label, which notifies the user that there is crop defined
     * for the current format. However the crop area is invalid. This can happen
     * if a new image was uploaded with different dimensions or the image format was changed.
     */
    showInvalidCropLabel = function() {
        var $element = $('<div class="invalid-crop"/>');
        this.sandbox.stop(this.$el.find('.label-container .invalid-crop'));
        this.$el.find('.label-container').append($element);

        this.sandbox.start([{
            name: 'label@husky',
            options: {
                el: $element,
                type: 'WARNING',
                instanceName: 'invalid-crop-' + this.media.id,
                title: 'sulu-media.crop-out-of-date',
                autoVanish: false,
                description: 'sulu-media.crop-out-of-date-text',
                additionalLabelClasses: 'small'
            }
        }]);
    },

    /**
     * Informs the user through a label that one ore more crops defined on the media
     * are invalid.
     *
     * @param {Array} formats An array with the invalid formats
     */
    showInvalidCropsLabel = function(formats) {
        if (formats.length === 0) {
            return;
        }

        var formatsString = formats.map(function(format) {
            return format.title;
        }).join(', ');

        var $element = $('<div class="invalid-crops"/>');
        this.$overlay.find('.info-label-container').append($element);

        this.sandbox.start([{
            name: 'label@husky',
            options: {
                el: $element,
                type: 'WARNING',
                instanceName: 'invalid-crops-' + this.media.id,
                title: 'sulu-media.crops-out-of-date',
                autoVanish: false,
                description: this.sandbox.translate('sulu-media.following-crops-out-of-date') + ': ' + formatsString,
                additionalLabelClasses: 'small'
            }
        }]);
    },

    /**
     * For a given format, constructs the data as expected of the area-selection
     * out of the options of the format.
     *
     * @param {Object} format The format
     *
     * @returns {Object} The data as expected by the area-selection
     */
    getSelectionDataFromFormat = function(format) {
        if (!format) {
            return null;
        }

        if (!!format.options && !FormatManager.cropOptionsAreValid(
                format.options,
                format.scale.x,
                format.scale.y,
                this.imageWidth,
                this.imageHeight
            )) {
            showInvalidCropLabel.call(this);
            return null;
        } else {
            this.sandbox.emit('husky.label.invalid-crop-' + this.media.id + '.vanish');
        }

        if (!!format.options) {
            return {
                width: format.options.cropWidth,
                height: format.options.cropHeight,
                x: format.options.cropX,
                y: format.options.cropY
            }
        }
    },

    /**
     * Out of the formats of this slide, constructs an array of
     * dropdown items, which can be used by the toolbar.
     *
     * @returns {Array} The array of toolbar dropdown items
     */
    getFormatDropdownItems = function() {
        var items = [], styleClass;

        $.each(this.formats, function(key, format) {
            styleClass = null;
            if (!!format.options && !FormatManager.cropOptionsAreValid(
                    format.options,
                    format.scale.x,
                    format.scale.y,
                    this.imageWidth,
                    this.imageHeight
                )) {
                styleClass = 'warning';
            } else if (!!format.options) {
                styleClass = 'checked';
            }

            items.push({
                id: key,
                title: format.title,
                styleClass: styleClass
            });
        }.bind(this));

        return items;
    },

    /**
     * Starts the toolbar component with a save button, a remove button
     * and a dropdown from which all the formats can be selected.
     */
    startToolbar = function() {
        this.sandbox.start([{
            name: 'toolbar@husky',
            options: {
                el: this.$el.find('.toolbar'),
                instanceName: 'cropping-' + this.media.id,
                skin: 'big',
                buttons: [
                    {
                        id: 'save',
                        icon: 'floppy-o',
                        title: 'public.save-and-apply',
                        disabled: true,
                        callback: saveClickHandler.bind(this)
                    },
                    {
                        id: 'remove',
                        icon: 'trash-o',
                        title: 'sulu-media.remove-crop',
                        disabled: !this.selectedFormat || !this.selectedFormat.options,
                        callback: removeClickHandler.bind(this)
                    },
                    {
                        id: 'formats',
                        icon: 'picture-o',
                        title: (!!this.selectedFormat) ? this.selectedFormat.title : 'sulu-media.image-formats',
                        dropdownItems: getFormatDropdownItems.call(this),
                        dropdownOptions: {
                            maxHeight: 385,
                            changeButton: true,
                            callback: formatsDropdownHandler.bind(this)
                        }
                    }
                ]
            }
        }]);
    },

    /**
     * @returns the first format in which the image can be cropped. Returns null if no such format exists.
     */
    getFirstCroppableFormat = function() {
        var croppableFormat = null;

        $.each(this.formats, function(key, format) {
            if (!croppableFormat && FormatManager.cropPossibleInInFormat(
                    format.scale.x,
                    format.scale.y,
                    this.imageWidth,
                    this.imageHeight
                )
            ) {
                croppableFormat = format;
            }
        }.bind(this));

        return croppableFormat;
    },

    /**
     * Out of the formats hold by the tab, returns those formats which contain invalid crop-options.
     *
     * @returns {Array} An array of formats with invalid crop options
     */
    getFormatsWithInvalidCrops = function() {
        var formats = [];

        $.each(this.formats, function(key, format) {
            if (!!format.options && !FormatManager.cropOptionsAreValid(
                    format.options,
                    format.scale.x,
                    format.scale.y,
                    this.imageWidth,
                    this.imageHeight
                )) {
                formats.push(format);
            }
        }.bind(this));

        return formats;
    },

    /**
     * Binds general dom events for the tab
     */
    bindDomEvents = function() {
        this.$el.find('.back').on('click', this.onBack);
    },

    /**
     * Binds custom aura events.
     */
    bindCustomEvents = function() {
        this.sandbox.on('sulu.area-selection.cropping-' + this.media.id + '.area-changed', function() {
            this.sandbox.emit('husky.toolbar.cropping-' + this.media.id + '.item.enable', 'save', false);
        }.bind(this));
    };

    return {

        sandbox: null,
        media: null,
        formats: null,
        selectedFormat: null,
        $overlay: null,
        $el: null,
        imageWidth: null,
        imageHeight: null,
        onBack: null,

        /**
         * Initializes the cropping slide by taking the needed dependencies
         *
         * @param {Object} $overlay The dom element of the main overlay
         * @param {Object} sandbox The sandbox of the overlay
         * @param {Object} media The media which should get cropped
         * @param {Function} onBack The function to execute when clicked on back
         */
        initialize: function($overlay, sandbox, media, onBack) {
            this.$overlay = $overlay;
            this.sandbox = sandbox;
            this.media = media;
            this.onBack = onBack;
            this.$el = $(_.template(elementTemplate, {
                hint: this.sandbox.translate('sulu-media.crop-double-click-hint')
            }));
        },

        /**
         * @returns {Object} The definition of the slide used by the overlay
         */
        getSlideDefinition: function() {
            return {
                displayHeader: false,
                data: this.$el,
                buttons: [],
                cancelCallback: function() {
                    this.sandbox.stop();
                }.bind(this)
            }
        },

        /**
         * Starts the functionality after the slide has been rendered by the overlay
         */
        start: function() {
            var whenStarted = $.Deferred();

            bindCustomEvents.call(this);
            bindDomEvents.call(this);
            FormatManager.loadFormats(this.media.id, this.sandbox.sulu.user.locale).then(function(formats) {
                this.formats = formats;
                startAreaSelection.call(this).then(function() {
                    showInvalidCropsLabel.call(this, getFormatsWithInvalidCrops.call(this));
                    startToolbar.call(this)
                }.bind(this));
                whenStarted.resolve();
            }.bind(this));

            return whenStarted;
        },

        /**
         * Cleans the slide
         */
        destroy: function() {
            if (!!this.$el) {
                this.sandbox.stop(this.$el.find('*'));
            }
        }
    };
});
