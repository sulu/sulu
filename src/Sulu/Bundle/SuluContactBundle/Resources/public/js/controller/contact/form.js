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
    'sulucontact/model/account',
    'sulucontact/model/contact',
    'sulucontact/model/country',
    'sulucontact/model/email',
    'sulucontact/model/emailType',
    'sulucontact/model/phone',
    'sulucontact/model/phoneType',
    'sulucontact/model/address',
    'sulucontact/model/addressType'
], function($, Backbone, Router, Account, Contact, Country, Email, EmailType, Phone, PhoneType, Address, AddressType) {

    'use strict';

    var contact;

    return Backbone.View.extend({

        events: {
            'submit #contact-form': 'submitForm',
            'click #addEmail': 'addEmail',
            'click #addPhone': 'addPhone',
            'click #addAddress': 'addAddress',
            'click .remove-email': 'removeEmail',
            'click .remove-phone': 'removePhone',
            'click .remove-address': 'removeAddress'
        },

        initialize: function() {
            this.render();
        },

        getTabs: function(id) {
            // TODO Tabs contact form
            return null;
        },

        render: function() {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/contact/template/contact/form'], function(Template) {
                var template;

                var contactJson = _.clone(Contact.prototype.defaults);

                if (!this.options.id) {
                    contact = new Contact();
                    this.initTemplate(contactJson, template, Template);
                } else {
                    contact = new Contact({id: this.options.id});
                    contact.fetch({
                        success: function(contact) {
                            var contactJson = contact.toJSON();
                            this.initTemplate(contactJson, template, Template);
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        initTemplate: function(contactJson, template, Template) {
            var emailJson = _.clone(Email.prototype.defaults);
            var phoneJson = _.clone(Phone.prototype.defaults);
            var addressJson = _.clone(Address.prototype.defaults);

            this.fillFields(contactJson.emails, 2, emailJson);
            this.fillFields(contactJson.phones, 2, phoneJson);
            this.fillFields(contactJson.addresses, 1, addressJson);

            template = _.template(Template, contactJson);
            this.$el.html(template);

            this.initFields();
        },

        initFields: function() {
            this.$('#company').huskyAutoComplete({
                url: '/contacts/api/accounts'
            });
            this.$('#company').huskyAutoComplete({
                url: '/contacts/api/accounts'
            });

            var that = this;
            this.$('.type-value').each(function(event) {
                if ($(this).parent().hasClass('email-item')) {
                    that.initDropDown($(this).parent(), emailTypes);
                } else if ($(this).parent().hasClass('phone-item')) {
                    that.initDropDown($(this).parent(), phoneTypes);
                } else if ($(this).parent().parent().parent().hasClass('address-item')) {
                    that.initDropDown($(this).parent(), addressTypes);
                }
            });
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

        submitForm: function(event) {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            event.preventDefault();
            contact.set({
                firstName: this.$('#first-name').val(),
                lastName: this.$('#last-name').val(),
                title: this.$('#title').val(),
                position: this.$('#position').val(),
                account: new Account({id: this.$('#company').data('id')})
            });

            // FIXME remove
            var emailType = new EmailType({
                id: 1
            });

            $('#emails .email-item').each(function() {
                var email = contact.get('emails').get($(this).data('id'));
                if (!email) {
                    email = new Email();
                }
                var emailValue = $(this).find('.email-value').val();
                if (emailValue) {
                    email.set({
                        email: emailValue,
                        emailType: {id: $(this).find('.type-value').data('id')}
                    });
                    contact.get('emails').add(email);
                }
            });

            // FIXME remove
            var phoneType = new PhoneType({
                id: 1
            });

            $('#phones .phone-item').each(function() {
                var phone = contact.get('phones').get($(this).data('id'));
                if (!phone) {
                    phone = new Phone();
                }
                var phoneValue = $(this).find('.phone-value').val();
                if (phoneValue) {
                    phone.set({
                        phone: phoneValue,
                        phoneType: {id: $(this).find('.type-value').data('id')}
                    });

                    contact.get('phones').add(phone);
                }
            });

            // FIXME remove
            var addressType = new AddressType({
                id: 1
            });
            var country = new Country({
                id: 1
            });

            $('#addresses .address-item').each(function() {
                var address = contact.get('addresses').get($(this).data('id'));
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
                        country: country,
                        addressType: {id: $(this).find('.type-value').data('id')}
                    });

                    contact.get('addresses').add(address);
                }
            });

            contact.save(null, {
                success: function() {
                    Router.navigate('contacts/people');
                }
            });
        },

        addEmail: function(event) {
            var $element = $(event.currentTarget);
            var id = $element.data("target-id");
            var $div = $('#' + id);

            var $email = $(_.template(this.staticTemplates.emailRow(), {email: ''}));
            $div.append($email);
            //$(window).scrollTop($email.offset().top);

            this.initDropDown($email.find('.type-value').parent(), emailTypes);
        },

        addPhone: function(event) {
            var $element = $(event.currentTarget);
            var id = $element.data("target-id");
            var $div = $('#' + id);

            var $phone = $(_.template(this.staticTemplates.phoneRow(), {phone: ''}));
            $div.append($phone);
            //$(window).scrollTop($phone.offset().top);

            this.initDropDown($phone.find('.type-value').parent(), phoneTypes);
        },

        addAddress: function(event) {
            var $element = $(event.currentTarget);
            var id = $element.data("target-id");
            var $div = $('#' + id);

            require(['text!sulucontact/templates/contact/address.html'], function(Template) {
                var $address = $(_.template(Template, {address: {
                    id: null,
                    street: '',
                    number: '',
                    additional: '',
                    zip: '',
                    city: '',
                    state: '',
                    country: ''
                }}));
                $div.append($address);
                $(window).scrollTop($address.offset().top);

                this.initDropDown($address.find('.type-value').parent(), addressTypes);
            }.bind(this));
        },

        removeEmail: function(event) {
            var $element = $(event.currentTarget).parent();
            var id = $element.data('id');
            if (id != null && id != '') {
                var email = contact.get('emails').get(id);
                contact.get('emails').remove(email);
            }
            $element.remove();
        },

        removePhone: function(event) {
            var $element = $(event.currentTarget).parent();
            var id = $element.data('id');
            if (id != null && id != '') {
                var phone = contact.get('phones').get(id);
                contact.get('phones').remove(phone);
            }
            $element.remove();
        },

        removeAddress: function(event) {
            var $element = $(event.currentTarget).parent().parent();
            var id = $element.data('id');
            if (id != null && id != '') {
                var address = contact.get('addresses').get(id);
                contact.get('addresses').remove(address);
            }
            $element.remove();
        },

        fillFields: function(field, minAmount, value) {
            while (field.length < minAmount) {
                field.push(value);
            }
        },

        staticTemplates: {
            emailRow: function() {
                return [
                    '<div class="grid-col-6 email-item" data-id="<%= email.id %>">',
                    '<label class="bold drop-down-trigger type-value pull-left" data-id="<%= (!!email.emailType)?email.emailType.id :defaults.emailType.id %>">',
                    '<span class="type-name"><%= (!!email.emailType)?email.emailType.name : defaults.emailType.name %></span>',
                    '<span class="dropdown-toggle inline"></span>',
                    '</label>',
                    '<div class="remove-email"><span class="icon-remove pull-right"></span></div>',
                    '<input class="form-element emailValue" type="text" value="<%= email.email %>"/>',
                    '</div>'
                ].join('')
            },
            phoneRow: function() {
                return [
                    '<div class="grid-col-6 phone-item" data-id="<%= phone.id %>">',
                    '<label class="bold drop-down-trigger type-value pull-left" data-id="<%= (!!phone.phoneType)? phone.phoneType.id : defaults.phoneType.id %>">',
                    '<span class="type-name"><%= (!!phone.phoneType)? phone.phoneType.name : defaults.phoneType.name %></span>',
                    '<span class="dropdown-toggle inline"></span>',
                    '</label>',
                    '<div class="remove-phone"><span class="icon-remove pull-right"></span></div>',
                    '<input class="form-element phoneValue" type="text" value="<%= phone.phone %>"/>',
                    '</div>'
                ].join('')
            }
        }
    });
});