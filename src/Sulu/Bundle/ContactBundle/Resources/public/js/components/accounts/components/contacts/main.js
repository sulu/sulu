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
    'text!sulucontact/components/accounts/components/contacts/contact-relation.form.html'
], function(RelationalStore, ContactRelationForm) {

    'use strict';

    var constants = {
            relationFormSelector: '#contact-relation-form',
            contactSelector: '#contact-field',
            positionSelector: '#company-contact-position',
        },

        companyPosition = null,

        bindCustomEvents = function() {
            // navigate to edit contact
            this.sandbox.on('husky.datagrid.item.click', function(item) {
                this.sandbox.emit('sulu.contacts.contact.load', item);
                this.sandbox.emit('husky.navigation.select-item', 'contacts/contacts');
            }, this);

            // delete clicked
            this.sandbox.on('sulu.list-toolbar.delete', function() {
                this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                    this.sandbox.emit('sulu.contacts.accounts.delete', ids);
                }.bind(this));
            }, this);

            // back to list
            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.contacts.accounts.list');
            }, this);

            // add new record to datagrid
            this.sandbox.on('sulu.contacts.accounts.contact.saved', function(model) {
                this.sandbox.emit('husky.datagrid.record.add', model);
            }, this);

            // remove record from datagrid
            this.sandbox.on('sulu.contacts.accounts.contacts.removed', function(id) {
                this.sandbox.emit('husky.datagrid.record.remove', id);
            }, this);

            // when radio button is clicked
            this.sandbox.on('husky.datagrid.radio.selected', function(id, columName) {
                this.sandbox.emit('sulu.contacts.accounts.contacts.set-main', id);
            }, this);

            // when a position is selected in the company overlay
            this.sandbox.on('husky.select.company-position-select.selected.item', function(id) {
                companyPosition = id;
            }, this);
        },

        createRelationOverlay = function(data) {
            var template, $overlay, $list;

            // extend data by additional variables
            data = this.sandbox.util.extend(true, {}, {
                translate: this.sandbox.translate,
                position: ''
            }, data);

            template = this.sandbox.util.template(ContactRelationForm, data);

            // create container for overlay
            $overlay = this.sandbox.dom.createElement('<div />');
            $list = this.sandbox.dom.find('#people-list');
            this.sandbox.dom.append($list, $overlay);

            // create overlay with data
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $overlay,
                        title: this.sandbox.translate('contact.accounts.add-contact'),
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'contact-relation',
                        data: template,
                        okCallback: addContactRelation.bind(this)
                    }
                },
                {
                    name: 'auto-complete@husky',
                    options: {
                        el: constants.contactSelector,
                        remoteUrl: '/admin/api/contacts?flat=true&fields=id,fullName&searchFields=fullName',
                        getParameter: 'search',
                        resultKey: 'contacts',
                        instanceName: 'contact',
                        valueKey: 'fullName',
                        noNewValues: true
                    }
                }
            ]);

            this.sandbox.util.load('/admin/api/contact/positions')
                .then(function(response) {
                    this.sandbox.start([
                        {
                            name: 'select@husky',
                            options: {
                                el: constants.positionSelector,
                                instanceName: 'company-position-select',
                                valueName: 'position',
                                returnValue: 'id',
                                data: response._embedded.positions,
                                noNewValues: true
                            }
                        }
                    ]);
                }.bind(this))
                .fail(function(textStatus, error) {
                    this.sandbox.logger.error(textStatus, error);
                }.bind(this));

            this.data = data;
        },

        // triggers removal of account contact relation
        removeContact = function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                if (ids.length > 0) {
                    // and trigger deletion
                    this.sandbox.emit('sulu.contacts.accounts.contacts.remove', ids);
                }
            }.bind(this));
        },

        // opens column options
//        openColumnOptions = function() {
//            var instanceName;
//            this.sandbox.dom.append('body', '<div id="column-options-overlay" />');
//            this.sandbox.start([
//                {
//                    name: 'column-options@husky',
//                    options: {
//                        el: '#column-options-overlay',
//                        data: this.sandbox.sulu.getUserSetting(this.options.columnOptions.key),
//                        hidden: false,
//                        instanceName: this.options.instanceName,
//                        trigger: '.toggle'
//                    }
//                }
//            ]);
//            instanceName = this.options.instanceName ? this.options.instanceName + '.' : '';
//            this.sandbox.once('husky.column-options.' + instanceName + 'saved', function(data) {
//                this.sandbox.sulu.saveUserSetting(this.options.columnOptions.key, data, this.options.columnOptions.url);
//            }.bind(this));
//        },

        // list-toolbar template
        listTemplate = function() {
            return [
                {
                    id: 'add',
                    icon: 'plus-circle',
                    class: 'highlight-white',
                    title: 'add',
                    position: 10,
                    callback: createRelationOverlay.bind(this)
                },
                {
                    id: 'settings',
                    icon: 'gear',
                    items: [
//                      // TODO: currently column options are not needed for this list, but this can change in future
//                        {
//                            title: this.sandbox.translate('list-toolbar.column-options'),
//                            callback: openColumnOptions.bind(this)
//                        },
                        {
                            title: this.sandbox.translate('contact.accounts.contact-remove'),
                            callback: removeContact.bind(this)
                        }
                    ]
                }
            ];
        },

        // adds a new contact relation
        addContactRelation = function() {
            var contactInput = this.sandbox.dom.find(constants.contactSelector + ' input', constants.relationFormSelector),
                id = this.sandbox.dom.data(contactInput, 'id');
            if (!!id) {
                this.sandbox.emit('sulu.contacts.accounts.contact.save', id, companyPosition);
            }
        };

    return {
        view: true,

        layout: {
            sidebar: {
                width: 'fixed',
                cssClasses: 'sidebar-padding-50'
            }
        },

        templates: ['/admin/contact/template/contact/list'],

        initialize: function() {

            this.render();
            bindCustomEvents.call(this);

            if (!!this.options.data && !!this.options.data.id) {
                this.initSidebar('/admin/widget-groups/account-detail?account=', this.options.data.id);
            }
        },

        initSidebar: function(url, id) {
            this.sandbox.emit('sulu.sidebar.set-widget', url + id);
        },

        render: function() {

            RelationalStore.reset(); //FIXME really necessary?

            this.sandbox.emit('sulu.', this.options.account);

            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/contact/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'accountsContactsFields', '/admin/api/contacts/fields?accountContacts=true',
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
                    searchFields: ['fullName'],
                    resultKey: 'contacts',
                    contentFilters: {
                        isMainContact: 'radio'
                    },
                    viewOptions: {
                        table: {
                            selectItem: {
                                type: 'checkbox'
                            },
                            removeRow: false
                        }
                    }
                }
            );
        }
    };
});
