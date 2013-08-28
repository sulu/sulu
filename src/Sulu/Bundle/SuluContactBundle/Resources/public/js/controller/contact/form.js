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
], function ($, Backbone, Router, Account, Contact, Country, Email, EmailType, Phone, PhoneType, Address, AddressType) {

    'use strict';

    var contact;

    return Backbone.View.extend({

        events: {
            'submit #contact-form': 'submitForm',
            'click #addEmail': 'addEmail',
            'click #addPhone': 'addPhone',
            'click #addAddress': 'addAddress'
        },

        initialize: function () {
            this.render();
        },

        getTabs: function (id) {
            // TODO Tabs contact form
            return null;
        },

        render: function () {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/contact/template/contact/form'], function (Template) {
                var template;

                var contactJson = _.clone(Contact.prototype.defaults);
                var emailJson = _.clone(Email.prototype.defaults);
                var phoneJson = _.clone(Phone.prototype.defaults);
                var addressJson = _.clone(Address.prototype.defaults);

                if (!this.options.id) {
                    contact = new Contact();

                    this.fillFields(contactJson.emails, 2, emailJson);
                    this.fillFields(contactJson.phones, 2, phoneJson);
                    this.fillFields(contactJson.addresses, 1, addressJson);

                    template = _.template(Template, contactJson);
                    this.$el.html(template);
                } else {
                    contact = new Contact({id: this.options.id});
                    contact.fetch({
                        success: function (contact) {
                            var contactJson = contact.toJSON();

                            this.fillFields(contactJson.emails, 2, emailJson);
                            this.fillFields(contactJson.phones, 2, phoneJson);
                            this.fillFields(contactJson.addresses, 1, addressJson);

                            template = _.template(Template, contactJson);
                            this.$el.html(template);
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        submitForm: function (event) {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            event.preventDefault();
            contact.set({
                firstName: this.$('#firstName').val(),
                lastName: this.$('#lastName').val(),
                title: this.$('#title').val(),
                position: this.$('#position').val()
            });

            // FIXME remove
            var emailType = new EmailType({
                id: 1
            });

            $('#emails .email-item').each(function () {
                var email = contact.get('emails').get($(this).data('id'));
                if (!email) {
                    email = new Email();
                }
                var emailValue = $(this).find('.emailValue').val();
                if (emailValue) {
                    email.set({
                        email: emailValue,
                        emailType: emailType
                    });
                    contact.get('emails').add(email);
                }
            });

            // FIXME remove
            var phoneType = new PhoneType({
                id: 1
            });

            $('#phones .phone-item').each(function () {
                var phone = contact.get('phones').get($(this).data('id'));
                if (!phone) {
                    phone = new Phone();
                }
                var phoneValue = $(this).find('.phoneValue').val();
                if (phoneValue) {
                    phone.set({
                        phone: phoneValue,
                        phoneType: phoneType
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

            $('#addresses .address-item').each(function () {
                var address = contact.get('addresses').get($(this).data('id'));
                if (!address) {
                    address = new Address();
                }
                var street = $(this).find('.streetValue').val();
                var number = $(this).find('.numberValue').val();
                var addition = $(this).find('.additionValue').val();
                var zip = $(this).find('.zipValue').val();
                var city = $(this).find('.cityValue').val();
                var state = $(this).find('.stateValue').val();

                if (street && number && zip && city && state) {
                    address.set({
                        street: street,
                        number: number,
                        addition: addition,
                        zip: zip,
                        city: city,
                        state: state,
                        country: country,
                        addressType: addressType
                    });

                    contact.get('addresses').add(address);
                }
            });

            contact.save(null, {
                success: function () {
                    Router.navigate('contacts/people');
                }
            });
        },

        addEmail: function (event) {
            var $element = $(event.currentTarget);
            var id = $element.data("target-id");
            var $div = $('#' + id);

            $div.append(_.template(this.staticTemplates.emailRow(), {email: ''}));
        },

        addPhone: function (event) {
            var $element = $(event.currentTarget);
            var id = $element.data("target-id");
            var $div = $('#' + id);

            $div.append(_.template(this.staticTemplates.phoneRow(), {phone: ''}));
        },

        addAddress: function (event) {
            var $element = $(event.currentTarget);
            var id = $element.data("target-id");
            var $div = $('#' + id);

            require(['text!sulucontact/templates/contact/address.html'], function (Template) {
                $div.append(_.template(Template, {id: null, street: '', number: '', additional: '', zip: '', city: '', state: '', country: ''}));
            });
        },

        fillFields: function(field, minAmount, value) {
            while (field.length < minAmount) {
                field.push(value);
            }
        },

        staticTemplates: {
            emailRow: function () {
                return [
                    '<div class="grid-col-6 email-item">',
                    '<label>[Email Type]</label>',
                    '<input class="form-element emailValue" type="text" value="<%= email %>"/>',
                    '</div>'
                ].join('')
            },
            phoneRow: function () {
                return [
                    '<div class="grid-col-6 phone-item">',
                    '<label>[Phone Type]</label>',
                    '<input class="form-element phoneValue" type="text" value="<%= phone %>"/>',
                    '</div>'
                ].join('')
            }
        }
    });
});