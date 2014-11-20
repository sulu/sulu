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
 * @param {Object} [options] Configuration object
 */
define([],
    function() {

        'use strict';

        var defaults = {
                instanceName: 'contact',
                template: 'default',
                parentTemplate: null,
                listener: 'default',
                parentListener: null
               // settings: []
            },

            constants = {
                bottomToolbarId: '#bottom-list-toolbar'
            },

            /**
             * Template for bottom toolbar
             * @returns {*[]}
             */
            listTemplate = {
                default: function(){
                    return [
                        {
                            id: 'add',
                            icon: 'plus-circle',
                            class: 'highlight-white',
                            position: 10,
                            callback: function() {
                                this.sandbox.emit('sulu.bottom-toolbar.add');
                            }.bind(this)
                        },
                        {
                            id: 'delete',
                            icon: 'trash-o',
                            position: 20,
                            disabled: true,
                            callback: function() {
                                this.sandbox.emit('sulu.bottom-toolbar.delete');
                            }.bind(this)
                        },
                        {
                            id: 'settings',
                            icon: 'magic',
                            position: 30,
                            disabled: true
                        //    items:  this.sandbox.emit('sulu.bottom-toolbar.magic')
//                            callback: function() {
//                                this.sandbox.emit('sulu.bottom-toolbar.magic');
//                            }.bind(this)
                        }
                    ];
                }
            };

//            defineSettingsItems = function() {
//                var instanceName = this.options.instanceName ? this.options.instanceName + '.' : '',
//                    postfix;
//                this.sandbox.on('husky.datagrid.number.selections', function(number) {
//                    postfix = number > 0 ? 'enable' : 'disable';
//                    this.sandbox.emit('husky.toolbar.' + instanceName + 'item.' + postfix, 'delete', false);
//                }.bind(this));
//            };

        return {
            initialize: function() {

                this.options = this.sandbox.util.extend({}, defaults, this.options);
                this.instanceName = this.options.instanceName;

                this.render();
                this.bindCustomEvents();

            },

            bindCustomEvents: function() {
                var instanceName = this.options.instanceName ? this.options.instanceName + '.' : '',
                    postfix;
                this.sandbox.on('husky.datagrid.number.selections', function(number) {
                    postfix = number > 0 ? 'enable' : 'disable';
                    this.sandbox.emit('husky.toolbar.' + instanceName + 'item.' + postfix, 'delete', false);
                }.bind(this));
//
//                // TODO: listen to settings event
//                this.sandbox.on('settingsevents', function(data) {
//                    this.sandbox.emit('husky.toolbar.' + instanceName + 'item.sets', data, 'settings');
//                }.bind(this));

            },

            render: function() {
//                // TODO: check if this.options.template is set && if it is a string OR object
//                var data =  listTemplate[this.options.template].call(this);
//                data = this.sandbox.util.extend(true, {}, data, { settings: {
//                    items: this.options.settings
//                }});


                if (this.options.items.hasOwnProperty('add')) {
                    // TODO: add items to add
                   // var first = null;
                    //this.first = this.options.items.add;
                    listTemplate.default[0]= this.options.items.add;
                }

                if (this.options.items.hasOwnProperty('settings')) {
                    // TODO: add items to add
                    //this.options.items[1]
                }
                else {

                }
                // init toolbar
                this.sandbox.start([
                    {
                        name: 'toolbar@husky',
                        options: {
                            el: constants.bottomToolbarId,
//                            data: data,
                            data: listTemplate.default.call(this),
                            instanceName: this.options.instanceName
                        }
                    }
                ]);
            }
        };
    });
