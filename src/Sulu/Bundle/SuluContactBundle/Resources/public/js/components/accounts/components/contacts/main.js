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
                this.sandbox.emit('sulu.contacts.contact.load', item);
            }, this);

            // delete clicked
            this.sandbox.on('sulu.list-toolbar.delete', function() {
                this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                    this.sandbox.emit('sulu.contacts.accounts.delete', ids);
                }.bind(this));
            }, this);
        },

        listTemplate = function() {
            return [
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
            ];
        };

    return {
        view: true,

        templates: ['/admin/contact/template/contact/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);
        },

        render: function() {

            RelationalStore.reset(); //FIXME really necessary?

            this.sandbox.emit('sulu.', this.options.account);

            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/contact/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'accountsContactsFields', '/admin/api/contacts/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'contacts',
                    inHeader: true,
                    template: listTemplate
                },
                {
                    el: this.sandbox.dom.find('#people-list', this.$el),
                    url: '/admin/api/accounts/' + this.options.data.id + '/contacts?flat=true',
                    searchInstanceName: 'contacts',
                    viewOptions: {
                        table: {
                            fullWidth: true,
                            selectItem: false,
                            removeRow: false
                        }
                    }
                }
            );
        }
    };
});
