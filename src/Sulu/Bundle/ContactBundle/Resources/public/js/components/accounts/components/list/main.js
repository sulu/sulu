/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'mvc/relationalstore'
], function(RelationalStore) {

    'use strict';


    var bindCustomEvents = function() {
            // navigate to edit contact
            this.sandbox.on('husky.datagrid.item.click', function(item) {
                this.sandbox.emit('sulu.contacts.accounts.load', item);
            }, this);

            // delete clicked
            this.sandbox.on('sulu.list-toolbar.delete', function() {
                this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                    this.sandbox.emit('sulu.contacts.accounts.delete', ids);
                }.bind(this));
            }, this);

            // add clicked
            this.sandbox.on('sulu.list-toolbar.add', function() {
                this.sandbox.emit('sulu.contacts.accounts.new');
            }, this);

        },

        addNewAccount = function(type) {
            this.sandbox.emit('sulu.contacts.accounts.new', type);
        };

    return {

        view: true,

        templates: ['/admin/contact/template/account/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);
        },

        render: function() {

            RelationalStore.reset(); //FIXME really necessary?

            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/account/list'));


            this.sandbox.start([
                {
                    name: 'tabs@husky',
                    options: {
                        el: '#filter-tabs',
                        data: {
                            items: [
                                {
                                    title: 'test1'
                                },
                                {
                                    title: 'test2'
                                }
                            ]
                        }
                    }
                }
            ]);

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'accountsFields', '/admin/api/accounts/fields',
                {
                    el: '#list-toolbar-container',
                    instanceName: 'accounts',
                    parentTemplate: 'default',
                    template: function() {
                        return [
                            {
                                id: 'add',
                                icon: 'circle-plus',
                                class: 'highlight',
                                title: this.sandbox.translate('sulu.list-toolbar.add'),
                                items: [
//                                    {
//                                        id: 'add-basic',
//                                        title: this.sandbox.translate('contact.account.add-basic'),
//                                        callback: addNewAccount.bind(this, 'basic')
//                                    },
                                    {
                                        id: 'add-lead',
                                        title: this.sandbox.translate('contact.account.add-lead'),
                                        callback: addNewAccount.bind(this, 'lead')
                                    },
                                    {
                                        id: 'add-customer',
                                        title: this.sandbox.translate('contact.account.add-customer'),
                                        callback: addNewAccount.bind(this, 'customer')
                                    },
                                    {
                                        id: 'add-supplier',
                                        title: this.sandbox.translate('contact.account.add-supplier'),
                                        callback: addNewAccount.bind(this, 'supplier')
                                    }
                                ],
                                callback: function() {
                                    this.sandbox.emit('sulu.list-toolbar.add');
                                }.bind(this)
                            }
                        ];
                    }

                },
                {
                    el: this.sandbox.dom.find('#companies-list', this.$el),
                    url: '/admin/api/accounts?flat=true',
                    sortable: true,
                    selectItem: {
                        type: 'checkbox'
                    }
                });

        }

    };
});
