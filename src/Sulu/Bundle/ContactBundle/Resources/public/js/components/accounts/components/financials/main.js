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

    var defaults = {
            headline: 'contact.accounts.title'
        },
        constants = {
            addBankAccountButtonId: '#add-bank-account'
        },
        // sets toolbar
        setHeaderToolbar = function() {
            this.sandbox.emit('sulu.header.set-toolbar', {
                template: 'default'
            });
        },

        addBankAccount = function() {
            this.sandbox.form.addToCollection(this.form, 'bankAccounts', {});
        };

    return {

        view: true,

        templates: ['/admin/contact/template/account/financials'],

        initialize: function() {

            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.saved = true;

            this.form = '#financials-form';

            // set header toolbar
            setHeaderToolbar.call(this);
            this.setHeaderBar(true);

            this.render();

            this.listenForChange();
        },

        render: function() {
            var data = this.options.data;

            this.html(this.renderTemplate(this.templates[0]));

            this.initForm(data);

            this.bindDomEvents();
            this.bindCustomEvents();
        },

        initForm: function(data) {
            var formObject = this.sandbox.form.create(this.form);
            this.addCollectionFilters(data);
            formObject.initialized.then(function() {
                this.setFormData(data);
            }.bind(this));
        },

        addCollectionFilters : function(data) {
            // add collection filters
            this.sandbox.form.addCollectionFilter(this.form, 'bankAccounts', function(bankAccount) {
                if (bankAccount.id === "") {
                    delete bankAccount.id;
                }
                return bankAccount.bankName !== "" && bankAccount.bic !== "" && bankAccount.iban !== "";
            });
        },

        setFormData: function(data) {
            // this guarantees that one bankaccount field is shown
            if (!!data && !data.bankAccounts.length) {
                delete data.bankAccounts;
            }

            // set formdata
            this.sandbox.form.setData(this.form, data).then(function() {
                this.sandbox.start(this.form);
                // TODO: mark required fields
            }.bind(this));
        },

        bindDomEvents: function() {
            this.sandbox.dom.keypress(this.form, function(event) {
                if (event.which === 13) {
                    event.preventDefault();
                    this.submit();
                }
            }.bind(this));

            this.sandbox.dom.on(constants.addBankAccountButtonId, 'click', addBankAccount.bind(this));
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
            }.bind(this), "select, input, textarea");
            // TODO: only activate this, if wanted
            this.sandbox.dom.on(this.form, 'keyup', function() {
                this.setHeaderBar(false);
            }.bind(this), "input, textarea");

            // if a field-type gets changed or a field gets deleted
            this.sandbox.on('sulu.contact-form.changed', function() {
                this.setHeaderBar(false);
            }.bind(this));
        }

    };
});
