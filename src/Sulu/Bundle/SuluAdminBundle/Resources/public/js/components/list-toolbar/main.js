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
define([], function() {

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
            default: function() {
                return [
                    {
                        id: 'add',
                        icon: 'user-add',
                        class: 'highlight',
                        title: 'add',
                        callback: function() {
                            this.sandbox.emit('sulu.list-toolbar.add');
                        }.bind(this)
                    },
                    {
                        id: 'delete',
                        icon: 'bin',
                        title: 'delete',
                        disabled: true,
                        callback: function() {
                            this.sandbox.emit('sulu.list-toolbar.delete');
                        }.bind(this)
                    },
                    {
                        id: 'settings',
                        icon: 'cogwheel',
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
                                        this.sandbox.sulu.saveUserSetting(this.options.columnOptions.key, data, this.options.columnOptions.url);
                                    }.bind(this));
                                }.bind(this)
                            }
                        ]
                    }
                ];
            },
            defaultEditableList: function() {
                var defaults = templates.default.call(this);
                defaults.splice(1, 0, {
                    icon: 'floppy-saved',
                    iconSize: 'large',
                    disabled: true,
                    id: 'save',
                    title: this.sandbox.translate('sulu.list-toolbar.save'),
                    callback: function() {
                        this.sandbox.emit('sulu.list-toolbar.save');
                    }.bind(this)
                });
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
        };

    return {
        view: true,

        initialize: function() {

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

            var $container = this.sandbox.dom.createElement('<div />');
            this.html($container);

            // see if template has listener set
            if (this.options.listener) {
                this.options.listener.call(this);
            }
            // see if template has listener set
            if (this.options.parentListener) {
                this.options.parentListener.call(this);
            }

            this.sandbox.start([
                {
                    name: 'toolbar@husky',
                    options: {
                        hasSearch: true,
                        el: $container,
                        data: this.options.template,
                        instanceName: this.options.instanceName,
                        showTitleAsTooltip: true,
                        searchOptions: {
                            placeholderText: 'public.search'
                        }
                    }
                }
            ]);
        }
    };
});
