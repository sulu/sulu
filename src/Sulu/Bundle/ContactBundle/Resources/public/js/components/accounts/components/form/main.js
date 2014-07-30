/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    var defaults = {
            headline: 'contact.accounts.title'
        },
        fields = ['urls', 'emails', 'faxes', 'phones', 'notes', 'addresses'],

        constants = {
            tagsId: '#tags',
            addressAddId: '#address-add',
            addAddressWrapper: '.grid-row'
        };

    return {

        view: true,

        layout: {
            sidebar: {
                width: 'fixed',
                cssClasses: 'sidebar-padding-50'
            }
        },

        templates: ['/admin/contact/template/account/form'],

        customTemplates: {
            addAddressesIcon: [
                '<div class="grid-row">',
                '    <div class="grid-col-12">',
                '       <span id="address-add" class="fa-plus-circle icon address-add clickable pointer m-left-140"></span>',
                '   </div>',
                '</div>'].join('')
        },

        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.form = '#contact-form';
            this.saved = true;
            this.autoCompleteInstanceName = 'contacts-';

            this.dfdListenForChange = this.sandbox.data.deferred();
            this.dfdFormIsSet = this.sandbox.data.deferred();

            this.instanceNameTypeOverlay = 'accountCategories';
            this.accountCategoryURL = 'api/account/categories';
            this.contactBySystemURL = 'api/contacts?bySystem=true';

            this.render();
            this.getAccountTypeData();
            this.setHeaderBar(true);
            this.listenForChange();

            if (!!this.options.data && !!this.options.data.id) {
                this.initSidebar('/admin/widget-groups/account-detail?account=', this.options.data.id);
            }
        },

        initSidebar: function(url, id) {
            this.sandbox.emit('sulu.sidebar.set-widget', url + id);
        },

        getAccountTypeData: function() {
            this.sandbox.emit('sulu.contacts.account.get.types', function(accountType, accountTypes) {
                this.accountType = accountType;
                this.accountTypes = accountTypes;
            }.bind(this));
        },

        render: function() {
            var data, excludeItem;

            this.sandbox.once('sulu.contacts.set-defaults', this.setDefaults.bind(this));
            this.sandbox.once('sulu.contacts.set-types', this.setTypes.bind(this));

            this.html(this.renderTemplate('/admin/contact/template/account/form'));

            this.titleField = this.$find('#name');

            data = this.initContactData();
            this.accountType = null;
            this.accountTypes = null;

            excludeItem = [];
            if (!!this.options.data.id) {
                excludeItem.push({id: this.options.data.id});
            }

            this.sandbox.start([
                {
                    name: 'auto-complete@husky',
                    options: {
                        el: '#company',
                        remoteUrl: '/admin/api/accounts?searchFields=name&fields=id,name&flat=true',
                        getParameter: 'search',
                        resultKey: 'accounts',
                        value: !!data.parent ? data.parent : null,
                        instanceName: 'companyAccount' + data.id,
                        valueName: 'name',
                        noNewValues: true,
                        excludes: [
                            {id: data.id, name: data.name}
                        ]
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
            if (this.options.data.id) {
                uid += '-' + this.options.data.id;
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
                            remoteUrl: '/admin/api/tags?flat=true&sortBy=name',
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
         * Inits the select for the account category
         */
        initCategorySelect: function(formData) {
            this.preselectedCategoryId = !!formData.accountCategory ? formData.accountCategory.id : null;
            this.accountCategoryData = null;

            this.sandbox.util.load(this.accountCategoryURL)
                .then(function(response) {

                    // data is data for select but not for overlay
                    var data = response._embedded.accountCategories;
                    this.accountCategoryData = this.copyArrayOfObjects(data);

                    // translate values for select but not for overlay
                    this.sandbox.util.foreach(data, function(el) {
                        el.category = this.sandbox.translate(el.category);
                    }.bind(this));

                    this.addDividerAndActionsForSelect(data);

                    this.sandbox.start([
                        {
                            name: 'select@husky',
                            options: {
                                el: '#accountCategory',
                                instanceName: 'account-category',
                                multipleSelect: false,
                                defaultLabel: this.sandbox.translate('contact.accounts.category.select'),
                                valueName: 'category',
                                repeatSelect: false,
                                preSelectedElements: [this.preselectedCategoryId],
                                data: data
                            }
                        }
                    ]);

                }.bind(this))
                .fail(function(textStatus, error) {
                    this.sandbox.logger.error(textStatus, error);
                }.bind(this));
        },

        /**
         * Inits the select for the account category
         */
        initResponsibleContactSelect: function(formData) {
            var preselectedResponsibleContactId = !!formData.responsiblePerson ? formData.responsiblePerson.id : null;
            this.responsiblePersons = null;

            this.sandbox.util.load(this.contactBySystemURL)
                .then(function(response) {

                    this.responsiblePersons = response._embedded.contacts;

                    this.sandbox.start([
                        {
                            name: 'select@husky',
                            options: {
                                el: '#responsiblePerson',
                                instanceName: 'responsible-person',
                                multipleSelect: false,
                                defaultLabel: this.sandbox.translate('dropdown.please-choose'),
                                valueName: 'fullName',
                                repeatSelect: false,
                                preSelectedElements: [preselectedResponsibleContactId],
                                data: this.responsiblePersons
                            }
                        }
                    ]);

                }.bind(this))
                .fail(function(textStatus, error) {
                    this.sandbox.logger.error(textStatus, error);
                }.bind(this));
        },

        /**
         * Adds divider and actions to dropdown elements
         * @param data
         */
        addDividerAndActionsForSelect: function(data) {
            data.push({divider: true});
            data.push({id: -1, category: this.sandbox.translate('public.edit-entries'), callback: this.showCategoryOverlay.bind(this), updateLabel: false});
        },

        /**
         * Triggers event to show overlay
         */
        showCategoryOverlay: function() {
            var $overlayContainer = this.sandbox.dom.$('<div id="overlayContainer"></div>'),
                config = {
                    instanceName: 'accountCategories',
                    el: '#overlayContainer',
                    openOnStart: true,
                    removeOnClose: true,
                    triggerEl: null,
                    title: this.sandbox.translate('public.edit-entries'),
                    data: this.accountCategoryData,
                    valueName: 'category'
                };

            this.sandbox.dom.remove('#overlayContainer');
            this.sandbox.dom.append(this.$el, $overlayContainer);
            this.sandbox.emit('sulu.types.' + this.instanceNameTypeOverlay + '.open', config);
        },

        /**
         * Shows the overlay to manage account categories
         */
        startCategoryOverlay: function() {
            var $container = this.sandbox.dom.createElement('<div/>');
            this.sandbox.dom.append(this.$el, $container);
            this.sandbox.start([
                {
                    name: 'type-overlay@suluadmin',
                    options: {
                        el: $container,
                        overlay: {
                            el: '#overlayContainer',
                            instanceName: 'accountCategories',
                            removeOnClose: true
                        },
                        instanceName: this.instanceNameTypeOverlay,
                        url: this.accountCategoryURL,
                        data: this.accountCategoryData
                    }
                }
            ]);
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
            var contactJson = this.options.data;

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
                    this.setFormData(data);
                    this.initCategorySelect(data);
                    this.initResponsibleContactSelect(data);
                    this.startCategoryOverlay();
                }.bind(this));
            }.bind(this));

            // initialize contact form
            this.sandbox.start([
                {
                    name: 'contact-form@sulucontact',
                    options: {
                        el: '#contact-edit-form',
                        fieldTypes: this.fieldTypes,
                        defaultTypes: this.defaultTypes
                    }
                }
            ]);
        },

        setFormData: function(data) {
            // add collection filters to form
            this.sandbox.emit('sulu.contact-form.add-collectionfilters', this.form);
            this.sandbox.form.setData(this.form, data).then(function() {
                this.sandbox.start(this.form);
                this.sandbox.emit('sulu.contact-form.add-required', ['email']);
                this.sandbox.emit('sulu.contact-form.content-set');
                this.dfdFormIsSet.resolve();
            }.bind(this));
        },

        // sets headline title to account name
        updateHeadline: function() {
            this.sandbox.emit('sulu.header.set-title', this.sandbox.dom.val(this.titleField));
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

            // delete account
            this.sandbox.on('sulu.header.toolbar.delete', function() {
                this.sandbox.emit('sulu.contacts.account.delete', this.options.data.id);
            }, this);

            // account saved
            this.sandbox.on('sulu.contacts.accounts.saved', function(data) {
                // reset forms data
                this.options.data = data;
                this.initContactData();
                this.setFormData(data);
                this.setHeaderBar(true);
            }, this);

            // account saved
            this.sandbox.on('sulu.header.toolbar.save', function() {
                this.submit();
            }, this);

            // back to list
            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.contacts.accounts.list');
            }, this);

            this.sandbox.on('sulu.types.' + this.instanceNameTypeOverlay + '.closed', function(data) {
                var selected = [];

                this.accountCategoryData = this.copyArrayOfObjects(data);
                selected.push(parseInt(!!this.selectedAccountCategory ? this.selectedAccountCategory : this.preselectedCategoryId, 10));
                this.addDividerAndActionsForSelect(data);

                // translate values for select but not for overlay
                this.sandbox.util.foreach(data, function(el) {
                    el.category = this.sandbox.translate(el.category);
                }.bind(this));

                this.sandbox.emit('husky.select.account-category.update', data, selected);
            }, this);
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

        submit: function() {
            if (this.sandbox.form.validate(this.form)) {
                var data = this.sandbox.form.getData(this.form);

                if (data.id === '') {
                    delete data.id;
                }

                data.tags = this.sandbox.dom.data(this.$find(constants.tagsId), 'tags');

                this.updateHeadline();

                // FIXME auto complete in mapper
                data.parent = {
                    id: this.sandbox.dom.attr('#company input', 'data-id')
                };

                this.sandbox.emit('sulu.contacts.accounts.save', data);
            }
        },

        /** @var Bool saved - defines if saved state should be shown */
        setHeaderBar: function(saved) {
            if (saved !== this.saved) {
                var type = (!!this.options.data && !!this.options.data.id) ? 'edit' : 'add';
                this.sandbox.emit('sulu.header.toolbar.state.change', type, saved, true);
            }
            this.saved = saved;
        },

        listenForChange: function() {
            this.dfdListenForChange.then(function() {
                this.sandbox.dom.on('#contact-form', 'change', function() {
                    this.setHeaderBar(false);
                }.bind(this), "select, input, textarea");

                this.sandbox.dom.on('#contact-form', 'keyup', function() {
                    this.setHeaderBar(false);
                }.bind(this), "input, textarea");

                // if a field-type gets changed or a field gets deleted
                this.sandbox.on('sulu.contact-form.changed', function() {
                    this.setHeaderBar(false);
                }.bind(this));
            }.bind(this));

            this.sandbox.on('husky.select.account-category.selected.item', function(id) {
                if (id > 0) {
                    this.selectedAccountCategory = id;
                    this.setHeaderBar(false);
                }
            }.bind(this));

            this.sandbox.on('husky.select.responsible-person.selected.item', function(id) {
                if (id > 0) {
                    this.selectedResponsiblePerson = id;
                    this.setHeaderBar(false);
                }
            }.bind(this));
        }
    };
});
