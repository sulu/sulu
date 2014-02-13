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
            parentChangeStateCallback: null
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
            default: function(saved, type) {
                if (!!saved) {
                    this.sandbox.emit('husky.edit-toolbar.item.disable', 'save-button');
                } else {
                    this.sandbox.emit('husky.edit-toolbar.item.enable', 'save-button');
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

            var template = this.options.template,
                parentTemplate = this.options.parentTemplate;

            // load templates:
            this.options.template = getTemplate.call(this, template);

            if (!this.options.changeStateCallback || typeof this.options.changeStateCallback !== 'function') {
                this.options.changeStateCallback = getChangeStateCallback.call(this, template);
            }

            if (this.options.parentTemplate !== null) {

                this.options.parentTemplate = getTemplate.call(this, parentTemplate);

                if (!this.options.parentChangeStateCallback || typeof this.options.parentChangeStateCallback !== 'function') {
                    this.options.parentChangeStateCallback = getChangeStateCallback.call(this, parentTemplate);
                }

                this.options.template = this.options.template.concat(this.options.parentTemplate);
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
            if (typeof this.options.changeStateCallback === 'function') {
                this.options.changeStateCallback.call(this, saved, type);
            }
            if (typeof this.options.parentChangeStateCallback === 'function') {
                this.options.parentChangeStateCallback.call(this, saved, type);
            }
        }
    };
});
