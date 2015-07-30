/**
 * @class ThumbnailView (Datagrid Decorator)
 * @constructor
 *
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
            unselectOnBackgroundClick: true,
        },

        constants = {
            containerClass: 'contact-grid',
            selectedClass: 'selected',
            itemHeadClass: 'item-head',
            itemInfoClass: 'item-info',

            idProperty: 'id',
            mailProperty: 'mainEmail',
        },

        templates = {
            item: [
                '<div class="contact-item">',
                '   <div class="' + constants.itemHeadClass + '">',
                '       <div class="head-container">',
                '           <div class="image" style="background-image: url(\'<%= picture %>\')"></div>',
                '           <div class="head-name"><%= name %></div>',
                '       </div>',
                '       <div class="head-checkbox custom-checkbox"><input type="checkbox"><span class="icon"></span></div>',
                '       <% if (!!isSuluUser) { %>',
                '       <div class="head-sulubox"></div>',
                '       <% } %>',
                '   </div>',
                '   <div class="' + constants.itemInfoClass + '">',
                '       <% if (location !== "undefined") { %>',
                '       <div class="info-row">',
                '           <span class="fa-map-marker info-icon"></span>',
                '           <span class="info-text"><%= location %></span>',
                '       </div>',
                '       <% } %>',
                '       <% if (mail !== "undefined") { %>',
                '       <div class="info-row">',
                '           <span class="fa-envelope info-icon"></span>',
                '           <span class="info-text"><%= mail %></span>',
                '       </div>',
                '       <% } %>',
                '   </div>',
                '</div>'
            ].join('')
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
            this.sandbox.dom.on('.grid', 'click', function() {
                if (this.options.unselectOnBackgroundClick) {
                    this.unselectAllItems();
                }
            }.bind(this));
        },

        /**
         * Parses the data and passes it item by item to a render function
         * @param items {Array} array with items to render
         */
        renderItems: function(items) {
            // loop through each data record
            this.sandbox.util.foreach(items, function(record) {
                var id, picture, name, isSuluUser, location, mail;

                id = record[constants.idProperty];;
                picture = '/bundles/sulucontact/js/components/contacts/components/list/decorators//sample_avatar.jpg';
                name = [record['firstName'], record['lastName']].join(' ');
                isSuluUser = Math.random()<.3;
                location = 'Testhausen 8, AT';
                mail = record[constants.mailProperty];

                // pass the found data to a render method
                this.renderItem(id, picture, name, isSuluUser, location, mail);
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
        renderItem: function(id, picture, name, isSuluUser, location, mail) {
            this.$items[id] = this.sandbox.dom.createElement(
                this.sandbox.util.template(templates.item)({
                    picture: picture,
                    name: this.sandbox.util.cropTail(String(name), 32),
                    isSuluUser: isSuluUser,
                    location: this.sandbox.util.cropTail(String(location),26),
                    mail: this.sandbox.util.cropMiddle(String(mail), 26),
                })
            );

            if (this.datagrid.itemIsSelected.call(this.datagrid, id)) {
                this.selectItem(id);
            }
            this.sandbox.dom.append(this.$el, this.$items[id]);
            this.bindItemDomEvents(id);
        },

        /**
         * Destroys the view
         */
        destroy: function() {
            this.sandbox.dom.off('.grid', 'click');
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
            }.bind(this), "." + constants.itemInfoClass);

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
