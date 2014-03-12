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

    var defaults = {
        headline: 'contact.accounts.title'
    };

    return {

        view: true,

        templates: ['/admin/contact/template/account/form'],

        initialize: function() {

            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.form = '#contact-form';
            this.saved = true;


            this.accountType = this.getAccountType();
            this.setHeadlines(this.accountType);
            this.render();
            this.initContactForm();
            this.setHeaderBar(true);
            this.listenForChange();
        },

        render: function() {
            var data, excludeItem;

            this.sandbox.once('sulu.contacts.set-defaults', this.setDefaults.bind(this));

            this.html(this.renderTemplate('/admin/contact/template/account/form'));

            this.titleField = this.$find('#name');

            data = this.options.data;

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
                        excludes: [
                            {id: data.id, name: data.name}
                        ]
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

        /**
         * returns the accounttype
         * @returns {number}
         */
        getAccountType: function() {
            var typeInfo, compareAttribute,
                accountType = 0,
                accountTypes = AppConfig.getSection('sulu-contact').accountTypes; // get account types

            // if newly created account, get type id
            if (!!this.options.data.id) {
                typeInfo = this.options.data.type;
                compareAttribute = 'id';
            } else if (!!this.options.accountTypeName){
                typeInfo = this.options.accountTypeName;
                compareAttribute = 'name';
            } else {
                typeInfo = 0;
                compareAttribute = 'id';
            }

            // get account type information
            this.sandbox.util.foreach(accountTypes, function(type) {
                if (type[compareAttribute] === typeInfo) {
                    accountType = type;
                    this.options.data.type = type.id;
                    return false; // break loop
                }
            }.bind(this));

            return accountType;
        },

        setHeadlines: function(accountType) {
            var titleAddition = this.sandbox.translate(accountType.translation),
                title = this.sandbox.translate(this.options.headline);

            if (!!this.options.data.id) {
                titleAddition += ' #' + this.options.data.id;
                title = this.options.data.name;
            }

            this.sandbox.emit('sulu.content.set-title-addition', titleAddition);
            this.sandbox.emit('sulu.content.set-title', title);
        },

        initContactForm: function() {

            // TODO: get fields from configuration
            // TODO: data-mapper should be able to render underscore template
            // TODO: init email as in contact form (to show email field if not empty)
            // TODO: FETCH ALL FIELDS : (phone, address, website, fax, email)
            // TODO: FETCH ALL FIELD TYPES


            var fieldTypes = ['address', 'email', 'fax', 'phone', 'website'],
                dropdownData = [];

            this.sandbox.util.foreach(fieldTypes,function(type, index) {
                dropdownData.push({id:index, name:type});
            });




            // initialize dropdown
            this.sandbox.start([
                {
                    name: 'dropdown@husky',
                    toggle: '.contact-options-toggle',
                    options: {
                        el: '#contact-options-dropdown',
                        alignment: 'right',
                        shadow: true,
                        data: [
                            {
                                id: 1,
                                name: 'public.edit-fields',
                                callback: function() {

                                }
                            },
                            {
                                id: 2,
                                name: 'public.add-fields',
                                callback: function() {
                                    var tmpl = [
                                            '<div class="grid-row">',
                                            '   <div id="field-select" class="grid-col-6"></div>',
                                            '   <div id="field-type-select" class="grid-col-6"></div>',
                                            '</div>'
                                        ],

                                        newTemplate = this.sandbox.dom.createElement(tmpl.join(''));

                                    this.sandbox.start([
                                        {
                                            name: 'overlay@husky',
                                            options: {
                                                title: this.sandbox.translate('public.add-fields'),
                                                openOnStart: true,
                                                removeOnClose: true,
                                                data: newTemplate
                                            }
                                        },
                                        {
                                            name: 'dropdown-multiple-select@husky',
                                            options: {
                                                el: '#field-select',
                                                instanceName: 'i1',
                                                singleSelect: true,
                                                data: dropdownData
                                            }
                                        }
                                    ]);

                                    this.sandbox.on('husky.dropdown.multiple.select.i1.selected.item', function(id) {
                                        // TODO: now update second dropdown with correct values

                                        this.sandbox.stop('#field-type-select');

                                        this.sandbox.start([
                                            {
                                                name: 'dropdown-multiple-select@husky',
                                                options: {
                                                    el: '#field-type-select',
                                                    singleSelect: true,
                                                    instanceName: 'i2',
                                                    data: [
                                                        {id: 0, name: 'office'},
                                                        {id: 1, name: 'private'}
                                                    ]
                                                }
                                            }
                                        ]);
                                    });
                                }
                            }
                        ]
                    }
                }
            ]);
        },

        updateHeadline: function() {
            this.sandbox.emit('sulu.content.set-title', this.sandbox.dom.val(this.titleField));
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

        },

        bindDomEvents: function() {
//            this.sandbox.dom.on(this.titleField, 'keyup', this.updateHeadline.bind(this));

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

                this.updateHeadline();

                // FIXME auto complete in mapper
                data.parent = {
                    id: this.sandbox.dom.data('#company input', 'id')
                };

                this.sandbox.emit('sulu.contacts.accounts.save', data);
            }
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
            // TODO: only activate this, if wanted
//            this.sandbox.dom.on('#contact-form', 'keyup', function() {
//                this.setHeaderBar(false);
//            }.bind(this), "input");
        }

    };
});
