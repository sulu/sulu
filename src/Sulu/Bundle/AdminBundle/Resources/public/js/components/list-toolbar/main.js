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
 */
define([], function () {

    'use strict';

    var defaults = {
            heading: '',
            template: 'default',
            parentTemplate: null,
            listener: 'default',
            parentListener: null,
            instanceName: 'content',
            columnOptions: {
                disabled: false,
                data: [],
                key: null
            }
        },

        templates = {
            default: function () {
                return [
                    {
                        id: 'add',
                        icon: 'plus-circle',
                        class: 'highlight-white',
                        title: 'add',
                        position: 10,
                        callback: function () {
                            this.sandbox.emit('sulu.list-toolbar.add');
                        }.bind(this)
                    },
                    {
                        id: 'delete',
                        icon: 'trash-o',
                        title: 'delete',
                        position: 20,
                        disabled: true,
                        callback: function () {
                            this.sandbox.emit('sulu.list-toolbar.delete');
                        }.bind(this)
                    },
                    {
                        id: 'settings',
                        icon: 'gear',
                        position: 30,
                        items: [
                            {
                                title: this.sandbox.translate('sulu.list-toolbar.import'),
                                disabled: true
                            },
                            {
                                title: this.sandbox.translate('sulu.list-toolbar.export'),
                                disabled: true
                            },
                            {
                                title: this.sandbox.translate('list-toolbar.column-options'),
                                disabled: false,
                                callback: function () {
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
                                    this.sandbox.once('husky.column-options.' + instanceName + 'saved', function (data) {
                                        this.sandbox.sulu.saveUserSetting(this.options.columnOptions.key, data);
                                    }.bind(this));
                                }.bind(this)
                            }
                        ]
                    }
                ];
            },
            defaultEditable: function () {
                return templates.default.call(this).concat([
                    {
                        id: 'edit',
                        icon: 'pencil',
                        title: 'edit',
                        position: 25,
                        disabled: true,
                        callback: function () {
                            this.sandbox.emit('sulu.list-toolbar.edit');
                        }.bind(this)
                    }
                ]);
            },
            defaultNoSettings: function () {
                var defaults = templates.default.call(this);
                defaults.splice(2, 1);
                return defaults;
            },
            changeable: function () {
                return [
                    {
                        id: 'change',
                        icon: 'th-large',
                        items: [
                            {
                                title: this.sandbox.translate('sulu.list-toolbar.small-thumbnails'),
                                callback: function () {
                                    this.sandbox.emit('sulu.list-toolbar.change.thumbnail-small');
                                }.bind(this)
                            },
                            {
                                title: this.sandbox.translate('sulu.list-toolbar.big-thumbnails'),
                                callback: function () {
                                    this.sandbox.emit('sulu.list-toolbar.change.thumbnail-large');
                                }.bind(this)
                            },
                            {
                                title: this.sandbox.translate('sulu.list-toolbar.table'),
                                callback: function () {
                                    this.sandbox.emit('sulu.list-toolbar.change.table');
                                }.bind(this)
                            }
                        ]
                    }
                ];
            },
            defaultEditableList: function () {
                var defaults = templates.default.call(this);
                defaults.splice(1, 0, {
                    icon: 'floppy-o',
                    iconSize: 'large',
                    disabled: true,
                    id: 'save',
                    title: this.sandbox.translate('sulu.list-toolbar.save'),
                    callback: function () {
                        this.sandbox.emit('sulu.list-toolbar.save');
                    }.bind(this)
                });
                return defaults;
            }
        },
        listener = {
            default: function () {
                var instanceName = this.options.instanceName ? this.options.instanceName + '.' : '',
                    postfix;
                this.sandbox.on('husky.datagrid.number.selections', function (number) {
                    postfix = number > 0 ? 'enable' : 'disable';
                    this.sandbox.emit('husky.toolbar.' + instanceName + 'item.' + postfix, 'delete', false);
                }.bind(this));

                this.sandbox.on('sulu.list-toolbar.' + instanceName + 'delete.state-change', function (enable) {
                    postfix = !!enable ? 'enable' : 'disable';
                    this.sandbox.emit('husky.toolbar.' + instanceName + 'item.' + postfix, 'delete', false);
                }.bind(this));

                this.sandbox.on('sulu.list-toolbar.' + instanceName + 'edit.state-change', function (enable) {
                    postfix = !!enable ? 'enable' : 'disable';
                    this.sandbox.emit('husky.toolbar.' + instanceName + 'item.' + postfix, 'edit', false);
                }.bind(this));
            },
            defaultEditableList: function () {
                var instanceName = this.options.instanceName ? this.options.instanceName + '.' : '';
                listener.default.call(this);

                this.sandbox.on('husky.datagrid.data.changed', function () {
                    this.sandbox.emit('husky.toolbar.' + instanceName + 'item.enable', 'save');
                }.bind(this));
            }
        },

        /**
         * merges two templates and replaces parent items with child items if they have the same id
         * @param parentTemplate
         * @param childTemplate
         * @returns {Array}
         */
            mergeTemplates = function (parentTemplate, childTemplate) {
            var template = parentTemplate.slice(0),
                parentIds = [];

            // get parent ids
            this.sandbox.util.foreach(parentTemplate, function (parent) {
                parentIds.push(parent.id);
            }.bind(this));

            // now merge arrays
            this.sandbox.util.foreach(childTemplate, function (child) {
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
            parseTemplate = function (template, defaultTemplates) {
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

        /**
         * Delegates the start of the toolbar to the header
         */
            startToolbarInHeader = function (options) {
            // remove configured el (let header decide which container to use)
            this.sandbox.emit('sulu.header.set-toolbar', options);
        },

        /**
         * Starts the husky-toolbar with given options
         * @param options {object} options The options to pass to the toolbar-component
         */
            startToolbarComponent = function (options) {
            this.sandbox.start([
                {
                    name: 'toolbar@husky',
                    options: options
                }
            ]);
        };

    return {

        initialize: function () {

            // merge defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.options.template = parseTemplate.call(this, this.options.template, templates);
            this.options.listener = parseTemplate.call(this, this.options.listener, listener);


            // check if parent template is set
            if (!!this.options.parentTemplate) {
                this.options.parentTemplate = parseTemplate.call(this, this.options.parentTemplate, templates);
                this.options.parentListener = parseTemplate.call(this, this.options.parentListener, listener);

                this.options.template = mergeTemplates.call(this, this.options.parentTemplate, this.options.template);
            }

            var $container,
                options = {
                    hasSearch: true,
                    data: this.options.template,
                    instanceName: this.options.instanceName,
                    showTitleAsTooltip: true,
                    searchOptions: {
                        placeholderText: 'public.search'
                    }
                };

            // see if template has listener set
            if (this.options.listener) {
                this.options.listener.call(this);
            }
            // see if template has listener set
            if (this.options.parentListener) {
                this.options.parentListener.call(this);
            }

            // start the toolbar right ahead or delegate the initialization to the header
            if (this.options.inHeader !== true) {
                $container = this.sandbox.dom.createElement('<div />');
                this.html($container);
                options.el = $container;
                startToolbarComponent.call(this, options);
            } else {
                // hide element-container, because toolbar gets rendered in header
                this.sandbox.dom.hide(this.$el);
                startToolbarInHeader.call(this, options);
            }
        }
    };
});
