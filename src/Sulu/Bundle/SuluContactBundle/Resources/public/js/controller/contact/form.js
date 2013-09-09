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
    'sulucontact/controller/form',
    'sulucontact/model/account',
    'sulucontact/model/contact',
    'sulucontact/model/country',
    'sulucontact/model/email',
    'sulucontact/model/emailType',
    'sulucontact/model/phone',
    'sulucontact/model/phoneType',
    'sulucontact/model/address',
    'sulucontact/model/addressType',
    'sulucontact/model/url',
    'sulucontact/model/urlType'
], function($, Backbone, Router, Form, Account, Contact, Country, Email, EmailType, Phone, PhoneType, Address, AddressType) {

    'use strict';

    return Form.extend({
        initialize: function() {
            this.setListUrl('contacts/people');
            this.render();
        },

        render: function() {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/contact/template/contact/form'], function(Template) {
                console.log('test');
                var template;

                var contactJson = $.extend(true, {}, Contact.prototype.defaults);

                if (!this.options.id) {
                    this.setModel(new Contact());
                    this.initTemplate(contactJson, template, Template);
                } else {
                    this.setModel(new Contact({id: this.options.id}));
                    this.getModel().fetch({
                        success: function(contact) {
                            var contactJson = contact.toJSON();
                            this.initTemplate(contactJson, template, Template);
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        setStatic: function() {
            this.getModel().set({
                firstName: this.$('#first-name').val(),
                lastName: this.$('#last-name').val(),
                title: this.$('#title').val(),
                position: this.$('#position').val(),
                account: {id: this.$('#company .name-value').data('id')}
            });
        }
    });
});
