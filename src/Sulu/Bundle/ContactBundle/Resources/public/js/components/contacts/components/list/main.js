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

        templates: ['/admin/contact/template/contact/list'],

        initialize: function() {
            this.render();
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/contact/list'));

            this.sandbox.start([{
                name: 'list-toolbar@suluadmin',
                options: {
                    el: '#options-dropdown'
                }
            }])

            // datagrid
            this.sandbox.start([{
                name: 'datagrid@husky',
                options: {
                    el: this.sandbox.dom.find('#people-list', this.$el),
                    url: '/admin/api/contacts?flat=true&fields=id,title,firstName,lastName,position',
                    pagination: false,
                    selectItem: {
                        type: 'checkbox'
                    },
                    removeRow: false,
                    tableHead: [

                        {content: this.sandbox.translate('contact.contacts.contactTitle')},
                        {content: this.sandbox.translate('contact.contacts.firstName')},
                        {content: this.sandbox.translate('contact.contacts.lastName')},
                        {content: this.sandbox.translate('contact.contacts.position')}
                    ],
                    excludeFields: ['id']
                }
            }]);

            // navigate to edit contact
            this.sandbox.on('husky.datagrid.item.click', function(item) {
                this.sandbox.emit('sulu.contacts.contacts.load', item);
            }, this);


//            this.sandbox.on('husky.dropdown.options.clicked',  function() {
//                this.sandbox.emit('husky.dropdown.options.toggle');
//            }, this);

//            // optionsmenu clicked
//            this.sandbox.on('husky.dropdown.options.item.click', function(event) {
//                if (event.type === "delete") {
//                    this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
//                        this.sandbox.emit('sulu.contacts.contacts.delete', ids);
//                    }.bind(this));
//                }
//            },this);


            this.sandbox.on('sulu.list-toolbar.add', function() {
                console.log("whoot?");
                this.sandbox.emit('sulu.contacts.contacts.new');
            }, this);
        }
    };
});
