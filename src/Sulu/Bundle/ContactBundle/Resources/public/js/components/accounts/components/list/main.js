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
    'app-config',
    'widget-groups'
], function(RelationalStore, AppConfig, WidgetGroups) {

    'use strict';

    var constants = {
            datagridInstanceName: 'accounts'
        },
        bindCustomEvents = function() {
        // delete clicked
            this.sandbox.on('sulu.list-toolbar.delete', function() {
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.items.get-selected', function(ids) {
                    this.sandbox.emit('sulu.contacts.accounts.delete', ids);
                }.bind(this));
            }, this);

            // add clicked
            this.sandbox.on('sulu.list-toolbar.add', function() {
                this.sandbox.emit('sulu.contacts.accounts.new');
            }, this);

            // checkbox clicked
            this.sandbox.on('husky.datagrid.' + constants.datagridInstanceName + '.number.selections', function(number) {
                var postfix = number > 0 ? 'enable' : 'disable';
                this.sandbox.emit('husky.toolbar.accounts.item.' + postfix, 'delete', false);
            }.bind(this));

            if (WidgetGroups.exists('account-info')) {
                // show sidebar for selected item
                this.sandbox.on('husky.datagrid.' + constants.datagridInstanceName + '.item.click', function(id) {
                    this.sandbox.emit(
                        'sulu.sidebar.set-widget',
                        '/admin/widget-groups/account-info?account=' + id
                    );
                }, this);
            }
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
            return {
                title: 'contact.accounts.title',
                noBack: true,

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

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'accounts', '/admin/api/accounts/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'accounts',
                    parentTemplate: 'default',
                    inHeader: true,
                    groups: [
                        {
                            id: 1,
                            align: 'left'
                        },
                        {
                            id: 2,
                            align: 'right'
                        }
                    ],
                    template: function() {
                        return this.getToolbarTemplate();
                    }.bind(this)
                },
                {
                    el: this.sandbox.dom.find('#companies-list', this.$el),
                    url: '/admin/api/accounts?flat=true',
                    resultKey: 'accounts',
                    searchInstanceName: 'accounts',
                    instanceName: constants.datagridInstanceName,
                    searchFields: ['name'],
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
                },
                'accounts',
                '#companies-list-info'
            );
        },

        getToolbarTemplate: function() {
            return [
                {
                    id: 'add',
                    icon: 'plus-circle',
                    class: 'highlight-white',
                    position: 1,
                    title: this.sandbox.translate('sulu.list-toolbar.add'),
                    callback: function() {
                        this.sandbox.emit('sulu.list-toolbar.add');
                    }.bind(this)
                }
            ];
        }
    };
});
