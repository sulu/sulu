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
    'services/sulucontact/contact-manager'
], function(Config, WidgetGroups, ContactManager) {

    'use strict';

    var form = '#contact-form',
        fields = ['urls', 'emails', 'faxes', 'phones', 'notes'],

        constants = {
            tagsId: '#tags',
            addressAddId: '#address-add',
            bankAccountAddId: '#bank-account-add',
            addAddressWrapper: '.grid-row',
            addBankAccountsWrapper: '.grid-row',
            editFormSelector: '#contact-edit-form'
        },

        customTemplates = {
            addBankAccountsIcon: [
                '<div class="grid-row">',
                '   <div class="grid-col-12">',
                '       <div id="bank-account-add" class="addButton bank-account-add m-left-140"></div>',
                '   </div>',
                '</div>'
            ].join(''),
            addAddressesIcon: [
                '<div class="grid-row">',
                '   <div class="grid-col-12">',
                '       <div id="address-add" class="addButton address-add m-left-140"></div>',
                '   </div>',
                '</div>'
            ].join('')
        };

    return {

        view: true,

        layout: function() {
            return {
                content: {
                    width: 'max',
                    leftSpace: false,
                    topSpace: false,
                    rightSpace: false
                },
                sidebar: {
                    width: 'max',
                    cssClasses: 'sidebar-padding-50'
                }
            };
        },

        templates: ['/admin/contact/template/contact/form'],

        initialize: function() {
            this.saved = true;
            this.formId = '#contact-form';
            this.autoCompleteInstanceName = 'accounts-';

            this.dfdAllFieldsInitialized = this.sandbox.data.deferred();
            this.dfdListenForChange = this.sandbox.data.deferred();
            this.dfdFormIsSet = this.sandbox.data.deferred();
            this.dfdBirthdayIsSet = this.sandbox.data.deferred();

            // define when all fields are initialized
            this.sandbox.data.when(this.dfdListenForChange, this.dfdBirthdayIsSet).then(function() {
                this.dfdAllFieldsInitialized.resolve();
            }.bind(this));

            ContactManager.loadOrNew(this.options.id).then(function(data) {
                this.data = data;
                this.render();
                this.listenForChange();

                if (!!this.data && !!this.data.id && WidgetGroups.exists('contact-detail')) {
                    this.initSidebar(
                        '/admin/widget-groups/contact-detail?contact=',
                        this.data.id
                    );
                }
            }.bind(this));
        },

        destroy: function() {
            this.sandbox.emit('sulu.header.toolbar.item.hide', 'disabler');
        },

        initSidebar: function(url, id) {
            this.sandbox.emit('sulu.sidebar.set-widget', url + id);
        },

        render: function() {
            this.sandbox.emit(this.options.disablerToggler + '.change', this.data.disabled);
            this.sandbox.emit('sulu.header.toolbar.item.show', 'disabler');
            this.sandbox.once('sulu.contacts.set-defaults', this.setDefaults.bind(this));
            this.sandbox.once('sulu.contacts.set-types', this.setTypes.bind(this));
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/contact/form'));
            this.sandbox.on('husky.dropdown.type.item.click', this.typeClick.bind(this));
            var data = this.initContactData();
            this.companyInstanceName = 'companyContact' + data.id;
            this.initForm(data);
            this.setTags(data);
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

        setFormData: function(data, startForm) {
            this.numberOfBankAccounts = !!data.bankAccounts ? data.bankAccounts.length : 0;
            this.updateBankAccountAddIcon(this.numberOfBankAccounts);

            // add collection filters to form
            this.sandbox.emit('sulu.contact-form.add-collectionfilters', form);
            this.sandbox.form.setData(form, data).then(function() {

                if (!!startForm) {
                    this.sandbox.start(form);
                } else {
                    this.sandbox.start('#contact-fields');
                }

                this.sandbox.emit('sulu.contact-form.add-required', ['email']);
                this.sandbox.emit('sulu.contact-form.content-set');
                this.dfdFormIsSet.resolve();
            }.bind(this)).fail(function(error) {
                this.sandbox.logger.error("An error occured when setting data!", error);
            }.bind(this));
        },

        initForm: function(data) {
            var options = Config.get('sulucontact.components.autocomplete.default.account');
            options.el = '#company';
            options.value = !!data.account ? data.account : '';
            options.instanceName = this.companyInstanceName;

            this.sandbox.start([
                {
                    name: 'auto-complete@husky',
                    options: options
                }
            ]);

            this.numberOfAddresses = data.addresses.length;
            this.updateAddressesAddIcon(this.numberOfAddresses);

            // when  contact-form is initalized
            this.sandbox.on('sulu.contact-form.initialized', function() {
                // set form data
                var formObject = this.sandbox.form.create(form);
                formObject.initialized.then(function() {
                    this.setFormData(data, true);
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

        /**
         * Adds or removes icon to add addresses
         * @param numberOfAddresses
         */
        updateAddressesAddIcon: function(numberOfAddresses) {
            var $addIcon = this.$find(constants.addressAddId),
                addIcon;

            if (!!numberOfAddresses && numberOfAddresses > 0 && $addIcon.length === 0) {
                addIcon = this.sandbox.dom.createElement(customTemplates.addAddressesIcon);
                this.sandbox.dom.after(this.sandbox.dom.find('#addresses'), addIcon);
            } else if (numberOfAddresses === 0 && $addIcon.length > 0) {
                this.sandbox.dom.remove(this.sandbox.dom.closest($addIcon, constants.addAddressWrapper));
                this.sandbox.emit('sulu.contact-form.update.addAddressLabel', '#addresses');
            }
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.contact-form.added.address', function() {
                this.numberOfAddresses += 1;
                this.updateAddressesAddIcon(this.numberOfAddresses);
            }, this);

            this.sandbox.on('sulu.contact-form.removed.address', function() {
                this.numberOfAddresses -= 1;
                this.updateAddressesAddIcon(this.numberOfAddresses);
            }, this);

            // contact save
            this.sandbox.on('sulu.tab.save', this.save, this);

            this.sandbox.on('husky.input.birthday.initialized', function() {
                this.dfdBirthdayIsSet.resolve();
            }, this);

            this.sandbox.once('husky.select.position-select.initialize', function() {
                if (!this.sandbox.dom.find('#' + this.companyInstanceName).val()) {
                    this.enablePositionDropdown(false);
                }
            }, this);

            this.sandbox.on('sulu.contact-form.added.bank-account', function() {
                this.numberOfBankAccounts += 1;
                this.updateBankAccountAddIcon(this.numberOfBankAccounts);
            }, this);

            this.sandbox.on('sulu.contact-form.removed.bank-account', function() {
                this.numberOfBankAccounts -= 1;
                this.updateBankAccountAddIcon(this.numberOfBankAccounts);
            }, this);

            this.initializeDropDownListener(
                'title-select',
                'api/contact/titles');
            this.initializeDropDownListener(
                'position-select',
                'api/contact/positions');

            this.sandbox.on('husky.toggler.sulu-toolbar.changed', this.toggleDisableContact.bind(this));

            this.sandbox.on('sulu.router.navigate', this.cleanUp.bind(this));
        },

        /**
         * Disables or enables the contact
         * @param disable {Boolean} true to disable, false to enable
         */
        toggleDisableContact: function(disable) {
            this.data.disabled = disable;
            this.sandbox.emit('sulu.tab.dirty');
        },

        /**
         * Does some cleanup with aura components
         */
        cleanUp: function() {
            // stop contact form before leaving
            this.sandbox.stop(constants.editFormSelector);
        },

        initContactData: function() {
            var contactJson = this.data;

            this.sandbox.util.foreach(fields, function(field) {
                if (!contactJson.hasOwnProperty(field)) {
                    contactJson[field] = [];
                }
            });

            contactJson.emails = this.fillFields(contactJson.emails, 1, {
                id: null,
                email: '',
                emailType: this.defaultTypes.emailType
            });
            contactJson.phones = this.fillFields(contactJson.phones, 1, {
                id: null,
                phone: '',
                phoneType: this.defaultTypes.phoneType
            });
            contactJson.faxes = this.fillFields(contactJson.faxes, 1, {
                id: null,
                fax: '',
                faxType: this.defaultTypes.faxType
            });
            contactJson.notes = this.fillFields(contactJson.notes, 1, {
                id: null,
                value: ''
            });
            contactJson.urls = this.fillFields(contactJson.urls, 0, {
                id: null,
                url: '',
                urlType: this.defaultTypes.urlType
            });

            return contactJson;
        },

        typeClick: function(event, $element) {
            $element.find('*.type-value').data('element').setValue(event);
        },

        /**
         * Takes an array of fields and fields it up with empty fields till a minimum amount
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

        save: function() {
            if (this.sandbox.form.validate(form)) {
                var data = this.sandbox.util.extend(false, {}, this.data, this.sandbox.form.getData(form));
                if (data.id === '') {
                    delete data.id;
                }
                data.tags = this.sandbox.dom.data(this.$find(constants.tagsId), 'tags');

                // FIXME auto complete in mapper
                // only get id, if auto-complete is not empty:
                data.account = {
                    id: this.sandbox.dom.attr('#' + this.companyInstanceName, 'data-id')
                };
                this.sandbox.emit('sulu.tab.saving');
                ContactManager.save(data).then(function(savedData) {
                    this.data = data;
                    this.initContactData();
                    this.setFormData(this.data);
                    this.sandbox.emit('sulu.tab.saved', savedData);
                }.bind(this));
            }
        },

        /**
         * Register events for editable drop downs
         * @param instanceName
         */
        initializeDropDownListener: function(instanceName) {
            var instance = 'husky.select.' + instanceName;
            this.sandbox.on(instance + '.selected.item', function(id) {
                if (id > 0) {
                    this.sandbox.emit('sulu.tab.dirty');
                }
            }.bind(this));
            this.sandbox.on(instance + '.deselected.item', function() {
                this.sandbox.emit('sulu.tab.dirty');
            }.bind(this));
            this.sandbox.on(instance + '.delete', this.deleteSelectData.bind(this, instanceName));
            this.sandbox.on(instance + '.save', this.saveSelectData.bind(this, instanceName));
        },

        /**
         * Saves the data of the editable selects
         * @param type The type of the editable select
         * @param data The data to save
         */
        saveSelectData: function(type, data) {
            var method = (type === 'title-select') ? ContactManager.saveTitles : ContactManager.savePositions;
            method(data).then(function(response) {
                this.sandbox.emit(
                    'husky.select.' + type + '.update',
                    response,
                    [response[response.length - 1]], // preselected
                    true,
                    true
                );
            }.bind(this));
        },

        /**
         * Deletes the data of the editable selects
         * @param type The type of the editable select
         * @param ids The ids of the data to delete
         */
        deleteSelectData: function(type, ids) {
            var method = (type === 'title-select') ? ContactManager.deleteTitle : ContactManager.deletePosition;
            this.sandbox.util.foreach(ids, function(id) {
                method(id);
            }.bind(this));
        },

        /**
         * Enables or disables the position dropdown
         * @param data - event
         */
        enablePositionDropdown: function(enable) {
            if (!!enable) {
                this.sandbox.emit('husky.select.position-select.enable');
            } else {
                this.sandbox.emit('husky.select.position-select.disable');
            }
        },

        // event listens for changes in form
        listenForChange: function() {
            // listen for change after TAGS and BIRTHDAY-field have been set
            this.sandbox.data.when(this.dfdAllFieldsInitialized).then(function() {

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

                this.sandbox.on('sulu.contact-form.changed', function() {
                    this.sandbox.emit('sulu.tab.dirty');
                }.bind(this));

                // disable position dropdown when company is empty
                this.sandbox.dom.on('#company', 'keyup', function(data) {
                    if (!data.target.value) {
                        this.enablePositionDropdown(false);
                    }
                }.bind(this));

                // enabel position dropdown only if something got selected
                this.companySelected = 'husky.auto-complete.' +
                this.companyInstanceName +
                '.select';
                this.sandbox.on(this.companySelected, function() {
                    this.enablePositionDropdown(true);
                }.bind(this));

            }.bind(this));

            this.sandbox.on('husky.select.form-of-address.selected.item', function() {
                this.sandbox.emit('sulu.tab.dirty');
            }.bind(this));
        },

        /**
         * Adds or removes icon to add bank accounts depending on the number of bank accounts
         * @param numberOfBankAccounts
         */
        updateBankAccountAddIcon: function(numberOfBankAccounts) {
            var $addIcon = this.$find(constants.bankAccountAddId),
                addIcon;

            if (!!numberOfBankAccounts && numberOfBankAccounts > 0 && $addIcon.length === 0) {
                addIcon = this.sandbox.dom.createElement(customTemplates.addBankAccountsIcon);
                this.sandbox.dom.after(this.sandbox.dom.find('#bankAccounts'), addIcon);
            } else if (numberOfBankAccounts === 0 && $addIcon.length > 0) {
                this.sandbox.dom.remove(this.sandbox.dom.closest($addIcon, constants.addBankAccountsWrapper));
            }
        }
    };
});
