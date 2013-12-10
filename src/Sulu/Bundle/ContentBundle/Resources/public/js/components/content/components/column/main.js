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

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/content/template/content/column'));

            // datagrid
            this.sandbox.start([
                {
                    name: 'column-navigation@husky',
                    options: {
                        el: '#content-column',
                        url: '/admin/api/nodes?depth=1'
                    }
                }
            ]);

            this.sandbox.on('husky.column.navigation.add', function(parent) {
                this.sandbox.emit('sulu.content.contents.new', parent);
            }.bind(this));

            this.sandbox.on('husky.column.navigation.edit', function(item) {
                this.sandbox.emit('sulu.content.contents.load', item.id);
            }.bind(this));
        }
    };
});
