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


            this.getAccountType();
            this.setHeadlines();
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

        getAccountType: function() {
            var typeInfo, compareAttribute,
                accountTypes = AppConfig.getSection('sulu-contact').accountTypes; // get account types

            // if newly created account, get type id
            if (!!this.options.data.id) {
                typeInfo = this.options.data.type;
                compareAttribute = 'id';
            } else {
                typeInfo = this.options.accountTypeName;
                compareAttribute = 'name';
            }

            // get account type information
            this.sandbox.util.foreach(accountTypes, function(type) {
                if (type[compareAttribute] === typeInfo) {
                    this.accountType = type;
                    this.options.data.type = type.id;
                    return false; // break loop
                }
            }.bind(this));

            return this.accountType;
        },

        setHeadlines: function() {
            this.$headlines = this.sandbox.dom.find('.headlines');
            var titleAddition = this.sandbox.translate(this.accountType.translation),
                title = this.sandbox.translate(this.options.headline);

            if (!!this.options.data.id) {
                titleAddition += ' #' + this.options.data.id;
                title = this.options.data.name;
            }

            this.sandbox.dom.html(this.sandbox.dom.find('h6', this.$headlines), titleAddition);
            this.sandbox.dom.html(this.sandbox.dom.find('h1', this.$headlines), title);
        },

        initContactForm: function() {

            var tmpl = [
                '<div class="">',
                '   <div id="field-select"></div>',
                '   <div id="field-type-select"></div>',
                '</div>'
            ],

                newTemplate = this.sandbox.dom.createElement(tmpl.join(''));

            // initialize dropdown
            this.sandbox.start([{
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
                                this.sandbox.start([
                                    {
                                        name: 'dropdown-multiple-select@husky',
                                        options: {
                                            el: '#field-select',
                                            singleSelect: true,
                                            data: [
                                                {id: 0, name: 'Deutsch'},
                                                {id: 1, name: 'English'},
                                                {id: 2, name: 'Spanish'},
                                                {id: 3, name: 'Italienisch'}
                                            ]
                                        }
                                    },
                                    {
                                        name: 'overlay@husky',
                                        options: {
                                            title: this.sandbox.translate('public.add-fields'),
                                            openOnStart: true,
                                            removeOnClose: true,
                                            data: newTemplate
                                        }
                                    }
                                ]);
                            }
                        },
                        {
                            id: 2,
                            name: 'public.add-fields'
                        }
                    ]
                }
            }
            ]);
        },

        resetTitle: function() {
            this.sandbox.dom.html(this.sandbox.dom.find('h1', this.$headlines),this.sandbox.dom.val(this.titleField));
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
            this.sandbox.dom.on(this.titleField, 'keyup', this.resetTitle.bind(this));

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
