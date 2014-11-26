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
            },

            constants = {
                bottomToolbarId: '#bottom-list-toolbar'
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
                            callback: function() {
                                this.sandbox.emit('sulu.bottom-toolbar.add');
                            }.bind(this)
                        },
                        {
                            id: 'delete',
                            icon: 'trash-o',
                            position: 20,
                            callback: function() {
                                this.sandbox.emit('sulu.bottom-toolbar.delete');
                            }.bind(this)
                        },
                        {
                            id: 'settings',
                            icon: 'magic',
                            position: 30,
                            disabled: true
                        }
                    ];
                }
            };

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
            },

            render: function() {
                this.sandbox.start([
                    {
                        name: 'toolbar@husky',
                        options: {
                            el: constants.bottomToolbarId,
                            data: listTemplate.default.call(this),
                            instanceName: this.options.instanceName
                        }
                    }
                ]);
            }
        };
    });
