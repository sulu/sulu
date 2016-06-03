/**
 * @class CardView (Datagrid Decorator)
 * @constructor
 *
 * @param {Boolean} [unselectOnBackgroundClick] should items get deselected on document click
 * @param {String} [emptyListTranslations] translation key for the empty-list indicator
 * @param {String} [imageFormat] api-format which is used for head-image
 * @param {Object} [viewOptions] Configuration object
 * @param {Object} [fields] Defines which data-columns are used to render cards
 * @param {String} [fields.picture]
 * @param {Array} [fields.title]
 * @param {Array} [fields.firstInfoRow]
 * @param {Array} [fields.secondInfoRow]
 * @param {Object} [separators] Defines separators between data-columns
 * @param {String} [separators.title]
 * @param {String} [separators.infoRow]
 * @param {Object} [icons] Defines info-row icons
 * @param {String} [icons.firstInfoRow]
 * @param {String} [icons.secondInfoRow]
 *
 * @param {Boolean} [rendered] property used by the datagrid-main class
 * @param {Function} [initialize] function which gets called once at the start of the view
 * @param {Function} [render] function to render data
 * @param {Function} [addRecord] function to add a new record to the grid
 * @param {Function} [removeRecord] function to remove an existing record from the grid
 * @param {Function} [destroy] function to destroy the view and unbind events
 */
define(function() {

    'use strict';

    var defaults = {
            unselectOnBackgroundClick: true,
            imageFormat: '100x100',
            emptyListTranslation: 'public.empty-list',
            fields: {
                firstInfoRow: ['city', 'countryCode'],
                secondInfoRow: ['mainEmail']
            },
            separators: {
                title: ' ',
                infoRow: ', '
            },
            icons: {
                firstInfoRow: 'fa-map-marker',
                secondInfoRow: 'fa-envelope'
            }
        },

        constants = {
            cardGridClass: 'card-grid',
            emptyIndicatorClass: 'empty-list',
            selectedClass: 'selected',
            actionNavigatorClass: 'action-navigator',

            itemHeadClass: 'item-head',
            itemInfoClass: 'item-info'
        },

        templates = {
            item: [
                '<div class="card-item">',
                '   <div class="' + constants.itemHeadClass + '">',
                '       <div class="head-container">',
                '           <div class="head-image ' + constants.actionNavigatorClass + '">',
                '               <span class="<%= pictureIcon %> image-default"></span>',
                '               <div class="image-content" style="background-image: url(\'<%= picture %>\')"></div>',
                '           </div>',
                '           <div class="head-name ' + constants.actionNavigatorClass + '"><%= name %></div>',
                '       </div>',
                '       <div class="head-checkbox custom-checkbox"><input type="checkbox"><span class="icon"></span></div>',
                '   </div>',
                '</div>'
            ].join(''),
            infoContainer: [
                '<div class="' + [constants.itemInfoClass, constants.actionNavigatorClass].join(" ") + '"></div>'
            ].join(''),
            infoRow: [
                '<div class="info-row">',
                '   <span class="<%= icon %> info-icon"></span>',
                '   <span class="info-text"><%= text %></span>',
                '</div>'
            ].join(''),
            emptyIndicator: [
                '<div class="' + constants.emptyIndicatorClass + '">',
                '   <div class="fa-coffee icon"></div>',
                '   <span><%= text %></span>',
                '</div>'
            ].join('')
        },

        /**
         * Concats the entries of the given columns of the given record to a string.
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

    return function() {
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
                this.renderCardContainer($container);
                this.bindGeneralDomEvents();

                this.renderRecords(data.embedded);
                this.rendered = true;
            },

            /**
             * Render the card-container into the given dom element
             * The card-container contains the empty-list-indicator and the card-grid
             * @param $container
             */
            renderCardContainer: function($container) {
                this.$el = this.sandbox.dom.createElement('<div class="card-grid-container"/>');

                // render empty indicator
                var $empty = this.sandbox.util.template(templates.emptyIndicator, {
                    text: this.sandbox.translate(this.options.emptyListTranslation)
                });
                this.sandbox.dom.append(this.$el, $empty);

                // render card-grid
                var $grid = this.sandbox.dom.createElement('<div class="' + constants.cardGridClass + '"/>');
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
             * Bind dom related events for datagrid-view
             */
            bindGeneralDomEvents: function() {
                if (this.options.unselectOnBackgroundClick) {
                    this.sandbox.dom.on('body', 'click.cards', function() {
                        this.deselectAllRecords();
                    }.bind(this));
                }
            },

            /**
             * Parses the data and passes it item by item to a render function
             * @param items {Array} array with items to render
             */
            renderRecords: function(items, appendAtBottom) {
                this.updateEmptyIndicatorVisibility();
                this.sandbox.util.foreach(items, function(record) {
                    var item = processContentFilters.call(this, record);
                    var id, picture, title, firstInfoRow, secondInfoRow;

                    id = item.id;
                    picture = item[this.options.fields.picture].url || '';

                    title = concatRecordColumns(item, this.options.fields.title, this.options.separators.title);
                    firstInfoRow = concatRecordColumns(item, this.options.fields.firstInfoRow, this.options.separators.infoRow);
                    secondInfoRow = concatRecordColumns(item, this.options.fields.secondInfoRow, this.options.separators.infoRow);

                    // pass the found data to a render method
                    this.renderItem(id, picture, title, firstInfoRow, secondInfoRow, appendAtBottom);
                }.bind(this));
            },

            /**
             * Renders a card-grid item with the given properties
             * @param id
             * @param picture
             * @param title
             * @param firstInfoRow
             * @param secondInfoRow
             */
            renderItem: function(id, picture, title, firstInfoRow, secondInfoRow, appendAtBottom) {
                this.$items[id] = this.sandbox.dom.createElement(
                    this.sandbox.util.template(templates.item)({
                        name: this.sandbox.util.cropTail(String(title), 25),
                        picture: picture,
                        pictureIcon: this.options.icons.picture
                    })
                );

                if (!!picture) {
                    this.sandbox.dom.addClass(this.sandbox.dom.find('.head-image', this.$items[id]), 'no-default');
                }

                if (!!firstInfoRow) {
                    this.addInfoRowToItem(this.$items[id], this.options.icons.firstInfoRow, firstInfoRow);
                }

                if (!!secondInfoRow) {
                    this.addInfoRowToItem(this.$items[id], this.options.icons.secondInfoRow, secondInfoRow);
                }

                if (this.datagrid.itemIsSelected.call(this.datagrid, id)) {
                    this.selectRecord(id);
                }

                if (!!appendAtBottom) {
                    $('.' + constants.cardGridClass).append(this.$items[id]);
                } else {
                    $('.' + constants.cardGridClass).prepend(this.$items[id]);
                }

                this.bindItemEvents(id);
            },

            /**
             * Add an info-row to the given item
             * @param $item
             * @param icon icon-class of the info-row
             * @param text text of the info row
             */
            addInfoRowToItem: function($item, icon, text) {
                var $container = this.sandbox.dom.find('.' + constants.itemInfoClass, $item);
                if (!$container.length) {
                    $container = this.sandbox.dom.createElement(this.sandbox.util.template(templates.infoContainer)());
                    this.sandbox.dom.append($item, $container);
                }
                this.sandbox.dom.append($container, this.sandbox.dom.createElement(
                    this.sandbox.util.template(templates.infoRow)({
                        icon: icon,
                        text: this.sandbox.util.cropMiddle(String(text), 22)
                    }))
                );
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
                this.sandbox.dom.off('body', 'click.cards');
                this.sandbox.dom.remove(this.$el);
            },

            /**
             * Binds Dom-Events for a card-item
             * @param id {Number|String} the identifier of the thumbnail to bind events on
             */
            bindItemEvents: function(id) {
                this.sandbox.dom.on(this.$items[id], 'click', function(event) {
                    this.sandbox.dom.stopPropagation(event);
                    this.datagrid.itemAction.call(this.datagrid, id);
                }.bind(this), "." + constants.actionNavigatorClass);

                this.sandbox.dom.on(this.$items[id], 'click', function(event) {
                    this.sandbox.dom.stopPropagation(event);
                    this.toggleItemSelected(id);
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
             * Selects an item with a given id
             * @param id {Number|String} the id of the item
             */
            selectRecord: function(id) {
                this.sandbox.dom.addClass(this.$items[id], constants.selectedClass);
                if (!this.sandbox.dom.is(this.sandbox.dom.find('input[type="checkbox"]', this.$items[id]), ':checked')) {
                    this.sandbox.dom.prop(this.sandbox.dom.find('input[type="checkbox"]', this.$items[id]), 'checked', true);
                }
                this.datagrid.setItemSelected.call(this.datagrid, id);
            },

            /**
             * Unselects an item with a given id
             * @param id {Number|String} the id of the item
             */
            deselectRecord: function(id) {
                this.sandbox.dom.removeClass(this.$items[id], constants.selectedClass);
                if (this.sandbox.dom.is(this.sandbox.dom.find('input[type="checkbox"]', this.$items[id]), ':checked')) {
                    this.sandbox.dom.prop(this.sandbox.dom.find('input[type="checkbox"]', this.$items[id]), 'checked', false);
                }
                this.datagrid.setItemUnselected.call(this.datagrid, id);
            },

            /**
             * Adds a record to the view
             * @param record
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
                    this.datagrid.removeRecord.call(this.datagrid, recordId);
                    this.updateEmptyIndicatorVisibility();
                    return true;
                }
                return false;
            },

            /**
             * Unselects all card items
             */
            deselectAllRecords: function() {
                this.sandbox.util.each(this.$items, function(id) {
                    this.deselectRecord(Number(id));
                }.bind(this));
            }
        };
    };
});
