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

            this.initContactForm();

            this.createForm(data);

            this.bindDomEvents();
            this.bindCustomEvents();
        },


        /**
         * is getting called when template is initialized
         * @param defaultTypes
         */
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

        /**
         * sets headline to the current title input
         * @param accountType
         */
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
            this.fillFields(contactJson.notes, 1, {
                id: null,
                value: ''
            });
//            this.fillFields(contactJson.addresses, 1, {
//                id: null,
//                addressType: this.defaultTypes.addressType,
//                street: this.sandbox.translate('contact.add.address')
//            });
            return contactJson;
        },

        // CONTACT
        initForm: function() {

            var formObject = this.sandbox.form.create(this.form);

            formObject.initialized.then(function() {
                // now set data
                this.initializeData(data);
            }.bind(this));

            // initialize contact form
            this.sandbox.start([{
                name: 'contact-form@sulucontact',
                options: {
                    el:'#contact-options-dropdown',
                    trigger: '.contact-options-toggle',
                }
            }]);
            // when initalized
            this.sandbox.on('sulu.contact-form.initialized', function() {
                // create contact-form elements and prepare data
                this.sandbox.emit('sulu.contact-form.create', this.form);
            }.bind(this));
        },

        // sets headline title to account name
        updateHeadline: function() {
            this.sandbox.emit('sulu.content.set-title', this.sandbox.dom.val(this.titleField));
        },

        initializeData: function(data) {
            var emailSelector = '#contact-fields *[data-mapper-property-tpl="email-tpl"]:first';
            this.sandbox.form.setData(this.form, data).then(function() {
                this.sandbox.start(this.form);
                this.sandbox.form.addConstraint(this.form, emailSelector + ' input.email-value', 'required', {required: true});
                this.sandbox.dom.addClass(emailSelector + ' label span:first', 'required');
            }.bind(this));
        },

        createForm: function(data) {






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
                this.initContactData();
                this.initializeData(data);
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
                this.sandbox.emit('sulu.edit-toolbar.content.state.change', type, saved, true);
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
