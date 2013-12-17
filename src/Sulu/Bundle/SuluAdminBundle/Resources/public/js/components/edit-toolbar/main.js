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
            instanceName: 'content'
        },

        templates = {
            default: function() {
                return[
                    {
                        id: 'save-button',
                        icon: 'floppy',
                        disabledIcon: 'floppy-saved',
                        iconSize: 'large',
                        class: 'highlight',
                        disabled: true,
                        callback: function() {
                            this.sandbox.emit('sulu.edit-toolbar.save');
                        }.bind(this)
                    },
                    {
                        icon: 'cogwheel',
                        iconSize: 'large',
                        class: 'highlight-gray',
                        group: 'right',
                        items: [
                            {
                                title: 'delete',
                                callback: function() {
                                    this.sandbox.emit('sulu.edit-toolbar.delete');
                                }.bind(this)
                            }
                        ]
                    }
                ];
            }
        },

        changeStateCallbacks = {
            default: function(saved, type) {
                if (!!saved) {
                    this.sandbox.emit('husky.edit-toolbar.item.disable', 'save-button');
                } else {
                    this.sandbox.emit('husky.edit-toolbar.item.enable', 'save-button');
                }
            }
        };

    return {
        view: true,

        initialize: function() {
            // merge defaults
            this.options = this.sandbox.util.extend(true, {}, this.options, defaults);

            var template = this.options.template;

            // load template:
            if (typeof template === 'string') {
                try {
                    this.options.template = JSON.parse(template);
                } catch (e) {
                    if (!!templates[template]) {
                        this.options.template = templates[template].call(this);
                    } else {
                        this.sandbox.logger.log('no template found!');
                    }
                }
            }

            if (!this.options.changeStateCallback || typeof this.options.changeStateCallback !== 'function') {
                if (!!changeStateCallbacks[template]) {
                    this.options.changeStateCallback = changeStateCallbacks[template];
                } else {
                    this.sandbox.logger.log('no template found!');
                }
            }

            this.sandbox.start([
                {
                    name: 'edit-toolbar@husky',
                    options: {
                        el: this.options.el,
                        pageFunction: this.options.pageFunction,
                        data: this.options.template
                    }
                }
            ]);

            // bind events (also initializes first component)
            this.bindCustomEvents();
        },

        /**
         * listens to tab events
         */
        bindCustomEvents: function() {
            var instanceName = (this.options.instanceName && this.options.instanceName !== '') ? this.options.instanceName + '.' : '';
            // load component on start
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'state.change', this.changeState.bind(this));
        },

        changeState: function(type, saved) {
            this.options.changeStateCallback.call(this, saved, type);
        }
    };
});
