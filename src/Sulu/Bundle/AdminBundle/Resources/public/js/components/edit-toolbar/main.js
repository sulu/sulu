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
            parentTemplate: null,
            instanceName: 'content',
            changeStateCallback: null,
            parentChangeStateCallback: null,
            containerSelector: '#edit-toolbar-container'
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
                        'position': 1,
                        disabled: true,
                        callback: function() {
                            this.sandbox.emit('sulu.edit-toolbar.save');
                        }.bind(this)
                    },
                    {
                        icon: 'cogwheel',
                        iconSize: 'large',
                        group: 'left',
                        id: 'options-button',
                        position: 30,
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
            }
        },


        changeStateCallbacks = {
            default: function(saved) {
                if (!!saved) {
                    this.sandbox.emit('husky.edit-toolbar.item.disable', 'save-button');
                } else {
                    this.sandbox.emit('husky.edit-toolbar.item.enable', 'save-button', false);
                }
            }
        },

        getTemplate = function(template) {
            var templateObj = template;
            if (typeof template === 'string') {
                try {
                    templateObj = JSON.parse(template);
                } catch (e) {
                    if (!!templates[template]) {
                        templateObj = templates[template].call(this);
                    } else {
                        this.sandbox.logger.log('no template found!');
                    }
                }
            } else if (typeof templateObj === 'function') {
                templateObj = template();
            }
            return templateObj;
        },

        getChangeStateCallback = function(template) {
            if (!!changeStateCallbacks[template]) {
                return changeStateCallbacks[template];
            } else {
                this.sandbox.logger.log('no template found!');
            }
        };

    return {
        view: true,

        initialize: function() {
            // merge defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.$container = this.sandbox.dom.$(this.options.containerSelector);

            this.buildTemplate(this.options.template, this.options.parentTemplate);
            this.startComponent();

            //start component with the right dimensions
            this.sandbox.emit('sulu.app.content.get-dimensions', this.resizeListener.bind(this));

            // bind events (also initializes first component)
            this.bindCustomEvents();
        },

        /**
         * Builds the template which gets used by the husky-component
         */
        buildTemplate: function(template, parentTemplate) {
            this.options.template = getTemplate.call(this, template);
            if (!this.options.changeStateCallback || typeof this.options.changeStateCallback !== 'function') {
                this.options.changeStateCallback = getChangeStateCallback.call(this, template);
            }

            //if a parentTemplate is set merge it with the current template
            if (this.options.parentTemplate !== null) {

                this.options.parentTemplate = getTemplate.call(this, parentTemplate);
                if (!this.options.parentChangeStateCallback || typeof this.options.parentChangeStateCallback !== 'function') {
                    this.options.parentChangeStateCallback = getChangeStateCallback.call(this, parentTemplate);
                }

                this.options.template = this.options.template.concat(this.options.parentTemplate);
            }
        },

        /**
         * Starts the husky-component
         */
        startComponent: function() {
            var $container = this.sandbox.dom.createElement('<div />');
            this.html($container);

            this.sandbox.start([
                {
                    name: 'edit-toolbar@husky',
                    options: {
                        el: $container,
                        pageFunction: this.options.pageFunction,
                        data: this.options.template
                    }
                }
            ]);
        },

        /**
         * listens to tab events
         */
        bindCustomEvents: function() {
            var instanceName = (this.options.instanceName && this.options.instanceName !== '') ? this.options.instanceName + '.' : '';
            // load component on start
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'state.change', this.changeState.bind(this));

            //make sure container keeps the width of the content
            this.sandbox.on('sulu.app.content.dimensions-changed', this.resizeListener.bind(this));

            //abstract husky event
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'items.set', function(id, items) {
                this.sandbox.emit('husky.edit-toolbar.items.set', id, items);
            }.bind(this));

            //abstract husky event
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'button.set', function(id, object) {
                this.sandbox.emit('husky.edit-toolbar.button.set', id, object);
            }.bind(this));

            //abstract husky event
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'item.loading', function(id) {
                this.sandbox.emit('husky.edit-toolbar.item.loading', id);
            }.bind(this));

            //abstract husky event
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'item.change', function(id, name) {
                this.sandbox.emit('husky.edit-toolbar.item.change', id, name);
            }.bind(this));

            //abstract husky event
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'item.show', function(id, name) {
                this.sandbox.emit('husky.edit-toolbar.item.show', id, name);
            }.bind(this));

            //abstract husky event
            this.sandbox.on('sulu.edit-toolbar.' + instanceName + 'item.enable', function(id, highlight) {
                this.sandbox.emit('husky.edit-toolbar.item.enable', id, highlight);
            }.bind(this));
        },

        /**
         * Applies new dimensions to the component container
         * @param dimensions {Object} The dimensions with width and left to apply
         */
        resizeListener: function(dimensions) {
            this.sandbox.dom.width(this.$el, dimensions.width);
            this.sandbox.dom.css(this.$container, {'margin-left': dimensions.left + 'px'});
        },

        changeState: function(type, saved) {
            if (typeof this.options.changeStateCallback === 'function') {
                this.options.changeStateCallback.call(this, saved, type);
            }
            if (typeof this.options.parentChangeStateCallback === 'function') {
                this.options.parentChangeStateCallback.call(this, saved, type);
            }
        }
    };
});
