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
    'services/sulumedia/overlay-manager',
    'services/sulumedia/user-settings-manager'
], function(OverlayManager, UserSettingsManager) {

    'use strict';

    var defaults = {
            unselectOnBackgroundClick: true,
            selectable: true,
            selectOnAction: false,
            imageFormat: '190x',
            emptyListTranslation: 'public.empty-list',
            fields: {
                image: 'thumbnails',
                title: ['title'],
                description: ['mimeType', 'size']
            },
            separators: {
                title: ' ',
                description: ', '
            }
        },

        constants = {
            masonryGridId: 'masonry-grid',
            emptyIndicatorClass: 'empty-list',

            itemHeadClass: 'item-head',
            itemInfoClass: 'item-info',

            selectedClass: 'selected',
            loadingClass: 'loading',

            headIconClass: 'head-icon',
            headImageClass: 'head-image',
            actionNavigatorClass: 'action-navigator',
            downloadNavigatorClass: 'download-navigator',
            playVideoNavigatorClass: 'play-video-navigator',
        },

        templates = {
            emptyIndicator: [
                '<div class="' + constants.emptyIndicatorClass + '" style="display: none">',
                '   <div class="fa-coffee icon"></div>',
                '   <span><%= text %></span>',
                '</div>'
            ].join(''),
            item: [
                '<div class="masonry-item ' + constants.loadingClass + '">',
                '   <div class="masonry-head ' + constants.actionNavigatorClass + '">',
                '       <div class="fa-coffee ' + constants.headIconClass + '"></div>',
                '       <img ondragstart="return false;" class="' + constants.headImageClass + '" src="<%= image %>"/>',
                '   </div>',
                '   <div class="masonry-info">',
                '       <span class="title ' + constants.actionNavigatorClass + '"><%= title %></span><br/>',
                '       <span class="description ' + constants.actionNavigatorClass + '"><%= description %></span>',
                '   </div>',
                '   <div class="masonry-footer">',
                '       <% if (!!selectable) { %>',
                '       <div class="footer-checkbox custom-checkbox"><input type="checkbox"><span class="icon"></span></div>',
                '       <% } %>',
                '       <a href= "<%= downloadUrl %>" class="fa-cloud-download footer-download ' + constants.downloadNavigatorClass + '"></a>',
                '       <% if (!!isVideo) { %>',
                '           <span class="fa-play footer-play-video ' + constants.playVideoNavigatorClass + '"></span>',
                '       <% } %>',
                '   </div>',
                '</div>'
            ].join('')
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
        };

    return {

        /**
         * Initializes the view, gets called only once
         * @param {Object} context The context of the datagrid class
         * @param {Object} options The options used by the view
         */
        initialize: function(context, options) {
            // context of the datagrid-component
            this.datagrid = context;

            // make sandbox available in this-context
            this.sandbox = this.datagrid.sandbox;

            // merge defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, options);

            this.setVariables();
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

            this.renderRecords(data.embedded);
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
            var $empty = this.sandbox.util.template(templates.emptyIndicator, {
                text: this.sandbox.translate(this.options.emptyListTranslation)
            });
            this.sandbox.dom.append(this.$el, $empty);

            // render masonry-grid
            var $grid = this.sandbox.dom.createElement('<div id="' + constants.masonryGridId + '"/>');
            this.sandbox.dom.append(this.$el, $grid);

            this.sandbox.dom.append($container, this.$el);
        },

        /**
         * Show the empty-list-indicator if datagrid contains no data, else hide it
         */
        updateEmptyIndicatorVisibility: function() {
            if (!!this.datagrid.data.embedded && this.datagrid.data.embedded.length > 0) {
                this.sandbox.dom.hide('.' + constants.emptyIndicatorClass);
            } else {
                this.sandbox.dom.show('.' + constants.emptyIndicatorClass);
            }
        },

        /**
         * Start the masonry-plugin on the masonry-grid element
         */
        initializeMasonryGrid: function() {
            this.sandbox.masonry.initialize('#' + constants.masonryGridId, {
                align: 'left',
                direction: 'left',
                itemWidth: 190,
                offset: 30,
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
            this.sandbox.util.foreach(records, function(record) {
                var item = processContentFilters.call(this, record);
                var id = item.id,
                    image = item[this.options.fields.image].url || '',
                    downloadUrl = item.url,
                    title = concatRecordColumns(item, this.options.fields.title,this.options.separators.title),
                    description = concatRecordColumns(
                        item,
                        this.options.fields.description,
                        this.options.separators.description
                    ),
                    isVideo = (item.type.name === 'video');

                // pass the found data to a render method
                this.renderItem(id, image, downloadUrl, title, description, isVideo, appendAtBottom);
            }.bind(this));
        },

        /**
         * Renders an masonry-grid item with the given properties
         * @param id
         * @param image
         * @param downloadUrl
         * @param title
         * @param description
         * @param isVideo
         * @param appendAtBottom
         */
        renderItem: function(id, image, downloadUrl, title, description, isVideo, appendAtBottom) {
            this.$items[id] = this.sandbox.dom.createElement(
                this.sandbox.util.template(templates.item, {
                    image: image,
                    downloadUrl: downloadUrl,
                    title: this.sandbox.util.cropMiddle(String(title), 24),
                    description: this.sandbox.util.cropMiddle(String(description), 32),
                    isVideo: isVideo,
                    selectable: this.options.selectable
                })
            );

            if (this.datagrid.itemIsSelected.call(this.datagrid, id)) {
                this.selectRecord(id);
            }

            if (!!appendAtBottom) {
                this.sandbox.dom.append(this.sandbox.dom.find('#' + constants.masonryGridId, this.$el), this.$items[id]);
            } else {
                this.sandbox.dom.prepend(this.sandbox.dom.find('#' + constants.masonryGridId, this.$el), this.$items[id]);
            }
            this.bindItemLoadingEvents(id);
            this.bindItemDomEvents(id);
        },

        /**
         * Binds dom-related events on a masonry-grid item
         * @param id the identifier of the thumbnail to bind events on
         */
        bindItemDomEvents: function(id) {
            this.sandbox.dom.on(this.$items[id], 'click', function(event) {
                this.sandbox.dom.stopPropagation(event);
                this.datagrid.itemAction.call(this.datagrid, id);

                if (this.options.selectOnAction) {
                    this.toggleItemSelected(id);
                }
            }.bind(this), '.' + constants.actionNavigatorClass);

            this.sandbox.dom.on(this.$items[id], 'click', function(event) {
                this.sandbox.dom.stopPropagation(event);
                window.location.href = $(event.currentTarget).attr('href');
            }.bind(this), '.' + constants.downloadNavigatorClass);

            this.sandbox.dom.on(this.$items[id], 'click', function(event) {
                this.sandbox.dom.stopPropagation(event);
                OverlayManager.startPlayVideoOverlay.call(this, id, UserSettingsManager.getMediaLocale());
            }.bind(this), '.' + constants.playVideoNavigatorClass);

            if (!!this.options.selectable) {
                this.sandbox.dom.on(this.$items[id], 'click', function(event) {
                    this.sandbox.dom.stopPropagation(event);
                    this.toggleItemSelected(id);
                }.bind(this));
            }
        },

        /**
         * Bind image-loading events on a masonry-grid item
         * @param id
         */
        bindItemLoadingEvents: function(id) {
            this.sandbox.dom.one($(this.$items[id]).find('.' + constants.headImageClass), 'load', function() {
                this.sandbox.dom.remove($(this.$items[id]).find('.' + constants.headIconClass));
                this.sandbox.masonry.refresh('#' + constants.masonryGridId, true);
                this.sandbox.dom.removeClass(this.$items[id], constants.loadingClass);
            }.bind(this));

            this.sandbox.dom.one($(this.$items[id]).find('.' + constants.headImageClass), 'error', function() {
                this.sandbox.dom.remove($(this.$items[id]).find('.' + constants.headImageClass));
                this.sandbox.masonry.refresh('#' + constants.masonryGridId, true);
                this.sandbox.dom.removeClass(this.$items[id], constants.loadingClass);
            }.bind(this));
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
            this.sandbox.masonry.destroy('#' + constants.masonryGridId);
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
         * Removes a data record from the view
         * @param recordId {Number|String} the records identifier
         * @returns {Boolean} true if deleted succesfully
         */
        removeRecord: function(recordId) {
            if (!!this.$items[recordId]) {
                this.sandbox.dom.remove(this.$items[recordId]);
                this.sandbox.masonry.refresh('#' + constants.masonryGridId, true);
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

            this.sandbox.masonry.updateFilterClasses('#' + constants.masonryGridId);
            this.sandbox.masonry.filter('#' + constants.masonryGridId, filter);
        }
    };
});
