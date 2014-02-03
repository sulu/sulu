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
                        position: 99,
                        items: [
                            {
                                title: this.sandbox.translate('sulu.edit-toolbar.delete'),
                                callback: function() {
                                    this.sandbox.emit('sulu.edit-toolbar.delete');
                                }.bind(this)
                            }
                        ]
                    }
                ];
            },
            defaultPreview: function() {
                var defaults = templates.default.call(this);
                defaults.splice(1, 0, {
                    icon: 'eye-open',
                    iconSize: 'large',
                    group: 'right',
                    position: 1,
                    items: [
                        {
                            title: this.sandbox.translate('sulu.edit-toolbar.new-window'),
                            callback: function() {
                                this.sandbox.emit('sulu.edit-toolbar.preview.new-window');
                            }.bind(this)
                        }
                    ]
                });
                return defaults;
            }
        },

        changeStateCallbacks = {
            default: function(saved, type) {
                if (!!saved) {
                    this.sandbox.emit('husky.edit-toolbar.item.disable', 'save-button');
                } else {
                    this.sandbox.emit('husky.edit-toolbar.item.enable', 'save-button');
                }
            },
            defaultPreview: function(saved, type) {
                if (!!saved) {
                    this.sandbox.emit('husky.edit-toolbar.item.disable', 'save-button');
                } else {
                    this.sandbox.emit('husky.edit-toolbar.item.enable', 'save-button');
                }
            }
        },

        changePaddingLeft = function(paddingLeft) {
            this.sandbox.dom.css('#edit-toolbar-container', {'padding-left': 50 + paddingLeft});

            emitSizeChange.call(this);
        },

        resizeListener = function() {
            var contentWidth = this.sandbox.dom.width('main#content'),
                $toolbar = this.$find('#edit-toolbar');


            if (!this.currentContentWidth || this.currentContentWidth !== contentWidth) {
                this.sandbox.dom.width($toolbar, contentWidth - 50);
                this.currentContentWidth = contentWidth;
            }
        };

    return {
        view: true,

        initialize: function() {


            // merge defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

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
                        el: this.$el,
                        pageFunction: this.options.pageFunction,
                        data: this.options.template
                    }
                }
            ]);

            // resize toolbar
            resizeListener.call(this);

            // bind events (also initializes first component)
            this.bindCustomEvents();
            this.bindDomEvents();
        },

        /**
         * listens to tab events
         */
        bindDomEvents: function() {
            // change size of edit-toolbar
            this.sandbox.dom.on(this.sandbox.dom.window, 'resize', resizeListener.bind(this));
        },

        /**
         * listens to tab events
         */
        bindCustomEvents: function() {
            var instanceName = (this.options.instanceName && this.options.instanceName !== '') ? this.options.instanceName + '.' : '';
            // load component on start
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'state.change', this.changeState.bind(this));
            this.sandbox.on('husky.navigation.size.changed', changePaddingLeft.bind(this));
        },

        changeState: function(type, saved) {
            this.options.changeStateCallback.call(this, saved, type);
        }
    };
});
