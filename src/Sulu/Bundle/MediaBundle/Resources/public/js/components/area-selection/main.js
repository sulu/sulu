/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Component for selecting areas (for example for cropping an image)
 *
 * @class AreaSelection
 * @constructor
 */
define(['underscore', 'jquery', 'text!./frame.html'], function(_, $, frameTemplate) {

    'use strict';

    var defaults = {
            eventNamespace: 'sulu.area-selection',
            instanceName: '',
            image: null,
            areaGuidingWidth: null,
            areaGuidingHeight: null,
            data: null,
            resizeable: true,
            draggable: true,
            tileSelectable: false,
            tileRow: 1,
            tileColumn: 1
        },

        translations = {
            minimumSizeReached: 'sulu-media.minimum-size-reached'
        },

        constants = {
            tileRows: 3,
            tileColumns: 3,
            selectedTileClass: 'selected',
            arrowUpClass: 'up',
            arrowUpRightClass: 'up-right',
            arrowRightClass: 'right',
            arrowDownRightClass: 'down-right',
            arrowDownClass: 'down',
            arrowDownLeftClass: 'down-left',
            arrowLeftClass: 'left',
            arrowUpLeftClass: 'up-left'
        },

        /**
         * Raised when the component has been successfully initialized.
         * @event sulu.area-selection.initialized
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        },

        /**
         * Raised when the area the component selects has changed.
         * @event sulu.area-selection.area-changed
         */
        AREA_CHANGED = function() {
            return createEventName.call(this, 'area-changed');
        },

        /**
         * Raised when a new tile of the grid is selected.
         *
         * @event sulu.area-selection.area-changed
         */
        TILE_SELECTED = function() {
            return createEventName.call(this, 'tile-selected');
        },

        /**
         * Listens on and sets the area-guide dimensions of the component
         *
         * @param {Number} areaGuidingWidth The new area-guide-width
         * @param {Number} areaGuidingHeight The new area-guide-height
         * @param {Object} data The new data to use
         *
         * @event sulu.area-selection.change-area-guide-dimensions
         */
        SET_AREA_GUIDE_DIMENSIONS = function() {
            return createEventName.call(this, 'set-area-guide-dimensions');
        },

        /**
         * returns normalized event names
         */
        createEventName = function(postFix) {
            return this.options.eventNamespace +
                '.' + (!!this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        };

    return {

        placeTileSelection: function () {
            if (!this.options.tileSelectable
                || !$.isNumeric(this.options.tileRow)
                || !$.isNumeric(this.options.tileColumn)
            ) {
                return;
            }

            var $tile = this.$el.find(
                [
                    '.lines *:nth-child(', this.options.tileRow + 1, ') ',
                    '.tile:nth-child(', this.options.tileColumn + 1, ')'
                ].join('')
            );
            this.selectTile($tile);
        },

        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.$frame = null;
            this.$backdrop = null;

            // The original width and height of the image.
            this.originalWidth = null;
            this.originalHeight = null;
            this.areaGuidingWidth = this.options.areaGuidingWidth;
            this.areaGuidingHeight = this.options.areaGuidingHeight;
            this.physicalAreaGuidingWidth = null;
            this.physicalAreaGuidingHeight = null;

            // The actual area element with it's physical coordinates.
            this.area = {
                $el: null,
                coordinates: {
                    x: null,
                    y: null,
                    width: null,
                    height: null
                }
            };

            // The result of the component which gets computed from the physical coordinates of the
            // area and the original dimensions of the image.
            this.$el.data('area', {
                x: null,
                y: null,
                width: null,
                height: null
            });

            if (!!this.options.draggable) {
                // Variables which are needed for dragging the area over the area
                this.dragging = {
                    enabled: false,
                    clickOffsetLeft: 0,
                    clickOffsetTop: 0
                };
            }

            if (!!this.options.resizeable) {
                // Variables which are needed for changing the width and height of the area
                this.resizing = {
                    enabled: false,
                    clickOffsetLeft: 0,
                    clickOffsetTop: 0
                };
            }

            // Holds the data set by the user
            this.data = this.options.data;

            this.renderFrame().then(function() {
                this.bindCustomEvents();
                this.placeSelection();
                this.bindDomEvents();
                this.bindDragEvents();
                this.bindResizeEvents();
                this.placeTileSelection();
                this.sandbox.emit(INITIALIZED.call(this), this.originalWidth, this.originalHeight);
            }.bind(this));
        },

        /**
         * Binds custom aura events to provide functionality to the outside.
         */
        bindCustomEvents: function() {
            this.sandbox.on(SET_AREA_GUIDE_DIMENSIONS.call(this), function(areaGuidingWidth, areaGuidingHeight, data) {
                this.areaGuidingWidth = areaGuidingWidth;
                this.areaGuidingHeight = areaGuidingHeight;
                this.data = data;
                this.placeSelection();
            }.bind(this));
        },

        /**
         * Places the selection within the frame. Gets called after the frame has been rendered and the image been loaded.
         */
        placeSelection: function() {
            this.physicalAreaGuidingWidth = this.areaGuidingWidth * this.$frame.width() / this.originalWidth;
            this.physicalAreaGuidingHeight = this.areaGuidingHeight * this.$frame.height() / this.originalHeight;

            if ((!!this.areaGuidingWidth && this.areaGuidingWidth > this.originalWidth) ||
                (!!this.areaGuidingHeight && this.areaGuidingHeight > this.originalHeight) ||
                (!this.areaGuidingWidth && !this.areaGuidingHeight)
            ) {
                this.area.$el.hide();
                this.$backdrop.hide();
                return;
            }

            this.computeInitialAreaCoordinates();
            this.area.$el.show();
            this.$backdrop.show();
        },

        /**
         * Unbinds event listener bound by the component
         */
        destroy: function() {
            $(document).off('.area-selection' + this.options.instanceName);
        },

        /**
         * Renderes the frame and initializes the image.
         *
         * @returns A promise which gets resolved when the image has been fully loaded
         */
        renderFrame: function() {
            this.$frame = $(_.template(frameTemplate, {
                image: this.options.image,
                minimumSizeInfo: this.sandbox.translate(translations.minimumSizeReached),
                resizeable: this.options.resizeable,
                tileSelectable: this.options.tileSelectable
            }));

            var whenImageLoaded = $.Deferred(),
                $image = this.$frame.find('.image');

            $image.one('load', function() {
                var rectangle;

                this.setImageSize($image);
                // Fix the width and the height due to a rendering bug in firefox.
                rectangle = $image[0].getBoundingClientRect();
                this.$frame.width(rectangle.width);
                this.$frame.height(rectangle.height);
                this.$frame.removeClass('invisible');
                whenImageLoaded.resolve();
            }.bind(this));
            $image.one('error', function() {
                whenImageLoaded.fail();
            });

            this.$el.addClass('sulu-area-selection');
            this.$frame.addClass('invisible');
            this.$el.append(this.$frame);
            this.area.$el = this.$frame.find('.area');
            this.area.$el.hide();
            this.$backdrop = this.$frame.find('.backdrop');
            this.$backdrop.hide();

            if (!!this.options.draggable) {
                this.area.$el.css('cursor', 'move');
            }

            if (!!this.options.tileSelectable) {
                this.$lines = this.$el.find('.lines');
                this.$tileArrows = this.$lines.find('.tile .arrow');

                this.$tileArrowMatrix = [
                    [this.$tileArrows[0], this.$tileArrows[3], this.$tileArrows[6]],
                    [this.$tileArrows[1], this.$tileArrows[4], this.$tileArrows[7]],
                    [this.$tileArrows[2], this.$tileArrows[5], this.$tileArrows[8]]
                ];
            }

            return whenImageLoaded;
        },

        /**
         * Makes the given tile the selected one.
         *
         * @param $tile
         */
        selectTile: function($tile) {
            this.$el.find('.lines .tile').removeClass(constants.selectedTileClass);
            $tile.addClass(constants.selectedTileClass);

            this.setTileArrows($tile);
        },

        setTileArrows: function($tile) {
            var x = $tile.index(),
                y = $tile.parent().index();

            this.$tileArrows.removeClass(constants.arrowUpClass);
            this.$tileArrows.removeClass(constants.arrowUpRightClass);
            this.$tileArrows.removeClass(constants.arrowRightClass);
            this.$tileArrows.removeClass(constants.arrowDownRightClass);
            this.$tileArrows.removeClass(constants.arrowDownClass);
            this.$tileArrows.removeClass(constants.arrowDownLeftClass);
            this.$tileArrows.removeClass(constants.arrowLeftClass);
            this.$tileArrows.removeClass(constants.arrowUpLeftClass);

            this.setTileArrow(x, y - 1, constants.arrowUpClass);
            this.setTileArrow(x + 1, y - 1, constants.arrowUpRightClass);
            this.setTileArrow(x + 1, y, constants.arrowRightClass);
            this.setTileArrow(x + 1, y + 1, constants.arrowDownRightClass);
            this.setTileArrow(x, y + 1, constants.arrowDownClass);
            this.setTileArrow(x - 1, y + 1, constants.arrowDownLeftClass);
            this.setTileArrow(x - 1, y, constants.arrowLeftClass);
            this.setTileArrow(x - 1, y - 1, constants.arrowUpLeftClass);
        },

        setTileArrow: function(x, y, className) {
            if (x < 0 || y < 0 || x >= constants.tileColumns || y >= constants.tileRows) {
                return;
            }

            $(this.$tileArrowMatrix[x][y]).addClass(className);
        },

        /**
         * Makes the image fit the container. The largest dimension gets fixed, so the
         * other dimension can automatically adapt.
         *
         * @param $image The img dom element
         */
        setImageSize: function($image) {
            this.originalHeight = $image[0].naturalHeight;
            this.originalWidth = $image[0].naturalWidth;

            if ($image.height() / $image.width() > this.$el.height() / this.$el.width()) {
                $image.height(Math.min($image.height(), this.$el.height()));
            } else {
                $image.width(Math.min($image.width(), this.$el.width()));
            }
        },

        /**
         * Binds general dom events
         */
        bindDomEvents: function() {
            // On double click on the area, reset it to the initial position
            this.area.$el.on('dblclick', function() {
                var coordinates = this.dataToCoordinates(this.getMaximumCenteredData());
                this.setAreaPosition(coordinates);
                this.setAreaSize(coordinates);
            }.bind(this));

            this.area.$el.on('click', '.tile', function(event) {
                var $tile = $(event.currentTarget);

                this.selectTile($tile);
                this.sandbox.emit(TILE_SELECTED.call(this), $tile.index(), $tile.parent().index());
            }.bind(this));

            // Prevent text-selection when moving the area
            this.area.$el.on('mousedown', function() {
                $(document).on('selectstart.area-selection' + this.options.instanceName, false);
            }.bind(this));

            $(document).on('mouseup.area-selection' + this.options.instanceName, function() {
                $(document).off('selectstart.area-selection' + this.options.instanceName);
            }.bind(this));
        },

        /**
         * Binds the events for moving the area within the image frame
         */
        bindDragEvents: function() {
            if (!this.options.draggable) {
                return;
            }

            this.area.$el.on('mousedown', ':not(.handle)', function(event) {
                this.dragging.enabled = true;
                this.dragging.clickOffsetLeft = event.pageX - this.area.$el.offset().left;
                this.dragging.clickOffsetTop = event.pageY - this.area.$el.offset().top;
            }.bind(this));

            $(document).on('mousemove.area-selection.' + this.options.instanceName, _.throttle(function(event) {
                if (!!this.dragging.enabled) {
                    this.setAreaPosition({
                        x: event.pageX - this.$frame.offset().left - this.dragging.clickOffsetLeft,
                        y: event.pageY - this.$frame.offset().top - this.dragging.clickOffsetTop
                    });
                }
            }.bind(this), 10));

            $(document).on('mouseup.area-selection.' + this.options.instanceName, function() {
                if (!!this.dragging.enabled) {
                    this.sandbox.emit(AREA_CHANGED.call(this));
                }
                this.dragging.enabled = false;
                this.dragging.clickOffsetLeft = 0;
                this.dragging.clickOffsetTop = 0;
            }.bind(this));
        },

        /**
         * Binds the events for resizing the area within the image frame
         */
        bindResizeEvents: function() {
            if (!this.options.resizeable) {
                return;
            }

            this.area.$el.on('mousedown', '.handle.south-east', function(event) {
                this.resizing.enabled = true;
                this.resizing.clickOffsetLeft = event.pageX - this.area.$el.offset().left - this.area.$el.width();
                this.resizing.clickOffsetTop = event.pageY - this.area.$el.offset().top - this.area.$el.height();
            }.bind(this));

            $(document).on('mousemove.area-selection.' + this.options.instanceName, _.throttle(function(event) {
                if (!!this.resizing.enabled) {
                    this.setAreaSize({
                        width: event.pageX - this.area.$el.offset().left - this.resizing.clickOffsetLeft,
                        height: event.pageY - this.area.$el.offset().top - this.resizing.clickOffsetTop
                    });
                }
            }.bind(this), 10));

            $(document).on('mouseup.area-selection.' + this.options.instanceName, function() {
                if (!!this.resizing.enabled) {
                    this.sandbox.emit(AREA_CHANGED.call(this));
                }
                this.resizing.enabled = false;
                this.resizing.clickOffsetLeft = 0;
                this.resizing.clickOffsetTop = 0;
            }.bind(this));
        },

        /**
         * Computes the initial coordinates. If data was passed to the component, the data
         * gets used to position the area. Otherwise the area gets placed into the center
         * of the image.
         */
        computeInitialAreaCoordinates: function() {
            var coordinates;

            if (!!this.data) {
                coordinates = this.dataToCoordinates(this.data);
            } else {
                coordinates = this.dataToCoordinates(this.getMaximumCenteredData());
            }

            this.area.coordinates = coordinates;
            this.setAreaPosition(coordinates);
            this.setAreaSize(coordinates);
        },

        /**
         * Sets the position of the area. Moreover the logical position coordinates are calculated from the passed size.
         * The passed size object gets validated (and probably changed through constraints) before applying the position.
         *
         * @param position {Object} an object with 'x' and 'y' property
         */
        setAreaPosition: function(position) {
            position = this.sandbox.util.extend(true, {}, this.area.coordinates, position);
            position = this.getConstrainedPosition(position);

            // Set the logical data (calculated from the coordinates)
            this.$el.data('area', _.extend(this.$el.data('area'), {
                x: Math.floor(position.x * this.originalWidth / this.$frame.width()),
                y: Math.floor(position.y * this.originalHeight / this.$frame.height())
            }));

            // Set the physical coordinates of the area
            this.area.$el.css('left', Math.round(position.x) + 'px');
            this.area.$el.css('top', Math.round(position.y) + 'px');
            this.area.coordinates.x = position.x;
            this.area.coordinates.y = position.y;

            // Give the backdrop the same coordinates as the area
            this.$backdrop.css('left', Math.round(position.x) + 'px');
            this.$backdrop.css('top', Math.round(position.y) + 'px');
        },

        /**
         * Sets the size of the area. Moreover the logical size coordinates are calculated from the passed size.
         * The passed size object gets validated (and probably changed) before applying the size.
         *
         * @param size {Object} an object with 'width' and 'height' property
         */
        setAreaSize: function(size) {
            size = this.sandbox.util.extend(true, {}, this.area.coordinates, size);
            size = this.getConstrainedSize(size);

            // Set the logical data (calculated from the size)
            this.$el.data('area', _.extend(this.$el.data('area'), {
                width: Math.floor(size.width * this.originalWidth / this.$frame.width()),
                height: Math.floor(size.height * this.originalHeight / this.$frame.height())
            }));

            if (
                (
                    (!!this.areaGuidingWidth && this.$el.data('area').width <= this.areaGuidingWidth)
                    || (!!this.areaGuidingHeight && this.$el.data('area').height <= this.areaGuidingHeight)
                )
                && !!this.options.resizeable
            ) {
                this.area.$el.addClass('minimum-size-reached');
            } else {
                this.area.$el.removeClass('minimum-size-reached');
            }

            // Set the physical size of the area
            this.area.$el.width(Math.round(size.width));
            this.area.$el.height(Math.round(size.height));
            this.area.coordinates.width = size.width;
            this.area.coordinates.height = size.height;

            // Give the backdrop the same coordinates as the area
            this.$backdrop.width(Math.round(size.width));
            this.$backdrop.height(Math.round(size.height));
        },

        /**
         * Constraints the passed position-object. For the returned size it is ensured
         * that if applied to the area, it won't exceed the frame borders.
         *
         * @param position {Object} The position object with 'x' and 'y' coordinates
         *
         * @returns {Object} The constraint version of the passed position
         */
        getConstrainedPosition: function(position) {
            position.x = Math.max(0, position.x);
            position.x = Math.min(this.$frame.width() - this.area.coordinates.width, position.x);
            position.y = Math.max(0, position.y);
            position.y = Math.min(this.$frame.height() - this.area.coordinates.height, position.y);

            return position;
        },

        /**
         * Constraints the a passed size-object. For the returned size it is ensured that
         * if applied to the area, it won't exceed the frame borders. Moreover if the
         * component was started with area-guide-coordinates it is ensured that the 'width' and
         * the 'height' are in the same ratio.
         *
         * @param size {Object} The size object with 'width' and 'height' coordinates
         *
         * @returns {Object} The constraint version of the passed size
         */
        getConstrainedSize: function(size) {
            size.width = Math.min(this.$frame.width() - this.area.coordinates.x, size.width);
            size.height = Math.min(this.$frame.height() - this.area.coordinates.y, size.height);

            // if both area-guide-width and area-guide-height are given, the ratio of width and height has to be enforced
            if (!!this.areaGuidingWidth && !!this.areaGuidingHeight) {
                size.height = size.width * this.areaGuidingHeight / this.areaGuidingWidth;
                if (size.height > this.$frame.height() - this.area.coordinates.y) {
                    size.height = this.$frame.height() - this.area.coordinates.y;
                    size.width = size.height * this.areaGuidingWidth / this.areaGuidingHeight;
                }
            }

            if (!!this.areaGuidingWidth) {
                size.width = Math.max(this.physicalAreaGuidingWidth, size.width);
            }
            if (!!this.areaGuidingHeight) {
                size.height = Math.max(this.physicalAreaGuidingHeight, size.height);
            }
            size.width = Math.max(1, size.width);
            size.height = Math.max(1, size.height);

            return size;
        },

        /**
         * Calculates and returns the data which maximize the width and height of the cropping-area.
         * Moreover the x and y values are set such that the area is positioned in the center.
         *
         * @returns {Object}
         */
        getMaximumCenteredData: function() {
            var data = {};

            if (!this.areaGuidingWidth || !this.areaGuidingHeight) {
                data.width = this.originalWidth;
                data.height = this.originalHeight;
                data.x = 0;
                data.y = 0;
            } else if (this.areaGuidingWidth / this.areaGuidingHeight > this.originalWidth / this.originalHeight) {
                data.width = this.originalWidth;
                data.height = data.width * this.areaGuidingHeight / this.areaGuidingWidth;
                data.x = 0;
                data.y = (this.originalHeight / 2) - (data.height / 2);
            } else {
                data.height = this.originalHeight;
                data.width = data.height * this.areaGuidingWidth / this.areaGuidingHeight;
                data.y = 0;
                data.x = (this.originalWidth / 2) - (data.width / 2)
            }

            return data;
        },

        /**
         * Converts a completely given object of logical data into its physical coordinates
         *
         * @param {Object} data The logical data
         * @returns {Object} the physical data
         */
        dataToCoordinates: function(data) {
            return {
                x: data.x * this.$frame.width() / this.originalWidth,
                y: data.y * this.$frame.height() / this.originalHeight,
                width: data.width * this.$frame.width() / this.originalWidth,
                height: data.height * this.$frame.height() / this.originalHeight
            };
        }
    };
});
