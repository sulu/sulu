/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'mvc/relationalstore',
    'app-config'
], function(RelationalStore, AppConfig) {

    'use strict';


    var defaults = {
            accountType: null
        },
        bindCustomEvents = function() {
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

        dataUrlAddition = '',

        /**
         * Generates the options for the tabs in the header
         * @returns {object} tabs options
         */
        getTabsOptions = function() {
            var returnObj = {
                callback: selectFilter.bind(this),
                preselect: false,
                preselector: 'position',
                data: null
            },
            items, i, len,
            accountTypes = AppConfig.getSection('sulu-contact').accountTypes,
            accountType;

            // generate items
            items = [
                {
                    id: 'all',
                    title: this.sandbox.translate('public.all')
                }
            ];
            for (i = 0, len = accountTypes.length; ++i < len;) {
                items.push({
                    id: accountTypes[i].id,
                    name: accountTypes[i].name,
                    title: this.sandbox.translate(accountTypes[i].translation)
                });
            }

            // get selected account-type
            if (!!this.options.accountType) {
                for (i = 0, len = accountTypes.length; ++i < len;) {
                    if (accountTypes[i].name === this.options.accountType) {
                        accountType = accountTypes[i];
                        break;
                    }
                }
                dataUrlAddition += '&searchFields=type&search=' + accountType.id;
            }

            returnObj.data = {items: items};
            returnObj.preselect = (!!accountType) ? accountType.id + 1 : false;

            return returnObj;
        },

        selectFilter = function(item) {
            var type = null;

            if (item.id !== 'all') {
                type = item.id;
            }
            this.sandbox.emit('husky.datagrid.url.update', {'type': type});
            this.sandbox.emit('sulu.contacts.accounts.list', item.name, true); // change url, but do not reload
        },

        addNewAccount = function(type) {
            this.sandbox.emit('sulu.contacts.accounts.new', type);
        };

    return {

        view: true,

        fullSize: {
            width: true
        },

        header: function() {
            return {
                title: 'contact.accounts.title',
                noBack: true,

                tabs: {
                    fullControl: true,
                    options: getTabsOptions.call(this)
                },

                breadcrumb: [
                    {title: 'navigation.contacts'},
                    {title: 'contact.accounts.title'}
                ]
            };
        },

        templates: ['/admin/contact/template/account/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);
        },

        render: function() {

            RelationalStore.reset(); //FIXME really necessary?

            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/account/list'));

            var items, i, len,
                dataUrlAddition = '',
                accountType,
            // get account types
                accountTypes = AppConfig.getSection('sulu-contact').accountTypes;


            // define string urlAddition if accountType is set
            if (!!this.options.accountType) {
                for (i = 0, len = accountTypes.length; ++i < len;) {
                    if (accountTypes[i].name === this.options.accountType) {
                        accountType = accountTypes[i];
                        break;
                    }
                }
                dataUrlAddition += '&type=' + accountType.id;
            }

            // -- initialize filter tabs --
            // define items array
            items = [
                {
                    id: 'all',
                    title: this.sandbox.translate('public.all')
                }
            ];
            for (i = 0, len = accountTypes.length; ++i < len;) {
                items.push({
                    id: accountTypes[i].id,
                    name: accountTypes[i].name,
                    title: this.sandbox.translate(accountTypes[i].translation)
                });
            }
            // start tabs component
            this.sandbox.start([
                {
                    name: 'tabs@husky',
                    options: {
                        el: '#filter-tabs',
                        callback: selectFilter.bind(this),
                        preselect: accountType ? accountType.id + 1 : false,
                        preselector: 'position',
                        data: { items: items }

                    }
                }
            ]);

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'accountsFields', '/admin/api/accounts/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'accounts',
                    parentTemplate: 'default',
                    inHeader: true,
                    template: function() {
                        return [
                            {
                                id: 'add',
                                icon: 'circle-plus',
                                class: 'highlight-white',
                                title: this.sandbox.translate('sulu.list-toolbar.add'),
                                items: [
                                    {
                                        id: 'add-basic',
                                        title: this.sandbox.translate('contact.account.add-basic'),
                                        callback: addNewAccount.bind(this, 'basic')
                                    },
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
                    url: '/admin/api/accounts?flat=true' + dataUrlAddition,
                    viewOptions: {
                        table: {
                            fullWidth: true
                        }
                    }
                });
        }
    };
});
