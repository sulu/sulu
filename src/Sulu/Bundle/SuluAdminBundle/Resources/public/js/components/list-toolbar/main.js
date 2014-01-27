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
            listener: 'default',
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
                        group: '1',
                        disabled: true,
                        callback: function() {
                            this.sandbox.emit('sulu.list-toolbar.delete');
                        }.bind(this)
                    },
                    {
                        id: 'settings',
                        icon: 'cogwheel',
                        group: '1',
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
                ]
            }
        },
        listener = {
            default: function() {
                var instanceName = this.options.instanceName ? this.options.instanceName + '.' : '',
                    postfix;
                this.sandbox.on('husky.datagrid.number.selections', function(number) {
                    postfix = number > 0 ? 'enable' : 'disable';
                    this.sandbox.emit('husky.toolbar.' + instanceName +'item.'+ postfix, 'delete');
                }.bind(this));
            }
        },

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



            var $container = this.sandbox.dom.createElement('<div />');
            this.html($container);


            // see if template has listener set
            if (this.options.listener) {
                this.options.listener.call(this);
            }


            this.sandbox.start([
                {
                    name: 'toolbar@husky',
                    options: {
                        hasSearch: true,
                        el: $container,
                        data: this.options.template,
                        instanceName: this.options.instanceName,
                        searchOptions: {
                            placeholderText: 'public.search'
                        }
                    }
                }
            ]);
        },

        /**
         * listens to tab events
         */
        bindCustomEvents: function() {

        }
    };
});
