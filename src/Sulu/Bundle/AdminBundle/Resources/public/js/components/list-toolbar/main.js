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
            showTitleAsTooltip: true,
            groups: [
                {
                    id: 1,
                    align: 'left'
                },
                {
                    id: 2,
                    align: 'right'
                }
            ],
            columnOptions: {
                disabled: false,
                data: [],
                key: null
            }
        },

        templates = {
            default: function() {
                return this.sandbox.sulu.buttons.get({
                        settings: {
                            options: {
                                dropdownItems: [{type: 'columnOptions'}]
                            }
                        }
                    }
                );
            },
            defaultEditable: function() {
                return templates.default.call(this).concat(this.sandbox.sulu.buttons.get({
                        editSelected: {
                            options: {
                                callback: function() {
                                    this.sandbox.emit('sulu.list-toolbar.edit');
                                }.bind(this)
                            }
                        }
                    }));

            },
            defaultNoSettings: function() {
                var defaults = templates.default.call(this);
                defaults.splice(2, 1);
                return defaults;
            },
            onlyAdd: function() {
                var defaults = templates.default.call(this);
                defaults.splice(1, 2);
                return defaults;
            },
            changeable: function() {
                return this.sandbox.sulu.buttons.get({
                    layout: {}
                });
            }
        },
        listener = {
            default: function() {
                var instanceName = this.options.instanceName ? this.options.instanceName + '.' : '',
                    postfix;
                this.sandbox.on('husky.datagrid.number.selections', function(number) {
                    postfix = number > 0 ? 'enable' : 'disable';
                    this.sandbox.emit('husky.toolbar.' + instanceName + 'item.' + postfix, 'deleteSelected', false);
                }.bind(this));

                this.sandbox.on('sulu.list-toolbar.' + instanceName + 'delete.state-change', function(enable) {
                    postfix = !!enable ? 'enable' : 'disable';
                    this.sandbox.emit('husky.toolbar.' + instanceName + 'item.' + postfix, 'deleteSelected', false);
                }.bind(this));

                this.sandbox.on('sulu.list-toolbar.' + instanceName + 'edit.state-change', function(enable) {
                    postfix = !!enable ? 'enable' : 'disable';
                    this.sandbox.emit('husky.toolbar.' + instanceName + 'item.' + postfix, 'editSelected', false);
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
                                trigger: '.toggle',
                                header: {
                                    title: this.sandbox.translate('list-toolbar.column-options.title')
                                }
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
                if (item.hasOwnProperty('dropdownItems')) {
                    // call recursively
                    item.dropdownItems = parseTemplateTypes.call(this, item.dropdownItems);
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
         * Starts the husky-toolbar with given options
         * @param options {object} options The options to pass to the toolbar-component
         */
        startToolbarComponent = function(options) {
            this.sandbox.dom.addClass(options.el, 'list-toolbar');
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

            this.options.template = parseTemplate.call(this, this.options.template, templates);
            this.options.listener = parseTemplate.call(this, this.options.listener, listener);

            // check if parent template is set
            if (!!this.options.parentTemplate) {
                this.options.parentTemplate = parseTemplate.call(this, this.options.parentTemplate, templates);
                this.options.parentListener = parseTemplate.call(this, this.options.parentListener, listener);

                this.options.template = mergeTemplates.call(this, this.options.parentTemplate, this.options.template);

            }

            // emit event to extend toolbar
            this.sandbox.emit(
                'sulu.list-toolbar.extend',
                this.options.context,
                this.options.template,
                this.options.instanceName,
                this.options.datagridInstanceName,
                this.options.listInfoContainerSelector
            );

            this.options.template = parseTemplateTypes.call(this, this.options.template);

            var $container,
                options = {
                    groups: this.options.groups,
                    hasSearch: true,
                    buttons: this.options.template,
                    instanceName: this.options.instanceName,
                    showTitleAsTooltip: this.options.showTitleAsTooltip,
                    showTitle: false
                };

            if (this.options.hasOwnProperty('hasSearch')) {
                options.hasSearch = this.options.hasSearch;
            }

            // see if template has listener set
            if (this.options.listener) {
                this.options.listener.call(this);
            }
            // see if template has listener set
            if (this.options.parentListener) {
                this.options.parentListener.call(this);
            }
            $container = this.sandbox.dom.createElement('<div />');
            this.html($container);
            options.el = $container;
            startToolbarComponent.call(this, options);
        }
    };
});
