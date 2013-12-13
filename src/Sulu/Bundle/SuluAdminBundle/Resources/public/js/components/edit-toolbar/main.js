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
            default:
                function() {
                    return[
                        {
                            id: 'save-button',
                            icon: 'floppy-saved',
                            iconSize: 'large',
                            class: 'highlight',
                            callback: function() {
                                this.sandbox.emit('sulu.edittoolbar.save');
                            }.bind(this)
                        },
                        {
                            icon: 'cogwheel',
                            iconSize: 'large',
                            class: 'highlight-gray',
                            group: 'right',
                            items: [
                                {
                                    title:'delete',
                                    callback: function() {
                                        this.sandbox.emit('sulu.edittoolbar.delete');
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
            this.options = this.sandbox.util.extend(true, {}, this.options, defaults);

            var template;

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

            this.sandbox.start([
                {
                    name: 'edit-toolbar@husky',
                    options: {
                        el: this.options.el,
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
//            var instanceName = (this.options.instanceName && this.options.instanceName!=='') ? this.options.instanceName+'.' : '';
//            // load component on start
//            this.sandbox.on('husky.tabs.'+instanceName+'initialized', this.startTabComponent.bind(this));
//            // load component after click
//            this.sandbox.on('husky.tabs.'+instanceName+'item.select', this.startTabComponent.bind(this));
        }

    };
});
