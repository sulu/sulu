/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app-config'], function(AppConfig) {

    'use strict';

    var defaults = {
            headline: 'contact.accounts.title'
        },
        fields = ['urls', 'emails', 'faxes', 'phones', 'notes', 'addresses'];

    return {

        view: true,

        templates: ['/admin/contact/template/account/form'],

        initialize: function() {

            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.form = '#contact-form';
            this.saved = true;
            this.accountCategoryURL = 'api/account/categories';

            this.accountType = this.getAccountType();
            this.setHeadlines(this.accountType);
            this.render();
            this.setHeaderBar(true);
            this.listenForChange();
        },

        render: function() {
            var data, excludeItem;

            this.sandbox.once('sulu.contacts.set-defaults', this.setDefaults.bind(this));
            this.sandbox.once('sulu.contacts.set-types', this.setTypes.bind(this));

            this.html(this.renderTemplate('/admin/contact/template/account/form'));

            this.titleField = this.$find('#name');

            data = this.initContactData();

            excludeItem = [];
            if (!!this.options.data.id) {
                excludeItem.push({id: this.options.data.id});
            }


            this.sandbox.start([
                {
                    name: 'auto-complete@husky',
                    options: {
                        el: '#company',
                        remoteUrl: '/admin/api/accounts?searchFields=id,name&flat=true',
                        getParameter: 'search',
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
            this.initCategorySelect(data);
            this.startCategoryOverlay();

            this.bindDomEvents();
            this.bindCustomEvents();
        },


        /**
         * Inits the select for the account category
         */
        initCategorySelect: function(formData) {

            var preselectedElemendId = !!formData.accountCategory ? formData.accountCategory.id : null;
            this.accountCategoryData = null;

            this.sandbox.util.load(this.accountCategoryURL)
                .then(function(response) {

                    var data = response['_embedded'],$overlayContainer;
                    this.accountCategoryData = data.slice(0,data.length);

                    data.push({divider: true});
                    data.push({id: -1, category: this.sandbox.translate('contacts.accounts.manage.categories'), callback: this.showCategoryOverlay.bind(this)});

                    this.sandbox.start([
                        {
                            name: 'select@husky',
                            options: {
                                el: '#accountCategory',
                                instanceName: 'account-category',
                                multipleSelect: false,
                                defaultLabel: this.sandbox.translate('contacts.accounts.category.select'),
                                valueName: 'category',
                                repeatSelect: true,
                                preSelectedElements: [preselectedElemendId],
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
                    title: this.sandbox.translate('contacts.accounts.manage.categories.title'),
                    data: this.accountCategoryData
                };

            this.sandbox.dom.append('body', $overlayContainer);
            this.sandbox.emit('sulu.types.open', config);
        },

        /**
         * Shows the overlay to manage account categories
         */
        startCategoryOverlay: function() {

            this.sandbox.start([
                {
                    name: 'type-overlay@suluadmin',
                    options: {
                        overlay: {
                            instanceName: 'accountCategory'
                        },
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
         * returns the accounttype
         * @returns {number}
         */
        getAccountType: function() {
            var typeInfo, compareAttribute,
                accountType = 0,
                accountTypes = AppConfig.getSection('sulu-contact').accountTypes; // get account types

            // if newly created account, get type id
            if (!!this.options.data.id) {
                typeInfo = this.options.data.type;
                compareAttribute = 'id';
            } else if (!!this.options.accountTypeName) {
                typeInfo = this.options.accountTypeName;
                compareAttribute = 'name';
            } else {
                typeInfo = 0;
                compareAttribute = 'id';
            }

            // get account type information
            this.sandbox.util.foreach(accountTypes, function(type) {
                if (type[compareAttribute] === typeInfo) {
                    accountType = type;
                    this.options.data.type = type.id;
                    return false; // break loop
                }
            }.bind(this));

            return accountType;
        },

        /**
         * sets headline to the current title input
         * @param accountType
         */
        setHeadlines: function(accountType) {
            var breadcrumb = [
                    {title: 'navigation.contacts'},
                    {title: 'contact.accounts.title', event: 'sulu.contacts.accounts.list'}
                ],
                title = this.sandbox.translate(this.options.headline);

            if (!!this.options.data.id) {
                breadcrumb.push({title: accountType.translation + ' #' + this.options.data.id});
                title = this.options.data.name;
            } else {
                breadcrumb.push({title: accountType.translation});
            }

            this.sandbox.emit('sulu.header.set-title', title);
            this.sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
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
            // when  contact-form is initalized
            this.sandbox.on('sulu.contact-form.initialized', function() {
                // set form data
                var formObject = this.sandbox.form.create(this.form);
                formObject.initialized.then(function() {
                    this.setFormData(data);
                }.bind(this));
            }.bind(this));

            // initialize contact form
            this.sandbox.start([
                {
                    name: 'contact-form@sulucontact',
                    options: {
                        el: '#contact-options-dropdown',
                        fieldTypes: this.fieldTypes
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
            }.bind(this));
        },

        // sets headline title to account name
        updateHeadline: function() {
            this.sandbox.emit('sulu.header.set-title', this.sandbox.dom.val(this.titleField));
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
        },


        submit: function() {
            if (this.sandbox.form.validate(this.form)) {
                var data = this.sandbox.form.getData(this.form);

                if (data.id === '') {
                    delete data.id;
                }

                this.updateHeadline();

                // FIXME auto complete in mapper
                data.parent = {
                    id: this.sandbox.dom.data('#company input', 'id')
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
            this.sandbox.dom.on('#contact-form', 'change', function() {
                this.setHeaderBar(false);
            }.bind(this), "select, input, textarea");
            // TODO: only activate this, if wanted
            this.sandbox.dom.on('#contact-form', 'keyup', function() {
                this.setHeaderBar(false);
            }.bind(this), "input, textarea");

            // if a field-type gets changed or a field gets deleted
            this.sandbox.on('sulu.contact-form.changed', function() {
                this.setHeaderBar(false);
            }.bind(this));
        }

    };
});
