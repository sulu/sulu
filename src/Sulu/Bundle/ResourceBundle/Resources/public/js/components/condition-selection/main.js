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
        BOOLEAN_TYPE = 4,

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
            $valueComponent = createValueInput.call(this, conditionGroup, 'grid-col-4', true);

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
         * @param gridColClass css class used for the wrapper of the input - should be a grid-col class
         * @param wrap
         */
        createValueInput = function(conditionGroup, gridColClass, wrap) {
            var $input = null,
                $wrapper;

            if (!!conditionGroup) {
                if (conditionGroup.conditions.length === 2) {
                    // TODO
                    this.sandbox.logger.error('Multiple conditions not yet supported!');
                } else {
                    $input = createInputForType.call(this, conditionGroup.conditions[0], constants.valueInputClass);
                }
            } else { // new added row
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
         * Decides which input should be displayed for the given condition
         * @param condition
         */
        createInputForType = function(condition) {
          switch(condition.type){
              case STRING_TYPE:
              case NUMBER_TYPE:
              case UNDEFINED_TYPE:
                  return createSimpleInput.call(this, condition.value, constants.valueInputClass);
              case DATETIME_TYPE:
              case BOOLEAN_TYPE:
                  // TODO
                  this.sandbox.logger.error('Valueinput for this type not yet implemented!');
                  break;
              default:
                  this.sandbox.logger.log('Field type '+condition.type+' is unknown!');
          }
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
                    return NUMBER_TYPE;
                case 'boolean':
                case 'date':
                case 'datetime':
                    this.sandbox.logger.error('Some types not yet supportet');
                    return;
                default:
                    this.sandbox.logger.error('Unsupported type '+ type+' found!');
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

            this.sandbox.dom.on(this.$container, 'change', fieldChangedEventHandler.bind(this), '.'+constants.fieldSelectClass);
        },

        bindCustomEvents = function(){
            // TODO
        },

        /**
         * Triggers update of operator and input field
         * @param event
         */
        fieldChangedEventHandler = function(event) {
            var fieldName = event.target.value,
                field = getFieldByName.call(this, fieldName),
                filteredOperators = filterOperatorsByType.call(this, field.type),
                $row = this.sandbox.dom.parent('.' + constants.conditionRowClass, event.target),
                $operatorSelect = this.sandbox.dom.find('.' + constants.operatorSelectClass, $row),
                $valueInput = this.sandbox.dom.find('.' + constants.valueInputClass, $row),
                $operatorSelectParent = this.sandbox.dom.parent($operatorSelect),
                $valueInputParent = this.sandbox.dom.parent($valueInput);

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
            var result = null;
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
            var $el = this.sandbox.dom.parent(event.currentTarget, '.'+constants.conditionRowClass),
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


