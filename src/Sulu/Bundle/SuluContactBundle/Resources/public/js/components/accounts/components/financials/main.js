/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    var bankAccountForm = '#bank-account-form',

        defaults = {
            headline: 'contact.accounts.title'
        },
        constants = {
            bankAccountsId: '#bankAccounts',
            bankAccountAddId: '#bank-account-add',
            addBankAccountsWrapper: '.grid-row'
        },

        customTemplates = {
            addBankAccountsIcon: [
                '<div class="grid-row">',
                '    <div class="grid-col-12">',
                '       <span id="bank-account-add" class="fa-plus-circle icon bank-account-add clickable pointer m-left-140"></span>',
                '   </div>',
                '</div>'].join('')
        };

    return {

        view: true,

        templates: ['/admin/contact/template/account/financials'],

        initialize: function() {

            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.saved = true;

            this.form = '#financials-form';

            this.termsOfDeliveryURL = 'api/termsofdeliveries';
            this.termsOfPaymentURL = 'api/termsofpayments';

            this.setHeaderBar(true);

            this.render();

            this.listenForChange();
        },

        render: function() {
            var data = this.options.data;

            this.html(this.renderTemplate(this.templates[0]));

            this.initForm(data);
            this.initTermsSelect(data);
            this.startTermsOfPaymentOverlay();

            this.bindDomEvents();
            this.bindCustomEvents();
        },

        /**
         * Shows the overlay to manage account categories
         */
        startTermsOfPaymentOverlay: function() {
            this.sandbox.start([
                {
                    name: 'type-overlay@suluadmin',
                    options: {
                        overlay: {
                            el: '#overlayContainer',
                            instanceName: 'terms-overlay',
                            removeOnClose: true
                        },
                        url: this.termsOfPaymentURL,
                        data: this.termsOfPaymentData
                    }
                }
            ]);
        },

        /**
         * Inits the select for the account category
         */
        initTermsSelect: function(formData) {
            this.preselectedTermsOfPaymentId = !!formData.termsOfPayment ? formData.termsOfPayment.id : null;
            this.termsOfPaymentData = null;
            this.preselectedTermsOfDeliveryId = !!formData.termsOfDelivery ? formData.termsOfDelivery.id : null;
            this.termsOfDeliveryData = null;

            this.sandbox.util.load(this.termsOfPaymentURL)
                .then(function(response) {

                    // data is data for select but not for overlay
                    var data = response._embedded;
                    this.termsOfPaymentData = this.copyArrayOfObjects(data);

                    // translate values for select but not for overlay
                    this.sandbox.util.foreach(data, function(el) {
                        el.terms = this.sandbox.translate(el.terms);
                    }.bind(this));

                    this.addDividerAndActionsForPaymentSelect(data);

                    this.sandbox.start([
                        {
                            name: 'select@husky',
                            options: {
                                el: '#termsOfPayment',
                                instanceName: 'terms-of-payment',
                                multipleSelect: false,
                                defaultLabel: this.sandbox.translate('contact.accounts.termsOfPayment.select'),
                                valueName: 'terms',
                                repeatSelect: false,
                                preSelectedElements: [this.preselectedTermsOfPaymentId],
                                data: data
                            }
                        }
                    ]);

                }.bind(this))
                .fail(function(textStatus, error) {
                    this.sandbox.logger.error(textStatus, error);
                }.bind(this));

            this.sandbox.util.load(this.termsOfDeliveryURL)
                .then(function(response) {

                    // data is data for select but not for overlay
                    var data = response._embedded;
                    this.termsOfDeliveryData = this.copyArrayOfObjects(data);

                    // translate values for select but not for overlay
                    this.sandbox.util.foreach(data, function(el) {
                        el.terms = this.sandbox.translate(el.terms);
                    }.bind(this));

                    this.addDividerAndActionsForDeliverySelect(data);

                    this.sandbox.start([
                        {
                            name: 'select@husky',
                            options: {
                                el: '#termsOfDelivery',
                                instanceName: 'terms-of-delivery',
                                multipleSelect: false,
                                defaultLabel: this.sandbox.translate('contact.accounts.termsOfDelivery.select'),
                                valueName: 'terms',
                                repeatSelect: false,
                                preSelectedElements: [this.preselectedTermsOfDeliveryId],
                                data: data
                            }
                        }
                    ]);

                }.bind(this))
                .fail(function(textStatus, error) {
                    this.sandbox.logger.error(textStatus, error);
                }.bind(this));
        },

        /**
         * Copies array of objects
         * @param data
         * @returns {Array}
         */
        copyArrayOfObjects: function(data) {
            var newArray = [];
            this.sandbox.util.foreach(data, function(el) {
                newArray.push(this.sandbox.util.extend(true, {}, el));
            }.bind(this));

            return newArray;
        },

        /**
         * Adds divider and actions to dropdown elements
         * @param data
         */
        addDividerAndActionsForPaymentSelect: function(data) {
            data.push({divider: true});
            data.push({id: -1, terms: this.sandbox.translate('public.edit-entries'), callback: this.showTermsOfPaymentOverlay.bind(this), updateLabel: false});
        },

        /**
         * Adds divider and actions to dropdown elements
         * @param data
         */
        addDividerAndActionsForDeliverySelect: function(data) {
            data.push({divider: true});
            data.push({id: -1, terms: this.sandbox.translate('public.edit-entries'), callback: this.showTermsOfDeliveryOverlay.bind(this), updateLabel: false});
        },

        /**
         * Triggers event to show overlay
         */
        showTermsOfDeliveryOverlay: function() {
            var $overlayContainer = this.sandbox.dom.$('<div id="overlayContainer"></div>'),
                config = {
                    instanceName: 'termsOfDelivery',
                    el: '#overlayContainer',
                    openOnStart: true,
                    removeOnClose: true,
                    triggerEl: null,
                    title: this.sandbox.translate('public.edit-entries'),
                    data: this.termsOfPaymentData
                };

            this.sandbox.dom.remove('#overlayContainer');
            this.sandbox.dom.append('body', $overlayContainer);
            this.sandbox.emit('sulu.types.open', config);
        },

        /**
         * Triggers event to show overlay
         */
        showTermsOfPaymentOverlay: function() {
            var $overlayContainer = this.sandbox.dom.$('<div id="overlayContainer"></div>'),
                config = {
                    instanceName: 'termsOfPayment',
                    el: '#overlayContainer',
                    openOnStart: true,
                    removeOnClose: true,
                    triggerEl: null,
                    title: this.sandbox.translate('public.edit-entries'),
                    data: this.termsOfPaymentData
                };

            this.sandbox.dom.remove('#overlayContainer');
            this.sandbox.dom.append('body', $overlayContainer);
            this.sandbox.emit('sulu.types.open', config);
        },

        initForm: function(data) {
            var formObject = this.sandbox.form.create(this.form);
            this.initBankAccountHandling(data);

            formObject.initialized.then(function() {
                this.setFormData(data);
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

            this.sandbox.on('sulu.contact-form.added.bank-account', function() {
                this.numberOfBankAccounts++;
                this.updateBankAccountAddIcon(this.numberOfBankAccounts);
            }, this);

            this.sandbox.on('sulu.contact-form.removed.bank-account', function() {
                this.numberOfBankAccounts--;
                this.updateBankAccountAddIcon(this.numberOfBankAccounts);
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
            }.bind(this), "select, input, textarea");
            // TODO: only activate this, if wanted
            this.sandbox.dom.on(this.form, 'keyup', function() {
                this.setHeaderBar(false);
            }.bind(this), "input, textarea");

            // if a field-type gets changed or a field gets deleted
            this.sandbox.on('sulu.contact-form.changed', function() {
                this.setHeaderBar(false);
            }.bind(this));
        },

        //FIXME Following code should be moved (partially) to a component (more abstract contact-form component)

        /**
         * Initializes the component responsible for handling bank accounts
         */
        initBankAccountHandling: function(data) {
            this.numberOfBankAccounts = data.bankAccounts.length;
            this.updateBankAccountAddIcon(this.numberOfBankAccounts);

            // when  contact-form is initalized
            this.sandbox.on('sulu.contact-form.initialized', function() {

                this.sandbox.emit('sulu.contact-form.add-collectionfilters', this.form);
                var formObject = this.sandbox.form.create(bankAccountForm);
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
        },

        /**
         * Adds or removes icon to add bank accounts depending on the number of bank accounts
         * @param numberOfBankAccounts
         */
        updateBankAccountAddIcon: function(numberOfBankAccounts) {
            var $addIcon = this.sandbox.dom.find(constants.bankAccountAddId),
                addIcon;

            if (!!numberOfBankAccounts && numberOfBankAccounts > 0 && $addIcon.length === 0) {
                addIcon = this.sandbox.dom.createElement(customTemplates.addBankAccountsIcon);
                this.sandbox.dom.after(this.sandbox.dom.find(constants.bankAccountsId), addIcon);
            } else if (numberOfBankAccounts === 0 && $addIcon.length > 0) {
                this.sandbox.dom.remove(this.sandbox.dom.closest($addIcon, constants.addBankAccountsWrapper));
            }
        }

    };
});
