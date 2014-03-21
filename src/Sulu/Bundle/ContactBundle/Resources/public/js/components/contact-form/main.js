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
            fieldTypes: ['address', 'email', 'fax', 'phone', 'website'],
            trigger: '.contact-options-toggle'
        },

        bindCustomEvents = function() {
            this.sandbox.on('sulu.contact-form.form.create', createForm.bind(this));
        },


        createForm = function(form, data, callback) {

            if (!form || !data || !callback) {
                throw "not enough parameters specified";
            }

            this.sandbox.form.addCollectionFilter(form, 'emails', function(email) {
                if (email.id === "") {
                    delete email.id;
                }
                return email.email !== "";
            });
            this.sandbox.form.addCollectionFilter(form, 'phones', function(phone) {
                if (phone.id === "") {
                    delete phone.id;
                }
                return phone.phone !== "";
            });
            this.sandbox.form.addCollectionFilter(form, 'urls', function(url) {
                if (url.id === "") {
                    delete url.id;
                }
                return url.url !== "";
            });
            this.sandbox.form.addCollectionFilter(form, 'notes', function(note) {
                if (note.id === "") {
                    delete note.id;
                }
                return note.value !== "";
            });

            initContactData(data);

            callback.call(this, data);


        },

    // CONTACT
        fillFields = function(field, minAmount, value) {
            if (!field) {
                return;
            }
            while (field.length < minAmount) {
                field.push(value);
            }
        },

        // CONTACT
        initContactData = function(data) {
            fillFields(data.urls, 1, {
                id: null,
                url: '',
                urlType: this.defaultTypes.urlType
            });
            fillFields(data.emails, 1, {
                id: null,
                email: '',
                emailType: this.defaultTypes.emailType
            });
            fillFields(data.phones, 1, {
                id: null,
                phone: '',
                phoneType: this.defaultTypes.phoneType
            });
            fillFields(data.notes, 1, {
                id: null,
                value: ''
            });
    //            this.fillFields(contactJson.addresses, 1, {
    //                id: null,
    //                addressType: this.defaultTypes.addressType,
    //                street: this.sandbox.translate('contact.add.address')
    //            });
            return data;
        },

        createAddOverlay = function() {
            var tmpl = [
                    '<div class="grid-row">',
                    '   <div id="field-select" class="grid-col-6"></div>',
                    '   <div id="field-type-select" class="grid-col-6"></div>',
                    '</div>'
                ],

                newTemplate = this.sandbox.dom.createElement(tmpl.join('')),
                dropdownData = [];

            this.sandbox.util.foreach(this.options.fieldTypes, function(type, index) {
                dropdownData.push({id: index, name: type});
            });

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
                // TODO: initialize second dropdown as well on beginning
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
        };

    return {

        initialize: function() {

            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);


            this.render();

            bindCustomEvents.call(this);

            this.sandbox.emit('sulu.contact-form.initialized');
        },

        render: function() {

            var $container = this.sandbox.dom.createElement('<div id="contact-form-options-container" />');;

            // add new container
            this.sandbox.dom.append(this.$el, $container);


            // TODO: implement options dropdown functionality for adding and editing contact details
            // initialize dropdown
            this.sandbox.start([
                {
                    name: 'dropdown@husky',
                    options: {
                        trigger: this.$el,
                        el: $container,
                        alignment: 'right',
                        shadow: true,
                        toggleClassOn: this.$el,
                        data: [
                            {
                                id: 1,
                                name: 'public.edit-fields',
                                callback: function() {
                                    alert("a s d f ");
                                }
                            },
                            {
                                id: 2,
                                name: 'public.add-fields',
                                callback: createAddOverlay.bind(this)
                            }
                        ]
                    }
                }
            ]);
        }

    };
});
