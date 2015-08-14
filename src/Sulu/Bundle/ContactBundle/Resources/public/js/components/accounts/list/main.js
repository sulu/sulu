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
    'widget-groups'
], function(AccountManager, AccountRouter, DeleteDialog, WidgetGroups) {

    'use strict';

    var constants = {
            datagridInstanceName: 'accounts'
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
        },

        deleteCallback = function(ids) {
            DeleteDialog.showDialog(ids, function(deleteContacts){
                AccountManager.delete(ids, deleteContacts);
            }.bind(this));
        },

        clickCallback = function(id) {
            // show sidebar for selected item
            this.sandbox.emit(
                'sulu.sidebar.set-widget',
                '/admin/widget-groups/account-info?account=' + id
            );
        },

        actionCallback = function(id) {
            AccountRouter.toEdit(id);
        };

    return {

        view: true,

        layout: {
            content: {
                width: 'max'
            },
            sidebar: {
                width: 'fixed',
                cssClasses: 'sidebar-padding-50'
            }
        },

        header: function() {
            return {
                noBack: true,
                title: 'Account list', // todo: use translation key

                toolbar: {
                    buttons: {
                        add: {},
                        deleteSelected: {}
                    }
                }
            };
        },

        templates: ['/admin/contact/template/account/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);
        },

        render: function() {
            this.sandbox.dom.append(this.$el, this.renderTemplate('/admin/contact/template/account/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'accounts', '/admin/api/accounts/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'accounts',
                    template: 'default'
                },
                {
                    el: this.sandbox.dom.find('#companies-list', this.$el),
                    url: '/admin/api/accounts?flat=true',
                    resultKey: 'accounts',
                    searchInstanceName: 'accounts',
                    instanceName: constants.datagridInstanceName,
                    searchFields: ['name'],
                    clickCallback: (WidgetGroups.exists('account-info')) ? clickCallback.bind(this) : null,
                    actionCallback: actionCallback.bind(this)
                },
                'accounts',
                '#companies-list-info'
            );
        }
    };
});
