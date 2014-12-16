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

    var bindCustomEvents = function() {
            // navigate to edit account
            this.sandbox.on('husky.datagrid.item.click', function(id) {
                this.sandbox.emit(
                    'sulu.sidebar.set-widget',
                    '/admin/widget-groups/account-info?account=' + id
                );
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
            var items, i, index, type,
                accountTypes,
                contactSection = AppConfig.getSection('sulu-contact'),
                accountType,
                preselect;

            // check if accountTypes exist
            if (!contactSection || !contactSection.hasOwnProperty('accountTypes') ||
                contactSection.accountTypes.length < 1) {
                return false;
            }

            accountTypes = contactSection.accountTypes;
            // generate items
            items = [
                {
                    id: 'all',
                    title: this.sandbox.translate('public.all')
                }
            ];
            // parse accounts for tabs
            for (index in accountTypes) {
                if (index === 'basic') {
                    // exclude basic type from tabs
                    continue;
                }
                type = accountTypes[index];
                items.push({
                    id: parseInt(type.id, 10),
                    name: type.name,
                    title: this.sandbox.translate(type.translation)
                });
            }

            if (!!this.options.accountType) {
                for (i in accountTypes) {
                    if (i.toLowerCase() === this.options.accountType.toLowerCase()) {
                        accountType = accountTypes[i];
                        break;
                    }
                }
                if (!accountType) {
                    throw 'accountType ' + accountType + ' does not exist!';
                }
                dataUrlAddition += '&type=' + accountType.id;
            }

            preselect = (!!accountType) ? parseInt(accountType.id, 10) + 1 : false;

            return {
                callback: selectFilter.bind(this),
                preselector: 'position',
                data: {
                    items: items
                },
                preselect: preselect
            };
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

        header: function() {

            var tabs = false,
                tabOptions = getTabsOptions.call(this);

            if (tabOptions) {
                tabs = {
                    fullControl: true,
                    options: tabOptions
                };
            }

            return {
                title: 'contact.accounts.title',
                noBack: true,

                tabs: tabs,

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

            var i,
                dataUrlAddition = '',
                accountType,
            // get account types
                accountTypes = AppConfig.getSection('sulu-contact').accountTypes,
                assocAccountTypes = {};

            // create LUT for accountTypes
            for (i in accountTypes) {
                assocAccountTypes[accountTypes[i].id] = accountTypes[i];
                // get current accountType
                if (!!this.options.accountType && i.toLowerCase() === this.options.accountType.toLowerCase()) {
                    accountType = accountTypes[i];
                }
            }
            // define string urlAddition if accountType is set
            if (!!this.options.accountType) {
                if (!accountType) {
                    throw 'accountType ' + accountType + ' does not exist!';
                }
                dataUrlAddition += '&type=' + accountType.id;
            }

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'accounts', '/admin/api/accounts/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'accounts',
                    parentTemplate: 'default',
                    inHeader: true,
                    template: function() {
                        return [
                            {
                                id: 'add',
                                icon: 'plus-circle',
                                class: 'highlight-white',
                                position: 1,
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
                    resultKey: 'accounts',
                    searchInstanceName: 'accounts',
                    searchFields: ['name'],
                    contentFilters: {
                        // display account type name instead of type number
                        type: function(content) {
                            if(!!content) {
                                return this.sandbox.translate(assocAccountTypes[content].translation);
                            } else {
                                return '';
                            }
                        }.bind(this)
                    },
                    viewOptions: {
                        table: {
                            icons: [
                                {
                                    icon: 'pencil',
                                    column: 'name',
                                    align: 'left',
                                    callback: function(id) {
                                        this.sandbox.emit('sulu.contacts.accounts.load', id);
                                    }.bind(this)
                                }
                            ],
                            highlightSelected: true,
                            fullWidth: true
                        }
                    }
                });
        }
    };
});
