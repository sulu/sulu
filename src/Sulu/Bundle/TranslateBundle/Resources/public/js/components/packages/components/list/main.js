/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    var bindCustomEvents = function() {
        // navigate to edit contact
        this.sandbox.on('husky.datagrid.item.click', function(item) {
            this.sandbox.emit('sulu.translate.package.load', item);
        }, this);

        // delete clicked
        this.sandbox.on('sulu.list-toolbar.delete', function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.emit('sulu.translate.packages.delete', ids);
            }.bind(this));
        }, this);

        // add clicked
        this.sandbox.on('sulu.list-toolbar.add', function() {
            this.sandbox.emit('sulu.translate.package.new');
        }, this);
    };

    return {

        view: true,

        templates: ['/admin/translate/template/package/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);
        },

        render: function() {

            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/translate/template/package/list'));

            // datagrid
            this.sandbox.start([
                {
                    name: 'list-toolbar@suluadmin',
                    options: {
                        el: '#list-toolbar-container'
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: this.sandbox.dom.find('#package-list', this.$el),
                        url: '/admin/api/packages',
                        pagination: false,
                        selectItem: {
                            type: 'checkbox'
                        },
                        removeRow: false,
                        tableHead: [
                            {content: this.sandbox.translate('public.name'), attribute: "name"}
                        ]
                    }
                }
            ]);

        }
    };
});
