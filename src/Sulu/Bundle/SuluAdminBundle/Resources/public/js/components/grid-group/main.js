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
define(function () {

    'use strict';

    var defaults = {
            data: {},
            slideDuration: 250, //ms
            instanceName: 'undefined'
        },
        constants = {
            elementsKey: 'public.elements',
            slideDownIcon: 'caret-right',
            slideUpIcon: 'caret-down',
            componentClass: 'sulu-grid-group',
            groupClass: 'entity',
            slideClass: 'group-slide',
            rightContentClass: 'rightContent',
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
                '       <div class="', constants.colorPointClass , '" style="background-color: <%= color %>"></div>',
                '       <div class="fa-<%= icon %> icon"></div>',
                '       <div class="', constants.titleClass , '"><%= title %></div>',
                '       <div class="', constants.rightContentClass , '"></div>',
                '   </div>',
                '   <div class="', constants.slideClass , '"></div>',
                '</div>'
            ].join(''),
            totalElements: [
                '<span class="total"><%= total %> <%= elements %></span>'
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
         * triggered if title or pagination gets clicked
         *
         * @event sulu.grid-group.[INSTANCE_NAME].initialized
         * @param id {Number|String} id of the group
         */
            SHOW_GROUP = function() {
            return createEventName.call(this, 'show-group');
        },

        createEventName = function(postfix) {
            return namespace + ((!!this.options.instanceName) ? this.options.instanceName + '.' : '') + postfix;
        };

    return {

        /**
         * Initializes the grid-group
         */
        initialize: function () {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // stores all group objects with the corresponding id
            this.group = {};
            // stores key value paires of an array of selected elements as value and the corresponding datagrid-name as key
            this.selectedRecords = {};
            this.lastClickedGrid = null;

            this.bindCustomEvents();
            this.render();

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Bind custom-related events
         */
        bindCustomEvents: function () {
            // update a collection in the list if the data-record has changed
            this.sandbox.on('sulu.media.collections.collection-changed', this.updateGroup.bind(this));

            // slide all up
            this.sandbox.on(CLOSE_ALL_GROUPS.call(this), this.slideUpAll.bind(this));
            // slide all down
            this.sandbox.on(SHOW_ALL_GROUPS.call(this), this.slideDownAll.bind(this));

            // update the last clicked grid if a media got changed
            /*this.sandbox.on('sulu.media.collections.media-saved', function () {
             this.sandbox.emit('husky.datagrid.' + this.lastClickedGrid + '.update');
             }.bind(this));*/
        },

        /**
         * Deletes all the selected records
         */
        deleteRecords: function () {
            this.setSelectedRecords().then(function () {
                this.sandbox.sulu.showDeleteDialog(function (confirmed) {
                    if (confirmed === true) {
                        for (var key in this.selectedRecords) {
                            if (this.selectedRecords.hasOwnProperty(key)) {
                                this.sandbox.emit('sulu.media.collections.delete-media', this.selectedRecords[key], function (key, recordId) {
                                    this.sandbox.emit('husky.datagrid.' + this.group[key].datagridName + '.record.remove', recordId);
                                    this.group[key].selectedElements = 0;
                                    delete this.selectedRecords[key];
                                }.bind(this, key), true);
                            }
                        }
                    }
                }.bind(this));
            }.bind(this));
        },

        /**
         * Asks each datagrid with selected elements for the selected
         * element-ids and stores them in the global array
         */
        setSelectedRecords: function () {
            var count = 0, length = Object.keys(this.group).length,
                dfd = this.sandbox.data.deferred();

            for (var key in this.group) {
                if (this.group.hasOwnProperty(key) && this.group[key].selectedElements > 0) {
                    this.sandbox.emit('husky.datagrid.' + this.group[key].datagridName + '.items.get-selected', function (ids) {
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
         * @returns {Boolean} returns false if a new and unsafed colleciton exists
         */
        addGroup: function () {
            if (this.sandbox.form.validate('#' + constants.newFormId)) {
                var data = this.sandbox.form.getData('#' + constants.newFormId),
                    group = {
                        mediaNumber: 0,
                        style: {
                            color: data.color
                        }
                    };
                group = this.sandbox.util.extend(true, {}, group, data);
                // note that the first argument is a copy of group. It needs to be a copy, otherwise the id of the group gets set and so the group
                // couldn't be saved in the next step
                this.renderGroup(this.sandbox.util.extend(false, {}, group), this.$find('.' + constants.componentClass), true);
                this.sandbox.emit('sulu.media.collections.save-collection', group);
            } else {
                return false;
            }
        },


        /**
         * Renders the groups-list
         */
        render: function () {
            // render group items
            this.sandbox.dom.addClass(this.$el, constants.componentClass);
            this.sandbox.util.foreach(this.options.data, function (group) {
                this.renderGroup(group, this.$el, false);
            }.bind(this));
        },

        /**
         * Renders a single group object
         * @param group {Object} the group to render
         * @param $container {Object} the dom element to append to rendred group to
         * @param newCollection {Boolean} set true if the rendered colleciton is an unsaved new group
         */
        renderGroup: function (group, $container, newCollection) {
            var $group = this.sandbox.dom.createElement(this.sandbox.util.template(templates.group)({
                color: group.style.color,
                icon: constants.slideDownIcon,
                title: group.title
            }));

            if (newCollection === true) {
                group.id = constants.newGroupId;
            }

            // insert the total-elements markup
            this.sandbox.dom.html(
                this.sandbox.dom.find('.' + constants.rightContentClass, $group),
                this.sandbox.util.template(templates.totalElements)({
                    total: group.mediaNumber,
                    elements: this.sandbox.translate(constants.elementsKey)
                })
            );

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
        updateGroup: function (group) {
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
        startGroupDatagrid: function (id, $container) {
            // store number of selected elements
            this.sandbox.on('husky.datagrid.' + this.options.instanceName + id + '.number.selections', function (number) {
                this.group[id].selectedElements = number;
                this.emitElementsSelected();
            }.bind(this));

            // edit single record on datagrid click
            this.sandbox.on('husky.datagrid.' + this.options.instanceName + id + '.item.click', function (recordId) {
                this.lastClickedGrid = this.group[id].datagridName;
                this.sandbox.emit('sulu.media.collections.edit-media', recordId);
            }.bind(this));

            this.group[id].datagridName = this.options.instanceName + id;
            this.group[id].datagridLoaded = true;

            var $element = this.sandbox.dom.createElement('<div class="' + constants.gridContainerClass + '"/>');
            this.sandbox.dom.html($container, $element);
            this.sandbox.sulu.initList.call(this, 'mediaFields', '/admin/api/media/fields',
                {
                    el: $element,
                    url: '/admin/api/media?collection=' + id,
                    view: 'thumbnail',
                    pagination: 'showall',
                    instanceName: this.options.instanceName + id,
                    searchInstanceName: this.options.instanceName,
                    paginationOptions: {
                        showall: {
                            showAllHandler: this.showAllRecords.bind(this, id)
                        }
                    }
                }
            );
        },

        /**
         * Looks if any group has selected elements and emits an event
         * @returns {Boolean} returns true if the button got enabled
         */
        emitElementsSelected: function () {
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
        bindGroupDomEvents: function (id) {
            if (id !== constants.newGroupId) {
                // toggle slide-container
                this.sandbox.dom.on(this.group[id].$el, 'click', function () {
                    this.toggleGroup(this.group[id]);
                }.bind(this), '.head');

                this.sandbox.dom.on(this.group[id].$el, 'click', function (event) {
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
        toggleGroup: function (group) {
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
        slideUp: function (group) {
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
        slideDown: function (group) {
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
