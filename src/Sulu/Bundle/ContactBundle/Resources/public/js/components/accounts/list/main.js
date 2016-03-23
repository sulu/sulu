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
    'services/sulucontact/account-delete-dialog'
], function(AccountManager, AccountRouter, DeleteDialog) {

    'use strict';

    var constants = {
            datagridInstanceName: 'accounts',
            listViewStorageKey: 'accountListView',
            listPaginationStorageKey: 'accountListPagination'
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
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'table');
                this.sandbox.sulu.saveUserSetting(constants.listPaginationStorageKey, 'dropdown');

                // this isn't a perfect strategy because datagrid is rerendered on all three events
                // todo: find a better strategy to change pagination and view-decorator and load first page
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.view.change', 'table');
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.pagination.change', 'dropdown');
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.change.page', 1);

                this.sandbox.stickyToolbar.reset(this.$el);
            }.bind(this));

            this.sandbox.on('sulu.toolbar.change.cards', function() {
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'datagrid/decorators/card-view');
                this.sandbox.sulu.saveUserSetting(constants.listPaginationStorageKey, 'infinite-scroll');

                // this isn't a perfect strategy because datagrid is rerendered on all three events
                // todo: find a better strategy to change pagination and view-decorator and load first page
                this.sandbox.emit(
                    'husky.datagrid.' + constants.datagridInstanceName + '.view.change', 'datagrid/decorators/card-view'
                );
                this.sandbox.emit(
                    'husky.datagrid.' + constants.datagridInstanceName + '.pagination.change', 'infinite-scroll'
                );
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.change.page', 1);

                this.sandbox.stickyToolbar.reset(this.$el);
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

        stickyToolbar: true,

        layout: {
            content: {
                width: 'max'
            }
        },

        header: {
            noBack: true,

            title: 'contact.accounts.title',
            underline: false,

            toolbar: {
                buttons: {
                    add: {},
                    deleteSelected: {},
                    export: {
                        options: {
                            urlParameter: {
                                flat: true
                            },
                            url: '/admin/api/accounts.csv'
                        }
                    }
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
                searchFields: ['name', 'mainEmail'],
                resultKey: 'accounts',
                instanceName: constants.datagridInstanceName,
                actionCallback: actionCallback.bind(this),
                view: this.sandbox.sulu.getUserSetting(constants.listViewStorageKey) || 'datagrid/decorators/card-view',
                pagination: this.sandbox.sulu.getUserSetting(constants.listPaginationStorageKey) || 'infinite-scroll',
                viewOptions: {
                    table: {
                        actionIconColumn: 'name',
                        noImgIcon: 'fa-home'
                    },
                    'datagrid/decorators/card-view': {
                        imageFormat: '100x100-inset',
                        fields: {
                            picture: 'logo',
                            title: ['name']
                        },
                        icons: {
                            picture: 'fa-home'
                        }
                    }
                },
                paginationOptions: {
                    'infinite-scroll': {
                        reachedBottomMessage: 'public.reached-list-end'
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
