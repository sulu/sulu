/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @class Bottom-toolbar
 * @constructor
 *
 * @param {Object} [options] Configuration object
 * @param {String} [options.instanceName] name of the instance
 * @param {Object} [options.columnOptions] options to pass to the toolbar-columnOptions
 * @param {String|Array} [options.template] Template of items for the toolbar. Can be Object with valid structure (see husky) or a string representing an object with items (e.g. 'default')
 * @param {String} [options.listener] listens to tab events
 * @param {String|Array} [options.parentTemplate] same as toolbarTemplate. Gets merged with template
 * @param {String} [options.parentListener] see if template has listener set
 * @param {String} [options.el] element to store options
 */
define([],
    function() {

        'use strict';

        var defaults = {
                instanceName: '',
                template: 'default',
                parentTemplate: null,
                listener: 'default',
                parentListener: null,
                columnOptions: {
                    disabled: false,
                    data: [],
                    key: null
                }
            },
            constants = {
                bottomToolbarId: '#bottom-list-toolbar'
            },

             /**
             * listens on add event
             *
             * @event sulu.bottom-toolbar.[INSTANCE_NAME].add
             */
            ADD_CLICKED = function() {
                return this.sandbox.emit(creatEventName.call(this,'add'));
            },

            /**
             * listens on delete event
             *
             * @event sulu.bottom-toolbar.[INSTANCE_NAME].delete
             */
            DEL_CLICKED = function() {
                return this.sandbox.emit(creatEventName.call(this,'delete'));
            },

            /**
             * create emit of specific instanceNme
             *
             * @param {String} event name
             */
            creatEventName = function(postfix){
                return 'sulu.bottom-toolbar.' + ((!!this.options.instanceName) ? this.options.instanceName + '.' : '') + postfix;
            },

            /**
             * Template for bottom toolbar
             * @returns {*[]}
             */
            listTemplate = {
                default: function() {
                    return [
                        {
                            id: 'add',
                            icon: 'plus-circle',
                            class: 'highlight-white',
                            position: 1,
                            callback: ADD_CLICKED.bind(this)
                        },
                        {
                            id: 'delete',
                            icon: 'trash-o',
                            position: 20,
                            callback: DEL_CLICKED.bind(this)
                        },
                        {
                            id: 'settings',
                            icon: 'gear',
                            position: 30,
                            items: [
                                {
                                    type: 'columnOptions'
                                }
                            ]
                        }
                    ];
                },
                defaultNoSettings: function() {
                    var defaults = listTemplate.default.call(this);
                    defaults.splice(2, 1);
                    return defaults;
                },
                onlyAdd: function() {
                    var defaults = listTemplate.default.call(this);
                    defaults.splice(1, 2);
                    return defaults;
                }
            },

            listener = {
                default: function() {
                    var instanceName = this.options.instanceName ? this.options.instanceName + '.' : '',
                        postfix;
                    this.sandbox.on('husky.datagrid.number.selections', function(number) {
                        postfix = number > 0 ? 'enable' : 'disable';
                        this.sandbox.emit('husky.toolbar.' + instanceName + 'item.' + postfix, 'delete', false);
                    }.bind(this));
                },

                defaultEditableList: function() {
                    var instanceName = this.options.instanceName ? this.options.instanceName + '.' : '';
                    listener.default.call(this);

                    this.sandbox.on('husky.datagrid.data.changed', function() {
                        this.sandbox.emit('husky.toolbar.' + instanceName + 'item.enable', 'save');
                    }.bind(this));
                }
            },

            getColumnOptionsTemplate = function() {
                return {
                    title: this.sandbox.translate('list-toolbar.column-options'),
                    disabled: false,
                    callback: function() {
                        var instanceName;

                        this.sandbox.dom.append('body', '<div id="column-options-overlay" />');
                        this.sandbox.start([
                            {
                                name: 'column-options@husky',
                                options: {
                                    el: '#column-options-overlay',
                                    data: this.sandbox.sulu.getUserSetting(this.options.columnOptions.key),
                                    hidden: false,
                                    instanceName: this.options.instanceName,
                                    trigger: '.toggle'
                                }
                            }
                        ]);
                        instanceName = this.options.instanceName ? this.options.instanceName + '.' : '';
                        this.sandbox.once('husky.column-options.' + instanceName + 'saved', function(data) {
                            this.sandbox.sulu.saveUserSetting(this.options.columnOptions.key, data);
                        }.bind(this));
                    }.bind(this)
                };
            },

            /**
             * merges two templates and replaces parent items with child items if they have the same id
             * @param parentTemplate
             * @param childTemplate
             * @returns {Array}
             */
            mergeTemplates = function(parentTemplate, childTemplate) {
                var template = parentTemplate.slice(0),
                    parentIds = [];

                // get parent ids
                this.sandbox.util.foreach(parentTemplate, function(parent) {
                    parentIds.push(parent.id);
                }.bind(this));

                // now merge arrays
                this.sandbox.util.foreach(childTemplate, function(child) {
                    var parentIndex = parentIds.indexOf(child.id);
                    if (parentIndex < 0) {
                        template.push(child);
                    } else {
                        // if parent contains item with same id, replace it with child item
                        template[parentIndex] = child;
                    }
                }.bind(this));
                return template;
            },


            /**
             * returns parsed template
             * @param template
             * @param defaultTemplates
             * @returns {*}
             */
            parseTemplate = function(template, defaultTemplates) {
                // parse template, if it is a string
                if (typeof template === 'string') {
                    try {
                        template = JSON.parse(template);
                    } catch (e) {
                        // load template from variables
                        if (!!defaultTemplates[template]) {
                            template = defaultTemplates[template].call(this);
                        } else {
                            this.sandbox.logger.log('no template found!');
                        }
                    }
                } else if (typeof template === 'function') {
                    template = template.call(this);
                }
                return template;
            },

            parseTemplateTypes = function(template) {
                var i, len, item;

                for (i = -1, len = template.length; ++i < len;) {
                    item = template[i];
                    if (item.hasOwnProperty('items')) {
                        // call recursively
                        item.items = parseTemplateTypes.call(this, item.items);
                    }
                    if (item.hasOwnProperty('type')) {
                        if (item.type === 'columnOptions') {
                            template[i] = this.sandbox.util.extend({}, getColumnOptionsTemplate.call(this), item);
                        }
                    }
                }
                return template;
            },

            /**
             * Delegates the start of the toolbar to the header
             */
            startToolbarInHeader = function(options) {
                // remove configured el (let header decide which container to use)
                this.sandbox.emit('sulu.header.set-toolbar', options);
            },
            /**
             * Starts the husky-toolbar with given options
             * @param options {object} options The options to pass to the toolbar-component
             */
            startToolbarComponent = function(options) {
                this.sandbox.start([
                    {
                        name: 'toolbar@husky',
                        options: options
                    }
                ]);
            };

        return {
            initialize: function() {
                // merge defaults
                this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

                this.options.template = parseTemplate.call(this, this.options.template, listTemplate);
                this.options.listener = parseTemplate.call(this, this.options.listener, listener);

                // check if parent template is set
                if (!!this.options.parentTemplate) {
                    this.options.parentTemplate = parseTemplate.call(this, this.options.parentTemplate, listTemplate);
                    this.options.parentListener = parseTemplate.call(this, this.options.parentListener, listener);

                    this.options.template = mergeTemplates.call(this, this.options.parentTemplate, this.options.template);

                }
                this.options.template = parseTemplateTypes.call(this, this.options.template);

                var $container,
                    options = {
                        el: constants.bottomToolbarId,
                        data: this.options.template,
                        instanceName: this.options.instanceName,
                        showTitleAsTooltip: true
                    };

                // see if template has listener set
                if (this.options.listener) {
                    this.options.listener.call(this);
                }
                // see if template has listener set
                if (this.options.parentListener) {
                    this.options.parentListener.call(this);
                }

                // start the toolbar right ahead or delegate the initialization
                    $container = this.sandbox.dom.createElement('<div />');
                    this.html($container);
                    options.el = $container;
                    startToolbarComponent.call(this, options);
            }
        };
    });


