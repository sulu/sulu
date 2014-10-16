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
 * @param {String} [options.addButtonText] String of translation key for adding new entries
 * @param {String} [options.instanceName] String for instance name of types-overlay
 */

define([], function() {

    'use strict';

    var defaults = {
            url: null,
            data: null,
            overlay: {
                instanceName: null
            },
            addButtonText: 'public.add-entry',
            instanceName: null
        },

        constants = {
            overlayContentSelector: '.overlay-content',
            templateRemoveSelector: '.remove-row',
            templateAddSelector: '#addRow',
            typeRowSelector: '.type-row',
            contentInnerSelector: '.content-inner'
        },

        templates = {
            row: function() {
                return[
                    '<div class="grid-row type-row" data-id="">',
                    '   <div class="grid-col-8 pull-left"><input class="form-element" type="text" value=""/></div>',
                    '   <div class="grid-col-2 pull-right"><div class="remove-row btn gray-dark fit only-icon pull-right"><div class="fa-minus-circle"></div></div></div>',
                    '</div>'].join('');
            },
            skeleton: function(valueField) {
                return [
                    '<div class="content-inner">',
                    '   <% _.each(data, function(item) { %>',
                    '       <div class="grid-row type-row" data-id="<%= item.id %>">',
                    '           <div class="grid-col-8 pull-left"><input class="form-element" type="text" value="<%= item.',valueField,' %>"/></div>',
                    '           <div class="grid-col-2 pull-right"><div class="remove-row btn gray-dark fit only-icon pull-right"><div class="fa-minus-circle"></div></div></div>',
                    '       </div>',
                    ' <% }); %>',
                    '</div>',
                    '<div class="grid-row">',
                    '   <div id="addRow" class="addButton">',
                            this.sandbox.translate(this.options.addButtonText),
                    '   </div>',
                    '</div>'
                ].join('');
            }
        },

        eventNamespace = 'sulu.types.',

        /**
         * Initialized event
         * @event sulu.types.[instanceName].initialzed
         */
            INITIALIZED = function(instanceName) {
            return createEventName.call(this, instanceName+'.initialized');
        },

        /**
         * Loaded event
         * @event sulu.types.[instanceName].loaded
         */
            LOADED = function(instanceName) {
            return createEventName.call(this, instanceName+'.loaded');
        },

        /**
         * Saved event
         * @event sulu.types.[instanceName].saved
         */
            SAVED = function(instanceName) {
            return createEventName.call(this, instanceName+'.saved');
        },

        /**
         * Remove event
         * @event sulu.types.[instanceName].remove
         */
            REMOVE = function(instanceName) {
            return createEventName.call(this, instanceName+'.remove');
        },

        /**
         * Removed event
         * @event sulu.types.[instanceName].removed
         */
            REMOVED = function(instanceName) {
            return createEventName.call(this, instanceName+'.removed');
        },

        /**
         * Open event
         * @event sulu.types.[instanceName].open
         */
            OPEN = function(instanceName) {
            return createEventName.call(this, instanceName+'.open');
        },

        /**
         * Closed event
         * @event sulu.types.[instanceName].closed
         */
            CLOSED = function(instanceName) {
            return createEventName.call(this, instanceName+'.closed');
        },

        createEventName = function(postFix) {
            return eventNamespace + postFix;
        };

    return {

        /**
         * Waits for the App-Component to start,
         * then continues with the initialization
         */
        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.elementsToRemove = [];
            this.$elementsToRemove = [];
            this.instanceName = this.options.instanceName;
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
        saveNewEditedItemsAndClose: function(domData, method) {
            var data = this.parseDataFromDom(domData),
                changedData = this.getChangedData(data);

            if (changedData.length > 0) {
                this.sandbox.util.save(this.options.url, method, changedData)
                    .then(function(response) {
                        this.sandbox.emit(SAVED.call(this, this.instanceName), response);
                        var mergeData = this.mergeDomAndRequestData(response, this.parseDataFromDom(domData, true));
                        this.sandbox.emit(CLOSED.call(this, this.instanceName), mergeData);
                    }.bind(this)).fail(function(status, error) {
                        this.sandbox.logger.error(status, error);
                    }.bind(this));
            } else {
                this.sandbox.emit(CLOSED.call(this, this.instanceName), this.parseDataFromDom(domData, true));
            }
        },

        /**
         * Compares original and new data
         */
        getChangedData: function(newData) {
            var changedData = [];
            this.sandbox.util.each(newData, function(idx, el) {
                if (!el.id) {
                    changedData.push(el);
                } else {
                    this.sandbox.util.each(this.options.data, function(idx, origEl) {
                        if (el.id === origEl.id && el.category !== origEl.category && el.category !== '') {
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
            this.sandbox.util.save(this.options.url + '/' + id, 'DELETE')
                .then(function() {
                    this.sandbox.emit(REMOVED.call(this, this.instanceName));
                }.bind(this)).fail(function(status, error) {
                    this.sandbox.logger.error(status, error);
                    return null;
                }.bind(this));
        },

        /**
         * Deletes removed category items
         */
        removeDeletedItems: function() {

            // remove dom elements - important for the getChangedData method
            if (!!this.$elementsToRemove && this.$elementsToRemove.length > 0) {
                this.sandbox.util.each(this.$elementsToRemove, function(index, $el) {
                    this.sandbox.dom.remove($el);
                }.bind(this));
            }

            // remove items from db
            if (!!this.elementsToRemove && this.elementsToRemove.length > 0) {
                this.sandbox.util.each(this.elementsToRemove, function(index, el) {
                    this.deleteItem(el);
                }.bind(this));

                this.elementsToRemove = [];
                this.$elementsToRemove = [];
            }
        },

        /**
         * Extracts data from dom structure
         * @param domData
         * @param excludeDeleted
         */
        parseDataFromDom: function(domData, excludeDeleted) {
            var $rows = this.sandbox.dom.find(constants.typeRowSelector, domData),
                data = [],
                id, value, deleted, obj;

            this.sandbox.dom.each($rows, function(index, $el) {

                deleted = this.sandbox.dom.hasClass($el, 'faded');

                if (!!excludeDeleted) {
                    if (!deleted) {
                        id = this.sandbox.dom.data($el, 'id');
                        value = this.sandbox.dom.val(this.sandbox.dom.find('input', $el));
                        if (value !== '') {
                            obj = {id: id};
                            obj[this.valueName] = value;
                            data.push(obj);
                        }
                    }
                } else {
                    id = this.sandbox.dom.data($el, 'id');
                    value = this.sandbox.dom.val(this.sandbox.dom.find('input', $el));
                    if (value !== '') {
                        obj = {id: id};
                        obj[this.valueName] = value;
                        data.push(obj);
                    }
                }

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

            this.sandbox.dom.off(constants.templateAddSelector, 'click');
            this.sandbox.dom.off(constants.templateRemoveSelector, 'click');

            // bind click on remove icon
            this.sandbox.dom.on(this.$overlayInnerContent, 'click', function(event) {
                this.markElementForRemoval(event);
            }.bind(this), constants.templateRemoveSelector);

            // bind click on add icon
            this.sandbox.dom.on(constants.templateAddSelector, 'click', function() {
                this.sandbox.dom.append(this.$overlayInnerContent, templates.row.call(this));
            }.bind(this), this.$overlay);

        },

        /**
         * Marks an element for removal
         */
        markElementForRemoval: function(event) {
            var $row = this.sandbox.dom.parent(this.sandbox.dom.parent(event.currentTarget)),
                id = this.sandbox.dom.data($row, 'id');

            if (!!id) {
                this.sandbox.emit(REMOVE.call(this, this.instanceName), id, $row);
            }

            this.toggleStateOfRow($row);
        },

        /**
         * Callback for close of overlay with ok button
         */
        onCloseWithOk: function(domData) {
            this.removeDeletedItems();
            if (!!domData) {
                this.saveNewEditedItemsAndClose(domData, 'PATCH');
            }
        },

        /**
         * Merges data returned by the rest api and the dom
         * @param updatedData
         * @param parsedDomData
         */
        mergeDomAndRequestData: function(updatedData, parsedDomData) {
            this.sandbox.util.foreach(parsedDomData, function(parsedEl) {
                this.sandbox.util.foreach(updatedData, function(updatedEl) {
                    if (parsedEl.id === updatedEl.id) {
                        parsedEl.category = updatedEl.category;
                    } else if (parsedEl.category === updatedEl.category) {
                        parsedEl.id = updatedEl.id;
                    }
                }.bind(this));
            }.bind(this));

            return parsedDomData;
        },

        /**
         * Bind custom related events
         */
        bindCustomEvents: function() {
            this.sandbox.off();

            // use open event because initialzed is to early
            this.sandbox.on('husky.overlay.' + this.options.overlay.instanceName + '.opened', function() {
                this.$overlay = this.sandbox.dom.find(this.options.overlay.el);
                this.$overlayContent = this.sandbox.dom.find(constants.overlayContentSelector, this.$overlay);
                this.$overlayInnerContent = this.sandbox.dom.find(constants.contentInnerSelector, this.$overlayContent);

                this.bindDomEvents();
                this.sandbox.emit(INITIALIZED.call(this, this.instanceName));
            }.bind(this));

            this.sandbox.on(OPEN.call(this, this.instanceName), function(config) {
                this.startOverlayComponent(config);
            }.bind(this));

            this.sandbox.on(REMOVE.call(this, this.instanceName), function(id, $row) {
                this.updateRemoveList(id, $row);
            }.bind(this));
        },

        /**
         * Adds new item to the list or removes existing
         * @param id of element to remove
         * @param $row dom row of elment to remove
         */
        updateRemoveList: function(id, $row) {
            if (this.elementsToRemove.indexOf(id) === -1) {
                if (!!id) {
                    this.elementsToRemove.push(id);
                }
                this.$elementsToRemove.push($row);
            } else {
                if (!!id) {
                    this.elementsToRemove.splice(this.elementsToRemove.indexOf(id), 1);
                }
                this.$elementsToRemove.splice(this.elementsToRemove.indexOf($row), 1);
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

            this.valueName = config.valueName;

            config.data = this.sandbox.util.template(templates.skeleton.call(this,config.valueName), {data: this.options.data});

            config.okCallback = function(data) {
                this.onCloseWithOk(data);
            }.bind(this);

            config.cancelCallback = function() {
                this.elementsToRemove = [];
                this.$elementsToRemove = [];
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
