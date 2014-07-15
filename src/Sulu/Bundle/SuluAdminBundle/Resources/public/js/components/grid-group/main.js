/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @class Grid-group
 * @constructor
 *
 * @param {Object} [options] Configuration object
 */
define(function() {

    'use strict';

    var defaults = {
            data: {},
            slideDuration: 250, //ms
            instanceName: 'undefined',
            gridUrl: '',
            preselected: [],
            resultKey: '',
            dataGridOptions:{
                view: 'thumbnail',
                pagination: 'showall',
                matchings: [],
                viewOptions: null
            }
        },
        constants = {
            elementsKey: 'public.elements',
            slideDownIcon: 'caret-right',
            slideUpIcon: 'caret-down',
            componentClass: 'sulu-grid-group',
            groupClass: 'entity',
            slideClass: 'group-slide',
            titleClass: 'entity-title',
            colorPointClass: 'color',
            newGroupId: 'new',
            gridContainerClass: 'grid-container'
        },

        namespace = 'sulu.grid-group.',

        templates = {
            group: [
                '<div class="', constants.groupClass , '">',
                '   <div class="head">',
                '       <div class="fa-<%= icon %> icon"></div>',
                '       <div class="', constants.titleClass , '"><%= title %></div>',
                '   </div>',
                '   <div class="', constants.slideClass , '"></div>',
                '</div>'
            ].join('')
        },

        /**
         * trigger after initialization has finished
         *
         * @event sulu.grid-group.[INSTANCE_NAME].initialized
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        },

        /**
         * listens on and slides all groups down
         *
         * @event sulu.grid-group.[INSTANCE_NAME].show-all-groups
         */
        SHOW_ALL_GROUPS = function() {
            return createEventName.call(this, 'show-all-groups');
        },

        /**
         * listens on and passes the selected records to a callback
         *
         * @event sulu.grid-group.[INSTANCE_NAME].get-selected
         * @param {Function} callback to pass the selected records to
         */
        GET_SELECTED = function() {
            return createEventName.call(this, 'get-selected');
        },

        /**
         * listens on and passes the selected ids to a callback
         *
         * @event sulu.grid-group.[INSTANCE_NAME].get-selected-ids
         * @param {Function} callback to pass the selected ids to
         */
        GET_SELECTED_IDS = function() {
            return createEventName.call(this, 'get-selected-ids');
        },

        /**
         * listens on and slides all groups up
         *
         * @event sulu.grid-group.[INSTANCE_NAME].close-all-groups
         */
        CLOSE_ALL_GROUPS = function() {
            return createEventName.call(this, 'close-all-groups');
        },

        /**
         * emited if records got selected or deselected
         *
         * @event sulu.grid-group.[INSTANCE_NAME].elements-selected
         * @param selected {Boolean} true if there are selected elements
         */
        ELEMENTS_SELECTED = function() {
            return createEventName.call(this, 'elements-selected');
        },

        /**
         * removes a record from a group
         *
         * @event sulu.grid-group.[INSTANCE_NAME].elements-selected
         * @param groupId {Number|String} id of the group to remove the recrod from
         * @param recordId {Number|String} id of the record to remove
         */
        REMOVE_RECORD = function() {
            return createEventName.call(this, 'remove-record');
        },

        /**
         * triggered if title or pagination gets clicked
         *
         * @event sulu.grid-group.[INSTANCE_NAME].initialized
         * @param id {Number|String} id of the group
         */
        SHOW_GROUP = function() {
            return createEventName.call(this, 'show-group');
        },

        /**
         * triggered after a datagrid emits a clicked event
         *
         * @event sulu.grid-group.[INSTANCE_NAME].record-clicked
         * @param id {Number|String} id of the record
         */
        RECORD_CLICKED = function() {
            return createEventName.call(this, 'record-clicked');
        },

        /**
         * listens on and updates a group
         *
         * @event sulu.grid-group.[INSTANCE_NAME].update-group
         */
        UPDATE_GROUP = function() {
            return createEventName.call(this, 'update-group');
        },

        /**
         * listens on and adds a group to the list
         *
         * @event sulu.grid-group.[INSTANCE_NAME].add-group
         * @param {Object} the group object to add
         */
        ADD_GROUP = function() {
            return createEventName.call(this, 'add-group');
        },

        /**
         * listens on and updates the last-clicked datagrid
         *
         * @event sulu.grid-group.[INSTANCE_NAME].update-last-clicked
         */
        UPDATE_LAST_CLICKED = function() {
            return createEventName.call(this, 'update-last-clicked');
        },

        createEventName = function(postfix) {
            return namespace + ((!!this.options.instanceName) ? this.options.instanceName + '.' : '') + postfix;
        };

    return {

        /**
         * Initializes the grid-group
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // stores all group objects with the corresponding id
            this.group = {};
            // stores key value paires of an array of selected elements as value and the corresponding datagrid-name as key
            this.selectedRecords = {};
            this.lastClickedGrid = null;
            // save selected items
            this.selected = this.options.preselected;

            this.bindCustomEvents();
            this.render();

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Bind custom-related events
         */
        bindCustomEvents: function() {
            // update a collection in the list if the data-record has changed
            this.sandbox.on(UPDATE_GROUP.call(this), this.updateGroup.bind(this));
            // slide all up
            this.sandbox.on(CLOSE_ALL_GROUPS.call(this), this.slideUpAll.bind(this));
            // slide all down
            this.sandbox.on(SHOW_ALL_GROUPS.call(this), this.slideDownAll.bind(this));
            // get selected records
            this.sandbox.on(GET_SELECTED.call(this), this.getSelected.bind(this));
            // get selected ids
            this.sandbox.on(GET_SELECTED_IDS.call(this), this.getSelectedIds.bind(this));
            // remove a record
            this.sandbox.on(REMOVE_RECORD.call(this), this.removeRecord.bind(this));
            // add a new group
            this.sandbox.on(ADD_GROUP.call(this), this.addGroup.bind(this));

            // update the last clicked grid
            this.sandbox.on(UPDATE_LAST_CLICKED.call(this), function() {
                this.sandbox.emit('husky.datagrid.' + this.lastClickedGrid + '.update');
            }.bind(this));
        },

        /**
         * Passes all the selected records to a callback
         * @param callback {Function} to pass the selected elements to
         */
        getSelected: function(callback) {
            this.setSelectedRecords().then(function() {
                if (typeof callback === 'function') {
                    callback(this.selectedRecords);
                }
            }.bind(this));
        },

        /**
         * Passes all the selected ids to a callback
         * @param callback {Function} to pass the selected elements to
         */
        getSelectedIds: function(callback) {
            if (typeof callback === 'function') {
                callback(this.selected);
            }
        },

        /**
         * Removes a record from a datagrid
         * @param groupId {Number|String} id of the group
         * @param recordId {Number|String} id of the record
         */
        removeRecord: function(groupId, recordId) {
            this.sandbox.emit('husky.datagrid.' + this.group[groupId].datagridName + '.record.remove', recordId);
            this.group[groupId].selectedElements = 0;
            delete this.selectedRecords[groupId];
        },

        /**
         * Asks each datagrid with selected elements for the selected
         * element-ids and stores them in the global array
         */
        setSelectedRecords: function() {
            var count = 0, length = Object.keys(this.group).length,
                dfd = this.sandbox.data.deferred();

            this.selectedRecords = {};
            for (var key in this.group) {
                if (this.group.hasOwnProperty(key) && this.group[key].selectedElements > 0) {
                    this.sandbox.emit('husky.datagrid.' + this.group[key].datagridName + '.items.get-selected', function(ids) {
                        // stores the selected record-ids with the id of the corresponding datagrid
                        this.selectedRecords[key] = ids;
                        count++;
                        if (count === length) {
                            dfd.resolve();
                        }
                    }.bind(this));
                } else {
                    length--;
                    if (count === length) {
                        dfd.resolve();
                    }
                }
            }
            return dfd.promise();
        },

        /**
         * Adds a new group the the list
         * @param data {Object} new group to add
         */
        addGroup: function(data) {
            var group = {
                style: {
                    color: data.color
                }
            };
            group = this.sandbox.util.extend(true, {}, group, data);
            // note that the first argument is a copy of group. It needs to be a copy, otherwise the id of the group gets set and so the group
            // couldn't be saved in the next step
            this.renderGroup(this.sandbox.util.extend(false, {}, group), this.$el, true);
        },

        /**
         * Renders the groups-list
         */
        render: function() {
            // render group items
            this.sandbox.dom.addClass(this.$el, constants.componentClass);
            this.sandbox.util.foreach(this.options.data, function(group) {
                this.renderGroup(group, this.$el, false);
            }.bind(this));
        },

        /**
         * Renders a single group object
         * @param group {Object} the group to render
         * @param $container {Object} the dom element to append to rendred group to
         * @param newCollection {Boolean} set true if the rendered colleciton is an unsaved new group
         */
        renderGroup: function(group, $container, newCollection) {
            var $group = this.sandbox.dom.createElement(this.sandbox.util.template(templates.group, {
                icon: constants.slideDownIcon,
                title: group.title
            }));

            if (newCollection === true) {
                group.id = constants.newGroupId;
            }

            // hide the slide-container
            this.sandbox.dom.hide(this.sandbox.dom.find('.' + constants.slideClass, $group));

            this.sandbox.dom.append($container, $group);
            this.group[group.id] = {
                id: group.id,
                $el: $group,
                data: group,
                selectedElements: 0,
                datagridName: null,
                datagridLoaded: false
            };
            this.bindGroupDomEvents(group.id);
        },

        /**
         * Replaces a group with a passed one. If the id of the passed one isn't contained
         * in the global groups object it assumes that the passed group got newly added
         * @param group
         */
        updateGroup: function(group) {
            if (!!this.group[group.id]) {
                // if the passed group already exists override the data property
                this.group[group.id].data = group;
            } else if (!!this.group[constants.newGroupId]) {
                // else, if a placeholder group (new one) exists create a new group object and delete the placeholder
                this.group[group.id] = {
                    id: group.id,
                    $el: this.group[constants.newGroupId].$el,
                    data: group,
                    selectedElements: 0,
                    datagridName: null,
                    datagridLoaded: false
                };

                // rebind events
                this.sandbox.dom.off(this.group[constants.newGroupId].$el);
                delete this.group[constants.newGroupId];
                this.bindGroupDomEvents(group.id);
            } else {
                this.sandbox.logger.log('Error. Undefined group cannot be updated');
                return false;
            }

            // update title in dom
            this.sandbox.dom.html(
                this.sandbox.dom.find('.' + constants.titleClass, this.group[group.id].$el),
                this.group[group.id].data.title
            );
            // update color in dom
            this.sandbox.dom.css(
                this.sandbox.dom.find('.' + constants.colorPointClass, this.group[group.id].$el),
                {'background-color': this.group[group.id].data.style.color}
            );
        },

        /**
         * Starts the preview-datagrid for a group
         * @param id {Number|String} the groups identifier
         * @param $container {Object} the dom container to start the datagrid in
         */
        startGroupDatagrid: function(id, $container) {
            // store number of selected elements
            this.sandbox.on('husky.datagrid.' + this.options.instanceName + id + '.number.selections', function(number) {
                this.group[id].selectedElements = number;
                this.emitElementsSelected();
            }.bind(this));

            // edit single record on datagrid click
            this.sandbox.on('husky.datagrid.' + this.options.instanceName + id + '.item.click', function(recordId) {
                this.lastClickedGrid = this.group[id].datagridName;
                this.sandbox.emit(RECORD_CLICKED.call(this), recordId);
            }.bind(this));

            this.group[id].datagridName = this.options.instanceName + id;
            this.group[id].datagridLoaded = true;

            var $element = this.sandbox.dom.createElement('<div class="' + constants.gridContainerClass + '"/>'),
                instanceName = this.options.instanceName + id;
            this.sandbox.dom.html($container, $element);

            //start list-toolbar and datagrid
            this.sandbox.start([
                {
                    name: 'datagrid@husky',
                    options: {
                        el: $element,
                        url: this.options.gridUrl + id,
                        view: this.options.dataGridOptions.view,
                        pagination: this.options.dataGridOptions.pagination,
                        matchings: this.options.dataGridOptions.matchings,
                        viewOptions: this.options.dataGridOptions.viewOptions,
                        preselected: this.options.preselected,
                        resultKey: this.options.resultKey,
                        instanceName: instanceName,
                        searchInstanceName: this.options.instanceName,
                        paginationOptions: {
                            showall: {
                                showAllHandler: this.showAllRecords.bind(this, id)
                            }
                        }
                    }
                }
            ]);

            // item deselected
            this.sandbox.on('husky.datagrid.' + instanceName + '.item.deselect', function(id) {
                var index = this.selected.indexOf(id);
                this.selected.splice(index, 1);
            }.bind(this));
            // item selected
            this.sandbox.on('husky.datagrid.' + instanceName + '.item.select', function(id) {
                this.selected.push(id);
            }.bind(this));
        },

        /**
         * Looks if any group has selected elements and emits an event
         * @returns {Boolean} returns true if the button got enabled
         */
        emitElementsSelected: function() {
            for (var key in this.group) {
                if (this.group[key].selectedElements > 0) {
                    this.sandbox.emit(ELEMENTS_SELECTED.call(this), true);
                    return true;
                }
            }
            this.sandbox.emit(ELEMENTS_SELECTED.call(this), false);
            return false;
        },

        /**
         * Binds dom events on a group element
         * @param id {Number|String} the identifier of the group
         */
        bindGroupDomEvents: function(id) {
            if (id !== constants.newGroupId) {
                // toggle slide-container
                this.sandbox.dom.on(this.group[id].$el, 'click', function() {
                    this.toggleGroup(this.group[id]);
                }.bind(this), '.head');

                this.sandbox.dom.on(this.group[id].$el, 'click', function(event) {
                    this.sandbox.dom.stopPropagation(event);
                    this.showAllRecords(id);
                }.bind(this), '.' + constants.titleClass);
            }
        },

        /**
         * Slides all groups up
         */
        slideUpAll: function() {
            for (var id in this.group) {
                if (this.group.hasOwnProperty(id)) {
                    this.slideUp(this.group[id]);
                }
            }
        },

        /**
         * Slides all groups down
         */
        slideDownAll: function() {
            for (var id in this.group) {
                if (this.group.hasOwnProperty(id)) {
                    this.slideDown(this.group[id]);
                }
            }
        },

        /**
         * Slides a group up or down
         * @param group {Object} the object of the collectgroupion
         */
        toggleGroup: function(group) {
            // if slide-container is visible slide it up
            if (this.sandbox.dom.is(
                this.sandbox.dom.find('.' + constants.slideClass, group.$el),
                ':visible'
            )) {
                this.slideUp(group);
                // else slide it down
            } else {
                this.slideDown(group);
            }
        },

        /**
         * Slides a colleciton down
         * @param group {Object} the object of the group
         */
        slideUp: function(group) {
            this.sandbox.dom.slideUp(
                this.sandbox.dom.find('.' + constants.slideClass, group.$el),
                this.options.slideDuration
            );
            this.sandbox.dom.removeClass(
                this.sandbox.dom.find('.head .icon', group.$el),
                    'fa-' + constants.slideUpIcon
            );
            this.sandbox.dom.prependClass(
                this.sandbox.dom.find('.head .icon', group.$el),
                    'fa-' + constants.slideDownIcon
            );
        },

        /**
         * Slides a colleciton down
         * @param group {Object} the object of the group
         */
        slideDown: function(group) {
            this.sandbox.dom.slideDown(
                this.sandbox.dom.find('.' + constants.slideClass, group.$el),
                this.options.slideDuration
            );
            this.sandbox.dom.removeClass(
                this.sandbox.dom.find('.head .icon', group.$el),
                    'fa-' + constants.slideDownIcon
            );
            this.sandbox.dom.prependClass(
                this.sandbox.dom.find('.head .icon', group.$el),
                    'fa-' + constants.slideUpIcon
            );
            if (group.datagridLoaded === false) {
                this.startGroupDatagrid(
                    group.id,
                    this.sandbox.dom.find('.' + constants.slideClass, group.$el)
                );
            }
        },

        /**
         * Called if the show all pagination is clicked
         * @param id {Number|String} id of the group
         */
        showAllRecords: function(id) {
            this.sandbox.emit(SHOW_GROUP.call(this), id);
        }
    };
});
