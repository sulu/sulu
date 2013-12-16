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

        templates: ['/admin/content/template/content/list'],

        initialize: function() {
            this.render();
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/content/template/content/list'));

            // dropdown - showing options
            this.sandbox.start([{
                name: 'dropdown@husky',
                options: {
                    el: '#options-dropdown',
                    trigger: '.dropdown-toggle',
                    setParentDropDown: true,
                    instanceName: 'options',
                    alignment: 'right',
                    data: [
                        {
                            'id': 1,
                            'type':'delete',
                            'name': this.sandbox.translate('public.delete')
                        }
                    ]
                }
            }]);

            // datagrid
            this.sandbox.start([{
                name: 'datagrid@husky',
                options: {
                    el: this.sandbox.dom.find('#content-list', this.$el),
                    url: '/admin/api/nodes?depth=1',
                    pagination: false,
                    selectItem: {
                        type: 'checkbox'
                    },
                    removeRow: false,
                    tableHead: [

                        {content: this.sandbox.translate('content.contents.changer'), attribute: 'changer'},
                        {content: this.sandbox.translate('contact.contents.changed'), attribute: 'changed'},
                        {content: this.sandbox.translate('contact.contents.title'), attribute: 'title'}

                    ]
                }
            }]);

            // navigate to edit contact
            this.sandbox.on('husky.datagrid.item.click', function(id) {
                this.sandbox.emit('sulu.content.contents.load', id);
            }, this);


            this.sandbox.on('husky.dropdown.options.clicked',  function() {
                this.sandbox.emit('husky.dropdown.options.toggle');
            }, this);

            // optionsmenu clicked
            this.sandbox.on('husky.dropdown.options.item.click', function(event) {
                if (event.type === "delete") {
                    this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                        this.sandbox.emit('sulu.content.contents.delete', ids);
                    }.bind(this));
                }
            },this);

            // add button in headerbar
            this.sandbox.emit('husky.header.button-type', 'add');

            this.sandbox.on('husky.button.add.click', function() {
                this.sandbox.emit('sulu.content.contents.new');
            }, this);
        }
    };
});
