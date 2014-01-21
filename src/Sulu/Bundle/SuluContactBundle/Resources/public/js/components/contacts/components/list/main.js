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

    var bindCustomEvents = function() {
        // navigate to edit contact
        this.sandbox.on('husky.datagrid.item.click', function(item) {
            this.sandbox.emit('sulu.contacts.contacts.load', item);
        }, this);

        // delete clicked
        this.sandbox.on('sulu.list-toolbar.delete', function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.emit('sulu.contacts.contacts.delete', ids);
            }.bind(this));
        }, this);

        // add clicked
        this.sandbox.on('sulu.list-toolbar.add', function() {
            this.sandbox.emit('sulu.contacts.contacts.new');
        }, this);
    };

    return {

        view: true,

        templates: ['/admin/contact/template/contact/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/contact/list'));

            // init list-toolbar
            this.sandbox.sulu.initListToolbar.call(this, '#list-toolbar-container','fieldsContacts', '/admin/api/contacts/fields');

            // datagrid
            this.sandbox.start([
                {
                    name: 'datagrid@husky',
                    options: {
                        el: this.sandbox.dom.find('#people-list', this.$el),
                        url: '/admin/api/contacts?flat=true&fields=id,title,firstName,lastName,position',
                        pagination: false,
                        selectItem: {
                            type: 'checkbox'
                        },
                        removeRow: false,
                        searchInstanceName:'content',
                        tableHead: [

                            {content: this.sandbox.translate('contact.contacts.contactTitle')},
                            {content: this.sandbox.translate('contact.contacts.firstName')},
                            {content: this.sandbox.translate('contact.contacts.lastName')},
                            {content: this.sandbox.translate('contact.contacts.position')}
                        ],
                        excludeFields: ['id']
                    }
                }
            ]);
        }
    };
});
