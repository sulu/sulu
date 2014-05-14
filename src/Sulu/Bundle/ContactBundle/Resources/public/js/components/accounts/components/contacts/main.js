/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app-config'], function(AppConfig) {

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
        },

        /**
         * returns the accounttype
         * @returns {number}
         */
            getAccountType = function() {
            var typeInfo, compareAttribute,
                accountType = 0,
                accountTypes = AppConfig.getSection('sulu-contact').accountTypes; // get account types

            // if newly created account, get type id
            if (!!this.options.data.id) {
                typeInfo = this.options.data.type;
                compareAttribute = 'id';
            } else if (!!this.options.accountTypeName) {
                typeInfo = this.options.accountTypeName;
                compareAttribute = 'name';
            } else {
                typeInfo = 0;
                compareAttribute = 'id';
            }

            // get account type information
            this.sandbox.util.foreach(accountTypes, function(type) {
                if (type[compareAttribute] === typeInfo) {
                    accountType = type;
                    this.options.data.type = type.id;
                    return false; // break loop
                }
            }.bind(this));

            return accountType;
        },

        listTemplate = function() {
            return [
//                {
//                    id: 'delete',
//                    icon: 'bin',
//                    title: 'delete',
//                    disabled: true,
//                    callback: function() {
//                        this.sandbox.emit('sulu.list-toolbar.delete');
//                    }.bind(this)
//                }
//                ,
                {
                    id: 'settings',
                    icon: 'cogwheel',
                    items: [
                        {
                            title: this.sandbox.translate('list-toolbar.column-options'),
                            disabled: false,
                            callback: function() {
                                var instanceName;

                                this.sandbox.dom.append('body', '<div id="column-options-overlay" />');
                                this.sandbox.start([
                                    {
                                        name: 'column-options@husky',
                                        options: {
                                            el: '#column-options-overlay',
                                            data: this.sandbox.sulu.getUserSetting(this.options.columnOptions.key),
                                            hidden: false,
                                            instanceName: this.options.instanceName,
                                            trigger: '.toggle'
                                        }
                                    }
                                ]);
                                instanceName = this.options.instanceName ? this.options.instanceName + '.' : '';
                                this.sandbox.once('husky.column-options.' + instanceName + 'saved', function(data) {
                                    this.sandbox.sulu.saveUserSetting(this.options.columnOptions.key, data, this.options.columnOptions.url);
                                }.bind(this));
                            }.bind(this)
                        }
                    ]
                }
            ]
        },


        /**
         * sets headline to the current title input
         * @param accountType
         */
            setHeadlines = function(accountType) {
            var title = this.sandbox.translate(this.options.data.name),
                breadcrumb = [
                    {title: 'navigation.contacts'},
                    {title: 'contact.accounts.title', event: 'sulu.contacts.accounts.list'},
                    {title: accountType.translation + ' #' + this.options.data.id}
                ];

            this.sandbox.emit('sulu.header.set-title', title);
            this.sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
        };

    return {
        view: true,

//        fullSize: {
//            width: true
//        },

        templates: ['/admin/contact/template/contact/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);

            this.accountType = getAccountType.call(this);
            setHeadlines.call(this, this.accountType);
        },


        render: function() {

            this.sandbox.emit('sulu.', this.options.account);

            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/contact/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'accountsContactsFields', '/admin/api/contacts/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'contacts',
                    inHeader: true,
                    selectItem: {
                        type: null
                    },
                    template: listTemplate
                },
                {
                    el: this.sandbox.dom.find('#people-list', this.$el),
                    url: '/admin/api/accounts/' + this.options.data.id + '/contacts?flat=true',
                    fullWidth: true,
                    selectItem: {
                        type: 'checkbox'
                    },
                    removeRow: false,
                    searchInstanceName: 'contacts',
                    sortable: true
                }
            );
        }
    };
});
