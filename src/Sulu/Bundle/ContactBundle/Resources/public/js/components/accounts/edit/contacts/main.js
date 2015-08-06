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
    'text!sulucontact/components/accounts/edit/contacts/contact-relation.form.html',
    'text!sulucontact/components/accounts/edit/contacts/contact.form.html',
    'config',
    'widget-groups',
    'services/sulucontact/account-manager',
    'services/sulucontact/contact-manager',
    'services/sulucontact/contact-router'
], function(RelationalStore, ContactRelationForm, ContactForm, Config, WidgetGroups, AccountManager, ContactManager, ContactRouter) {

    'use strict';

    var constants = {
            relationFormSelector: '#contact-relation-form',
            contactSelector: '#contact-field',
            positionSelector: '#company-contact-position',
            newContactFormSelector: '#contact-form',
            contactListSelector: '#people-list'
        },

        actionCallback = function(contactId) {
            ContactRouter.toEdit(contactId);
        },

        bindCustomEvents = function() {
            // remove record from datagrid
            this.sandbox.on('sulu.contacts.accounts.contacts.removed', function(id) {
                this.sandbox.emit('husky.datagrid.record.remove', id);
            }, this);

            // when radio button is clicked
            this.sandbox.on('husky.datagrid.radio.selected', function(id) {
                AccountManager.setMainContact(this.data.id, id);
            }, this);

            // when a position is selected in the company overlay
            this.sandbox.on('husky.select.company-position-select.selected.item', function(id) {
                this.companyPosition = id;
            }, this);

            this.sandbox.dom.on('husky.datagrid.number.selections', function(number) {
                if (number > 0) {
                    this.sandbox.emit('husky.toolbar.contacts.item.enable', 'delete');
                } else {
                    this.sandbox.emit('husky.toolbar.contacts.item.disable', 'delete');
                }
            }.bind(this));

            // receive form of address values via template
            this.sandbox.once('sulu.contacts.set-types', function(types) {
                this.formOfAddress = types.formOfAddress;
                this.emailTypes = types.emailTypes;
            }.bind(this));

            this.sandbox.on('husky.overlay.new-contact.opened' , function(){
                var $form = this.sandbox.dom.find(constants.newContactFormSelector, this.$el);
                this.sandbox.start($form);
                this.sandbox.form.create(constants.newContactFormSelector);
            }.bind(this));
        },

        /**
         * Creates an overlay to add a new contact
         * @param data
         */
        createContactOverlay = function(data) {

            var template, $overlay, $list;

            // extend data by additional variables
            data = this.sandbox.util.extend(true, {}, {
                translate: this.sandbox.translate,
                formOfAddress: this.formOfAddress
            }, data);

            template = this.sandbox.util.template(ContactForm, data);

            // create container for overlay
            $overlay = this.sandbox.dom.createElement('<div />');
            $list = this.sandbox.dom.find(constants.contactListSelector);
            this.sandbox.dom.append($list, $overlay);

            // create overlay with data
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $overlay,
                        title: this.sandbox.translate('contact.accounts.add-new-contact-to-account'),
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'new-contact',
                        data: template,
                        skin: 'wide',
                        okCallback: addNewContact.bind(this)
                    }
                }
            ]);
        },

        /**
         * adds a new contact to an account when the form is valid
         */
        addNewContact = function(){
            if (this.sandbox.form.validate(constants.newContactFormSelector)) {
                var data = this.sandbox.form.getData(constants.newContactFormSelector);
                data.account = this.data;
                data.emails = [{
                    email: data.email,
                    emailType: this.emailTypes[0]
                }];
                ContactManager.save(data).then(function(contact) {
                    this.sandbox.emit('husky.datagrid.record.add', contact);
                }.bind(this));
                return true;
            } else {
                return false;
            }
        },

        createRelationOverlay = function(data) {
            var template, $overlay, $list, options;

            options = Config.get('sulucontact.components.autocomplete.default.contact');
            options.el = constants.contactSelector;

            // extend data by additional variables
            data = this.sandbox.util.extend(true, {}, {
                translate: this.sandbox.translate
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
                    options: options
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
                                noNewValues: true,
                                isNative: true
                            }
                        }
                    ]);
                }.bind(this))
                .fail(function(textStatus, error) {
                    this.sandbox.logger.error(textStatus, error);
                }.bind(this));
        },

        // triggers removal of account contact relation
        removeContactFromAccount = function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                if (ids.length > 0) {
                    AccountManager.removeAccountContacts(this.data.id, ids);
                }
            }.bind(this));
        },

        // list-toolbar template
        listTemplate = function() {
            return [
                {
                    id: 'add',
                    icon: 'plus-circle',
                    position: 1,
                    class: 'highlight',
                    dropdownItems: [
                        {
                            id: 'add-account-contact',
                            title: this.sandbox.translate('contact.account.add-account-contact'),
                            callback: createRelationOverlay.bind(this)
                        },
                        {
                            id: 'add-new-contact-to-account',
                            title: this.sandbox.translate('contact.accounts.add-new-contact-to-account'),
                            callback: createContactOverlay.bind(this)
                        }
                    ],
                    callback: function() {
                        this.sandbox.emit('sulu.list-toolbar.add');
                    }.bind(this)
                },
                {
                    id: 'settings',
                    icon: 'gear',
                    dropdownItems: [
                        {
                            id: 'delete',
                            title: this.sandbox.translate('contact.accounts.contact-remove'),
                            callback: removeContactFromAccount.bind(this),
                            disabled: true
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
                AccountManager.addAccountContact(this.options.id, id, this.companyPosition);
                ContactManager.loadOrNew(id).then(function(contact) {
                    this.sandbox.emit('husky.datagrid.record.add', contact);
                }.bind(this));
            }
        };

    return {
        view: true,

        layout: function() {
            return {
                content: {
                    width: 'fixed'
                },
                sidebar: {
                    width: 'max',
                    cssClasses: 'sidebar-padding-50'
                }
            };
        },

        templates: ['/admin/contact/template/contact/list'],

        initialize: function() {
            AccountManager.loadOrNew(this.options.id).then(function(data) {
                this.data = data;
                this.formOfAddress = null;
                this.companyPosition = null;
                bindCustomEvents.call(this);
                this.render();

                if (!!this.data && !!this.data.id && WidgetGroups.exists('account-detail')) {
                    this.initSidebar('/admin/widget-groups/account-detail?account=', this.data.id);
                }
            }.bind(this));
        },

        initSidebar: function(url, id) {
            this.sandbox.emit('sulu.sidebar.set-widget', url + id);
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/contact/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'accountsContactsFields', '/admin/api/contacts/fields?accountContacts=true',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'contacts',
                    template: listTemplate.call(this),
                    hasSearch: false
                },
                {
                    el: this.sandbox.dom.find('#people-list', this.$el),
                    url: '/admin/api/accounts/' + this.data.id + '/contacts?flat=true',
                    searchInstanceName: 'contacts',
                    searchFields: ['fullName'],
                    resultKey: 'contacts',
                    actionCallback: actionCallback.bind(this),
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
