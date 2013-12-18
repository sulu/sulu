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
                                    title: 'import',
                                    disabled: true
                                },
                                {
                                    title: 'export',
                                    disabled: true
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

            this.sandbox.start([
                {
                    name: 'toolbar@husky',
                    options: {
                        hasSearch: true,
                        el: this.options.el,
                        data: this.options.template,
                        instanceName: this.options.instanceName
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

        }
    };
});
