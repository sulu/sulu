/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontact/model/contact'
], function(Contact) {

    'use strict';

    var form = '#contact-form',
        contact,
        emailItem, phoneItem, addressItem, tmp;

    return {

        view: true,

        templates: ['/contact/template/contact/form'],

        initialize: function() {
            this.sandbox.off(); // FIXME automate this call
            contact = new Contact();
            if (!!this.options.id) {
                contact.set({id: this.options.id});
                contact.fetch({
                    success: function(contact) {
                        this.render();
                    }.bind(this)
                });
            } else {
                this.render();
            }
        },

        render: function() {
            this.initializeHeader();

            this.$el.html(this.renderTemplate('/contact/template/contact/form'));

            emailItem = this.$el.find('#emails .email-item:first');
            phoneItem = this.$el.find('#phones .phone-item:first');
            addressItem = this.$el.find('#addresses .address-item:first');

            this.sandbox.on('husky.dropdown.type.item.click', this.typeClick.bind(this));

            var data = this.initData();
            tmp = this.sandbox.form.create(form);
            // FIXME when everything is loaded
            setTimeout(function() {
                this.sandbox.form.setData(form, data);
                this.sandbox.start(form);

                this.sandbox.form.addConstraint(form, '#emails .email-item:first input.email-value', 'required', {required: true});
                // FIXME abstract JQuery
                this.$el.find('#emails .email-item:first label span:first').after('<span>&nbsp;*</span>');
            }.bind(this), 10);

            this.bindDomEvents();
        },

        bindDomEvents: function() {
            this.sandbox.dom.on('#addEmail', 'click', this.addEmail.bind(this));
            this.sandbox.dom.on('#emails', 'click', this.removeEmail.bind(this), '.remove-email');

            this.sandbox.dom.on('#addPhone', 'click', this.addPhone.bind(this));
            this.sandbox.dom.on('#phones', 'click', this.removePhone.bind(this), '.remove-phone');

            this.sandbox.dom.on('#addAddress', 'click', this.addAddress.bind(this));
            this.sandbox.dom.on('#addresses', 'click', this.removeAddress.bind(this), '.remove-address');
        },

        initData: function() {
            var contactJson = contact.toJSON();
            this.fillFields(contactJson.emails, 2, {
                id: null,
                email: '',
                emailType: defaults.emailType
            });
            this.fillFields(contactJson.phones, 2, {
                id: null,
                phone: '',
                phoneType: defaults.phoneType
            });
            this.fillFields(contactJson.addresses, 1, {
                id: null,
                addressType: defaults.addressType
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

        initializeHeader: function() {
            if (!this.options.id) {
                this.sandbox.emit('husky.header.button-type', 'save');
                this.sandbox.on('husky.button.save.click', function(event) {
                    this.submit();
                }, this);
            } else {
                this.sandbox.emit('husky.header.button-type', 'saveDelete');
                this.sandbox.on('husky.button.save.click', function(event) {
                    this.submit();
                }, this);
                this.sandbox.on('husky.button.delete.click', function(event) {
                    this.deleteModel();
                }, this);
            }
        },

        deleteModel: function() {
            this.sandbox.logger.log('delete Model');

            // TODO delete model
        },

        submit: function() {
            this.sandbox.logger.log('save Model');

            if (this.sandbox.form.validate(form)) {
                var data = this.sandbox.form.getData(form);

                data.emails = _.filter(data.emails, function(email) {
                    if (email.id === "")delete email.id;
                    return email.email !== "";
                });
                data.phones = _.filter(data.phones, function(phone) {
                    if (phone.id === "")delete phone.id;
                    return phone.phone !== "";
                });
                data.addresses = _.filter(data.addresses, function(address) {
                    if (address.id === "")delete address.id;
                    return address.street !== "" &&
                        address.number !== "" &&
                        address.zip !== "" &&
                        address.city !== "" &&
                        address.state !== "";
                });
                if (data.id === '') delete data.id;

                this.sandbox.logger.log('data', data);

                contact.set(data);

                contact.save(null, {
                    success: function() {
                        this.gotoList();
                    }.bind(this)
                });
            }
        },

        gotoList: function() {
            this.sandbox.emit('sulu.router.navigate', 'contacts/people');
        },

        addEmail: function() {
            var $item = emailItem.clone();
            this.sandbox.dom.append('#emails', $item);

            this.sandbox.form.addField(form, $item.find('.id-value'));
            this.sandbox.form.addField(form, $item.find('.type-value'));
            this.sandbox.form.addField(form, $item.find('.email-value'));

            this.sandbox.start($item);
        },

        removeEmail: function(event) {
            var $item = $(event.target).parent().parent().parent();

            this.sandbox.form.removeField(form, $item.find('.id-value'));
            this.sandbox.form.removeField(form, $item.find('.type-value'));
            this.sandbox.form.removeField(form, $item.find('.email-value'));

            $item.remove();
        },

        addPhone: function() {
            var $item = phoneItem.clone();
            this.sandbox.dom.append('#phones', $item);

            this.sandbox.form.addField(form, $item.find('.id-value'));
            this.sandbox.form.addField(form, $item.find('.type-value'));
            this.sandbox.form.addField(form, $item.find('.phone-value'));

            this.sandbox.start($item);
        },

        removePhone: function(event) {
            var $item = $(event.target).parent().parent().parent();

            this.sandbox.form.removeField(form, $item.find('.id-value'));
            this.sandbox.form.removeField(form, $item.find('.type-value'));
            this.sandbox.form.removeField(form, $item.find('.phone-value'));

            $item.remove();
        },

        addAddress: function() {
            var $item = addressItem.clone();
            this.sandbox.dom.append('#addresses', $item);
            $(window).scrollTop($item.offset().top);

            this.sandbox.form.addField(form, $item.find('.id-value'));
            this.sandbox.form.addField(form, $item.find('.type-value'));
            this.sandbox.form.addField(form, $item.find('.street-value'));
            this.sandbox.form.addField(form, $item.find('.number-value'));
            this.sandbox.form.addField(form, $item.find('.addition-value'));
            this.sandbox.form.addField(form, $item.find('.zip-value'));
            this.sandbox.form.addField(form, $item.find('.city-value'));
            this.sandbox.form.addField(form, $item.find('.state-value'));
            this.sandbox.form.addField(form, $item.find('.country-value'));

            this.sandbox.start($item);
        },

        removeAddress: function(event) {
            var $item = $(event.target).parent().parent().parent();

            this.sandbox.form.removeField(form, $item.find('.id-value'));
            this.sandbox.form.removeField(form, $item.find('.type-value'));
            this.sandbox.form.removeField(form, $item.find('.street-value'));
            this.sandbox.form.removeField(form, $item.find('.number-value'));
            this.sandbox.form.removeField(form, $item.find('.addition-value'));
            this.sandbox.form.removeField(form, $item.find('.zip-value'));
            this.sandbox.form.removeField(form, $item.find('.city-value'));
            this.sandbox.form.removeField(form, $item.find('.state-value'));
            this.sandbox.form.removeField(form, $item.find('.country-value'));

            $item.remove();
        }

    };
});
