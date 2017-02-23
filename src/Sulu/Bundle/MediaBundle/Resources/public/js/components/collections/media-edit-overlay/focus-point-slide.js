/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @class FocusPointSlide
 * Class which handles the focuse-point-slide in the media-edit overlay.
 * Note that this is not an aura-component but a simple require-js dependency.
 * @constructor
 */

define(['services/sulumedia/media-manager', 'text!./toolbar-slide.html'], function(mediaManager, toolbarSlideTemplate) {
    'use strict';

    /**
     * Binds general dom events for the tab.
     */
    var bindDomEvents = function() {
            this.$el.find('.back').on('click', this.onBack);
        },

        /**
         * Binds custom event for the tab.
         */
        bindCustomEvents = function() {
            this.sandbox.on('sulu.area-selection.' + this.instanceName + '.tile-selected', handleTileSelected.bind(this));
        },

        /**
         * Handles the tile selection of the grid. Should save the position and make sure the save button is enabled.
         */
        handleTileSelected = function (focusPointX, focusPointY) {
            this.focusPointX = focusPointX;
            this.focusPointY = focusPointY;

            this.sandbox.emit('husky.toolbar.' + this.instanceName + '.item.enable', 'save', false);
        },

        /**
         * Starts the area selection for the image.
         */
        startAreaSelection = function() {
            this.sandbox.start([{
                name: 'area-selection@sulumedia',
                options: {
                    el: this.$el.find('.area-selection'),
                    instanceName: this.instanceName,
                    image: this.media.url,
                    resizeable: false,
                    draggable: false,
                    tileSelectable: true,
                    tileColumn: this.media.focusPointX,
                    tileRow: this.media.focusPointY
                }
            }]);

            this.sandbox.once(
                'sulu.area-selection.focus-point-' + this.media.id + '.initialized',
                function(imageWidth, imageHeight) {
                    this.sandbox.emit(
                        'sulu.area-selection.focus-point-' + this.media.id + '.set-area-guide-dimensions',
                        imageWidth,
                        imageHeight,
                        {
                            width: imageWidth,
                            height: imageHeight,
                            x: 0,
                            y: 0
                        }
                    );
                }.bind(this)
            );
        },

        /**
         * Starts the toolbar component with a save button.
         */
        startToolbar = function() {
            this.sandbox.start([{
                name: 'toolbar@husky',
                options: {
                    el: this.$el.find('.toolbar'),
                    instanceName: this.instanceName,
                    skin: 'big',
                    buttons: [
                        {
                            id: 'save',
                            icon: 'floppy-o',
                            title: 'sulu-media.save-and-recrop',
                            disabled: true,
                            callback: saveClickHandler.bind(this)
                        }
                    ]
                }
            }]);
        },

        saveClickHandler = function() {
            this.sandbox.emit('husky.toolbar.' + this.instanceName + '.item.loading', 'save');
            this.media.focusPointX = this.focusPointX;
            this.media.focusPointY = this.focusPointY;

            mediaManager.save(this.media).then(function() {
                this.sandbox.emit('husky.toolbar.' + this.instanceName + '.item.disable', 'save');
            }.bind(this));

            this.sandbox.emit(
                'sulu.media-edit.preview.loading',
                this.sandbox.translate('sulu-media.saved-crops-not-visible')
            );

            this.sandbox.emit('sulu.media-edit.formats.update');
        };

    return {
        /**
         * Initializes the focus point slide by taking the required dependencies.
         *
         * @param sandbox
         * @param media
         * @param onBack
         */
        initialize: function(sandbox, media, onBack) {
            this.sandbox = sandbox;
            this.media = media;
            this.onBack = onBack;
            this.$el = $(_.template(toolbarSlideTemplate, {}));
            this.focusPointX = this.media.focusPointX;
            this.focusPointY = this.media.focusPointY;

            this.instanceName = 'focus-point-' + this.media.id;
        },

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

        start: function() {
            bindDomEvents.call(this);
            bindCustomEvents.call(this);
            startAreaSelection.call(this);
            startToolbar.call(this);
        }
    }
});
