/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['widget-groups'], function(WidgetGroups) {

    'use strict';

    var formSelector = '#bank-account-form',

        defaults = {
            headline: 'contact.accounts.title'
        },
        constants = {
            overlayIdTermsOfPayment: 'overlayContainerTermsOfPayment',
            overlayIdTermsOfDelivery: 'overlayContainerTermsOfDelivery',
            overlaySelectorTermsOfPayment: '#overlayContainerTermsOfPayment',
            overlaySelectorTermsOfDelivery: '#overlayContainerTermsOfDelivery',

            cgetTermsOfDeliveryURL: 'api/termsofdeliveries',
            cgetTermsOfPaymentURL: 'api/termsofpayments'
        };

    return {

        view: true,

        layout: function() {
            return {
                content: {
                    width: 'fixed'
                },
                sidebar: {
                    width: 'max',
                    cssClasses: 'sidebar-padding-50'
                }
            };
        },

        templates: ['/admin/contact/template/account/financials'],

        initialize: function() {

            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.saved = true;

            this.form = '#financials-form';
            this.termsOfDeliveryInstanceName = 'terms-of-delivery';
            this.termsOfPaymentInstanceName = 'terms-of-payment';

            this.setHeaderBar(true);

            this.render();

            this.listenForChange();

            if (!!this.options.data && !!this.options.data.id && WidgetGroups.exists('account-detail')) {
                this.initSidebar('/admin/widget-groups/account-detail?account=', this.options.data.id);
            }
        },

        initSidebar: function(url, id) {
            this.sandbox.emit('sulu.sidebar.set-widget', url + id);
        },

        render: function() {
            var data = this.options.data;

            this.html(this.renderTemplate(this.templates[0]));

            this.initForm(data);

            this.bindDomEvents();
            this.bindCustomEvents();
        },

        initTermsSelect: function(formData) {

            this.preselectedTermsOfPaymentId =
                !!formData.termsOfPayment ? [formData.termsOfPayment.id] : '';
            this.preselectedTermsOfDeliveryId =
                !!formData.termsOfDelivery ? [formData.termsOfDelivery.id] : '';

            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: '#termsOfPayment',
                        instanceName: this.termsOfPaymentInstanceName,
                        multipleSelect: false,
                        defaultLabel: this.sandbox.translate('public.please-choose'),
                        valueName: 'terms',
                        repeatSelect: false,
                        direction: 'bottom',
                        editable: true,
                        resultKey: 'termsOfPayments',
                        preSelectedElements: this.preselectedTermsOfPaymentId,
                        url: constants.cgetTermsOfPaymentURL
                    }
                },
                {
                    name: 'select@husky',
                    options: {
                        el: '#termsOfDelivery',
                        instanceName: this.termsOfDeliveryInstanceName,
                        multipleSelect: false,
                        defaultLabel: this.sandbox.translate('public.please-choose'),
                        valueName: 'terms',
                        repeatSelect: false,
                        direction: 'bottom',
                        editable: true,
                        resultKey: 'termsOfDeliveries',
                        preSelectedElements: this.preselectedTermsOfDeliveryId,
                        url: constants.cgetTermsOfDeliveryURL
                    }
                }
            ]);
        },

        initForm: function(data) {
            var formObject = this.sandbox.form.create(this.form);
            this.initFormHandling(data);

            formObject.initialized.then(function() {
                this.setFormData(data);
                this.initTermsSelect(data);
            }.bind(this));
        },

        setFormData: function(data) {
            // add collection filters to form
            this.sandbox.emit('sulu.contact-form.add-collectionfilters', this.form);
            this.sandbox.form.setData(this.form, data).then(function() {
                this.sandbox.start(this.form);
            }.bind(this)).fail(function(error) {
                this.sandbox.logger.error("An error occured when setting data!", error);
            }.bind(this));
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
            this.sandbox.on('sulu.contacts.accounts.financials.saved', function(data) {
                // reset forms data
                this.options.data = data;

                // TODO needed? problems?
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
                // TODO create event for saving accounts
                this.sandbox.emit('sulu.contacts.accounts.financials.save', data);
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
            this.sandbox.dom.on(this.form, 'change', function() {
                this.setHeaderBar(false);
            }.bind(this), '.changeListener select, ' +
                '.changeListener input, ' +
                '.changeListener textarea');

            this.sandbox.dom.on(this.form, 'keyup', function() {
                this.setHeaderBar(false);
            }.bind(this), '.changeListener select, ' +
                '.changeListener input, ' +
                '.changeListener textarea');

            // if a field-type gets changed or a field gets deleted
            this.sandbox.on('sulu.contact-form.changed', function() {
                this.setHeaderBar(false);
            }.bind(this));

            this.sandbox.on('husky.select.' + this.termsOfDeliveryInstanceName + '.selected.item', function(id) {
                if (id > 0) {
                    this.selectedTermsOfDelivery = id;
                    this.setHeaderBar(false);
                }
            }.bind(this));

            this.sandbox.on('husky.select.' + this.termsOfPaymentInstanceName + '.selected.item', function(id) {
                if (id > 0) {
                    this.selectedTermsOfPayment = id;
                    this.setHeaderBar(false);
                }
            }.bind(this));
        },

        initFormHandling: function(data) {
            // when  contact-form is initalized
            this.sandbox.on('sulu.contact-form.initialized', function() {

                this.sandbox.emit('sulu.contact-form.add-collectionfilters', this.form);
                var formObject = this.sandbox.form.create(formSelector);
                formObject.initialized.then(function() {
                    this.setFormData(data);
                }.bind(this));

            }.bind(this));

            // initialize contact form
            this.sandbox.start([
                {
                    name: 'contact-form@sulucontact',
                    options: {
                        el: '#financials-form'
                    }
                }
            ]);
        }
    };
});
