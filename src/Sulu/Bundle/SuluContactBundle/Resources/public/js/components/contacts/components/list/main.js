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
            this.sandbox.emit('sulu.sidebar.set-widget', '/admin/widget-groups/contact-info?contact=' + item);
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

        layout: {
            content: {
                width: 'max',
                leftSpace: false,
                rightSpace: false
            },
            sidebar: {
                width: 'fixed',
                cssClasses: 'sidebar-padding-50'
            }
        },

        header: {
            title: 'contact.contacts.title',
            noBack: true,

            breadcrumb: [
                {title: 'navigation.contacts'},
                {title: 'contact.contacts.title'}
            ]
        },

        templates: ['/admin/contact/template/contact/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/contact/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'contactsFields', '/admin/api/contacts/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'contacts',
                    inHeader: true
                },
                {
                    el: this.sandbox.dom.find('#people-list', this.$el),
                    url: '/admin/api/contacts?flat=true',
                    searchInstanceName: 'contacts',
                    searchFields: ['fullName'],
                    resultKey: 'contacts',
                    viewOptions: {
                        table: {
                            icons: [
                                {
                                    icon: 'pencil',
                                    column: 'fullName',
                                    align: 'left',
                                    callback: function(id) {
                                        this.sandbox.emit('sulu.contacts.contacts.load', id);
                                    }.bind(this)
                                }
                            ],
                            fullWidth: true
                        }
                    }
                }
            );
        }
    };
});
