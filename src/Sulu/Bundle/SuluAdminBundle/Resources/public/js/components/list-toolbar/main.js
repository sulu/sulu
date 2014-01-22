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

        getObjectIds = function(array, swap) {
            var temp = swap ? {} : [], i;
            for (i=0;i<array.length;i++) {
                if (swap) {
                    temp[array[i].id] = i;
                } else {
                    temp.push(array[i].id);
                }
            }
            return temp;
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
                                title: this.sandbox.translate('list-toolbar.column-options'),
                                disabled: false,
                                callback: function() {

                                    var userFields = this.sandbox.sulu.getUserSetting(this.options.columnOptions.key),
                                        serverFields = this.options.columnOptions.data,
                                        settings = [],
                                        serverindex, userKeys, serverKeys, serverKeysSwap;

                                    if (userFields) {
                                        serverKeys = getObjectIds.call(this, serverFields);
                                        serverKeysSwap = getObjectIds.call(this, serverFields, true);
                                        userKeys = getObjectIds.call(this, userFields);

                                        // keep all user settings if they still exist
                                        this.sandbox.util.foreach(userKeys, function(key, index) {
                                            serverindex = serverKeys.indexOf(key);
                                            if (serverindex >= 0) {
                                                // replace translation
                                                userFields[index].translation = serverFields[serverindex].translation;
                                                // add to result
                                                settings.push(userFields[index]);
                                                // remove from server keys
                                                serverKeys.splice(serverindex,1);
                                            }
                                        }.bind(this));
                                        // add new ones
                                        this.sandbox.util.foreach(serverKeys, function(key) {
                                            settings.push(serverFields[serverKeysSwap[key]]);
                                        }.bind(this));
                                    } else {
                                        settings = serverFields;
                                    }





                                    this.sandbox.dom.append('body', '<div id="column-options-overlay" />');
                                    this.sandbox.start([
                                        {
                                            name: 'column-options@husky',
                                            options: {
                                                el: '#column-options-overlay',
                                                data: settings,
                                                hidden: false,
                                                trigger: '.toggle'
                                            }
                                        }
                                    ]);
                                    this.sandbox.once('husky.column-options.saved', function(data) {
                                        this.sandbox.sulu.saveUserSetting(this.options.columnOptions.key, data, this.options.columnOptions.url);
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

            // parse template, if it is a string
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
