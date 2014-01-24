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
            instanceName: 'content',
            listener: null,
            columnOptions: {
                disabled: false,
                data: [],
                key: null
            }
        },

        templates = {
            default: function() {
                return {
                    data: [
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
                    ],
                    listener: function() {
                        var instanceName = this.options.instanceName ? this.options.instanceName + '.' : '';
                        this.sandbox.on('husky.datagrid.number.selections', function(number) {
                            this.sandbox.emit('husky.list-toolbar.' + instanceName + number > 0 ? 'enable' : 'disable');
                        });
                    }.bind(this)
                }
            }
        };

    return {
        view: true,


        initialize: function() {

            // merge defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // parse template, if it is a string
            if (typeof this.options.template === 'string') {
                try {
                    this.options.template = JSON.parse(this.options.template);
                } catch (e) {
                    if (!!templates[this.options.template]) {
                        this.options.template = templates[this.options.template].call(this).data;
                        this.options.listener = templates[this.options.template].call(this).listener;
                    } else {
                        this.sandbox.logger.log('no template found!');
                    }

                }
            }

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
                        data: this.options.template.data,
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
