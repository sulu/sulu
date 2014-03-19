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
    };

    return {

        view: true,

        templates: ['/admin/contact/template/account/form'],

        initialize: function() {

            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.form = '#contact-form';
            this.saved = true;


            this.accountType = this.getAccountType();
            this.setHeadlines(this.accountType);
            this.render();
            this.initContactForm();
            this.setHeaderBar(true);
            this.listenForChange();
        },

        render: function() {
            var data, excludeItem;

            this.sandbox.once('sulu.contacts.set-defaults', this.setDefaults.bind(this));

            this.html(this.renderTemplate('/admin/contact/template/account/form'));

            this.titleField = this.$find('#name');

            data = this.options.data;

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

            this.createForm(data);

            this.bindDomEvents();
            this.bindCustomEvents();
        },

        setDefaults: function(defaultTypes) {
            this.defaultTypes = defaultTypes;
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

        setHeadlines: function(accountType) {
            var titleAddition = this.sandbox.translate(accountType.translation),
                title = this.sandbox.translate(this.options.headline);

            if (!!this.options.data.id) {
                titleAddition += ' #' + this.options.data.id;
                title = this.options.data.name;
            }

            this.sandbox.emit('sulu.content.set-title-addition', titleAddition);
            this.sandbox.emit('sulu.content.set-title', title);
        },


        // CONTACT
        fillFields: function(field, minAmount, value) {
            if (!field) {
                return;
            }
            while (field.length < minAmount) {
                field.push(value);
            }
        },

        // CONTACT
        initContactData: function() {
            var contactJson = this.options.data;
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

//            this.fillFields(contactJson.addresses, 1, {
//                id: null,
//                addressType: this.defaultTypes.addressType,
//                street: this.sandbox.translate('contact.add.address')
//            });
            this.fillFields(contactJson.notes, 1, {
                id: null,
                value: ''
            });
            return contactJson;
        },

        // CONTACT
        initContactForm: function() {

            // TODO: get fields from configuration
            // TODO: FETCH ALL FIELDS : (phone, address, website, fax, email)

            var fieldTypes = ['address', 'email', 'fax', 'phone', 'website'],
                dropdownData = [];


            this.sandbox.util.foreach(fieldTypes, function(type, index) {
                dropdownData.push({id: index, name: type});
            });

            this.initContactData();


// TODO: implement options dropdown functionality for adding and editing contact details
//            // initialize dropdown
//            this.sandbox.start([
//                {
//                    name: 'dropdown@husky',
//                    toggle: '.contact-options-toggle',
//                    options: {
//                        el: '#contact-options-dropdown',
//                        alignment: 'right',
//                        shadow: true,
//                        data: [
//                            {
//                                id: 1,
//                                name: 'public.edit-fields',
//                                callback: function() {
//
//                                }
//                            },
//                            {
//                                id: 2,
//                                name: 'public.add-fields',
//                                callback: function() {
//                                    var tmpl = [
//                                            '<div class="grid-row">',
//                                            '   <div id="field-select" class="grid-col-6"></div>',
//                                            '   <div id="field-type-select" class="grid-col-6"></div>',
//                                            '</div>'
//                                        ],
//
//                                        newTemplate = this.sandbox.dom.createElement(tmpl.join(''));
//
//                                    this.sandbox.start([
//                                        {
//                                            name: 'overlay@husky',
//                                            options: {
//                                                title: this.sandbox.translate('public.add-fields'),
//                                                openOnStart: true,
//                                                removeOnClose: true,
//                                                data: newTemplate
//                                            }
//                                        },
//                                        {
//                                            name: 'dropdown-multiple-select@husky',
//                                            options: {
//                                                el: '#field-select',
//                                                instanceName: 'i1',
//                                                singleSelect: true,
//                                                data: dropdownData
//                                            }
//                                        }
//                                        // TODO: initialize second dropdown as well on beginning
//                                    ]);
//
//                                    this.sandbox.on('husky.dropdown.multiple.select.i1.selected.item', function(id) {
//                                        // TODO: now update second dropdown with correct values
//
//                                        this.sandbox.stop('#field-type-select');
//
//                                        this.sandbox.start([
//                                            {
//                                                name: 'dropdown-multiple-select@husky',
//                                                options: {
//                                                    el: '#field-type-select',
//                                                    singleSelect: true,
//                                                    instanceName: 'i2',
//                                                    data: [
//                                                        {id: 0, name: 'office'},
//                                                        {id: 1, name: 'private'}
//                                                    ]
//                                                }
//                                            }
//                                        ]);
//                                    });
//                                }
//                            }
//                        ]
//                    }
//                }
//            ]);
        },

        // sets headline title to account name
        updateHeadline: function() {
            this.sandbox.emit('sulu.content.set-title', this.sandbox.dom.val(this.titleField));
        },

        createForm: function(data) {
            var formObject = this.sandbox.form.create(this.form),
                emailSelector = '#contact-fields *[data-mapper-property-tpl="email-tpl"]:first';
            formObject.initialized.then(function() {

                this.sandbox.form.setData(this.form, data).then(function() {
                    this.sandbox.start(this.form);
                    this.sandbox.form.addConstraint(this.form, emailSelector + ' input.email-value', 'required', {required: true});
                    this.sandbox.dom.addClass(emailSelector + ' label span:first', 'required');
                }.bind(this));

            }.bind(this));

                this.sandbox.form.addCollectionFilter(this.form, 'emails', function(email) {
                    if (email.id === "") {
                        delete email.id;
                    }
                    return email.email !== "";
                });
                this.sandbox.form.addCollectionFilter(this.form, 'phones', function(phone) {
                    if (phone.id === "") {
                        delete phone.id;
                    }
                    return phone.phone !== "";
                });
                this.sandbox.form.addCollectionFilter(this.form, 'urls', function(url) {
                    if (url.id === "") {
                        delete url.id;
                    }
                    return url.url !== "";
                });
                this.sandbox.form.addCollectionFilter(this.form, 'notes', function(note) {
                    if (note.id === "") {
                        delete note.id;
                    }
                    return note.value !== "";
                });
//                this.sandbox.form.addCollectionFilter(this.form, 'addresses', function(address) {
//                    if (address.id === "") {
//                        delete address.id;
//                    }
//                    return address.street !== "" ||
//                        address.number !== "" ||
//                        address.zip !== "" ||
//                        address.city !== "" ||
//                        address.state !== "";
//                });


        },

        bindDomEvents: function() {
//            this.sandbox.dom.on(this.titleField, 'keyup', this.updateHeadline.bind(this));

            this.sandbox.dom.keypress(this.form, function(event) {
                if (event.which === 13) {
                    event.preventDefault();
                    this.submit();
                }
            }.bind(this));
        },

        bindCustomEvents: function() {
            // delete account
            this.sandbox.on('sulu.edit-toolbar.delete', function() {
                this.sandbox.emit('sulu.contacts.account.delete', this.options.data.id);
            }, this);

            // account saved
            this.sandbox.on('sulu.contacts.accounts.saved', function(data) {
                // reset forms data
                this.options.data = data;
                this.sandbox.form.setData(this.form, data);

                this.setHeaderBar(true);
            }, this);

            // account saved
            this.sandbox.on('sulu.edit-toolbar.save', function() {
                this.submit();
            }, this);

            // back to list
            this.sandbox.on('sulu.edit-toolbar.back', function() {
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
                this.sandbox.emit('sulu.edit-toolbar.content.state.change', type, saved);
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
        }

    };
});
