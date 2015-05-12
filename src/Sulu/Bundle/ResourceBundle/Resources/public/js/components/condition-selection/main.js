/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * handles condition selection
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
 */
define([], function() {

    // TODO write data in attribute?
    // TODO update event to update saved elements with ids

    'use strict';

    var UNDEFINED_TYPE = 0,
        STRING_TYPE = 1,
        NUMBER_TYPE = 2,
        DATETIME_TYPE = 3,
        BOOLEAN_TYPE = 4, // TODO ?

        defaults = {
            operatorsUrl: null,
            fieldsUrl: null,
            eventNamespace: 'sulu.condition-selection',
            dataAttribute: 'condition-selection',
            instanceName: 'condition',
            translations: {
                addButton: 'resource.filter.add-condition'
            },
            data: []
        },

        templates = {
            container: function(cssClass, id) {
                return ['<div class="', cssClass, '" id="', id, '" style="display:none"></div>'].join('');
            },
            button: function(id, text) {
                return [
                    '<div class="grid-row">',
                        '<div class="grid-col-3">',
                            '<div id="' , id , '" class="btn action">',
                                text,
                            '</div>',
                        '</div>',
                    '</div>'
                ].join('');
            },
            row: function(cssClass, id) {
                return ['<div class="',cssClass,' grid-row" data-id="', id , '"></div>'].join('');
            },
            removeButton: function(cssClass){
                return [
                    '<div class="grid-col-1 align-center pointer ', cssClass, '">',
                        '<span class="fa-minus-circle m-top-5"></span>',
                    '</div>'
                ].join('');
            },
            input: function(value, cssClass){
                return ['<input class="form-element husky-validate ',cssClass,'" type="text" value="',value,'">'].join('');
            },
            col: function(cssClass) {
                return ['<div class="',cssClass,'"></div>'].join('');
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
         * Stops the grid-group loader
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
                id = !!conditionGroup ? conditionGroup.id : 'new';

            $row = this.sandbox.dom.createElement(templates.row(constants.conditionRowClass, id));
            $deleteButton = this.sandbox.dom.createElement(templates.removeButton(constants.removeButtonClass));

            if (!!conditionGroup) {
                condition = conditionGroup.conditions[0];
                filteredOperators = filterOperatorsByType.call(this, condition.type);
                $fieldSelect = createFieldSelect.call(this, condition.field, false, true);
            } else {
                $fieldSelect = createFieldSelect.call(this, condition.field, true, true);
            }

            $operatorSelect = createOperatorSelect.call(this, condition.operator, filteredOperators, false, true);
            operator = getOperatorByOperandAndType.call(this, condition.operator, condition.type);
            $valueComponent = createValueInput.call(this, conditionGroup, operator, 'grid-col-4', true);

            this.sandbox.dom.append($row, $deleteButton);
            this.sandbox.dom.append($row, $fieldSelect);
            this.sandbox.dom.append($row, $operatorSelect);
            this.sandbox.dom.append($row, $valueComponent);
            this.sandbox.dom.append(this.$container, $row);
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
                    selectedOperator,
                    'operator',
                    'name',
                    operators,
                    constants.operatorSelectClass,
                    prependEmpty
                );

            if (!!wrap) {
                $wrapper = this.sandbox.dom.createElement(templates.col('grid-col-3'));
                this.sandbox.dom.append($wrapper, $select);
                return $wrapper;
            }
            return $select;
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
         */
        createValueInput = function(conditionGroup, operator, gridColClass, wrap) {
            var $input = null,
                $wrapper,
                condition;

            if (!!conditionGroup && !!operator) {
                if (conditionGroup.conditions.length > 1) {
                    this.sandbox.logger.error('Multiple conditions not yet supported!');
                } else {
                    condition = conditionGroup.conditions[0];
                    $input = createInputForType.call(this, operator, condition.value);
                }
            } else if (!conditionGroup && !!operator) {
                $input = createInputForType.call(this, operator, '');
            } else {
                $input = createSimpleInput.call(this, '', constants.valueInputClass);
            }

            if (!!wrap) {
                $wrapper = this.sandbox.dom.createElement(templates.col(gridColClass));
                this.sandbox.dom.append($wrapper, $input);
                return $wrapper;
            }

            return $input;
        },

        /**
         * Searches for an operator by its operand and type
         */
        getOperatorByOperandAndType = function(operand, type) {
            var result = null;

            if(typeof type === 'string') {
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
         */
        createInputForType = function(operator, value) {
          switch(operator.inputType){
              case 'date':
              case 'datepicker':
                  return createDatepicker.call(this, value, constants.valueInputClass);
              case 'select':
                  return createSelect.call(this, value, 'value', 'name', operator.values, constants.valueInputClass);
              case '':
              case 'simple':
                  return createSimpleInput.call(this, value, constants.valueInputClass);
              default:
                  this.sandbox.logger.error('Input type "' + type + '" is not supported!');
                  break;

          }
        },

        /**
         * Creates and starts datepicker input field
         * @param value
         * @param valueInputClass
         */
        createDatepicker = function(value, valueInputClass) {
            var $datepicker = this.sandbox.dom.createElement('<div class="'+valueInputClass+'"></div>');
            this.sandbox.start([
                {
                    name: 'input@husky',
                    options: {
                        el: $datepicker,
                        datepickerOptions: {"startDate":"1900-01-01", "endDate": new Date()},
                        skin: 'date',
                        value: value
                    }
                }
            ]);

            return $datepicker;
        },

        /**
         * Creates a simple input field
         * @param value
         * @param cssClass
         */
        createSimpleInput = function(value, cssClass){
           return this.sandbox.dom.createElement(templates.input(value, cssClass));
        },

        /**
         * Filters operators by type
         * @param type default is STRING_TYPE
         */
        filterOperatorsByType = function(type) {
            var result = [];

            if(typeof type === 'string') {
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
         * Retrieves a numeric representation for a string representation of a type
         * @param type
         * @returns {number}
         */
        getTypeByName = function(type){
            switch(type){

                case 'string':
                    return STRING_TYPE;
                case 'number':
                case 'integer':
                case 'float':
                    return NUMBER_TYPE;
                case 'boolean':
                    this.sandbox.logger.error('Some types not yet supportet'); // TODO?
                    return;
                case 'date':
                case 'datetime':
                    return DATETIME_TYPE;
                default:
                    this.sandbox.logger.error('Unsupported type "'+ type +'" found!');
                    return;
            }
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
            var options = [],
                translateText = null,
                $select = this.sandbox.dom.createElement('<select class="form-element ' + cssClass + '"></select>');

            if(!!prependEmpty){
                options.push('<option value=""></option>');
            }

            values.forEach(function(value) {
                translateText = this.sandbox.translate(value[displayProperty]);
                if (value[valueProperty] === selected) {
                    options.push('<option value="' + value[valueProperty] + '" selected>' + translateText + '</option>');
                } else {
                    options.push('<option value="' + value[valueProperty] + '">' + translateText + '</option>');
                }
            }.bind(this));

            this.sandbox.dom.append($select, options.join(''));
            return $select;
        },

        /**
         * Renders the add button at the bottm
         */
        renderAddButton = function() {
            var text = this.sandbox.translate(this.options.translations.addButton),
                $addButton = this.sandbox.dom.createElement(
                    templates.button.call(this, this.options.ids.addButton, text)
                );
            this.sandbox.dom.append(this.options.el, $addButton);
        },


        bindDomEvents = function(){
            // add button
            this.sandbox.dom.on(this.options.el, 'click', addConditionEventHandler.bind(this), '#'+this.options.ids.addButton);

            // remove buttons
            this.sandbox.dom.on(this.$container, 'click', removeConditionEventHandler.bind(this), '.'+constants.removeButtonClass);

            // field changed event handler
            this.sandbox.dom.on(this.$container, 'change', fieldChangedEventHandler.bind(this), '.'+constants.fieldSelectClass);

            // operator changed event handler
            this.sandbox.dom.on(this.$container, 'change', operatorChangedEventHandler.bind(this), '.'+constants.operatorSelectClass);
        },

        bindCustomEvents = function(){
            // TODO
        },

        /**
         * Triggers updte of input field
         */
        operatorChangedEventHandler = function() {
            var operatorValue = event.target.value,
                $row = this.sandbox.dom.closest(event.target, '.' + constants.conditionRowClass),
                fieldName = this.sandbox.dom.val(this.sandbox.dom.find('.' + constants.fieldSelectClass, this.$container)),
                field = getFieldByName.call(this, fieldName),
                operator = getOperatorByOperandAndType.call(this, operatorValue, field.type),
                $valueInput = this.sandbox.dom.find('.' + constants.valueInputClass, $row)[0],
                $valueInputParent = this.sandbox.dom.parent($valueInput);

            // TODO do this only when type changes
            // TODO stop possible component in valueInput
            this.sandbox.dom.remove($valueInput);
            $valueInput = createValueInput.call(this, null, operator, null, false);
            this.sandbox.dom.append($valueInputParent, $valueInput);

            // TODO trigger change in data?
        },

        /**
         * Triggers update of operator and input field
         * @param event
         */
        fieldChangedEventHandler = function(event) {
            var fieldName = event.target.value,
                field = getFieldByName.call(this, fieldName),
                filteredOperators = filterOperatorsByType.call(this, field.type),
                $row = this.sandbox.dom.closest(event.target, '.' + constants.conditionRowClass),
                $operatorSelect = this.sandbox.dom.find('.' + constants.operatorSelectClass, $row)[0],
                $valueInput = this.sandbox.dom.find('.' + constants.valueInputClass, $row)[0],
                $operatorSelectParent = this.sandbox.dom.parent($operatorSelect),
                $valueInputParent = this.sandbox.dom.parent($valueInput);

            // TODO do this only when type changes
            // TODO stop possible component in valueInput
            this.sandbox.dom.remove($operatorSelect);
            this.sandbox.dom.remove($valueInput);

            $operatorSelect = createOperatorSelect.call(this, null, filteredOperators, true, false);
            $valueInput = createValueInput.call(this);

            this.sandbox.dom.append($operatorSelectParent, $operatorSelect);
            this.sandbox.dom.append($valueInputParent, $valueInput);

            // TODO trigger change in data?
        },

        /**
         * Searches for a field by name
         * @param name
         * @returns {*}
         */
        getFieldByName = function(name) {
            var result = {type: 'string'}; // default if no type is defined
            this.fields.forEach(function(field) {
                if (field.name === name) {
                    result = field;
                    return false;
                }
            }.bind(this));
            return result;
        },

        /**
         * Adds a new condition row
         */
        addConditionEventHandler = function(){
            renderRow.call(this);
        },

        /**
         * Removes a condition from the dom and the data
         * @param event
         */
        removeConditionEventHandler = function(event){
            var $el = this.sandbox.dom.closest(event.currentTarget, '.'+constants.conditionRowClass),
                id = this.sandbox.dom.data($el, 'id'),
                conditionGroupIdx = null;

            if(id !== 'new') {
                this.options.data.forEach(function(el, idx){
                    if(el.id === id) {
                        conditionGroupIdx = idx;
                        return false;
                    }
                }.bind(this));
                this.options.data.splice(conditionGroupIdx, 1);
            }

            this.sandbox.dom.remove($el);
            updateDataAttribute.call(this);
        },

        /**
         * Updates the data attribute for the data mapper
         */
        updateDataAttribute = function(){
            // TODO update attribute and add validation type to fetch data
            this.sandbox.emit(DATA_CHANGED.call(this));
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

            // TODO set data via mapper or data
            // TODO bindCustomEvents.call(this);

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
                        this.fields = fields[0];

                        this.render();
                    }.bind(this));
            } else {
                this.sandbox.logger.error('Url for fields and/or operators not specified or invalid!');
            }
        },

        render: function() {
            this.$container = this.sandbox.dom.createElement(
                templates.container(constants.conditionContainerClass, this.options.ids.container));
            this.sandbox.dom.append(this.options.el, this.$container);

            if (!!this.options.data) {
                renderRows.call(this);
            }

            renderAddButton.call(this);
            stopLoader.call(this);
            this.sandbox.dom.show(this.$container);
            bindDomEvents.call(this);
            bindCustomEvents.call(this);

            this.sandbox.emit(INITIALIZED.call(this));
        }
    };
});


