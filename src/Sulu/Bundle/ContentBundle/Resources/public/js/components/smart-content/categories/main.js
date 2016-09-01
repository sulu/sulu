/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @class SmartContent/Categories
 * @constructor
 */
define(['services/husky/util'], function(util) {

    'use strict';

    var defaults = {
            options: {
                instanceName: 'categories',
                preselectedOperator: 'or',
                preselectedCategories: [],
                root: null
            },
            translations: {
                operatorLabel: 'smart-content.categories.operator-label',
                categoriesLabel: 'smart-content.categories.categories-label',
                useAnyCategory: 'smart-content.categories.use-any-category',
                useAllCategories: 'smart-content.categories.use-all-categories',
                noCategoriesAvailable: 'sulu.category.no-categories-available'
            },
            templates: {
                skeleton: [
                    '<div class="form-group m-bottom-20">',
                    '   <label><%=operatorLabel%></label>',
                    '   <div class="<%=constants.operatorClass%>"></div>',
                    '</div>',
                    '<div class="form-group">',
                    '   <label><%=categoriesLabel%></label>',
                    '   <div class="<%=constants.categoriesClass%> categories-container"></div>',
                    '</div>'
                ].join('')
            }
        },

        operators = {
            or: 'or',
            and: 'and'
        },

        constants = {
            operatorClass: 'operator',
            categoriesClass: 'categories'
        },

        /**
         * namespace for events
         * @type {string}
         */
        eventNamespace = 'smart-content.categories.';

    return {

        defaults: defaults,

        events: {
            names: {
                initialized: {postFix: 'initialized'},
                getData: {
                    postFix: 'get-data',
                    type: 'on'
                }
            },
            namespace: eventNamespace
        },

        data: {
            ids: defaults.preselectedCategories,
            items: [],
            operator: defaults.preselectedOperator
        },

        /**
         * Initialize component.
         */
        initialize: function() {
            // init this.data with preselected options.data
            this.data = {
                ids: this.options.preselectedCategories,
                items: [],
                operator: this.options.preselectedOperator
            };

            this.render();
            this.initComponents();
            this.bindCustomEvents();
        },

        /**
         * Render basic skeleton for sub components.
         */
        render: function() {
            this.$el.html(
                this.templates.skeleton(
                    {
                        constants: constants,
                        operatorLabel: this.translations.operatorLabel,
                        categoriesLabel: this.translations.categoriesLabel
                    }
                )
            );
        },

        /**
         * Init sub-components operator select and datagrid for categories.
         */
        initComponents: function() {
            this.sandbox.start(
                [
                    {
                        name: 'select@husky',
                        options: {
                            el: this.$find('.' + constants.operatorClass),
                            instanceName: this.options.instanceName,
                            value: 'name',
                            data: [
                                {id: operators.or, name: this.translations.useAnyCategory},
                                {id: operators.and, name: this.translations.useAllCategories}
                            ],
                            preSelectedElements: [operators[this.data.operator]]
                        }
                    },
                    {
                        name: 'datagrid@husky',
                        options: {
                            el: this.$find('.' + constants.categoriesClass),
                            instanceName: this.options.instanceName,
                            url: [
                                '/admin/api/categories',
                                (!!this.options.root ? ('/' + this.options.root) + '/children' : ''),
                                '?locale=' + this.sandbox.sulu.getDefaultContentLocale(),
                                '&flat=true&sortBy=depth&sortOrder=asc'
                            ].join(''),
                            resultKey: 'categories',
                            pagination: false,
                            childrenPropertyName: 'hasChildren',
                            resizeListener: false,
                            selectedCounter: true,
                            preselected: this.data.ids,
                            viewOptions: {
                                table: {
                                    cropContents: false,
                                    noItemsText: this.translations.noCategoriesAvailable,
                                    showHead: false,
                                    cssClass: 'white-box',
                                    selectItem: {type: 'checkbox', inFirstCell: true}
                                }
                            },
                            matchings: [
                                {name: 'name', content: 'Name'},
                                {name: 'id', disabled: true},
                                {name: 'children', disabled: true},
                                {name: 'parent', disabled: true}
                            ]
                        }
                    }
                ]
            ).then(
                function() {
                    this.sandbox.once(
                        this.sandbox.events.createEventName('husky.datagrid.', 'view.rendered', this.options.instanceName),
                        this.sandbox.emit.bind(
                            this,
                            this.sandbox.events.createEventName('husky.datagrid.', 'items.get-selected', this.options.instanceName),
                            this.setSelected.bind(this),
                            true
                        )
                    );
                }.bind(this)
            );
        },

        /**
         * Bind custom events for item de-/select and operator changed.
         */
        bindCustomEvents: function() {
            // data events to update data with datagrid and select events
            this.sandbox.on(
                this.sandbox.events.createEventName('husky.datagrid.', 'item.select', this.options.instanceName),
                this.add.bind(this)
            );
            this.sandbox.on(
                this.sandbox.events.createEventName('husky.datagrid.', 'item.deselect', this.options.instanceName),
                this.removeItem.bind(this)
            );
            this.sandbox.on(
                this.sandbox.events.createEventName('husky.select.', 'selected.item', this.options.instanceName),
                this.selectOperator.bind(this)
            );

            // getter for data
            this.events.getData(function(callback) {
                callback(this.getData());
            }.bind(this));
        },

        /**
         * Add category to data with given id.
         * @param {Integer} id
         * @param {Object} item
         */
        add: function(id, item) {
            if (this.data.ids.indexOf(id) > -1) {
                return;
            }

            this.data.ids.push(id);
            this.data.items.push(item);
        },

        /**
         * Remove category from data with given id.
         * @param {Integer} id
         */
        removeItem: function(id) {
            var index = this.data.ids.indexOf(id);

            if (index > -1) {
                this.data.ids.splice(index, 1);
                this.data.items.splice(index, 1);
            }
        },

        /**
         * Select operator.
         * @param {String} operator
         */
        selectOperator: function(operator) {
            this.data.operator = operator;
        },

        /**
         * Prepare data with datagrid returns.
         *
         * @param {Array} ids
         * @param {Array} items
         */
        setSelected: function(ids, items) {
            this.data.ids = util.deepCopy(ids);
            this.data.items = util.deepCopy(items);

            this.events.initialized(this.data);
        },

        /**
         * Returns data to given select-callback.
         */
        getData: function() {
            return this.data;
        }
    };
});
