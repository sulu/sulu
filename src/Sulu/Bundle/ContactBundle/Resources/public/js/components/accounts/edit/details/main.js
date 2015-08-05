/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'config',
    'widget-groups',
    'services/sulucontact/account-manager',
], function(Config, WidgetGroups, AccountManager) {

    'use strict';

    var defaults = {
            headline: 'contact.accounts.title'
        },
        fields = ['urls', 'emails', 'faxes', 'phones', 'notes', 'addresses'],

        constants = {
            tagsId: '#tags',
            addressAddId: '#address-add',
            addAddressWrapper: '.grid-row',

            bankAccountsId: '#bankAccounts',
            bankAccountAddSelector: '.bank-account-add',

            editFormSelector: '#contact-edit-form'
        },

        customTemplates = {
            addBankAccountsIcon: [
                '<div class="grid-row">',
                '    <div class="grid-col-12">',
                '       <span id="bank-account-add" class="fa-plus-circle icon bank-account-add clickable pointer m-left-140"></span>',
                '   </div>',
                '</div>'
            ].join('')
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

        templates: ['/admin/contact/template/account/form'],

        customTemplates: {
            addAddressesIcon: [
                '<div class="grid-row">',
                '    <div class="grid-col-12">',
                '       <span id="address-add" class="fa-plus-circle icon address-add clickable pointer m-left-140"></span>',
                '   </div>',
                '</div>'
            ].join('')
        },

        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            
            this.form = '#contact-form';
            this.formContactFields = '#contact-fields';
            this.autoCompleteInstanceName = 'contacts-';

            this.dfdListenForChange = this.sandbox.data.deferred();
            this.dfdFormIsSet = this.sandbox.data.deferred();

            AccountManager.loadOrNew(this.options.id).then(function(data) {
                this.data = data;
                this.render();
                this.listenForChange();

                if (!!this.data && !!this.data.id && WidgetGroups.exists('account-detail')) {
                    this.initSidebar('/admin/widget-groups/account-detail?account=', this.data.id);
                }
            }.bind(this));
        },

        initSidebar: function(url, id) {
            this.sandbox.emit('sulu.sidebar.set-widget', url + id);
        },

        render: function() {
            var data, excludeItem, options;

            this.sandbox.once('sulu.contacts.set-defaults', this.setDefaults.bind(this));
            this.sandbox.once('sulu.contacts.set-types', this.setTypes.bind(this));

            this.html(this.renderTemplate('/admin/contact/template/account/form'));

            this.titleField = this.$find('#name');

            data = this.initContactData();

            excludeItem = [];
            if (!!this.data.id) {
                excludeItem.push({id: this.data.id});
            }

            options = Config.get('sulucontact.components.autocomplete.default.account');
            options.el = '#company';
            options.value = !!data.parent ? data.parent : null;
            options.instanceName = 'companyAccount' + data.id;

            this.sandbox.start([
                {
                    name: 'auto-complete@husky',
                    options: options
                },
                {
                    name: 'input@husky',
                    options: {
                        el: '#vat',
                        instanceName: 'vat-input',
                        value: !!data.uid ? data.uid : ''
                    }
                }
            ]);

            this.initForm(data);

            this.setTags();

            this.bindDomEvents();
            this.bindCustomEvents();
            this.bindTagEvents(data);
        },

        // show tags and activate keylistener
        setTags: function() {
            var uid = this.sandbox.util.uniqueId();
            if (this.data.id) {
                uid += '-' + this.data.id;
            }
            this.autoCompleteInstanceName += uid;

            this.dfdFormIsSet.then(function() {
                this.sandbox.start([
                    {
                        name: 'auto-complete-list@husky',
                        options: {
                            el: '#tags',
                            instanceName: this.autoCompleteInstanceName,
                            getParameter: 'search',
                            itemsKey: 'tags',
                            remoteUrl: '/admin/api/tags?flat=true&sortBy=name&searchFields=name',
                            completeIcon: 'tag',
                            noNewTags: true
                        }
                    }
                ]);
            }.bind(this));
        },

        bindTagEvents: function(data) {
            if (!!data.tags && data.tags.length > 0) {
                // set tags after auto complete list was initialized
                this.sandbox.on('husky.auto-complete-list.' + this.autoCompleteInstanceName + '.initialized', function() {
                    this.sandbox.emit('husky.auto-complete-list.' + this.autoCompleteInstanceName + '.set-tags', data.tags);
                }.bind(this));
                // listen for change after items have been added
                this.sandbox.on('husky.auto-complete-list.' + this.autoCompleteInstanceName + '.items-added', function() {
                    this.dfdListenForChange.resolve();
                }.bind(this));
            } else {
                this.dfdListenForChange.resolve();
            }
        },

        /**
         * is getting called when template is initialized
         * @param defaultTypes
         */
        setDefaults: function(defaultTypes) {
            this.defaultTypes = defaultTypes;
        },

        /**
         * is getting called when template is initialized
         * @param types
         */
        setTypes: function(types) {
            this.fieldTypes = types;
        },

        /**
         * Takes an array of fields and fills it up with empty fields till a minimum amount
         * @param field {Object} array of fields to manipulate
         * @param minAmount {Number} minimum amount of fields to exist
         * @param value {Object} empty object to insert (for minimum amount of fields)
         * @returns {Object} manipulated fields array
         */
        fillFields: function(field, minAmount, value) {
            var i = -1, length = field.length, attributes;

            // if minimum fields stated is bigger than the actual length loop more times
            if (length < minAmount) {
                length = minAmount;
            }

            for (; ++i < length;) {
                // construct the attributes object for fields under and equal the minimum amount
                if ((i + 1) > minAmount) {
                    attributes = {};
                } else {
                    attributes = {
                        permanent: true
                    };
                }

                // if no more fields exists push new, empty fields
                if (!field[i]) {
                    field.push(value);
                    field[field.length - 1].attributes = attributes;
                } else {
                    field[i].attributes = attributes;
                }
            }

            return field;
        },

        initContactData: function() {
            var contactJson = this.data;

            this.sandbox.util.foreach(fields, function(field) {
                if (!contactJson.hasOwnProperty(field)) {
                    contactJson[field] = [];
                }
            });

            this.fillFields(contactJson.urls, 1, {
                id: null,
                url: '',
                urlType: this.defaultTypes.urlType
            });
            this.fillFields(contactJson.emails, 1, {
                id: null,
                email: '',
                emailType: this.defaultTypes.emailType
            });
            this.fillFields(contactJson.phones, 1, {
                id: null,
                phone: '',
                phoneType: this.defaultTypes.phoneType
            });
            this.fillFields(contactJson.faxes, 1, {
                id: null,
                fax: '',
                faxType: this.defaultTypes.faxType
            });
            this.fillFields(contactJson.notes, 1, {
                id: null,
                value: ''
            });
            return contactJson;
        },

        initForm: function(data) {
            this.numberOfAddresses = data.addresses.length;
            this.updateAddressesAddIcon(this.numberOfAddresses);

            // when  contact-form is initalized
            this.sandbox.on('sulu.contact-form.initialized', function() {
                // set form data
                var formObject = this.sandbox.form.create(this.form);
                formObject.initialized.then(function() {
                    this.formInitializedHandler(data);
                }.bind(this));
            }.bind(this));

            // initialize contact form
            this.sandbox.start([
                {
                    name: 'contact-form@sulucontact',
                    options: {
                        el: constants.editFormSelector,
                        fieldTypes: this.fieldTypes,
                        defaultTypes: this.defaultTypes
                    }
                }
            ]);
        },

        formInitializedHandler: function(data) {
            this.setFormData(data);
        },

        setFormData: function(data) {
            // add collection filters to form
            this.sandbox.emit('sulu.contact-form.add-collectionfilters', this.form);

            this.numberOfBankAccounts = !!data.bankAccounts ? data.bankAccounts.length : 0;
            this.updateBankAccountAddIcon(this.numberOfBankAccounts);

            this.sandbox.form.setData(this.form, data).then(function() {
                this.sandbox.start(this.formContactFields);
                this.sandbox.emit('sulu.contact-form.add-required', ['email']);
                this.sandbox.emit('sulu.contact-form.content-set');
                this.dfdFormIsSet.resolve();
            }.bind(this));
        },

        /**
         * Adds or removes icon to add addresses
         * @param numberOfAddresses
         */
        updateAddressesAddIcon: function(numberOfAddresses) {
            var $addIcon = this.sandbox.dom.find(constants.addressAddId),
                addIcon;

            if (!!numberOfAddresses && numberOfAddresses > 0 && $addIcon.length === 0) {
                addIcon = this.sandbox.dom.createElement(this.customTemplates.addAddressesIcon);
                this.sandbox.dom.after(this.sandbox.dom.find('#addresses'), addIcon);
            } else if (numberOfAddresses === 0 && $addIcon.length > 0) {
                this.sandbox.dom.remove(this.sandbox.dom.closest($addIcon, constants.addAddressWrapper));
            }
        },

        bindDomEvents: function() {
            this.sandbox.dom.keypress(this.form, function(event) {
                if (event.which === 13) {
                    event.preventDefault();
                    this.submit();
                }
            }.bind(this));
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.contact-form.added.address', function() {
                this.numberOfAddresses++;
                this.updateAddressesAddIcon(this.numberOfAddresses);
            }, this);

            this.sandbox.on('sulu.contact-form.removed.address', function() {
                this.numberOfAddresses--;
                this.updateAddressesAddIcon(this.numberOfAddresses);
            }, this);

            // account saved
            this.sandbox.on('sulu.tab.save', this.save, this);

            this.sandbox.on('sulu.contact-form.added.bank-account', function() {
                this.numberOfBankAccounts++;
                this.updateBankAccountAddIcon(this.numberOfBankAccounts);
            }, this);

            this.sandbox.on('sulu.contact-form.removed.bank-account', function() {
                this.numberOfBankAccounts--;
                this.updateBankAccountAddIcon(this.numberOfBankAccounts);
            }, this);

            this.sandbox.on('sulu.router.navigate', this.cleanUp.bind(this));
        },

        /**
         * Does some cleanup with aura components
         */
        cleanUp: function() {
            // stop contact form before leaving
            this.sandbox.stop(constants.editFormSelector);
        },

        /**
         * Copies array of objects
         * @param data
         * @returns {Array}
         */
        copyArrayOfObjects: function(data) {
            var newArray = [];
            this.sandbox.util.foreach(data, function(el) {
                newArray.push(this.sandbox.util.extend(true, {}, el));
            }.bind(this));

            return newArray;
        },

        save: function() {
            if (this.sandbox.form.validate(this.form)) {
                var data = this.sandbox.form.getData(this.form);
                if (!data.id) {
                    delete data.id;
                }
                data.tags = this.sandbox.dom.data(this.$find(constants.tagsId), 'tags');
                // FIXME auto complete in mapper
                data.parent = {
                    id: this.sandbox.dom.attr('#company input', 'data-id')
                };
                AccountManager.save(data).then(function(savedData) {
                    this.sandbox.emit('sulu.tab.saved', savedData);
                    this.data = savedData;
                    this.initContactData();
                    this.setFormData(this.data);
                }.bind(this));
            }
        },

        listenForChange: function() {
            this.dfdListenForChange.then(function() {
                this.sandbox.dom.on('#contact-form', 'change', function() {
                    this.sandbox.emit('sulu.tab.dirty');
                }.bind(this), '.changeListener select, ' +
                '.changeListener input, ' +
                '.changeListener textarea');

                this.sandbox.dom.on('#contact-form', 'keyup', function() {
                    this.sandbox.emit('sulu.tab.dirty');
                }.bind(this), '.changeListener select, ' +
                '.changeListener input, ' +
                '.changeListener textarea');

                // if a field-type gets changed or a field gets deleted
                this.sandbox.on('sulu.contact-form.changed', function() {
                    this.sandbox.emit('sulu.tab.dirty');
                }.bind(this));
            }.bind(this));
        },

        /**
         * Adds or removes icon to add bank accounts depending on the number of bank accounts
         * @param numberOfBankAccounts
         */
        updateBankAccountAddIcon: function(numberOfBankAccounts) {
            var $addIcon = this.sandbox.dom.find(constants.bankAccountAddSelector, this.$el),
                addIcon;

            if (!!numberOfBankAccounts && numberOfBankAccounts > 0 && $addIcon.length === 0) {
                addIcon = this.sandbox.dom.createElement(customTemplates.addBankAccountsIcon);
                this.sandbox.dom.after(this.sandbox.dom.find(constants.bankAccountsId), addIcon);
            } else if (numberOfBankAccounts === 0 && $addIcon.length > 0) {
                this.sandbox.dom.remove(this.sandbox.dom.closest($addIcon, constants.addBankAccountsWrapper));
            }
        }
    };
});
