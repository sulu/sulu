/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    return {

        view: true,

        templates: ['/admin/content/template/content/column'],

        initialize: function() {
            this.render();
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.column-navigation.add', function(parent) {
                this.sandbox.emit('sulu.content.contents.new', parent);
            }, this);

            this.sandbox.on('husky.column-navigation.edit', function(item) {
                this.sandbox.emit('sulu.content.contents.load', item.id);
            }, this);

            this.sandbox.on('husky.tabs.item.select', function(item) {
                if (!!item.action) {
                    this.sandbox.emit('sulu.router.navigate', item.action);
                }
            }, this);
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/content/template/content/column'));

            // datagrid && tabs
            this.sandbox.start([
                {
                    name: 'column-navigation@husky',
                    options: {
                        el: '#content-column',
                        url: '/admin/api/nodes?depth=1&webspace=' + this.options.webspace + '&language=' + this.options.language
                    }
                },
                {
                    name: 'tabs@husky',
                    options: {
                        el: '#list-tabs',
                        data: {
                            "items": [
                                {
                                    "id": 1,
                                    "title": "Content"
                                },
                                {
                                    "id": 2,
                                    "title": "Home page",
                                    "action": 'content/contents/' + this.options.webspace + '/' + this.options.language + '/edit:index/content'
                                }
                            ]
                        }
                    }
                }
            ]);

            this.bindCustomEvents();
        }
    };
});
