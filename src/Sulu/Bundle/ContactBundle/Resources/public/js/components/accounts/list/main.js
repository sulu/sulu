/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/sulucontact/account-manager',
    'services/sulucontact/account-router',
    'services/sulucontact/account-delete-dialog',
], function(AccountManager, AccountRouter, DeleteDialog) {

    'use strict';

    var constants = {
            datagridInstanceName: 'accounts',
            listViewStorageKey: 'accountListView'
        },

        bindCustomEvents = function() {
            // delete clicked
            this.sandbox.on('sulu.toolbar.delete', function() {
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.items.get-selected',
                    deleteCallback.bind(this));
            }, this);

            // remove from datagrid when deleted
            this.sandbox.on('sulu.contacts.account.deleted', function(accountId) {
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.record.remove', accountId);
            }, this);

            // add clicked
            this.sandbox.on('sulu.toolbar.add', function() {
                AccountRouter.toAdd();
            }, this);

            // checkbox clicked
            this.sandbox.on('husky.datagrid.' + constants.datagridInstanceName + '.number.selections', function(number) {
                var postfix = number > 0 ? 'enable' : 'disable';
                this.sandbox.emit('sulu.header.toolbar.item.' + postfix, 'deleteSelected', false);
            }, this);

            this.sandbox.on('sulu.toolbar.change.table', function() {
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.view.change', 'table');
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'table');
            }.bind(this));

            this.sandbox.on('sulu.toolbar.change.cards', function() {
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.view.change', 'decorators/cards');
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'decorators/cards');
            }.bind(this));
        },

        deleteCallback = function(ids) {
            DeleteDialog.showDialog(ids, function(deleteContacts) {
                AccountManager.delete(ids, deleteContacts);
            }.bind(this));
        },

        actionCallback = function(id) {
            AccountRouter.toEdit(id);
        };

    return {

        layout: {
            content: {
                width: 'max'
            }
        },

        header: {
            noBack: true,
            title: 'contact.accounts.title',

            toolbar: {
                buttons: {
                    add: {},
                    deleteSelected: {}
                }
            }
        },

        templates: ['/admin/contact/template/account/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);
        },

        /**
         * @returns {Object} the config object for the list-toolbar
         */
        getListToolbarConfig: function() {
            return {
                el: this.$find('#list-toolbar-container'),
                instanceName: 'accounts',
                template: this.sandbox.sulu.buttons.get({
                    accountDecoratorDropdown: {},
                    settings: {
                        options: {
                            dropdownItems: [
                                {
                                    type: 'columnOptions'
                                }
                            ]
                        }
                    }
                })
            };
        },

        /**
         * @returns {Object} the config object for the datagrid
         */
        getDatagridConfig: function() {
            return {
                el: this.sandbox.dom.find('#companies-list', this.$el),
                url: '/admin/api/accounts?flat=true',
                searchInstanceName: 'accounts',
                searchFields: ['name'],
                resultKey: 'accounts',
                instanceName: constants.datagridInstanceName,
                actionCallback: actionCallback.bind(this),
                view: this.sandbox.sulu.getUserSetting(constants.listViewStorageKey) || 'decorators/cards',
                viewOptions: {
                    table: {
                        actionIconColumn: 'name'
                    },
                    'decorators/cards': {
                        imageFormat: '100x100-inset',
                        fields: {
                            picture: 'logo',
                            title: ['name'],
                            firstInfoRow: ['city', 'countryCode'],
                            secondInfoRow: ['mainEmail'],
                        },
                        separators: {
                            title: ' ',
                            infoRow: ', '
                        },
                        icons: {
                            picture: 'fa-home',
                            firstInfoRow: 'fa-map-marker',
                            secondInfoRow: 'fa-envelope'
                        }
                    }
                }
            };
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/account/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'accounts', '/admin/api/accounts/fields',
                this.getListToolbarConfig(),
                this.getDatagridConfig(),
                'accounts',
                '#companies-list-info'
            );
        }
    };
});
