/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!sulucontact/components/contact-form/address.form.html'], function(AddressForm) {

    'use strict';

    var defaults = {
            fields: ['address', 'email', 'fax', 'phone', 'url'],
            fieldTypes: [],
            defaultTypes: [],
            trigger: '.contact-options-toggle'
        },

        constants = {
            fieldId: 'field-select',
            fieldTypeId: 'field-type-select',
            editDeleteSelector: '.delete',
            editDeleteIcon: 'fa-minus-circle',
            editUndoDeleteIcon: 'fa-plus-circle',
            fadedClass: 'faded',
            addressFormId: '#address-form',
            dropdownContainerId: '#contact-options-dropdown',
            addressRowTemplateSelector: '[data-mapper-property-tpl="address-tpl"]'
        },

        templates = {
            add: [
                '<div class="grid-row">',
                '   <div id="' + constants.fieldId + '" class="grid-col-6"></div>',
                '   <div id="' + constants.fieldTypeId + '" class="grid-col-6"></div>',
                '</div>',
                '<div class="grid-row m-bottom-0"></div>'
            ].join(''),
            editField: [
                '<div class="grid-row divider" data-deleted="false">',
                '   <div class="grid-col-7 pull-left">',
                '       <div id="<%= dropdownId %>"></div>',
                '   </div>',
                '   <div class="grid-col-2 pull-right">',
                '<% if (showDeleteButton == true) { %>',
                '       <div class="delete btn gray-dark fit only-icon pull-right">',
                '           <div class="fa-minus-circle"></div>',
                '       </div>',
                '<% } %>',
                '   </div>',
                '</div>'
            ].join('')
        },

        eventNamespace = 'sulu.contact-form',

        /**
         * is emited after initialization
         * @event sulu.contact-form.initialized
         */
            EVENT_INITIALIZED = function() {
            return eventNamespace + '.initialized';
        },

        /**
         * is emited when a field-type gets changed or a field gets deleted
         * @event sulu.contact-form.initialized
         */
            EVENT_CHANGED = function() {
            return eventNamespace + '.changed';
        },

        bindCustomEvents = function() {
            this.sandbox.on('sulu.contact-form.add-collectionfilters', addCollectionFilters.bind(this));
            this.sandbox.on('sulu.contact-form.add-required', addRequires.bind(this));
            this.sandbox.on('sulu.contact-form.is.initialized', isInitialized.bind(this));

            // bind events for add-fields overlay
            bindAddEvents.call(this);
        },

        bindDomEvents = function() {
            this.sandbox.dom.on(this.$el, 'click', editAddressClicked.bind(this), constants.addressRowTemplateSelector);
        },

        bindAddEvents = function() {
            this.sandbox.on('husky.dependent-select.add-fields.all.items.selected', function() {
                this.sandbox.emit('husky.overlay.add-fields.okbutton.activate');
            }.bind(this));

            this.sandbox.on('husky.dependent-select.add-fields.all.items.deselected', function() {
                this.sandbox.emit('husky.overlay.add-fields.okbutton.deactivate');
            }.bind(this));
        },

        /**
         * Binds events related to the edit-fields overlay
         */
            bindEditEvents = function() {
            if (this.$editOverlayContent !== null) {
                this.sandbox.dom.on(this.sandbox.dom.find('.grid-row', this.$editOverlayContent),
                    'click', deleteFieldHandler.bind(this),
                    constants.editDeleteSelector);
            }
        },

        /**
         * Handles the click on the edit-fields
         */
            deleteFieldHandler = function(event) {
            var $row = this.sandbox.dom.$(event.delegateTarget),
                $icon = this.sandbox.dom.find('[class^="icon"]', event.currentTarget),
                deleted = JSON.parse(this.sandbox.dom.attr($row, 'data-deleted'));

            // undo delete
            if (deleted === true) {
                this.sandbox.dom.removeClass($row, constants.fadedClass);
                this.sandbox.dom.removeClass($icon, constants.editUndoDeleteIcon);
                this.sandbox.dom.prependClass($icon, constants.editDeleteIcon);
                this.sandbox.dom.attr($row, 'data-deleted', "false");
                // mark as deleted
            } else {
                this.sandbox.dom.addClass($row, constants.fadedClass);
                this.sandbox.dom.removeClass($icon, constants.editDeleteIcon);
                this.sandbox.dom.prependClass($icon, constants.editUndoDeleteIcon);
                this.sandbox.dom.attr($row, 'data-deleted', "true");
            }
        },

        /**
         * Unbinds events related to the edit-fields overlay
         */
            unbindEditEvents = function() {
            this.sandbox.dom.off(this.$editOverlayContent);
        },

        removeCollectionFilters = function() {
            // add collection filters
            this.sandbox.form.removeCollectionFilter(this.form, 'addresses');
            this.sandbox.form.removeCollectionFilter(this.form, 'emails');
            this.sandbox.form.removeCollectionFilter(this.form, 'phones');
            this.sandbox.form.removeCollectionFilter(this.form, 'urls');
            this.sandbox.form.removeCollectionFilter(this.form, 'faxes');
            this.sandbox.form.removeCollectionFilter(this.form, 'notes');
        },

        addCollectionFilters = function(form) {

            this.form = form;

            // add collection filters
            this.sandbox.form.addCollectionFilter(this.form, 'addresses', function(address) {
                if (address.id === "") {
                    delete address.id;
                }
                return address.city !== "";
            });
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
                this.sandbox.dom.attr(emailSelector, 'data-contactform-required', true);
            }
        },

        getDataById = function(array, id) {
            for (var i = -1, len = array.length; ++i < len;) {
                if (array[i].id.toString() === id.toString()) {
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

            if (typeof fieldTypeId === 'object' && fieldTypeId.length > 0) {
                fieldTypeId = fieldTypeId[0];
            }

            data = this.dropdownDataArray[fieldId];
            dataType = getDataById(this.dropdownDataArray[fieldId].items, fieldTypeId);
            dataObject = {};
            dataObject[data.type] = '';
            dataObject[data.type + 'Type'] = {
                id: fieldTypeId,
                name: dataType.name
            };
            dataObject.attributes = {};

            if (data.type === 'address') {
                // open overlay with address form
                createAddressOverlay.call(this, dataObject);
            } else {
                // insert field
                this.sandbox.form.addToCollection(this.form, data.collection, dataObject).then(function($element) {
                    this.sandbox.start($element);
                }.bind(this));
            }

            // TODO: focus on just inserted field

            // remove overlay
            this.sandbox.emit('husky.overlay.add-fields.remove');
        },

        /**
         * Handles the logic when the overlay to edit fields is closed with
         * a click on ok
         */
            editOkClicked = function() {
            var i, length, newTypeId, newType, dataObject, deleted;
            // loop through all editable fields get the selected type and map it back into the array
            for (i = -1, length = this.editFieldsData.length; ++i < length;) {
                // first check if field got marked as deleted and if so delete it
                deleted = JSON.parse(this.sandbox.dom.attr(this.editFieldsData[i].$element, 'data-deleted'));
                if (deleted === true) {
                    this.sandbox.form.removeFromCollection(this.form, this.editFieldsData[i].mapperId);
                    this.sandbox.emit(EVENT_CHANGED.call(this));
                } else {

                    newTypeId = parseInt(this.sandbox.dom.data(this.editFieldsData[i].$dropdown, 'selection'), 10);
                    if (newTypeId !== this.editFieldsData[i].type.id) {

                        newType = getTypeById.call(this, this.editFieldsData[i].types, newTypeId);

                        // update type in form if selected type exists
                        if (newType !== null) {
                            dataObject = {};
                            dataObject[this.editFieldsData[i].typeName] = newType;
                            this.sandbox.form.editInCollection(this.form, this.editFieldsData[i].mapperId, dataObject);
                            this.sandbox.emit(EVENT_CHANGED.call(this));
                        }
                    }
                }
            }

            unbindEditEvents.call(this);
            this.sandbox.stop(this.$editOverlayContent);
        },

        editAddressClicked = function(event) {
            var $template = this.sandbox.dom.$(event.currentTarget),
                data = this.sandbox.form.getData(this.form, true, $template);
            createAddressOverlay.call(this, data, this.sandbox.dom.data($template, 'mapperId'));
        },

        /**
         * Takes an object of types with an id-property and returns the matching type for a given id
         * @param types
         * @param id
         * @returns {Object|null}
         */
            getTypeById = function(types, id) {
            for (var i = -1, length = types.length; ++i < length;) {
                if (types[i].id === id) {
                    return types[i];
                }
            }
            return null;
        },

        translateFieldTypes = function() {
            var translatedTypes = this.options.fieldTypes,
                i, len, type;
            for (type in translatedTypes) {
                for (i = -1, len = translatedTypes[type].length; ++i < len;) {
                    translatedTypes[type][i].name = this.sandbox.translate(translatedTypes[type][i].name);
                }
            }
            this.options.translatedFieldTypes = translatedTypes;
        },

        /**
         * Get the data from the form create a neat public object and render the DOM object
         * for the edit-fields overlay
         * @returns {*|HTMLElement}
         */
            createEditOverlayContent = function() {
            this.editFieldsData = [];
            removeCollectionFilters.call(this);
            var data = this.sandbox.form.getData(this.form, true),
                dataArray,
                i, length, key,
                $content = this.sandbox.dom.createElement('<div class="edit-fields"/>'),
                $element, required, permanent;
            addCollectionFilters.call(this, this.form);

            dataArray = {
                address: data.addresses,
                email: data.emails,
                fax: data.faxes,
                phone: data.phones,
                url: data.urls

            };

            //loop through object properties
            for (key in dataArray) {
                //foreach object property loop through its children
                for (i = -1, length = dataArray[key].length; ++i < length;) {

                    // look if belonging field is required
                    required = this.sandbox.dom.attr(
                        this.sandbox.dom.$('[data-mapper-id="' + dataArray[key][i].mapperId + '"]'),
                        'data-contactform-required'
                    );

                    // construct permanent boolean
                    permanent = false;
                    if (!!dataArray[key][i].attributes && !!dataArray[key][i].attributes.permanent) {
                        permanent = dataArray[key][i].attributes.permanent;
                    }

                    // create row form overlay-content
                    $element = this.sandbox.dom.createElement(this.sandbox.util.template(templates.editField)({
                        dropdownId: 'edit-dropdown-' + key + '-' + i,
                        showDeleteButton: (!required && !permanent)
                    }));

                    this.editFieldsData.push({
                        id: dataArray[key][i].id,
                        typeName: key + 'Type',
                        type: dataArray[key][i][key + 'Type'],
                        name: this.sandbox.translate('public.' + key),
                        $element: $element,
                        dropdownId: 'edit-dropdown-' + key + '-' + i,
                        types: this.options.fieldTypes[key],
                        mapperId: parseInt(dataArray[key][i].mapperId),
                        dropdownData: null,
                        $dropdown: null
                    });

                    this.sandbox.dom.append($content, $element);
                }
            }
            return $content;
        },

        /**
         * Generate the Data for the all Edit-fields dropdowns
         */
            generateEditFieldsDropdownData = function() {
            var i, length, x, xlength;

            //foreach edit-field
            for (i = -1, length = this.editFieldsData.length; ++i < length;) {
                this.editFieldsData[i].dropdownData = [];

                //foreach type in each edit-field
                for (x = -1, xlength = this.editFieldsData[i].types.length; ++x < xlength;) {
                    this.editFieldsData[i].dropdownData.push({
                        id: this.editFieldsData[i].types[x].id,
                        name: this.editFieldsData[i].name + ' (' + this.editFieldsData[i].types[x].name + ')'
                    });
                }
            }
        },

        /**
         * Start all edit-fields dropdowns
         */
            startEditFieldsDropdowns = function() {
            generateEditFieldsDropdownData.call(this);
            for (var i = -1, length = this.editFieldsData.length; ++i < length;) {

                this.editFieldsData[i].$dropdown = this.sandbox.dom.find('#' + this.editFieldsData[i].dropdownId,
                    this.editFieldsData[i].$element);

                this.sandbox.start([
                    {
                        name: 'select@husky',
                        options: {
                            el: this.editFieldsData[i].$dropdown,
                            instanceName: this.editFieldsData[i].dropdownId,
                            data: this.editFieldsData[i].dropdownData,
                            preSelectedElements: [this.editFieldsData[i].type.id]
                        }
                    }
                ]);
            }
        },

        /**
         * Create the edit-fields overlay
         */
            createEditOverlay = function() {

            // create container for overlay
            var $overlay = this.sandbox.dom.createElement('<div>');
            this.sandbox.dom.append('body', $overlay);

            this.$editOverlayContent = createEditOverlayContent.call(this);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $overlay,
                        title: this.sandbox.translate('public.edit-fields'),
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'edit-fields',
                        data: this.$editOverlayContent,
                        okCallback: editOkClicked.bind(this),
                        closeCallback: unbindEditEvents.bind(this)
                    }
                }
            ]);
            startEditFieldsDropdowns.call(this);
            bindEditEvents.call(this);
        },

        createAddressOverlay = function(data, mapperId) {

            var addressTemplate, formObject, $overlay, title,
                isNew = !!data ? false : true;

            // remove add overlay
            this.sandbox.emit('husky.overlay.add-fields.remove');
            // remove edit overlay
            this.sandbox.emit('husky.overlay.edit-fields.remove');

            // extend address data by additional variables
            this.sandbox.util.extend(true, data, {
                translate: this.sandbox.translate,
                countries: this.options.fieldTypes['countries']
            });

            // parse template
            addressTemplate = this.sandbox.util.template(AddressForm, data);

            // create container for overlay
            $overlay = this.sandbox.dom.createElement('<div>');
            this.sandbox.dom.append('body', $overlay);

            if (isNew) {
                title = this.sandbox.translate('contacts.add-address');
            } else {
                title = this.sandbox.translate('contacts.edit-address');
            }

            // create overlay with data
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $overlay,
                        title: title,
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'add-address',
                        data: addressTemplate,
                        skin: 'wide',
                        okCallback: addAddressOkClicked.bind(this, mapperId),
                        closeCallback: removeAddressFormEvents.bind(this)
                    }
                }
            ]);

            // after everything was added to dom
            this.sandbox.on('husky.overlay.add-address.opened', function() {
                // start form and set data
                formObject = this.sandbox.form.create(constants.addressFormId);
                formObject.initialized.then(function() {
                    this.sandbox.form.setData(constants.addressFormId, data);
                }.bind(this));
            }.bind(this));

//            // use husky select
//            this.sandbox.start([
//                {
//                    name: 'select@husky',
//                    options: {
//                        el: '#country-select',
//                        instanceName: 'country-select',
//                        data: this.options.fieldTypes['countries'],
//                        preSelectedElements: !!data.country ? [data.country.id]: [this.options.defaultTypes.country.id]
//                    }
//                }
//            ]);

        },

    // removes listeners of addressform
        removeAddressFormEvents = function() {
            this.sandbox.dom.off(constants.addressFormId);
        },

        addAddressOkClicked = function(mapperId) {
            var formData;

            if (!this.sandbox.form.validate(constants.addressFormId)) {
                return false;
            }
            formData = this.sandbox.form.getData(constants.addressFormId, true);

            // add to collection
            if (!mapperId) {
                this.sandbox.form.addToCollection(this.form, 'addresses', formData);
            } else {
                this.sandbox.form.editInCollection(this.form, mapperId, formData)
            }

            // set changed to be able to save
            this.sandbox.emit(EVENT_CHANGED.call(this));

            // remove change listener
            removeAddressFormEvents.call(this);
        },

        createAddOverlay = function() {
            var data, $overlay,
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

            // change data
            dropdownData.fax.collection = 'faxes';

            // convert object to array
            this.dropdownDataArray = Object.keys(dropdownData).map(function(key) {
                return dropdownData[key];
            });

            // create container for overlay
            $overlay = this.sandbox.dom.createElement('<div>');
            this.sandbox.dom.append('body', $overlay);

            // start overlay and dependent select
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $overlay,
                        title: this.sandbox.translate('public.add-fields'),
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'add-fields',
                        okInactive: true,
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
                        defaultLabels: this.sandbox.translate('public.please-choose'),
                        instanceName: 'add-fields',
                        container: ['#' + constants.fieldId, '#' + constants.fieldTypeId]
                    }
                }
            ]);
        };

    return {

        initialize: function() {

            this.initialized = false;
            this.$editOverlayContent = null;
            this.form = null;
            this.$addOverlay = null;
            this.dropdownDataArray = [];
            this.editFieldsData = [];

            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            translateFieldTypes.call(this);

            this.render();

            bindCustomEvents.call(this);
            bindDomEvents.call(this);

            this.sandbox.emit(EVENT_INITIALIZED.call(this));
            this.initialized = true;
        },

        render: function() {

            var $container = this.sandbox.dom.createElement('<div id="contact-form-options-container" />'),
                $dropdownContainer = this.$find(constants.dropdownContainerId);

            // add new container
//            this.sandbox.dom.append(this.$el, $container);
            this.sandbox.dom.append($dropdownContainer, $container);

            // TODO: implement options dropdown functionality for adding and editing contact details
            // initialize dropdown
            this.sandbox.start([
                {
                    name: 'dropdown@husky',
                    options: {
                        trigger: $dropdownContainer,
                        triggerOutside: true,
                        el: $container,
                        alignment: 'right',
                        shadow: true,
                        toggleClassOn: $dropdownContainer,
                        data: [
                            {
                                id: 1,
                                name: 'public.edit-fields',
                                callback: createEditOverlay.bind(this)
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
