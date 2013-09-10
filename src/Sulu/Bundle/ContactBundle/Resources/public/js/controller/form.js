/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'jquery',
    'backbone',
    'router',
    'parsley',
    'sulucontact/model/account',
    'sulucontact/model/contact',
    'sulucontact/model/country',
    'sulucontact/model/email',
    'sulucontact/model/emailType',
    'sulucontact/model/phone',
    'sulucontact/model/phoneType',
    'sulucontact/model/address',
    'sulucontact/model/addressType'
], function($, Backbone, Router, Parsley, Account, Contact, Country, Email, EmailType, Phone, PhoneType, Address, AddressType) {

    'use strict';

    var model;

    var listUrl;

    var excludeItem = null;

    return Backbone.View.extend({

        events: {
            'submit #contact-form': 'submitForm',
            'click #addEmail': 'addEmailEvent',
            'click #addPhone': 'addPhoneEvent',
            'click #addAddress': 'addAddressEvent',
            'click .remove-email': 'removeEmail',
            'click .remove-phone': 'removePhone',
            'click .remove-address': 'removeAddress'
        },

        getModel: function() {
            return model;
        },

        setModel: function(value) {
            model = value;
        },

        setListUrl: function(value) {
            listUrl = value;
        },

        setExcludeItem: function(item) {
            excludeItem = item;
        },

        getExcludeItems: function() {
            if (excludeItem != null) return [excludeItem];
            return [];
        },

        getTabs: function(id) {
            // TODO Tabs contact form
            return null;
        },

        initOptions: function() {
            var $optionsRight = $('#headerbar-mid-right');
            $optionsRight.empty();
            var $optionsLeft = $('#headerbar-mid-left');
            $optionsLeft.empty();

            this.$saveButton = this.staticTemplates.saveButton('Save', function(event) {
                if (!this.$saveButton.hasClass('loading')) {
                    this.$saveButton.addClass('loading');
                    if (!!this.options.id) this.$deleteButton.hide();

                    if (this.$form.parsley('validate')) {
                        this.$form.submit();
                    } else {
                        this.$saveButton.removeClass('loading');
                        if (!!this.options.id) this.$deleteButton.show();
                    }
                }
            }.bind(this));
            $optionsLeft.append(this.$saveButton);

            if (!!this.options.id) {
                this.$deleteButton = this.staticTemplates.deleteButton('Delete', function() {
                    if (!this.$deleteButton.hasClass('loading')) {
                        this.$deleteButton.addClass('loading');
                        this.$saveButton.hide();

                        this.initRemoveDialog();
                    }
                }.bind(this));
                $optionsRight.append(this.$deleteButton);
            }
        },

        initTemplate: function(json, template, Template) {
            template = _.template(Template, json);
            this.$el.html(template);

            this.initEmails(json);
            this.initPhones(json);
            this.initAddresses(json);

            this.initFields(json);

            // create dialog box
            this.$dialog = $('#dialog').huskyDialog({
                backdrop: true,
                width: '650px'
            });


            this.initOptions();

            this.$form = this.$('form[data-validate="parsley"]');
            this.$form.parsley({validationMinlength: 0});
        },

        initDropDown: function(that, types) {
            var $element = $(that);
            var dd = $element.huskyDropDown({
                data: types,
                trigger: '.drop-down-trigger',
                setParentDropDown: true
            });

            dd.data('Husky.Ui.DropDown').on('drop-down:click-item', function(item) {
                console.log("click item: " + item);
                $element.find('.type-value').data('id', item.id);
                $element.find('.type-name').text(item.name);
            }.bind(this));
        },

        initFields: function(json) {
            // FIXME excludeItems never is used ???
            var excludeItems = [];
            if (!!this.options.id) {
                excludeItems = [
                    {id: this.options.id}
                ];
            }
            this.$('#company').huskyAutoComplete({
                url: '/contact/api/accounts/list?searchFields=id,name',
                value: (!!json.account) ? json.account : json.parent,
                excludeItems: this.getExcludeItems()
            });
        },

        submitForm: function(event) {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            event.preventDefault();
            this.setStatic();
            $('#emails .email-item').each(function() {
                var email = model.get('emails').get($(this).data('id'));
                if (!email) {
                    email = new Email();
                }
                var emailValue = $(this).find('.email-value').val();
                if (emailValue) {
                    email.set({
                        email: emailValue,
                        emailType: {id: $(this).find('.type-value').data('id')}
                    });
                    model.get('emails').add(email);
                }
            });

            $('#phones .phone-item').each(function() {
                var phone = model.get('phones').get($(this).data('id'));
                if (!phone) {
                    phone = new Phone();
                }
                var phoneValue = $(this).find('.phone-value').val();
                if (phoneValue) {
                    phone.set({
                        phone: phoneValue,
                        phoneType: {id: $(this).find('.type-value').data('id')}
                    });

                    model.get('phones').add(phone);
                }
            });

            $('#addresses .address-item').each(function() {
                var address = model.get('addresses').get($(this).data('id'));
                if (!address) {
                    address = new Address();
                }
                var street = $(this).find('.street-value').val();
                var number = $(this).find('.number-value').val();
                var addition = $(this).find('.addition-value').val();
                var zip = $(this).find('.zip-value').val();
                var city = $(this).find('.city-value').val();
                var state = $(this).find('.state-value').val();

                if (street && number && zip && city && state) {
                    address.set({
                        street: street,
                        number: number,
                        addition: addition,
                        zip: zip,
                        city: city,
                        state: state,
                        country: {id: parseInt($(this).find('.country .select-value').val())},
                        addressType: {id: $(this).find('.type-value').data('id')}
                    });

                    model.get('addresses').add(address);
                }
            });

            if (this.$form.parsley('validate')) {
                model.save(null, {
                    success: function() {
                        this.gotoList();
                    }.bind(this)
                });
            } else {
                this.$saveButton.removeClass('loading');
            }
        },

        initEmails: function(json) {
            var emailJson = _.clone(Email.prototype.defaults);
            this.fillFields(json.emails, 2, emailJson);

            var first = true;
            json.emails.forEach(function(item) {
                this.addEmail(this.$('#emails'), item, first);
                first = false;
            }.bind(this));
        },

        addEmailEvent: function(event) {
            var $element = $(event.currentTarget),
                id = $element.data("target-id"),
                $div = $('#' + id),
                phoneJson = _.clone(Email.prototype.defaults);
            this.addEmail($div, phoneJson);
        },

        addEmail: function($div, json, first) {
            var $email = $(_.template(this.staticTemplates.emailRow(first), json));
            $div.append($email);
            //$(window).scrollTop($email.offset().top);

            this.initDropDown($email.find('.type-value').parent(), emailTypes);
        },

        initPhones: function(json) {
            var phoneJson = _.clone(Phone.prototype.defaults);
            this.fillFields(json.phones, 2, phoneJson);

            json.phones.forEach(function(item) {
                this.addPhone(this.$('#phones'), item);
            }.bind(this));
        },

        addPhoneEvent: function(event) {
            var $element = $(event.currentTarget),
                id = $element.data("target-id"),
                $div = $('#' + id),
                phoneJson = _.clone(Phone.prototype.defaults);

            this.addPhone($div, phoneJson);
        },

        addPhone: function($div, json) {
            var $phone = $(_.template(this.staticTemplates.phoneRow(), json));
            $div.append($phone);
            //$(window).scrollTop($phone.offset().top);

            this.initDropDown($phone.find('.type-value').parent(), phoneTypes);
        },

        initAddresses: function(json) {
            var addressJson = _.clone(Address.prototype.defaults);
            this.fillFields(json.addresses, 1, addressJson);

            json.addresses.forEach(function(item) {
                this.addAddress(this.$('#addresses'), item, false);
            }.bind(this));
        },

        addAddressEvent: function(event) {
            var $element = $(event.currentTarget),
                id = $element.data("target-id"),
                $div = $('#' + id),
                addressJson = _.clone(Address.prototype.defaults);
            this.addAddress($div, addressJson, true);
        },

        addAddress: function($div, json, scroll) {
            require(['text!sulucontact/templates/contact/address.html'], function(Template) {
                var $address = $(_.template(Template, json));
                $div.append($address);

                if (scroll == true) {
                    $(window).scrollTop($address.offset().top);
                }
                this.initDropDown($address.find('.type-value').parent(), addressTypes);
                $address.find('.country').huskySelect({
                    data: countries,
                    selected: json.country,
                    defaultItem: defaults.country
                });
            }.bind(this));
        },

        removeEmail: function(event) {
            var $element = $(event.currentTarget).parent();
            var id = $element.data('id');
            if (id != null && id != '') {
                var email = model.get('emails').get(id);
                model.get('emails').remove(email);
            }
            $element.remove();
        },

        removePhone: function(event) {
            var $element = $(event.currentTarget).parent();
            var id = $element.data('id');
            if (id != null && id != '') {
                var phone = model.get('phones').get(id);
                model.get('phones').remove(phone);
            }
            $element.remove();
        },

        removeAddress: function(event) {
            var $element = $(event.currentTarget).parent().parent();
            var id = $element.data('id');
            if (id != null && id != '') {
                var address = model.get('addresses').get(id);
                model.get('addresses').remove(address);
            }
            $element.remove();
        },

        fillFields: function(field, minAmount, value) {
            while (field.length < minAmount) {
                field.push(value);
            }
        },

        gotoList: function() {
            this.$dialog.off();
            this.$saveButton.off();
            if (!!this.options.id) {
                this.$deleteButton.off();
            }

            Router.navigate(listUrl);
        },

        staticTemplates: {
            emailRow: function(first) {
                return [
                    '<div class="grid-col-6 email-item" data-id="<%= id %>">',
                        '<label class="bold drop-down-trigger type-value pull-left" data-id="<%= (!!emailType)?emailType.id :defaults.emailType.id %>">',
                            '<span class="type-name"><%= (!!emailType)?emailType.name : defaults.emailType.name %></span><span>' + (!!first ? '&nbsp;*' : '') + '</span>',
                            '<span class="dropdown-toggle inline-block"></span>',
                        '</label>',
                        '<div class="remove-email"><span class="icon-remove pull-right"></span></div>',
                        '<input class="form-element email-value" type="text" value="<%= email %>" data-type="email" ' + (!!first ? 'data-required="true"' : '') + ' data-trigger="focusout" />',
                    '</div>'
                ].join('')
            },
            phoneRow: function() {
                return [
                    '<div class="grid-col-6 phone-item" data-id="<%= id %>">',
                        '<label class="bold drop-down-trigger type-value pull-left" data-id="<%= (!!phoneType)? phoneType.id : defaults.phoneType.id %>">',
                            '<span class="type-name"><%= (!!phoneType)? phoneType.name : defaults.phoneType.name %></span>',
                            '<span class="dropdown-toggle inline-block"></span>',
                        '</label>',
                        '<div class="remove-phone"><span class="icon-remove pull-right"></span></div>',
                        '<input class="form-element phone-value" type="text" value="<%= phone %>" data-trigger="focusout" data-minlength="3" />',
                    '</div>'
                ].join('')
            },
            saveButton: function(text, fn) {
                var $button = $('<div id="saveButton" class="pull-left pointer"><div class="loading-content"><span class="icon-caution pull-left block"></span><span class="m-left-5 bold pull-left m-top-2 block">' + text + '</span></div></div>');
                $button.on('click', fn);
                return $button;
            },
            deleteButton: function(text, fn) {
                var $button = $('<div id="deleteButton" class="pull-right pointer"><div class="loading-content"><span class="icon-circle-remove pull-left block"></span><span class="m-left-5 bold pull-left m-top-2 block">' + text + '</span></div></div>');
                $button.on('click', fn);
                return $button;
            }
        }
    });
});
