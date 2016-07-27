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
    'services/sulumedia/user-settings-manager'
], function($, _, OverlayManager, UserSettingsManager) {

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
            },
            emptyIcon: 'fa-coffee',
            noImgIcon: function(item) {
                return 'fa-file-o';
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
            playVideoNavigatorClass: 'play-video-navigator'
        },

        templates = {
            emptyIndicator: [
                '<div class="' + constants.emptyIndicatorClass + '" style="display: none">',
                '   <div class="<%= icon %> icon"></div>',
                '   <span><%= text %></span>',
                '</div>'
            ].join(''),
            item: [
                '<div class="masonry-item <% if (image !== "") { %>' + constants.loadingClass + '<% } %>">',
                '   <div class="masonry-head ' + constants.actionNavigatorClass + '">',
                '       <div class="<%= icon %> ' + constants.headIconClass + '"></div>',
                '       <% if (image !== "") { %>',
                '       <img ondragstart="return false;" class="' + constants.headImageClass + '" src="<%= image %>"/>',
                '       <% } %>',
                '   </div>',
                '   <div class="masonry-info">',
                '       <% if (!!fallbackLocale) { %>',
                '       <span class="badge"><%= fallbackLocale %></span>',
                '       <% } %>',
                '       <span class="title ' + constants.actionNavigatorClass + '"><%= title %></span><br/>',
                '       <span class="description ' + constants.actionNavigatorClass + '"><%= description %></span>',
                '   </div>',
                '   <div class="masonry-footer">',
                '       <% if (!!selectable) { %>',
                '       <div class="footer-checkbox custom-checkbox"><input type="checkbox"><span class="icon"></span></div>',
                '       <% } %>',
                '       <div class="fa-cloud-download footer-download footer-icon ' + constants.downloadNavigatorClass + '"></div>',
                '       <% if (!!isVideo) { %>',
                '           <span class="fa-play footer-play-video footer-icon ' + constants.playVideoNavigatorClass + '"></span>',
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
                var $empty = this.sandbox.util.template(templates.emptyIndicator, {
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
                var deferreds = _.map(records, function(record) {
                    var item = processContentFilters.call(this, record);
                    var image = item[this.options.fields.image].url || '',
                        title = concatRecordColumns(item, this.options.fields.title, this.options.separators.title),
                        description = concatRecordColumns(
                            item,
                            this.options.fields.description,
                            this.options.separators.description
                        ),
                        isVideo = (item.type === 'video'),
                        deferred = $.Deferred(),
                        items = [
                            {
                                id: 'download',
                                name: 'sulu.media.download_original',
                                url: window.location.protocol + '//' + window.location.host + item.url
                            },
                            {id: 'divider', divider: true},
                            {
                                id: window.location.protocol + '//' + window.location.host + item.url,
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
                        }));

                    // pass the found data to a render method
                    this.renderItem(
                        item.id,
                        image,
                        title,
                        item.locale !== this.options.locale ? item.locale : null,
                        description,
                        isVideo,
                        appendAtBottom,
                        this.options.noImgIcon(item)
                    );

                    this.sandbox.start([
                        {
                            name: 'dropdown@husky',
                            options: {
                                el: this.$items[item.id].find('.' + constants.downloadNavigatorClass),
                                instanceName: item.id,
                                data: items
                            }
                        }
                    ]);

                    this.sandbox.once('husky.dropdown.' + item.id + '.rendered', function() {
                        deferred.resolve();
                    });

                    return deferred;
                }.bind(this));

                $.when.apply($, deferreds).then(function() {
                    this.clipboard = this.sandbox.clipboard.initialize('.' + constants.downloadNavigatorClass + ' li', {
                        text: function(trigger) {
                            return trigger.getAttribute('data-id');
                        }
                    });
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
             */
            renderItem: function(id, image, title, fallbackLocale, description, isVideo, appendAtBottom, icon) {
                this.$items[id] = this.sandbox.dom.createElement(
                    this.sandbox.util.template(templates.item, {
                        image: image,
                        title: this.sandbox.util.cropMiddle(String(title), 20),
                        fallbackLocale: fallbackLocale,
                        description: this.sandbox.util.cropMiddle(String(description), 32),
                        isVideo: isVideo,
                        domain: window.location.protocol + '//' + window.location.host,
                        selectable: this.options.selectable,
                        icon: icon
                    })
                );

                if (this.datagrid.itemIsSelected.call(this.datagrid, id)) {
                    this.selectRecord(id);
                }

                if (!!appendAtBottom) {
                    this.sandbox.dom.append(this.sandbox.dom.find('#' + this.masonryGridId, this.$el), this.$items[id]);
                } else {
                    this.sandbox.dom.prepend(this.sandbox.dom.find('#' + this.masonryGridId, this.$el), this.$items[id]);
                }

                if (!!image) {
                    this.bindItemLoadingEvents(id);
                } else {
                    this.itemLoadedHandler(this.$items[id]);
                }

                this.bindItemEvents(id);
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

                this.sandbox.on('husky.dropdown.' + id + '.item.click', function(item) {
                    if (!item.url) {
                        return;
                    }

                    window.location.href = item.url;
                });
            },

            /**
             * Bind image-loading events on a masonry-grid item
             * @param id
             */
            bindItemLoadingEvents: function(id) {
                this.sandbox.dom.one($(this.$items[id]).find('.' + constants.headImageClass), 'load', function() {
                    this.sandbox.dom.remove($(this.$items[id]).find('.' + constants.headIconClass));
                    this.itemLoadedHandler(this.$items[id]);
                }.bind(this));

                this.sandbox.dom.one($(this.$items[id]).find('.' + constants.headImageClass), 'error', function() {
                    this.sandbox.dom.remove($(this.$items[id]).find('.' + constants.headImageClass));
                    this.itemLoadedHandler(this.$items[id]);
                }.bind(this));
            },

            /**
             * Marks an item as loaded and performs post loading tasks.
             *
             * @param {Object} $item The dom element of the loaded item
             */
            itemLoadedHandler: function($item) {
                this.sandbox.masonry.refresh('#' + this.masonryGridId, true);
                this.sandbox.dom.removeClass($item, constants.loadingClass);
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
                this.sandbox.masonry.refresh('#' + this.masonryGridId, true);
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
