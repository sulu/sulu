/**
 * @class ThumbnailView (Datagrid Decorator)
 * @constructor
 *
 * @param {Boolean} [unselectOnBackgroundClick] should items get deselected on document click
 * @param {Object} [viewOptions] Configuration object
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
            unselectOnBackgroundClick: true
        },

        constants = {
            containerClass: 'contact-grid',
            selectedClass: 'selected',
            suluUserClass: 'sulu-user',
            actionNavigatorClass: 'action-navigator',

            itemHeadClass: 'item-head',
            itemInfoClass: 'item-info',
        },

        templates = {
            item: [
                '<div class="contact-item">',
                '   <div class="' + constants.itemHeadClass + '">',
                '       <div class="head-container">',
                '           <div class="head-image ' + constants.actionNavigatorClass + '" style="background-image: url(\'<%= picture %>\')"></div>',
                '           <div class="head-name ' + constants.actionNavigatorClass + '"><%= name %></div>',
                '       </div>',
                '       <div class="head-checkbox custom-checkbox"><input type="checkbox"><span class="icon"></span></div>',
                '       <div class="head-sulubox"><div class="sulubox-signet"></div></div>',
                '   </div>',
                '   <div class="' + [constants.itemInfoClass, constants.actionNavigatorClass].join(" ") + '"></div>',
                '</div>'
            ].join(''),
            infoRow: [
                '       <div class="info-row">',
                '           <span class="<%= icon %> info-icon"></span>',
                '           <span class="info-text"><%= text %></span>',
                '       </div>'
            ].join('')
        },

        /**
         * Concat the entries of the given fields array to a single string. null-entries of the array are ignored
         * @param fields (Array)
         * @param separator String which separates two entries of the array
         * @returns {string}
         */
        concatFields = function(fields, separator) {
            var strings = [];
            fields.forEach(function(field) {
                if (!!field) {
                    strings.push(field);
                }
            });
            return strings.join(separator);
        }

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
            this.$el = this.sandbox.dom.createElement('<div class="' + constants.containerClass + '"/>');
            this.sandbox.dom.append($container, this.$el);

            this.bindGeneralDomEvents();
            this.renderItems(this.data.embedded);
            this.rendered = true;
        },

        /**
         * Bind dom related events for datagrid-view
         */
        bindGeneralDomEvents: function() {
            if (this.options.unselectOnBackgroundClick) {
                this.sandbox.dom.on('.body', 'click.contact.list', function() {
                    this.unselectAllItems();
                }.bind(this));
            }
        },

        /**
         * Parses the data and passes it item by item to a render function
         * @param items {Array} array with items to render
         */
        renderItems: function(items) {
            // loop through each data record
            this.sandbox.util.foreach(items, function(record) {
                var id, picture, name, isSuluUser, location, mail;

                id = record['id'];
                picture = this.getContactThumbnailUrl(record);
                isSuluUser = Math.random() < .5; //TODO: use api information
                mail = record['mainEmail'];

                // concat firstName and lastName because fullName should not be default
                name = concatFields([record['firstName'], record['lastName']], ' ');
                location = concatFields([record['city'], record['countryCode']], ', ');

                // pass the found data to a render method
                this.renderItem(id, picture, name, isSuluUser, location, mail);
            }.bind(this));
        },

        /**
         * Returns the url to the downsized contact-picture of the given contact-record
         * @param record
         * @returns url
         */
        getContactThumbnailUrl: function(record) {
            var result = this.datagrid.processContentFilter.call(
                this.datagrid,
                'thumbnails',
                record['thumbnails'],
                this.datagrid.types.THUMBNAILS,
                '200x200'
            );
            return result.url;
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
        renderItem: function(id, picture, name, isSuluUser, location, mail) {
            this.$items[id] = this.sandbox.dom.createElement(
                this.sandbox.util.template(templates.item)({
                    picture: picture,
                    name: this.sandbox.util.cropTail(String(name), 32),
                    isSuluUser: isSuluUser,
                })
            );

            if (!!isSuluUser) {
                this.sandbox.dom.addClass(this.$items[id], constants.suluUserClass);
            }

            if (!!location) {
                this.addInfoRowToItem(this.$items[id], 'fa-map-marker', location);
            }

            if (!!mail) {
                this.addInfoRowToItem(this.$items[id], 'fa-envelope', mail);
            }

            if (this.datagrid.itemIsSelected.call(this.datagrid, id)) {
                this.selectItem(id);
            }

            this.sandbox.dom.append(this.$el, this.$items[id]);
            this.bindItemDomEvents(id);
        },

        /**
         * Add an info-row to the given item
         * @param $item
         * @param icon icon-class of the info-row
         * @param text text of the info row
         */
        addInfoRowToItem: function($item, icon, text) {
            this.sandbox.dom.append(this.sandbox.dom.find('.' + constants.itemInfoClass, $item),
                this.sandbox.dom.createElement(
                    this.sandbox.util.template(templates.infoRow)({
                        icon: icon,
                        text: this.sandbox.util.cropMiddle(String(text), 26)
                    })
                )
            );
        },

        /**
         * Destroys the view
         */
        destroy: function() {
            this.sandbox.dom.off('.body', 'click.contact.list');
            this.sandbox.dom.remove(this.$el);
        },

        /**
         * Binds Dom-Events for a thumbnail
         * @param id {Number|String} the identifier of the thumbnail to bind events on
         */
        bindItemDomEvents: function(id) {
            this.sandbox.dom.on(this.$items[id], 'click', function() {
                this.sandbox.dom.stopPropagation(event);
                this.datagrid.itemAction.call(this.datagrid, id);
            }.bind(this), "." + constants.actionNavigatorClass);

            this.sandbox.dom.on(this.$items[id], 'click', function(event) {
                this.sandbox.dom.stopPropagation(event);
                this.toggleItemSelected(id);
            }.bind(this), "." + constants.itemHeadClass);
        },

        /**
         * Toggles an item with a given id selected or unselected
         * @param id {Number|String} the id of the item
         */
        toggleItemSelected: function(id) {
            if (this.datagrid.itemIsSelected.call(this.datagrid, id) === true) {
                this.unselectItem(id);
            } else {
                this.selectItem(id);
            }
        },

        /**
         * Selects an item with a given id
         * @param id {Number|String} the id of the item
         */
        selectItem: function(id) {
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
        unselectItem: function(id) {
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
        addRecord: function(record) {
            this.renderItems([record]);
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
                return true;
            }
            return false;
        },

        /**
         * Unselects all contact items
         */
        unselectAllItems: function() {
            this.sandbox.util.each(this.$items, function(id) {
                this.unselectItem(Number(id));
            }.bind(this));
        }
    };
});
