/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * provides:
 *  - sulu.edittoolbar.setState();
 *  - sulu.edittoolbar.setButton(id);
 *
 * triggers:
 *  - sulu.edittoolbar.submit - when most left button was clicked
 *
 * options:
 *  - heading - string
 *  - tabsData - dataArray needed for building tabs
 *  -
 *
 *
 */

define([], function() {

    'use strict';

    var defaults = {
            heading: '',
            template: 'default',
            instanceName: 'content',
            columnOptions: {
                disabled: false,
                data: [],
                key: null
            }
        },

        templates = {
            default: function() {
                return[
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
                                title: this.sandbox.translate('sulu.list-toolbar.column-options'),
                                disabled: false,
                                callback: function() {

                                    this.sandbox.sulu.storage.loadSettings(this.options.columnOptions.key, '', function(loadedData) {

                                        this.sandbox.dom.append('body', '<div id="column-options-overlay" />');
                                        this.sandbox.start([
                                            {
                                                name: 'column-options@husky',
                                                options: {
                                                    el: '#column-options-overlay',
                                                    data: loadedData,
                                                    hidden: false,
                                                    trigger: '.toggle'
                                                }
                                            }
                                        ]);
                                        this.sandbox.once('husky.column-options.saved', function(data) {
                                            this.sandbox.sulu.storage.saveSettings(this.options.columnOptions.key, data, this.options.columnOptions.url);
                                        }.bind(this));
                                    }.bind(this));
//
                                }.bind(this)
                            }
                        ]
                    }
                ];
            }
        };

    return {
        view: true,


        initialize: function() {

            // merge defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // load template:
            if (typeof this.options.template === 'string') {
                try {
                    this.options.template = JSON.parse(this.options.template);
                } catch (e) {
                    if (!!templates[this.options.template]) {
                        this.options.template = templates[this.options.template].call(this);
                    } else {
                        this.sandbox.logger.log('no template found!');
                    }

                }
            }

            var $container = this.sandbox.dom.createElement('<div />');
            this.html($container);

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
