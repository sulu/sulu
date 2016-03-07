/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Handles management of conditions
 *
 * @class ConditionSelection
 * @constructor
 *
 * @param {Object} [options] Configuration object
 * @param {Object} [options.operatorsUrl] Url to load the operators
 * @param {Object} [options.fieldsUrl] Url to load the fields
 * @param {Object} [options.eventNamespace] Namespace for the events
 * @param {Object} [options.dataAttribute] Data attribute for validation and setting/getting data
 * @param {Object} [options.instanceName] Instance name for usage with multiple instances
 * @param {Object} [options.translations] Translation-keys for the component
 * @param {Object} [options.data] Data which should already be set
 * @param {Object} [options.validationSelector] selector to add fields to validation
 */
define([], function() {

    'use strict';

    var UNDEFINED_TYPE = 0,
        STRING_TYPE = 1,
        NUMBER_TYPE = 2,
        DATETIME_TYPE = 3,
        BOOLEAN_TYPE = 4,
        TAGS_TYPE = 5,
        AUTO_COMPLETE_TYPE = 6,

        defaults = {
            operatorsUrl: null,
            fieldsUrl: null,
            eventNamespace: 'sulu.condition-selection',
            dataAttribute: 'condition-selection',
            instanceName: 'condition',
            translations: {
                addButton: 'resource.filter.add-condition'
            },
            data: [],
            validationSelector: null
        },

        typeMappings = {
            string: STRING_TYPE,
            number: NUMBER_TYPE,
            integer: NUMBER_TYPE,
            float: NUMBER_TYPE,
            boolean: BOOLEAN_TYPE,
            date: DATETIME_TYPE,
            datetime: DATETIME_TYPE,
            tags: TAGS_TYPE,
            'auto-complete': AUTO_COMPLETE_TYPE
        },

        templates = {
            container: function(cssClass, id) {
                return ['<div class="', cssClass, '" id="', id, '" style="display:none"></div>'].join('');
            },
            button: function(id, text) {
                return [
                    '<div class="grid-row">',
                    '   <div class="grid-col-6">',
                    '       <div id="', id, '" class="btn action fit">',
                    '           <span class="fa-plus-circle"></span>',
                    '           <span class="text">', text, '</span>',
                    '       </div>',
                    '   </div>',
                    '</div>'
                ].join('');
            },
            row: function(cssClass, id) {
                return ['<div class="', cssClass, ' grid-row" data-id="', id, '"></div>'].join('');
            },
            removeButton: function(cssClass) {
                return [
                    '<div class="grid-col-1 align-center pointer ', cssClass, '">',
                    '<span class="fa-minus-circle m-top-7"></span>',
                    '</div>'
                ].join('');
            },
            input: function(value, cssClass) {
                return ['<input data-validation-required="true" class="form-element husky-validate ', cssClass, '" type="text" value="', value, '">'].join('');
            },
            col: function(cssClass) {
                return ['<div class="', cssClass, '"></div>'].join('');
            },
            select: function(cssClass) {
                return ['<select data-validation-required="true" class="form-element husky-validate ', cssClass, '"></select>'].join('');
            }
        },

        constants = {
            valueInputClass: 'value-input',
            conditionContainerClass: 'conditions-container',
            conditionRowClass: 'condition-row',
            operatorSelectClass: 'operator-select',
            fieldSelectClass: 'field-select',
            removeButtonClass: 'condition-remove'
        },

        /**
         * raised when all overlay components returned their value
         * @event sulu.condition-selection.initialized
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        },

        /**
         * update the elements of the condition selection
         * @event data-changed
         */
        EVENT_DATA_CHANGED = function() {
            return 'data-changed';
        },

        /**
         * raised when all overlay components returned their value
         * @event sulu.condition-selection.[instanceName].data-changed
         */
        DATA_CHANGED = function() {
            return createEventName.call(this, 'data-changed');
        },

        /**
         * returns normalized event names
         */
        createEventName = function(postFix) {
            return this.options.eventNamespace + '.' + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        },

        /**
         * Starts the loader for the component
         */
        startLoader = function() {
            var $loaderContainer = this.sandbox.dom.createElement('<div id="' + this.options.ids.loader + '"></div>');
            this.sandbox.dom.append(this.options.el, $loaderContainer);

            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: $loaderContainer,
                        size: '100px',
                        color: '#cccccc'
                    }
                }
            ]);
        },

        /**
         * Stops the loader
         */
        stopLoader = function() {
            this.sandbox.stop('#' + this.options.ids.loader);
        },

        /**
         * Renders all rows for the given data
         */
        renderRows = function() {
            if (!!this.options.data && this.options.data.length > 0) {
                this.options.data.forEach(function(conditionGroup) {
                    renderRow.call(this, conditionGroup);
                }.bind(this));
            }
        },

        /**
         * Renders a row for an existing condition group or a new row
         * @param conditionGroup
         */
        renderRow = function(conditionGroup) {
            var condition = {},
                $row,
                filteredOperators = [],
                $deleteButton,
                $fieldSelect,
                $operatorSelect,
                operator,
                $valueComponent,
                id = !!conditionGroup ? conditionGroup.id : 'new',
                field;

            $row = this.sandbox.dom.createElement(templates.row(constants.conditionRowClass, id));
            $deleteButton = this.sandbox.dom.createElement(templates.removeButton(constants.removeButtonClass));

            if (!!conditionGroup) {
                condition = conditionGroup.conditions[0];
                filteredOperators = filterOperatorsByType.call(this, condition.type);
                $fieldSelect = createFieldSelect.call(this, condition.field, false, true);
            } else {
                $fieldSelect = createFieldSelect.call(this, condition.field, true, true);
            }

            field = this.fields[condition.field] || this.usedFields[condition.field] || {};

            operator = getOperatorByOperandAndType.call(this, condition.operator, condition.type);
            $operatorSelect = createOperatorSelect.call(this, operator, filteredOperators, false, true);
            $valueComponent = createValueInput.call(
                this,
                conditionGroup,
                operator,
                'grid-col-4',
                true,
                field['filter-type-parameters'] || {}
            );

            this.sandbox.dom.append($row, $deleteButton);
            this.sandbox.dom.append($row, $fieldSelect);
            this.sandbox.dom.append($row, $operatorSelect);
            this.sandbox.dom.append($row, $valueComponent);
            this.sandbox.dom.append(this.$container, $row);

            $row.data('field', field);
        },

        /**
         * Creates a select for operators
         * @param selectedOperator
         * @param operators
         * @param prependEmpty
         * @param wrap
         */
        createOperatorSelect = function(selectedOperator, operators, prependEmpty, wrap) {
            var $wrapper,
                $select = createSelect.call(
                    this,
                    !!selectedOperator ? selectedOperator.id : null,
                    'id',
                    'name',
                    operators,
                    constants.operatorSelectClass,
                    prependEmpty
                );

            if (!!selectedOperator) {
                this.sandbox.dom.data($select, 'type', selectedOperator.type);
            }

            if (!!wrap) {
                $wrapper = this.sandbox.dom.createElement(templates.col('grid-col-3'));
                this.sandbox.dom.append($wrapper, $select);
                return $wrapper;
            }

            return $select;
        },

        /**
         * Removes a field from the used fields and adds it to the available fields
         * should always be called in context with update select fields
         * @param fieldName
         */
        removesUsedField = function(fieldName) {
            var value;

            if (!fieldName) {
                return;
            }

            if (!!this.usedFields[fieldName]) {
                value = this.usedFields[fieldName];
                delete this.usedFields[fieldName];
                this.fields[fieldName] = value;
            } else {
                this.sandbox.logger.error('Field ' + fieldName + ' could not be found in used fields!');
            }
        },

        /**
         * Adds a field to the used fields and removes it from the available ones
         * should always be called in context with update select fields
         * @param fieldName
         */
        addUsedField = function(fieldName) {
            var value;

            if (!!this.fields[fieldName]) {
                value = this.fields[fieldName];
                delete this.fields[fieldName];
                this.usedFields[fieldName] = value;
            } else {
                this.sandbox.logger.error('Field ' + fieldName + ' could not be found in fields!');
            }
        },

        /**
         * Updates select fields according the fields but skips the given one
         * @param $currentSelect
         */
        updateSelectFields = function($currentSelect) {
            var $fieldSelects = this.sandbox.dom.find('.' + constants.fieldSelectClass, this.$el);

            this.sandbox.util.foreach($fieldSelects, function($select) {
                if ($select !== $currentSelect) {
                    updateOptionsOfSelect.call(this, $select);
                }
            }.bind(this));
        },

        /**
         * Updates the options of a given select according the usedFields
         * @param $select
         */
        updateOptionsOfSelect = function($select) {
            var $options = this.sandbox.dom.find('option', $select),
                value = this.sandbox.dom.val($select),
                prependEmpty = false, translatedText;

            if (!this.sandbox.dom.val($options[0])) {
                prependEmpty = true;
            }

            this.sandbox.dom.remove($options);
            $options = createOptionsForSelect.call(this, value, 'name', 'translation', this.fields, prependEmpty);

            if (!!value) {
                // when the selected value is not in the fields append it
                if (!this.fields[value]) {
                    translatedText = this.sandbox.translate(this.usedFields[value]['translation']);
                    $options.push('<option value="' + this.usedFields[value]['name'] + '" selected>' + translatedText + '</option>');
                }
            }

            this.sandbox.dom.append($select, $options.join(''));
        },

        /**
         * Creates a select for fields
         * @param selectedField
         * @param prependEmpty
         * @param wrap
         */
        createFieldSelect = function(selectedField, prependEmpty, wrap) {
            var $wrapper,
                $select = createSelect.call(
                    this,
                    selectedField,
                    'name',
                    'translation',
                    this.fields,
                    constants.fieldSelectClass,
                    prependEmpty
                );

            if (!!selectedField) {
                addUsedField.call(this, selectedField);
                updateSelectFields.call(this, $select[0]);
                this.sandbox.dom.data($select, 'value', selectedField);
            }

            if (!!wrap) {
                $wrapper = this.sandbox.dom.createElement(templates.col('grid-col-4'));
                this.sandbox.dom.append($wrapper, $select);
                return $wrapper;
            }

            return $select;
        },

        /**
         * Creates the input(s) depending on the condition group and the type of the selected field
         * @param conditionGroup
         * @param operator
         * @param gridColClass css class used for the wrapper of the input - should be a grid-col class
         * @param wrap
         * @param parameters
         */
        createValueInput = function(conditionGroup, operator, gridColClass, wrap, parameters) {
            var $input = null,
                $wrapper,
                condition, id;

            if (!!conditionGroup && !!operator) {
                if (conditionGroup.conditions.length > 1) {
                    this.sandbox.logger.error('Multiple conditions not yet supported!');
                } else {
                    condition = conditionGroup.conditions[0];
                    $input = createInputForType.call(this, operator, condition.value, parameters || {});
                    id = condition.id;
                }
            } else if (!conditionGroup && !!operator) {
                $input = createInputForType.call(this, operator, '', parameters || {});
            } else {
                $input = createSimpleInput.call(this, '', constants.valueInputClass);
            }

            // add field to validation
            if (!!this.options.validationSelector) {
                this.sandbox.form.addField(this.options.validationSelector, $input);
            }

            this.sandbox.dom.data($input, 'id', id);

            if (!!wrap) {
                $wrapper = this.sandbox.dom.createElement(templates.col(gridColClass));
                this.sandbox.dom.append($wrapper, $input);
                return $wrapper;
            }

            return $input;
        },

        /**
         * Searches for an operator by id
         */
        getOperatorById = function(id) {
            var result = null;
            id = parseInt(id);

            this.operators.forEach(function(op) {
                if (op.id === id) {
                    result = op;
                    return false;
                }
            }.bind(this));

            return result;
        },

        /**
         * Searches for an operator by its operand and type
         */
        getOperatorByOperandAndType = function(operand, type) {
            var result = null;

            if (typeof type === 'string') {
                type = getTypeByName.call(this, type);
            }

            this.operators.forEach(function(op) {
                if (op.operator === operand && op.type === type) {
                    result = op;
                    return false;
                }
            }.bind(this));

            return result;
        },

        /**
         * Decides which input should be displayed for the given condition
         * @param operator
         * @param value
         * @param parameters
         */
        createInputForType = function(operator, value, parameters) {
            switch (operator.inputType) {
                case 'date':
                case 'datepicker':
                    return createDatepicker.call(this, value, constants.valueInputClass);
                case 'select':
                case 'boolean':
                case 'radio':
                case 'checkbox':
                    return createSelect.call(this, value, 'value', 'name', operator.values, constants.valueInputClass, true);
                case '':
                case 'simple':
                    return createSimpleInput.call(this, value, constants.valueInputClass);
                default:
                    return createGenericInput.call(this, value, constants.valueInputClass, operator, parameters);
            }
        },

        /**
         * Decides which input should be displayed for the given condition
         */
        getInputValue = function(operator, $row) {
            switch (operator.inputType || '') {
                case 'date':
                case 'datepicker':
                    return this.sandbox.dom.val(this.sandbox.dom.find('.' + constants.valueInputClass + ' input', $row));
                case '':
                case 'select':
                case 'boolean':
                case 'radio':
                case 'checkbox':
                case 'simple':
                    return this.sandbox.dom.val(this.sandbox.dom.find('.' + constants.valueInputClass, $row));
                default:
                    return $('.' + constants.valueInputClass, $row).data('value');
            }
        },

        /**
         * Start a component for given operator.
         *
         * @param value
         * @param cssClass
         * @param operator
         * @param parameters
         *
         * @returns {*|jQuery|HTMLElement}
         */
        createGenericInput = function(value, cssClass, operator, parameters) {
            var $el = $('<div class="' + cssClass + '"/>');

            this.sandbox.start([
                {
                    name: 'condition-selection/' + operator.inputType + '@suluresource',
                    options: {
                        el: $el,
                        operator: operator,
                        value: value,
                        parameters: parameters
                    }
                }
            ]);

            return $el;
        },

        /**
         * Creates and starts datepicker input field
         * @param value
         * @param valueInputClass
         */
        createDatepicker = function(value, valueInputClass) {
            var $datepicker = this.sandbox.dom.createElement('<div data-value="' + value + '" class="' + valueInputClass + '"></div>'),
                dfdDatePicker = this.sandbox.data.deferred(), instance;

            this.deferreds.push(dfdDatePicker);
            instance = 'cs-datepicker' + this.deferreds.length;

            this.sandbox.start([
                {
                    name: 'input@husky',
                    options: {
                        el: $datepicker,
                        datepickerOptions: {"endDate": new Date()},
                        skin: 'date',
                        value: value,
                        instanceName: instance
                    }
                }
            ]);

            this.sandbox.on('husky.input.' + instance + '.initialized', function() {
                dfdDatePicker.resolve();
            }.bind(this));

            return $datepicker;
        },

        /**
         * Creates a simple input field
         * @param value
         * @param cssClass
         */
        createSimpleInput = function(value, cssClass) {
            return this.sandbox.dom.createElement(templates.input(value, cssClass));
        },

        /**
         * Filters operators by type
         * @param type default is STRING_TYPE
         */
        filterOperatorsByType = function(type) {
            var result = [];

            if (typeof type === 'string') {
                type = getTypeByName.call(this, type);
            } else {
                type = type || UNDEFINED_TYPE;
            }

            this.operators.forEach(function(operator) {
                if (operator.type === type) {
                    result.push(operator);
                }
            }.bind(this));
            return result;
        },

        /**
         * Returns if given type is supported for filtering.
         *
         * @param {string} type
         *
         * @returns {boolean}
         */
        isSupportedType = function(type) {
            if (typeMappings.hasOwnProperty(type)) {
                return true;
            }

            return false;
        },

        /**
         * Retrieves a numeric representation for a string representation of a type.
         *
         * @param {string} type
         *
         * @returns {number}
         */
        getTypeByName = function(type) {
            // check if mapping is supported
            if (!isSupportedType(type)) {
                this.sandbox.logger.error('Unsupported type "' + type + '" found!');

                return null;
            }

            return typeMappings[type];
        },

        /**
         * Creates a select for given values, wraps it in a grid col and returns it
         *
         * @param selected selected element
         * @param valueProperty name of the property which should be the value for each option
         * @param displayProperty name of the property which holds the value that should be displayed
         * @param values array of objects to display in select
         * @param cssClass css class for the select
         * @param prependEmpty prepend an empty option
         */
        createSelect = function(selected, valueProperty, displayProperty, values, cssClass, prependEmpty) {
            var options = createOptionsForSelect.call(this, selected, valueProperty, displayProperty, values, prependEmpty),
                $select = this.sandbox.dom.createElement(templates.select.call(this, cssClass));

            if (!!this.options.validationSelector) {
                this.sandbox.form.addField(this.options.validationSelector, $select);
            }

            this.sandbox.dom.append($select, options.join(''));
            return $select;
        },

        /**
         * Creates and returns an array of options for a select
         * @param selected
         * @param valueProperty
         * @param displayProperty
         * @param values
         * @param prependEmpty
         */
        createOptionsForSelect = function(selected, valueProperty, displayProperty, values, prependEmpty) {
            var key, translateText, options = [];

            if (!!prependEmpty) {
                options.push('<option value=""></option>');
            }

            for (key in values) {
                if (values.hasOwnProperty(key)) {
                    translateText = this.sandbox.translate(values[key][displayProperty]);
                    if (values[key][valueProperty] === selected) {
                        options.push('<option value="' + values[key][valueProperty] + '" selected>' + translateText + '</option>');
                    } else {
                        options.push('<option value="' + values[key][valueProperty] + '">' + translateText + '</option>');
                    }
                }
            }

            return options;
        },

        /**
         * Renders the add button at the bottom
         */
        renderAddButton = function() {
            var text = this.sandbox.translate(this.options.translations.addButton),
                $addButton = this.sandbox.dom.createElement(
                    templates.button.call(this, this.options.ids.addButton, text)
                );
            this.sandbox.dom.append(this.options.el, $addButton);
        },

        bindDOMEvents = function() {
            // add button
            this.sandbox.dom.on(this.options.el, 'click', addConditionEventHandler.bind(this), '#' + this.options.ids.addButton);

            // remove buttons
            this.sandbox.dom.on(this.$container, 'click', removeConditionEventHandler.bind(this), '.' + constants.removeButtonClass);

            // field changed event handler
            this.sandbox.dom.on(this.$container, 'change', fieldChangedEventHandler.bind(this), '.' + constants.fieldSelectClass);

            // operator changed event handler
            this.sandbox.dom.on(this.$container, 'change', operatorChangedEventHandler.bind(this), '.' + constants.operatorSelectClass);

            // data change event handler
            this.sandbox.dom.on(this.$el, EVENT_DATA_CHANGED.call(this), dataChangedEventHandler.bind(this));

            // listen for select change
            this.sandbox.dom.on(this.$container, 'change', function() {
                // FIXME Datepicker triggers multiple change events?
                updateDataAttribute.call(this);
            }.bind(this), 'select, input, div, .pickdate');

            // update operator data
            this.sandbox.dom.on(this.$container, 'change', function(event) {
                updateOperatorType.call(this, event);
            }.bind(this), 'select.' + constants.operatorSelectClass);

            // field select listener
            this.sandbox.dom.on(this.$container, 'change', fieldSelectChangedHandler.bind(this), 'select.' + constants.fieldSelectClass);
        },

        /**
         * Handles the event when a select for a field changed its value
         * @param event
         */
        fieldSelectChangedHandler = function(event) {
            var $select = this.sandbox.dom.createElement(event.currentTarget),
                oldValue = this.sandbox.dom.data($select, 'value'),
                value = this.sandbox.dom.val($select);

            if (!!oldValue) {
                removesUsedField.call(this, oldValue);
            }

            if (!!value) {
                addUsedField.call(this, value);
                updateSelectFields.call(this, $select[0]);
                this.sandbox.dom.data($select, 'value', value);
            }
        },

        updateOperatorType = function(event) {
            var $select = event.currentTarget,
                operatorId = this.sandbox.dom.val($select),
                operator = getOperatorById.call(this, operatorId);

            this.sandbox.dom.data($select, 'type', operator.type);
        },

        /**
         * Resets the used fields and puts all fields back into the fields obj
         */
        resetUsedFields = function() {
            for (var fieldName in this.usedFields) {
                this.fields[fieldName] = this.usedFields[fieldName];
            }
            this.usedFields = {};
        },

        /**
         * Triggers data update for component
         */
        dataChangedEventHandler = function() {
            var data = this.sandbox.dom.data(this.options.el, 'condition-selection');
            resetUsedFields.call(this);
            updateData.call(this, data);
            updateDataAttribute.call(this);
        },

        /**
         * Handles data update for component
         * @param data
         */
        updateData = function(data) {
            // remove all rows and remove them from validation
            var $rows = this.sandbox.dom.find('.' + constants.conditionRowClass, this.$container);
            this.sandbox.dom.each($rows, function(idx, $row) {
                removeConditionFromDomAndValidation.call(this, $row);
            }.bind(this));

            // add new rows
            this.options.data = data;
            renderRows.call(this);
        },

        /**
         * Triggers updte of input field
         */
        operatorChangedEventHandler = function(event) {
            var operatorId = event.target.value,
                $row = this.sandbox.dom.closest(event.target, '.' + constants.conditionRowClass),
                operator = getOperatorById.call(this, operatorId),
                $valueInput = this.sandbox.dom.find('.' + constants.valueInputClass, $row)[0],
                $valueInputParent = this.sandbox.dom.parent($valueInput),
                field = $row.data('field');

            // remove field from validation
            if (!!this.options.validationSelector) {
                this.sandbox.form.removeField(this.options.validationSelector, $valueInput);
            }

            this.sandbox.stop($valueInput);
            this.sandbox.dom.remove($valueInput);
            $valueInput = createValueInput.call(this, null, operator, null, false, field['filter-type-parameters']);
            this.sandbox.dom.append($valueInputParent, $valueInput);
        },

        /**
         * Triggers update of operator and input field
         * @param event
         */
        fieldChangedEventHandler = function(event) {
            var fieldName = event.target.value,
                field = this.fields[fieldName],
                filteredOperators = filterOperatorsByType.call(this, field['filter-type']),
                $row = this.sandbox.dom.closest(event.target, '.' + constants.conditionRowClass),
                $operatorSelect = this.sandbox.dom.find('.' + constants.operatorSelectClass, $row)[0],
                $valueInput = this.sandbox.dom.find('.' + constants.valueInputClass, $row)[0],
                $operatorSelectParent = this.sandbox.dom.parent($operatorSelect),
                $valueInputParent = this.sandbox.dom.parent($valueInput);

            $row.data('field', field);

            // remove fields from validation
            if (!!this.options.validationSelector) {
                this.sandbox.form.removeField(this.options.validationSelector, $valueInput);
                this.sandbox.form.removeField(this.options.validationSelector, $operatorSelect);
            }

            this.sandbox.dom.remove($operatorSelect);
            this.sandbox.stop($valueInput);
            this.sandbox.dom.remove($valueInput);

            $operatorSelect = createOperatorSelect.call(this, null, filteredOperators, true, false);
            $valueInput = createValueInput.call(this);

            this.sandbox.dom.append($operatorSelectParent, $operatorSelect);
            this.sandbox.dom.append($valueInputParent, $valueInput);
        },

        /**
         * Adds a new condition row
         */
        addConditionEventHandler = function() {
            renderRow.call(this);
            this.sandbox.emit(DATA_CHANGED.call(this));
        },

        /**
         * Removes a condition from the dom and the data
         * @param event
         */
        removeConditionEventHandler = function(event) {
            var $row = this.sandbox.dom.closest(event.currentTarget, '.' + constants.conditionRowClass),
                $select = this.sandbox.dom.find('.' + constants.fieldSelectClass, $row),
                value = this.sandbox.dom.data($select, 'value');

            removesUsedField.call(this, value);
            updateSelectFields.call(this, $select);
            removeConditionFromDomAndValidation.call(this, $row);
        },

        /**
         * Removes a condition form the dom and the validation
         *
         * @param $row
         */
        removeConditionFromDomAndValidation = function($row) {
            var id = this.sandbox.dom.data($row, 'id'),
                $fieldSelect = this.sandbox.dom.find('.' + constants.fieldSelectClass, $row),
                $operatorSelect = this.sandbox.dom.find('.' + constants.operatorSelectClass, $row),
                $valueInput = this.sandbox.dom.find('.' + constants.valueInputClass, $row);

            if (!!this.options.validationSelector) {
                this.sandbox.form.removeField(this.options.validationSelector, $fieldSelect);
                this.sandbox.form.removeField(this.options.validationSelector, $operatorSelect);
                this.sandbox.form.removeField(this.options.validationSelector, $valueInput);
            }

            this.sandbox.dom.remove($row);
            updateDataAttribute.call(this);
        },

        /**
         * Retrieves data from a row for a conditiongroup
         */
        getDataFromRow = function($row) {
            var cgData = {conditions: []},
                cgId = this.sandbox.dom.data($row, 'id'),
                fieldValue, operatorId, type, value, conditionId, condition, operator;

            if (!!cgId && cgId !== 'new') {
                cgData['id'] = cgId;
            }

            fieldValue = this.sandbox.dom.val(this.sandbox.dom.find('.' + constants.fieldSelectClass, $row));
            type = this.sandbox.dom.data(this.sandbox.dom.find('.' + constants.operatorSelectClass, $row), 'type');
            operatorId = this.sandbox.dom.val(this.sandbox.dom.find('.' + constants.operatorSelectClass, $row));
            conditionId = this.sandbox.dom.data(this.sandbox.dom.find('.' + constants.valueInputClass, $row), 'id');
            operator = getOperatorById.call(this, operatorId);

            value = getInputValue.call(this, operator || {}, $row);

            condition = {
                type: type,
                field: fieldValue,
                operator: !!operator ? operator.operator : null,
                value: value
            };

            if (!!conditionId) {
                condition.id = conditionId;
            }

            cgData.conditions.push(condition);
            return cgData;
        },

        /**
         * Retrieves data from current conditions
         */
        getData = function() {
            var data = [],
                $rows = this.sandbox.dom.find('.' + constants.conditionRowClass, this.$container);
            this.sandbox.dom.each($rows, function(idx, $row) {
                data.push(getDataFromRow.call(this, $row));
            }.bind(this));
            return data;
        },

        /**
         * Updates the data attribute for the data mapper
         */
        updateDataAttribute = function() {
            this.data = getData.call(this);
            this.sandbox.dom.data(this.options.el, 'conditionSelection', this.data);
            this.sandbox.emit(DATA_CHANGED.call(this));
        },

        /**
         * Transforms the fields array to a object for easier handling
         * @param fields array of fields
         */
        transformFieldsArrayToObject = function(fields) {
            var result = {};

            fields.forEach(function(field) {
                if (isSupportedType(field['filter-type'])) {
                    result[field.name] = field;
                }
            }.bind(this));

            return result;
        };

    return {

        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.options.ids = {
                loader: 'condition-selection-' + this.options.instanceName + '-loader',
                container: 'condition-selection-' + this.options.instanceName + '-container',
                addButton: 'condition-selection-' + this.options.instanceName + '-add-button'
            };

            startLoader.call(this);
            this.fetchOperatorsAndFields();
        },

        /**
         * Loads operators and fields if url is given and
         * triggers rendering if they successfully loaded
         */
        fetchOperatorsAndFields: function() {
            var operatorPromise, fieldsPromise;

            if (!!this.options.operatorsUrl &&
                typeof this.options.operatorsUrl === 'string' && !!this.options.fieldsUrl &&
                typeof this.options.fieldsUrl === 'string') {
                operatorPromise = this.sandbox.util.load(this.options.operatorsUrl);
                fieldsPromise = this.sandbox.util.load(this.options.fieldsUrl);

                this.sandbox.data.when(operatorPromise, fieldsPromise).done(
                    function(operators, fields) {
                        this.operators = operators[0]._embedded.items;
                        this.fields = transformFieldsArrayToObject.call(this, fields[0]);

                        this.render();
                    }.bind(this));
            } else {
                this.sandbox.logger.error('Url for fields and/or operators not specified or invalid!');
            }
        },

        render: function() {
            this.usedFields = {};
            this.deferreds = [];
            this.$container = this.sandbox.dom.createElement(
                templates.container(constants.conditionContainerClass, this.options.ids.container));
            this.sandbox.dom.append(this.options.el, this.$container);

            if (!!this.options.data) {
                renderRows.call(this);
            }

            renderAddButton.call(this);
            stopLoader.call(this);
            this.sandbox.dom.show(this.$container);

            if (!!this.deferreds) {
                this.sandbox.data.when.apply(this, this.deferreds).then(function() {
                    bindDOMEvents.call(this);
                }.bind(this));
            } else {
                bindDOMEvents.call(this);
            }

            this.sandbox.emit(INITIALIZED.call(this));
        }
    };
});
