/**
 * @class MasonryView (Datagrid Decorator)
 * @constructor
 *
 * @param {Object} [viewOptions] Configuration object
 * @param {Boolean} [unselectOnBackgroundClick] should items get deselected on document click
 * @param {Boolean} [selectable] should items be selectable
 * @param {String} [imageFormat] api-format which is used for head-image
 * @param {String} [emptyListTranslations] translation key for the empty-list indicator
 * @param {Object} [fields] Defines which data-columns are used to render cards
 * @param {String} [fields.image]
 * @param {Array} [fields.title]
 * @param {Array} [fields.description]
 * @param {Object} [separators] Defines separators between data-columns content
 * @param {String} [separators.description]
 *
 * @param {Boolean} [rendered] property used by the datagrid-main class
 * @param {Function} [initialize] function which gets called once at the start of the view
 * @param {Function} [render] function to render data
 * @param {Function} [addRecord] function to add a new record to the grid
 * @param {Function} [removeRecord] function to remove an existing record from the grid
 * @param {Function} [destroy] function to destroy the view and unbind events
 */
define([
    'jquery',
    'underscore',
    'services/sulumedia/overlay-manager',
    'services/sulumedia/user-settings-manager',
    'text!sulumedia/components/collections/masonry-decorator/item.html',
    'text!sulumedia/components/collections/masonry-decorator/empty-indicator.html'
], function($, _, OverlayManager, UserSettingsManager, itemTemplate, emptyTemplate) {

    'use strict';

    var defaults = {
            unselectOnBackgroundClick: true,
            selectable: true,
            selectOnAction: false,
            imageFormat: 'sulu-260x',
            emptyListTranslation: 'public.empty-list',
            fields: {
                image: 'thumbnails',
                title: ['title'],
                description: ['mimeType', 'size']
            },
            separators: {
                title: ' ',
                description: ', '
            },
            emptyIcon: 'fa-coffee',
            actionIcons: ['fa-pencil'],
            noImgIcon: function() {
                return 'fa-file-o';
            }
        },

        constants = {
            masonryGridId: 'masonry-grid',
            emptyIndicatorClass: 'empty-list',

            selectedClass: 'selected',
            loadingClass: 'loading',

            iconClass: 'image-icon',
            imageClass: 'image',
            noImageClass: 'no-image',
            actionNavigatorClass: 'action-navigator',
            downloadNavigatorClass: 'download-navigator',
            playVideoNavigatorClass: 'play-video-navigator'
        },

        /**
         * Concat the entries of the given columns of the given record to a string.
         * Record-columns are separated by the given separator. Empty record-columns are ignored.
         * @param record
         * @param columns
         * @param separator
         * @returns {string}
         */
        concatRecordColumns = function(record, columns, separator) {
            if (!!record && !!columns) {
                var strings = [];
                columns.forEach(function(field) {
                    if (!!record[field]) {
                        strings.push(record[field]);
                    }
                });
                return strings.join(separator);
            }
        },

        /**
         * Apply datagrid-content-filters on the given record column by column
         * datagrid-content-filters are used to format the raw database-values (for example size)
         * @param record
         * @returns {*}
         */
        processContentFilters = function(record) {
            var item = this.sandbox.util.extend(false, {}, record);
            this.datagrid.matchings.forEach(function(matching) {
                var argument = (matching.type === this.datagrid.types.THUMBNAILS) ? this.options.imageFormat : '';
                item[matching.attribute] = this.datagrid.processContentFilter.call(
                    this.datagrid,
                    matching.attribute,
                    item[matching.attribute],
                    matching.type,
                    argument
                );
            }.bind(this));

            return item;
        },

        REFRESH = function() {
            return this.createEventName('masonry.refresh');
        };

    return function() {
        return {

            /**
             * Initializes the view, gets called only once
             * @param {Object} context The context of the datagrid class
             * @param {Object} options The options used by the view
             */
            initialize: function(context, options) {
                this.masonryGridId = constants.masonryGridId + (new Date()).getTime();

                // context of the datagrid-component
                this.datagrid = context;

                // make sandbox available in this-context
                this.sandbox = this.datagrid.sandbox;

                // merge defaults with options
                this.options = this.sandbox.util.extend(true, {}, defaults, options);

                this.setVariables();

                this.sandbox.on(REFRESH.call(this.datagrid), function() {
                    this.sandbox.masonry.refresh('#' + this.masonryGridId, true);
                }.bind(this));
            },

            /**
             * Sets the starting variables for the view
             */
            setVariables: function() {
                this.rendered = false;
                this.$el = null;

                // global array to store the dom elements
                this.$items = {};
            },

            /**
             * Method to render this view
             * @param data object containing the data which is rendered
             * @param $container dom-element to render datagrid in
             */
            render: function(data, $container) {
                this.renderMasonryContainer($container);
                this.initializeMasonryGrid();
                this.bindGeneralDomEvents();

                this.renderRecords(data.embedded, true);
                this.rendered = true;
            },

            /**
             * Render the masonry-container into the given dom element
             * The masonry-container contains the empty-list-inidicator and the masonry-grid
             * @param $container
             */
            renderMasonryContainer: function($container) {
                this.$el = this.sandbox.dom.createElement('<div class="masonry-container"/>');

                // render empty indicator
                var $empty = this.sandbox.util.template(emptyTemplate, {
                    text: this.sandbox.translate(this.options.emptyListTranslation),
                    icon: this.options.emptyIcon
                });
                this.sandbox.dom.append(this.$el, $empty);

                // render masonry-grid
                var $grid = this.sandbox.dom.createElement('<div id="' + this.masonryGridId + '" class="masonry-grid"/>');
                this.sandbox.dom.append(this.$el, $grid);

                this.sandbox.dom.append($container, this.$el);
            },

            /**
             * Show the empty-list-indicator if datagrid contains no data, else hide it
             */
            updateEmptyIndicatorVisibility: function() {
                if (!!this.datagrid.data && !!this.datagrid.data.embedded && this.datagrid.data.embedded.length > 0) {
                    this.$el.find('.' + constants.emptyIndicatorClass).hide();
                } else {
                    this.$el.find('.' + constants.emptyIndicatorClass).show();
                }
            },

            /**
             * Start the masonry-plugin on the masonry-grid element
             */
            initializeMasonryGrid: function() {
                this.sandbox.masonry.initialize('#' + this.masonryGridId, {
                    align: 'left',
                    direction: 'left',
                    itemWidth: 260,
                    offset: 20,
                    verticalOffset: 20,
                    possibleFilters: [constants.selectedClass]
                });
            },

            /**
             * Bind dom related events for datagrid-view
             */
            bindGeneralDomEvents: function() {
                if (this.options.unselectOnBackgroundClick) {
                    this.sandbox.dom.on('body', 'click.masonry', function() {
                        this.deselectAllRecords();
                    }.bind(this));
                }
            },

            /**
             * Parses the data and passes it item by item to a render function
             * @param records {Array} array with records to render
             * @param appendAtBottom
             */
            renderRecords: function(records, appendAtBottom) {
                this.updateEmptyIndicatorVisibility();
                var itemLoadedDeferreds = [];

                records.forEach(function(record) {
                    var item = processContentFilters.call(this, record);
                    var image = item[this.options.fields.image].url || '',
                        title = concatRecordColumns(item, this.options.fields.title, this.options.separators.title),
                        description = concatRecordColumns(
                            item,
                            this.options.fields.description,
                            this.options.separators.description
                        ),
                        isVideo = (item.type === 'video'),
                        itemLoadedDeferred;

                    // pass the found data to a render method
                    itemLoadedDeferred = this.renderItem(
                        item.id,
                        image,
                        title,
                        item.locale !== this.options.locale ? item.locale : null,
                        description,
                        isVideo,
                        appendAtBottom,
                        this.options.noImgIcon(item)
                    );

                    itemLoadedDeferreds.push(itemLoadedDeferred);
                }.bind(this));

                // When all items have been completly loaded
                $.when.apply($, itemLoadedDeferreds).then(function() {
                    // Safari removed the class before masonry got to position the elements
                    // which led to undesired rendering glitches. Positioning the task
                    // at the end of the execution stack fixes the issue.
                    _.delay(function() {
                        this.$el.find('.masonry-item').removeClass(constants.loadingClass);
                    }.bind(this), 0);
                }.bind(this));
            },

            /**
             * Renders an masonry-grid item with the given properties
             * @param id
             * @param image
             * @param title
             * @param fallbackLocale
             * @param description
             * @param isVideo
             * @param appendAtBottom
             * @param icon
             *
             * @return {Object} a promise which gets resolved, when the item has been loaded
             */
            renderItem: function(id, image, title, fallbackLocale, description, isVideo, appendAtBottom, icon) {
                var titleWidth = this.getTitleTextWidth(!!fallbackLocale),
                    itemLoadedDeferred = $.Deferred();

                this.$items[id] = this.sandbox.dom.createElement(
                    this.sandbox.util.template(itemTemplate, {
                        image: image,
                        title: this.sandbox.util.cropMiddle(String(title), titleWidth),
                        fallbackLocale: fallbackLocale,
                        description: this.sandbox.util.cropMiddle(String(description), 35),
                        isVideo: isVideo,
                        domain: window.location.protocol + '//' + window.location.host,
                        selectable: this.options.selectable,
                        placeholderIcon: icon
                    })
                );
                this.$items[id].addClass(constants.loadingClass);

                if (this.datagrid.itemIsSelected.call(this.datagrid, id)) {
                    this.selectRecord(id);
                }

                if (!!appendAtBottom) {
                    this.sandbox.dom.append(this.sandbox.dom.find('#' + this.masonryGridId, this.$el), this.$items[id]);
                } else {
                    this.sandbox.dom.prepend(this.sandbox.dom.find('#' + this.masonryGridId, this.$el), this.$items[id]);
                }

                if (!!image) {
                    this.bindItemLoadingEvents(id, itemLoadedDeferred);
                } else {
                    this.itemLoadedHandler(this.$items[id], itemLoadedDeferred);
                }
                this.renderActionIcons(id);

                this.bindItemEvents(id);

                return itemLoadedDeferred;
            },

            /**
             * Renders the action icon into an item with a given id.
             *
             * @param {Number} id
             */
            renderActionIcons: function(id) {
                this.options.actionIcons.forEach(function(actionIcon) {
                    var item = this.datagrid.getRecordById(id),
                        iconClass = (typeof actionIcon === 'string') ? actionIcon : actionIcon.icon,
                        $icon = $('<div class="' + iconClass + ' action-icon"/>');

                    if (!actionIcon.type || actionIcon.type === item.type) {
                        this.$items[id].find('.action-icons').append($icon);
                        if (!!actionIcon.action) {
                            $icon.on('click', function(event) {
                                event.stopPropagation();
                                actionIcon.action(item);
                            }.bind(this));
                        }
                    }
                }.bind(this));
            },

            /**
             * Starts the download dropdown for a given record
             *
             * @param {Object} record The data from which the dropdown gets initialized and started.
             */
            startDownloadDropdown: function(id) {
                // Ensure that dropdown only gets started if not already started
                if (!!this.$items[id].find('.' + constants.downloadNavigatorClass).children().length) {
                    return;
                }

                var record = this.datagrid.getRecordById(id),
                    dropdownItems = [
                        {
                            id: 'download',
                            name: 'sulu.media.download_original',
                            url: window.location.protocol + '//' + window.location.host + record.url
                        },
                        {id: 'divider', divider: true},
                        {
                            id: window.location.protocol + '//' + window.location.host + record.url,
                            name: 'sulu.media.copy_original',
                            info: 'sulu.media.copy_url',
                            clickedInfo: 'sulu.media.copied_url'
                        }
                    ].concat(_.map(record.thumbnails, function(url, format) {
                        return {
                            id: window.location.protocol + '//' + window.location.host + url,
                            name: format,
                            info: 'sulu.media.copy_url',
                            clickedInfo: 'sulu.media.copied_url'
                        };
                    })),
                    $element = $('<span class="fa-cloud-download"/>');

                this.$items[id].find('.' + constants.downloadNavigatorClass).append($element);

                this.sandbox.start([
                    {
                        name: 'dropdown@husky',
                        options: {
                            el: $element,
                            instanceName: id,
                            data: dropdownItems
                        }
                    }
                ]);

                this.sandbox.once('husky.dropdown.' + record.id + '.rendered', function() {
                    this.clipboard = this.sandbox.clipboard.initialize('.' + constants.downloadNavigatorClass + ' li', {
                        text: function(trigger) {
                            return trigger.getAttribute('data-id');
                        }
                    });
                }.bind(this));
            },

            /**
             * Stops the download dropdown for a given record
             *
             * {Integer} id The id for the item for which the dropdown gets stopped
             */
            stopDownloadDropdown: function(id) {
                this.sandbox.stop(this.$items[id].find('.' + constants.downloadNavigatorClass + ' *'));
            },

            /**
             * Returns the number of characters which the title of an item can have at most
             *
             * @param {boolean} hasBatch True iff item has a batch which displays the fallback locale
             * @returns {number} The number of characters allowed at most
             */
            getTitleTextWidth: function(hasBatch) {
                var width = 29;
                if (hasBatch) {
                    width -= 3;
                }

                return width;
            },

            /**
             * Binds dom-related events on a masonry-grid item
             * @param id the identifier of the thumbnail to bind events on
             */
            bindItemEvents: function(id) {
                this.sandbox.dom.on(this.$items[id], 'click', function(event) {
                    this.sandbox.dom.stopPropagation(event);
                    this.datagrid.itemAction.call(this.datagrid, id);

                    if (this.options.selectOnAction) {
                        this.toggleItemSelected(id);
                    }
                }.bind(this), '.' + constants.actionNavigatorClass);

                this.sandbox.dom.on(this.$items[id], 'click', function(event) {
                    this.sandbox.dom.stopPropagation(event);
                    OverlayManager.startPlayVideoOverlay.call(this, id, UserSettingsManager.getMediaLocale());
                }.bind(this), '.' + constants.playVideoNavigatorClass);

                if (!!this.options.selectable) {
                    this.sandbox.dom.on(this.$items[id], 'click', function(event) {
                        if ($(event.target).hasClass('husky-dropdown-trigger')
                            || $(event.target).parents().hasClass('husky-dropdown-trigger')
                        ) {
                            return;
                        }

                        this.sandbox.dom.stopPropagation(event);
                        this.toggleItemSelected(id);
                    }.bind(this));
                }

                this.$items[id].on('mouseenter', function() {
                    this.startDownloadDropdown(id);
                }.bind(this));

                this.$items[id].on('mouseleave', function() {
                    this.stopDownloadDropdown(id);
                }.bind(this));

                this.bindItemDownloadEvents(id);
            },

            /**
             * Binds events for an item regarding its download dropdown
             *
             * @param {Number|String} id The id of the item
             */
            bindItemDownloadEvents: function(id) {
                this.sandbox.on('husky.dropdown.' + id + '.item.click', function(item) {
                    if (!item.url) {
                        return;
                    }

                    window.location.href = item.url;
                });
            },

            /**
             * Bind image-loading events on a masonry-grid item
             *
             * @param {Number|String} id The id of the item
             * @param {Object} itemLoadedDeferred A promise to resolve when the item has been completely loaded
             */
            bindItemLoadingEvents: function(id, itemLoadedDeferred) {
                this.sandbox.dom.one($(this.$items[id]).find('.' + constants.imageClass), 'load', function() {
                    this.sandbox.dom.remove($(this.$items[id]).find('.' + constants.iconClass));
                    this.itemLoadedHandler(this.$items[id], itemLoadedDeferred);
                }.bind(this));

                this.sandbox.dom.one($(this.$items[id]).find('.' + constants.imageClass), 'error', function() {
                    this.sandbox.dom.remove($(this.$items[id]).find('.' + constants.imageClass));
                    this.itemLoadedHandler(this.$items[id], itemLoadedDeferred);
                }.bind(this));
            },

            /**
             * Marks an item as loaded and performs post loading tasks.
             *
             * @param {Object} $item The dom element of the loaded item
             * @param {Object} itemLoadedDeferred A promise to resolve when the item has been completely loaded
             */
            itemLoadedHandler: function($item, itemLoadedDeferred) {
                this.lockItemImageHeight($item);

                if ($item.find('.' + constants.iconClass).length !== 0) {
                    $item.addClass(constants.noImageClass);
                }

                this.sandbox.masonry.refresh('#' + this.masonryGridId, true);

                itemLoadedDeferred.resolve();
            },

            /**
             * For a given hidden item. This method locks the height of the image container.
             * This is necessary for making the zoom effect on hover possible.
             *
             * @param {Object} $tem The dom object of the item
             */
            lockItemImageHeight: function($item) {
                $item.find('.masonry-image').height($item.find('.masonry-image').height());
            },

            /**
             * Toggles an item with a given id selected or unselected
             * @param id {Number|String} the id of the item
             */
            toggleItemSelected: function(id) {
                if (this.datagrid.itemIsSelected.call(this.datagrid, id) === true) {
                    this.deselectRecord(id);
                } else {
                    this.selectRecord(id);
                }
            },

            /**
             * Takes an object with options and extends the current ones
             * @param options {Object} new options to merge to the current ones
             */
            extendOptions: function(options) {
                this.options = this.sandbox.util.extend(true, {}, this.options, options);
            },

            /**
             * Destroys the view
             */
            destroy: function() {
                this.sandbox.dom.off('body', 'click.masonry');
                this.sandbox.masonry.destroy('#' + this.masonryGridId);
                this.sandbox.dom.remove(this.$el);
            },

            /**
             * Adds a record to the view
             * @param record
             * @param appendAtBottom
             * @public
             */
            addRecord: function(record, appendAtBottom) {
                this.renderRecords([record], appendAtBottom);
            },

            /**
             * Adds multiple records to the view
             * @param record
             * @param appendAtBottom
             * @public
             */
            addRecords: function(records, appendAtBottom) {
                this.renderRecords(records, appendAtBottom);
            },

            /**
             * Removes a data record from the view
             * @param recordId {Number|String} the records identifier
             * @returns {Boolean} true if deleted succesfully
             */
            removeRecord: function(recordId) {
                if (!!this.$items[recordId]) {
                    this.sandbox.dom.remove(this.$items[recordId]);
                    this.sandbox.masonry.refresh('#' + this.masonryGridId, true);
                    this.datagrid.removeRecord.call(this.datagrid, recordId);

                    this.updateEmptyIndicatorVisibility();
                    return true;
                }
                return false;
            },

            /**
             * Selects an item with a given id
             * @param id {Number|String} the id of the item
             */
            selectRecord: function(id) {
                this.sandbox.dom.addClass(this.$items[id], constants.selectedClass);
                $(this.$items[id]).attr('data-filter-class', JSON.stringify([constants.selectedClass]));
                if (!this.sandbox.dom.is(this.sandbox.dom.find('input[type="checkbox"]', this.$items[id]), ':checked')) {
                    this.sandbox.dom.prop(this.sandbox.dom.find('input[type="checkbox"]', this.$items[id]), 'checked', true);
                }
                this.datagrid.setItemSelected.call(this.datagrid, id);
            },

            /**
             * Deselect an item with a given id
             * @param id {Number|String} the id of the item
             */
            deselectRecord: function(id) {
                this.sandbox.dom.removeClass(this.$items[id], constants.selectedClass);
                $(this.$items[id]).attr('data-filter-class', JSON.stringify([]));
                if (this.sandbox.dom.is(this.sandbox.dom.find('input[type="checkbox"]', this.$items[id]), ':checked')) {
                    this.sandbox.dom.prop(this.sandbox.dom.find('input[type="checkbox"]', this.$items[id]), 'checked', false);
                }
                this.datagrid.setItemUnselected.call(this.datagrid, id);
            },

            /**
             * Deselect all items
             */
            deselectAllRecords: function() {
                this.sandbox.util.each(this.$items, function(id) {
                    this.deselectRecord(Number(id));
                }.bind(this));
            },

            showSelected: function(show) {
                var filter = [],
                    $items = $('.masonry-item:not(.selected)');

                if (!!show) {
                    filter.push(constants.selectedClass);
                    $items.hide();
                } else {
                    $items.show();
                }

                this.sandbox.masonry.updateFilterClasses('#' + this.masonryGridId);
                this.sandbox.masonry.filter('#' + this.masonryGridId, filter);
            }
        };
    };
});
