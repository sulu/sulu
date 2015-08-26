/**
 * @class ThumbnailView (Datagrid Decorator)
 * @constructor
 *
 * @param {Boolean} [unselectOnBackgroundClick] should items get deselected on document click
 * @param {Boolean} [selectable] should items be selectable
 * @param {String} [imageFormat] api-format which is used for head-image
 * @param {Object} [viewOptions] Configuration object
 * @param {Object} [viewOptions.fields] Defines which data-columns are used to render cards
 * @param {String} [viewOptions.fields.image]
 * @param {Array} [viewOptions.fields.title]
 * @param {Array} [viewOptions.fields.description]
 * @param {Object} [viewOptions.separators] Defines separators between data-columns
 * @param {String} [viewOptions.separators.description]
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
            selectable: true,
            imageFormat: '190x',
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
            containerId: 'masonry-grid',
            headIconClass: 'head-icon',
            headImageClass: 'head-image',
            actionNavigatorClass: 'action-navigator',
            downloadNavigatorClass: 'download-navigator',
            selectedClass: 'selected',
            loadingClass: 'loading',

            itemHeadClass: 'item-head',
            itemInfoClass: 'item-info'
        },

        templates = {
            item: [
                '<div class="masonry-item ' + constants.loadingClass + '">',
                '   <div class="masonry-head ' + constants.actionNavigatorClass + '">',
                '       <div class="fa-coffee ' + constants.headIconClass + '"></div>',
                '       <img class="' + constants.headImageClass + '" src="<%= image %>"/>',
                '   </div>',
                '   <div class="masonry-info">',
                '       <span class="title ' + constants.actionNavigatorClass + '"><%= title %></span><br/>',
                '       <span class="description ' + constants.actionNavigatorClass + '"><%= description %></span>',
                '   </div>',
                '   <div class="masonry-footer">',
                '       <% if (!!selectable) { %>',
                '       <div class="footer-checkbox custom-checkbox"><input type="checkbox"><span class="icon"></span></div>',
                '       <% } %>',
                '       <span class="fa-cloud-download footer-download ' + constants.downloadNavigatorClass + '"></span>',
                '   </div>',
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

        processContentFilters = function(record){
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

        /**
         * triggered when a when the download icon gets clicked
         * @event husky.datagrid.download-clicked
         * @param {Number|String} the id of the data-record
         */
        DOWNLOAD_CLICKED = function() {
            return this.datagrid.createEventName.call(this.datagrid, 'download-clicked');
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
            this.data = null;
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
            this.data = data;
            this.$el = this.sandbox.dom.createElement('<div id="' + constants.containerId + '"/>');
            this.sandbox.dom.append($container, this.$el);

            this.bindGeneralDomEvents();
            this.masonry = this.sandbox.masonry.initialize('#' + constants.containerId, {
                align: 'left',
                direction: 'left',
                itemWidth: 190,
                offset: 30,
                verticalOffset: 20
            });

            this.renderRecords(this.data.embedded);
            this.rendered = true;
        },

        /**
         * Bind dom related events for datagrid-view
         */
        bindGeneralDomEvents: function() {
            if (this.options.unselectOnBackgroundClick) {
                this.sandbox.dom.on('.body', 'click.contact.list', function() {
                    this.deselectAllRecords();
                }.bind(this));
            }
        },

        /**
         * Parses the data and passes it item by item to a render function
         * @param records {Array} array with records to render
         */
        renderRecords: function(records) {
            // loop through each data record
            this.sandbox.util.foreach(records, function(record) {
                var item = processContentFilters.call(this, record);
                var id, image, title, description;

                id = item.id;
                image = item[this.options.fields.image].url || '';
                title = concatRecordColumns(item, this.options.fields.title, this.options.separators.title);
                description = concatRecordColumns(item, this.options.fields.description, this.options.separators.description);

                // pass the found data to a render method
                this.renderItem(id, image, title, description);
            }.bind(this));
        },

        /**
         * Renders the actual contact item
         * @param id {String|Number} the identifier of the data record
         * @param imgSrc {String} the thumbnail src of the data record
         * @param imgAlt {String} the thumbnail alt tag of the data record
         * @param title {String} the title of the data record
         * @param description {String} the thumbnail description to render
         * @param record {Object} the original data record
         */
        renderItem: function(id, image, title, description) {
            this.$items[id] = this.sandbox.dom.createElement(
                this.sandbox.util.template(templates.item)({
                    image: image,
                    title: this.sandbox.util.cropMiddle(String(title), 24),
                    description: this.sandbox.util.cropMiddle(String(description), 32),
                    selectable: this.options.selectable
                })
            );

            if (this.datagrid.itemIsSelected.call(this.datagrid, id)) {
                this.selectRecord(id);
            }

            this.sandbox.dom.append(this.$el, this.$items[id]);
            this.bindItemLoadingEvents(id);
            this.bindItemDomEvents(id);
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
            this.sandbox.dom.off('.body', 'click.contact.list');
            this.sandbox.masonry.destroy('#' + constants.containerId);
            this.sandbox.dom.remove(this.$el);
        },

        /**
         * Binds Dom-Events for a thumbnail
         * @param id {Number|String} the identifier of the thumbnail to bind events on
         */
        bindItemDomEvents: function(id) {
            this.sandbox.dom.on(this.$items[id], 'click', function(event) {
                this.sandbox.dom.stopPropagation(event);
                this.datagrid.itemAction.call(this.datagrid, id);
            }.bind(this), "." + constants.actionNavigatorClass);

            this.sandbox.dom.on(this.$items[id], 'click', function(event) {
                this.sandbox.dom.stopPropagation(event);
                this.sandbox.emit(DOWNLOAD_CLICKED.call(this), id);
            }.bind(this), "." + constants.downloadNavigatorClass);

            if (!!this.options.selectable) {
                this.sandbox.dom.on(this.$items[id], 'click', function(event) {
                    this.sandbox.dom.stopPropagation(event);
                    this.toggleItemSelected(id);
                }.bind(this));
            }
        },

        bindItemLoadingEvents: function(id) {
            this.sandbox.dom.one(this.sandbox.dom.find('.' + constants.headImageClass, this.$items[id]), 'load', function() {
                this.sandbox.dom.remove(this.sandbox.dom.find('.' + constants.headIconClass, this.$items[id]));
                this.sandbox.masonry.refresh('#' + constants.containerId, true);
                this.sandbox.dom.removeClass(this.$items[id], constants.loadingClass);
            }.bind(this));

            this.sandbox.dom.one(this.sandbox.dom.find('.' + constants.headImageClass, this.$items[id]), 'error', function() {
                this.sandbox.dom.remove(this.sandbox.dom.find('.' + constants.headImageClass, this.$items[id]));
                this.sandbox.masonry.refresh('#' + constants.containerId, true);
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
         * Adds a record to the view
         * @param record
         * @public
         */
        addRecord: function(record) {
            this.renderRecords([record]);
        },

        /**
         * Removes a data record from the view
         * @param recordId {Number|String} the records identifier
         * @returns {Boolean} true if deleted succesfully
         */
        removeRecord: function(recordId) {
            if (!!this.$items[recordId]) {
                this.sandbox.dom.remove(this.$items[recordId]);
                this.sandbox.masonry.refresh('#' + constants.containerId, true);
                this.datagrid.removeRecord.call(this.datagrid, recordId);
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

        deselectAllRecords: function() {
            this.sandbox.util.each(this.$items, function(id) {
                this.deselectRecord(Number(id));
            }.bind(this));
        },
    };
});
