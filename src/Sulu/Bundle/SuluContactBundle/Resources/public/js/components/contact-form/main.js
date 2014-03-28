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
            fields: ['address', 'email', 'fax', 'phone', 'url'],
            fieldTypes: [],
            trigger: '.contact-options-toggle'
        },

        constants = {
            fieldId: 'field-select',
            fieldTypeId: 'field-type-select'
        },

        templates = {
            add: [
                '<div class="grid-row">',
                '   <div id="' + constants.fieldId + '" class="grid-col-6"></div>',
                '   <div id="' + constants.fieldTypeId + '" class="grid-col-6"></div>',
                '</div>',
                '<div class="grid-row m-bottom-0"></div>'
            ].join('')
        },

        bindCustomEvents = function() {
            this.sandbox.on('sulu.contact-form.add-collectionfilters', addCollectionFilters.bind(this));
            this.sandbox.on('sulu.contact-form.add-required', addRequires.bind(this));
            this.sandbox.on('sulu.contact-form.is.initialized', isInitialized.bind(this));
        },


        addCollectionFilters = function(form) {
            // set form
            this.form = form;

            // add collection filters
            this.sandbox.form.addCollectionFilter(this.form, 'emails', function(email) {
                if (email.id === "") {
                    delete email.id;
                }
                return email.email !== "";
            });
            this.sandbox.form.addCollectionFilter(this.form, 'phones', function(phone) {
                if (phone.id === "") {
                    delete phone.id;
                }
                return phone.phone !== "";
            });
            this.sandbox.form.addCollectionFilter(this.form, 'urls', function(url) {
                if (url.id === "") {
                    delete url.id;
                }
                return url.url !== "";
            });
            this.sandbox.form.addCollectionFilter(this.form, 'faxes', function(fax) {
                if (fax.id === "") {
                    delete fax.id;
                }
                return fax.fax !== "";
            });
            this.sandbox.form.addCollectionFilter(this.form, 'notes', function(note) {
                if (note.id === "") {
                    delete note.id;
                }
                return note.value !== "";
            });
        },

        addRequires = function(data) {
            var tplNames = {
                    email: 'email-tpl'
                },
                tplSelector = '#contact-fields *[data-mapper-property-tpl="<%= selector %>"]:first',
                emailSelector;

            if (data.indexOf('email') !== -1) {
                emailSelector = this.sandbox.util.template(tplSelector, {selector: tplNames.email});
                this.sandbox.form.addConstraint(this.form, emailSelector + ' input.email-value', 'required', {required: true});
                this.sandbox.dom.addClass(emailSelector + ' label span:first', 'required');
            }
        },

        getDataById = function(array, id) {
            for (var i = -1, len = array.length; ++i < len;) {
                if (array[i].id === id) {
                    return array[i];
                }
            }
        },

        isInitialized = function(callback) {
            if (!this.initialized) {
                this.sandbox.on('sulu.contact-form.initialized', function() {
                    callback.call(this);
                }.bind(this));
            } else {
                callback.call(this);
            }
        },

        addOkClicked = function() {
            var field = this.sandbox.dom.children('#' + constants.fieldId)[0],
                fieldType = this.sandbox.dom.children('#' + constants.fieldTypeId)[0],
                fieldId = this.sandbox.dom.data(field, 'selection'),
                fieldTypeId = this.sandbox.dom.data(fieldType, 'selection'),
                data, dataType, dataObject;

            if (!fieldId || !fieldTypeId) {
            }

            data = this.dropdownDataArray[fieldId];
            dataType = getDataById(this.dropdownDataArray[fieldId].items, fieldTypeId);
            dataObject = {};
            dataObject[data.type] = '';
            dataObject[data.type+'Type'] = {
                id: fieldTypeId,
                name: dataType.name
            };

            // insert field
            this.sandbox.form.addToCollection(this.form, data.collection, dataObject);


            // TODO: focus on just inserted field

            // remove overlay
            this.sandbox.emit('husky.overlay.add-overlay.remove');
        },

        translateFieldTypes = function() {
            var translatedTypes = this.options.fieldTypes,
                i, len, type;
            for (type in translatedTypes) {
                for (i= -1, len = translatedTypes[type].length; ++i < len;) {
                    translatedTypes[type][i].name = this.sandbox.translate(translatedTypes[type][i].name);
                }
            }
            this.options.translatedFieldTypes = translatedTypes;
        },


        createAddOverlay = function() {
            var data,
                dropdownData = {};

            this.dropdownDataArray = [];

            this.$addOverlay = this.sandbox.dom.createElement(templates.add),

                // create object
                this.sandbox.util.foreach(this.options.fields, function(type, index) {
                    if (!!this.options.fieldTypes && this.options.fieldTypes[type]) {
                        // TODO: USE ARRAY INSTEAD OF OBJECT WHEN DATA HAS NOT TO BE MANIPULATED ANYMORE
                        data = {
                            id: index,
                            name: this.sandbox.translate('public.' + type),
                            type: type,
                            collection: type + 's',
                            items: this.options.translatedFieldTypes[type]
                        };
                        dropdownData[type] = (data);

                    } else {
                        throw 'contact-form@sulu: fieldTypes not defined for type ' + type;
                    }
                }.bind(this));

            // TODO:  REMOVE AFTER ADDRESSES CAN BE ADDED
            // change data
            dropdownData.address.disabled = true;
            dropdownData.fax.collection = 'faxes';

            // convert object to array
            this.dropdownDataArray = Object.keys(dropdownData).map(function(key) {
                return dropdownData[key];
            });

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        title: this.sandbox.translate('public.add-fields'),
                        openOnStart: true,
                        instanceName: 'add-overlay',
                        data: this.$addOverlay,
                        okCallback: addOkClicked.bind(this)
                    }
                },
                {
                    name: 'dependent-select@husky',
                    options: {
                        el: this.$addOverlay,
                        singleSelect: true,
                        data: this.dropdownDataArray,
                        container: ['#' + constants.fieldId, '#' + constants.fieldTypeId]
//                        selectOptions: [null,{preSelectedElements:[]}]
                    }
                }
            ]);
        };

    return {

        initialize: function() {

            this.initialized = false;

            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            translateFieldTypes.call(this);

            this.render();

            bindCustomEvents.call(this);

            this.sandbox.emit('sulu.contact-form.initialized');
            this.initialized = true;
        },

        render: function() {

            var $container = this.sandbox.dom.createElement('<div id="contact-form-options-container" />');

            // add new container
            this.sandbox.dom.append(this.$el, $container);



            // TODO: implement options dropdown functionality for adding and editing contact details
            // initialize dropdown
            this.sandbox.start([
                {
                    name: 'dropdown@husky',
                    options: {
                        trigger: this.$el,
                        triggerOutside: true,
                        el: $container,
                        alignment: 'right',
                        shadow: true,
                        toggleClassOn: this.$el,
                        data: [
                            {
                                id: 1,
                                name: 'public.edit-fields',
                                callback: function() {
                                    this.sandbox.emit('sulu.labels.warning.show', 'Editing fields is not yet implemented');
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
