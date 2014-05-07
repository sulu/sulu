/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

/**
 * @class Type Overlay
 * @constructor
 *
 * @param {String} [options.url] url to fetch data from
 * @param {Array} [options.data] data to display in the template
 * @param {String} [options.template] underscore template
 */

define([], function() {

    'use strict';

    var defaults = {

            url: null,
            data: null,
            overlay: {
                instanceName: null
            },

            templateRow: '<div class="grid-row type-row" data-id="">' +
                '   <div class="grid-col-8 pull-left"><input class="form-element" type="text" value=""/></div>' +
                '   <div class="grid-col-2 pull-right"><div class="remove-row btn gray-dark fit only-icon pull-right"><div class="icon-circle-minus"></div></div></div>' +
                '</div>',

            template: ['<div class="content-inner">',
                '   <% _.each(data, function(item) { %>',
                '       <div class="grid-row type-row" data-id="<%= item.id %>">',
                '           <div class="grid-col-8 pull-left"><input class="form-element" type="text" value="<%= item.category %>"/></div>',
                '           <div class="grid-col-2 pull-right"><div class="remove-row btn gray-dark fit only-icon pull-right"><div class="icon-circle-minus"></div></div></div>',
                '       </div>',
                ' <% }); %>',
                '</div>',
                '<div class="grid-row"><div id="addRow" class="addButton"></div></div>'].join('')

        },

        constants = {
            overlayContentSelector: '.overlay-content',
            templateRemoveSelector: '.remove-row',
            templateAddSelector: '#addRow',
            typeRowSelector: '.type-row',
            contentInnerSelector: '.content-inner'
        },

        eventNamespace = 'sulu.types.',

        /**
         * Initialized event
         * @event sulu.types.initialzed
         */
            INITIALZED = function() {
            return createEventName.call(this, 'initialzed');
        },

        /**
         * Loaded event
         * @event sulu.types.loaded
         */
            LOADED = function() {
            return createEventName.call(this, 'loaded');
        },

        /**
         * Saved event
         * @event sulu.types.saved
         */
            SAVED = function() {
            return createEventName.call(this, 'saved');
        },

        /**
         * Remove event
         * @event sulu.types.remove
         */
            REMOVE = function() {
            return createEventName.call(this, 'remove');
        },

        /**
         * Removed event
         * @event sulu.types.removed
         */
            REMOVED = function() {
            return createEventName.call(this, 'removed');
        },

        /**
         * open event
         * @event sulu.types.open
         */
            OPEN = function() {
            return createEventName.call(this, 'open');
        },


        createEventName = function(postFix) {
            return eventNamespace + postFix;
        };

    return {

        view: true,

        /**
         * Waits for the App-Component to start,
         * then continues with the initialization
         */
        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.elementsToRemove = [];
            this.bindCustomEvents();
        },

        /**
         * Loads data from URL
         * @returns {Object} data object
         */
        loadData: function() {

            this.sandbox.util.load(this.options.url)
                .then(function(response) {
                    this.sandbox.emit(LOADED);
                    return response;
                }.bind(this)).fail(function(status, error) {
                    this.sandbox.logger.error(status, error);
                    return null;
                }.bind(this));
        },

        /**
         * Saves data
         * @param domData
         * @param method
         */
        saveNewEditedItems: function(domData, method) {

            var data = this.parseDataFromDom(domData),
                changedData = this.getChangedData(data);

            if(changedData.length > 0) {
                this.sandbox.util.save(this.options.url, method, changedData)
                    .then(function(response) {
                        this.sandbox.emit(SAVED, response);
                        return response;
                    }.bind(this)).fail(function(status, error) {
                        this.sandbox.logger.error(status, error);
                        return null;
                    }.bind(this));
            }
        },

        /**
         * Compares original and new data
         */
        getChangedData: function(newData){
            var changedData = [];
            this.sandbox.util.each(newData,function(idx, el){
                if(!el.id) {
                    changedData.push(el);
                } else {
                    this.sandbox.util.each(this.options.data,function(idx, origEl){
                        if(el.id === origEl.id && el.category !== origEl.category && el.category !== '') {
                            changedData.push(el);
                        }
                    }.bind(this));
                }
            }.bind(this));

            return changedData;
        },

        /**
         * delete elements
         * @param id
         */
        deleteItem: function(id) {

            this.sandbox.util.save(this.options.url+'/'+id, 'DELETE')
                .then(function() {
                    this.sandbox.emit(REMOVED.call(this));
                }.bind(this)).fail(function(status, error) {
                    this.sandbox.logger.error(status, error);
                    return null;
                }.bind(this));
        },

        /**
         * Deletes removed category items
         */
        removeDeletedItems: function(){

            if(!!this.elementsToRemove && this.elementsToRemove.length > 0) {
                this.sandbox.util.each(this.elementsToRemove, function(index, el){
                    this.deleteItem(el);
                }.bind(this));
            }
        },

        /**
         * Extracts data from dom structure
         */
        parseDataFromDom: function(domData) {
            var $rows = this.sandbox.dom.find(constants.typeRowSelector, domData),
                data = [],
                id, value;

            this.sandbox.dom.each($rows, function(index, $el) {
                id = this.sandbox.dom.data($el, 'id');
                value = this.sandbox.dom.val(this.sandbox.dom.find('input', $el));
                data.push({id: id, category: value});
            }.bind(this));

            return data;
        },

        /**
         * Marks a row as removed
         * @param $row
         */
        toggleStateOfRow: function($row) {
            this.sandbox.dom.toggleClass($row, 'faded');
        },

        /**
         * Bind dom events
         */
        bindDomEvents: function() {

            this.sandbox.dom.off(constants.templateAddSelector);
            this.sandbox.dom.off(constants.templateRemoveSelector);

            // bind click on remove icon
            this.sandbox.dom.on(constants.templateRemoveSelector, 'click', function(event) {

                var $row = this.sandbox.dom.parent(this.sandbox.dom.parent(event.currentTarget)),
                    id = this.sandbox.dom.data($row, 'id');

                if (!!id) {
                    this.sandbox.emit(REMOVE.call(this),id);
                }

                this.toggleStateOfRow($row);

            }.bind(this), this.$overlayInnerContent);

            // bind click on add icon
            this.sandbox.dom.on(constants.templateAddSelector, 'click', function() {
                this.sandbox.dom.append(this.$overlayInnerContent,this.options.templateRow);

                // TODO no eventlistener on newly added rows

            }.bind(this), this.$overlay);

        },

        /**
         * Callback for close of overlay with ok button
         */
        onCloseWithOk: function(domData){
            if (!!domData) {
                this.saveNewEditedItems(domData, 'PATCH');
            }
            this.removeDeletedItems();
        },

        /**
         * Bind custom related events
         */
        bindCustomEvents: function() {

            // use open event because initialzed is to early
            this.sandbox.on('husky.overlay.'+this.options.overlay.instanceName+'.opened', function(){
                this.$overlay = this.sandbox.dom.find(this.options.overlay.el);
                this.$overlayContent = this.sandbox.dom.find(constants.overlayContentSelector, this.$overlay);
                this.$overlayInnerContent = this.sandbox.dom.find(constants.contentInnerSelector, this.$overlayContent);

                this.bindDomEvents();
                this.sandbox.emit(INITIALZED.call(this));
            }.bind(this));

            this.sandbox.on(OPEN.call(this), function(config){
                this.startOverlayComponent(config);
            }.bind(this));

            this.sandbox.on(REMOVE.call(this), function(id){
                this.updateRemoveList(id);
            }.bind(this));
        },

        /**
         * Adds new item to the list or removes existing
         */
        updateRemoveList: function(id){
            if(this.elementsToRemove.indexOf(id) === -1) {
                this.elementsToRemove.push(id);
            } else {
                this.elementsToRemove.splice(this.elementsToRemove.indexOf(id) , 1);
            }
        },

        /**
         * Starts the husky component
         * @param config
         */
        startOverlayComponent: function(config) {

            if (!!this.options.url && !config.data) {
                this.options.data = this.loadData();
            } else {
                this.options.data = config.data;
            }

            config.data = this.sandbox.util.template(this.options.template, {data: this.options.data});

            config.okCallback = function(data) {
                this.onCloseWithOk(data);
            }.bind(this);


            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: config
                }
            ]);
        }
    };
});
