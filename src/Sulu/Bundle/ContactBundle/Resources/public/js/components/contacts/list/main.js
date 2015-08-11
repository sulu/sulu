/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require.config({
    paths: {
        'decorators/contact-card': '../../sulucontact/js/components/contacts/list/decorators/contact-view'
    }
});

define([
    'services/sulucontact/contact-manager',
    'services/sulucontact/contact-router',
    'services/sulucontact/contact-delete-dialog',
    'widget-groups'
], function(ContactManager, ContactRouter, DeleteDialog, WidgetGroups) {

    'use strict';

    var constants = {
            datagridInstanceName: 'contacts',
            listViewStorageKey: 'contactListView'
        },

        bindCustomEvents = function() {
            // delete clicked
            this.sandbox.on('sulu.toolbar.delete', function() {
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.items.get-selected',
                    deleteCallback.bind(this));
            }, this);

            // remove from datagrid when deleted
            this.sandbox.on('sulu.contacts.contact.deleted', function(contactId) {
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.record.remove', contactId);
            }, this);

            // add clicked
            this.sandbox.on('sulu.toolbar.add', function() {
                ContactRouter.toAdd();
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

            this.sandbox.on('sulu.toolbar.change.contact-card', function() {
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.view.change', 'decorators/contact-card');
                this.sandbox.sulu.saveUserSetting(constants.listViewStorageKey, 'decorators/contact-card');
            }.bind(this));
        },

        deleteCallback = function(ids){
            DeleteDialog.showDialog(ids, function() {
                    ContactManager.delete(ids);
            }.bind(this));
        },

        clickCallback = function(item) {
            // show sidebar for selected item
            this.sandbox.emit('sulu.sidebar.set-widget', '/admin/widget-groups/contact-info?contact=' + item);
        },

        actionCallback = function(id) {
            ContactRouter.toEdit(id);
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

        header: {
            noBack: true,
            toolbar: {
                buttons: {
                    add: {},
                    deleteSelected: {}
                }
            }
        },

        templates: ['/admin/contact/template/contact/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/contact/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'contacts', '/admin/api/contacts/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'contacts',
                    template: this.sandbox.sulu.buttons.get({
                        settings: {
                            options: {
                                dropdownItems: [
                                    {
                                        type: 'columnOptions'
                                    }
                                ]
                            }
                        },
                        layoutContact: {}
                    })
                },
                {
                    el: this.sandbox.dom.find('#people-list', this.$el),
                    url: '/admin/api/contacts?flat=true',
                    searchInstanceName: 'contacts',
                    searchFields: ['fullName'],
                    view: this.sandbox.sulu.getUserSetting(constants.listViewStorageKey) || 'decorators/contact-card',
                    resultKey: 'contacts',
                    instanceName: constants.datagridInstanceName,
                    clickCallback: (WidgetGroups.exists('contact-info')) ? clickCallback.bind(this) : null,
                    actionCallback: actionCallback.bind(this)
                },
                'contacts',
                '#people-list-info'
            );
        }
    };
});
