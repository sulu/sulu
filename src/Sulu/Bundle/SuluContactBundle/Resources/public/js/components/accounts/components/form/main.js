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


        return {

            view: true,

            templates: ['/admin/contact/template/account/form'],

            initialize: function() {
                this.form = '#contact-form';
                this.saved = true;
                this.addressCounter=1;
                this.render();
                this.setHeaderBar(true);
                this.listenForChange();
            },

            render: function() {
                var data, excludeItem, key, accountTypeId,
                    typeInfo, compareAttribute,
                    accountTypes = AppConfig.getSection('sulu-contact').accountTypes; // get account types


                // if newly created account, get type id
                if (!!this.options.data.id) {
                    typeInfo = this.options.data.type;
                    compareAttribute = 'id';
                } else {
                    typeInfo = this.options.accountTypeName;
                    compareAttribute = 'name';
                }

                // get account type information
                this.sandbox.util.foreach(accountTypes, function(type) {
                    if (type[compareAttribute] === typeInfo) {
                        this.accountType = type;
                        this.options.data.type = type.id;
                        return false; // break loop
                    }
                }.bind(this));


                this.sandbox.once('sulu.contacts.set-defaults', this.setDefaults.bind(this));

                this.html(this.renderTemplate('/admin/contact/template/account/form'));

                this.emailItem = this.$find('#emails .emails-item:first');
                this.phoneItem = this.$find('#phones .phones-item:first');
                this.addressItem = this.$find('#addresses .addresses-item:first');

                this.sandbox.on('husky.dropdown.type.item.click', this.typeClick.bind(this));

                data = this.initData();
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
                            excludes: [{id: data.id, name: data.name}]
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

            createForm: function(data) {
                var formObject = this.sandbox.form.create(this.form);
                formObject.initialized.then(function() {

                    this.sandbox.form.setData(this.form, data).then(function() {
                        if (!!data.urls[0]) {
                            this.sandbox.dom.val('#url', data.urls[0].url);
                        }

                        this.sandbox.start(this.form);
                        this.sandbox.form.addConstraint(this.form, '#emails .emails-item:first input.email-value', 'required', {required: true});
                        this.sandbox.dom.find('#emails .emails-item:first .remove-email').remove();
                        this.sandbox.dom.addClass('#emails .emails-item:first label span:first', 'required');
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
                this.sandbox.form.addCollectionFilter(this.form, 'addresses', function(address) {
                    if (address.id === "") {
                        delete address.id;
                    }
                    return address.street !== "" ||
                        address.number !== "" ||
                        address.zip !== "" ||
                        address.city !== "" ||
                        address.state !== "";
                });
            },

            bindDomEvents: function() {
                this.sandbox.dom.on('#addEmail', 'click', this.addEmail.bind(this));
                this.sandbox.dom.on('#emails', 'click', this.removeEmail.bind(this), '.remove-email');

                this.sandbox.dom.on('#addPhone', 'click', this.addPhone.bind(this));
                this.sandbox.dom.on('#phones', 'click', this.removePhone.bind(this), '.remove-phone');

                this.sandbox.dom.on('#addAddress', 'click', this.addAddress.bind(this));
                this.sandbox.dom.on('#addresses', 'click', this.removeAddress.bind(this), '.remove-address');

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
                this.sandbox.on('sulu.contacts.accounts.saved', function(id) {
                    this.options.data.id = id;
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

            initData: function() {
                var contactJson = this.options.data;
                this.fillFields(contactJson.emails, 2, {
                    id: null,
                    email: '',
                    emailType: this.defaultTypes.emailType
                });
                this.fillFields(contactJson.phones, 2, {
                    id: null,
                    phone: '',
                    phoneType: this.defaultTypes.phoneType
                });
                this.fillFields(contactJson.addresses, 1, {
                    id: null,
                    addressType: this.defaultTypes.addressType
                });
                return contactJson;
            },

            typeClick: function(event, $element) {
                this.sandbox.logger.log('email click', event);
                $element.find('*.type-value').data('element').setValue(event);
            },

            fillFields: function(field, minAmount, value) {
                while (field.length < minAmount) {
                    field.push(value);
                }
            },

            submit: function() {
                if (this.sandbox.form.validate(this.form)) {
                    var data = this.sandbox.form.getData(this.form);

                    data.urls = [
                        {
                            url: this.sandbox.dom.val('#url'),
                            urlType: {
                                id: this.defaultTypes.urlType.id
                            }
                        }
                    ];

                    if (data.id === '') {
                        delete data.id;
                    }

                    // FIXME auto complete in mapper
                    data.parent = {
                        id: this.sandbox.dom.data('#company input', 'id')
                    };

                    this.sandbox.emit('sulu.contacts.accounts.save', data);
                }
            },


            // checks if el is in next row and adds margin top if necessary
            checkRowMargin: function(item) {
                var parent = this.sandbox.dom.parent(item);
                if (this.sandbox.dom.children(parent).length > 2) {
                    this.sandbox.dom.addClass(item, 'm-top-20');
                }
            },

            addEmail: function() {
                var $item = this.emailItem.clone();
                this.sandbox.dom.append('#emails', $item);

                this.sandbox.form.addField(this.form, $item.find('.id-value'));
                this.sandbox.form.addField(this.form, $item.find('.type-value'));
                this.sandbox.form.addField(this.form, $item.find('.email-value'));

                this.checkRowMargin($item);

                this.sandbox.start($item);
            },

            removeEmail: function(event) {
                var $item = $(event.target).parent().parent().parent();

                this.sandbox.form.removeField(this.form, $item.find('.id-value'));
                this.sandbox.form.removeField(this.form, $item.find('.type-value'));
                this.sandbox.form.removeField(this.form, $item.find('.email-value'));

                $item.remove();
            },

            addPhone: function() {
                var $item = this.phoneItem.clone();
                this.sandbox.dom.append('#phones', $item);

                this.sandbox.form.addField(this.form, $item.find('.id-value'));
                this.sandbox.form.addField(this.form, $item.find('.type-value'));
                this.sandbox.form.addField(this.form, $item.find('.phone-value'));

                this.checkRowMargin($item);

                this.sandbox.start($item);
            },

            removePhone: function(event) {
                var $item = $(event.target).parent().parent().parent();

                this.sandbox.form.removeField(this.form, $item.find('.id-value'));
                this.sandbox.form.removeField(this.form, $item.find('.type-value'));
                this.sandbox.form.removeField(this.form, $item.find('.phone-value'));

                $item.remove();
            },

            addAddress: function() {
                var $item = this.addressItem.clone();
                $item = this.setLabelsAndIdsForAddressItem($item);
                this.sandbox.dom.append('#addresses', $item);
                $(window).scrollTop($item.offset().top);

                this.sandbox.form.addField(this.form, $item.find('.id-value'));
                this.sandbox.form.addField(this.form, $item.find('.type-value'));
                this.sandbox.form.addField(this.form, $item.find('.street-value'));
                this.sandbox.form.addField(this.form, $item.find('.number-value'));
                this.sandbox.form.addField(this.form, $item.find('.addition-value'));
                this.sandbox.form.addField(this.form, $item.find('.zip-value'));
                this.sandbox.form.addField(this.form, $item.find('.city-value'));
                this.sandbox.form.addField(this.form, $item.find('.state-value'));
                this.sandbox.form.addField(this.form, $item.find('.country-value'));

                this.sandbox.start($item);
            },

            setLabelsAndIdsForAddressItem: function($item){

                var $labels = this.sandbox.dom.find('label[for]', $item),
                    $inputs = this.sandbox.dom.find('input[type=text],select', $item);

                this.sandbox.dom.each($inputs, function(index, value){

                    var elementName = this.sandbox.dom.data(value, 'mapper-property');

                    this.sandbox.dom.attr($labels[index], {for: elementName+this.addressCounter.toString()});
                    this.sandbox.dom.attr($inputs[index], {id: elementName+this.addressCounter.toString()});

                }.bind(this));

                return $item;
            },

            removeAddress: function(event) {
                var $item = $(event.target).parent().parent().parent();

                this.sandbox.form.removeField(this.form, $item.find('.id-value'));
                this.sandbox.form.removeField(this.form, $item.find('.type-value'));
                this.sandbox.form.removeField(this.form, $item.find('.street-value'));
                this.sandbox.form.removeField(this.form, $item.find('.number-value'));
                this.sandbox.form.removeField(this.form, $item.find('.addition-value'));
                this.sandbox.form.removeField(this.form, $item.find('.zip-value'));
                this.sandbox.form.removeField(this.form, $item.find('.city-value'));
                this.sandbox.form.removeField(this.form, $item.find('.state-value'));
                this.sandbox.form.removeField(this.form, $item.find('.country-value'));

                $item.remove();
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
                }.bind(this), "select, input");
                this.sandbox.dom.on('#contact-form', 'keyup', function() {
                    this.setHeaderBar(false);
                }.bind(this), "input");
            }

        };
});
