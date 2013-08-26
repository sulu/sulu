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
    'sulucontact/model/contact',
    'sulucontact/model/email',
    'sulucontact/model/phone',
    'sulucontact/model/address'
], function ($, Backbone, Router, Contact, Email, Phone, Address) {

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
            require(['text!/contact/template/contact/form'], function (Template) {
                var template;
                if (!this.options.id) {
                    contact = new Contact();
                    template = _.template(Template, {
                        firstName: '',
                        lastName: '',
                        title: '',
                        position: '',
                        account: null,
                        emails: [
                            {
                                id: null,
                                email: ''
                            },
                            {
                                id: null,
                                email: ''
                            }
                        ],
                        phones: [
                            {
                                id: null,
                                phone: ''
                            },
                            {
                                id: null,
                                phone: ''
                            }
                        ],
                        addresses: [
                            {
                                street: '',
                                number: '',
                                additional: '',
                                zip: '',
                                city: '',
                                state: ''
                            }
                        ]
                    });
                    this.$el.html(template);
                } else {
                    contact = new Contact({id: this.options.id});
                    contact.fetch({
                        success: function (contact) {
                            var contactJson = contact.toJSON();
                            while (contactJson.emails.length < 2) {
                                contactJson.emails.push({id: null, email: ''});
                            }
                            while (contactJson.phones.length < 2) {
                                contactJson.phones.push({id: null, phone: ''});
                            }
                            while (contactJson.addresses.length < 1) {
                                contactJson.addresses.push({
                                    id: null,
                                    street: '',
                                    number: '',
                                    additional: '',
                                    zip: '',
                                    city: '',
                                    state: ''
                                });
                            }
                            template = _.template(Template, contactJson);
                            this.$el.html(template);
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        submitForm: function (event) {
            event.preventDefault();
            contact.set({
                firstName: this.$('#firstName').val(),
                lastName: this.$('#lastName').val(),
                title: this.$('#title').val(),
                position: this.$('#position').val()
            });

            $('#emails .email-item').each(function () {
                var email = contact.get('emails').get($(this).data('id'));
                if (!email) {
                    email = new Email();
                }
                var emailValue = $(this).find('.emailValue').val();
                if (emailValue) {
                    email.set({
                        email: emailValue
                    });
                    contact.get('emails').add(email);
                }
            });

            $('#phones .phone-item').each(function () {
                var phone = contact.get('phones').get($(this).data('id'));
                if (!phone) {
                    phone = new Phone();
                }
                var phoneValue = $(this).find('.phoneValue').val();
                if (phoneValue) {
                    phone.set({
                        phone: phoneValue
                    });
                    contact.get('phones').add(phone);
                }
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

                address.set({
                    street: street,
                    number: number,
                    addition: addition,
                    zip: zip,
                    city: city,
                    state: state
                });

                if (street && number && zip && city && state) {
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

            require(['text!sulucontact/templates/address.template'], function (Template) {
                $div.append(_.template(Template, {id: null, street: '', number: '', additional: '', zip: '', city: '', state: '', country: ''}));
            });
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