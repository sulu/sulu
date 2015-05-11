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

    'use strict';

    var UNDEFINED_TYPE = 0,
        STRING_TYPE = 1,
        NUMBER_TYPE = 2,
        DATETIME_TYPE = 3,

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
                return '<div class="' + cssClass + '" id="' + id + '" style="display:none"></div>';
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
            }
        },

        constants = {
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
                id = !!conditionGroup ? conditionGroup.id : 'new';

            $row = this.sandbox.dom.createElement(templates.row(constants.conditionRowClass, id));
            $deleteButton = this.sandbox.dom.createElement(templates.removeButton(constants.removeButtonClass));

            if (!!conditionGroup) {
                condition = conditionGroup.conditions[0];
                filteredOperators = filterOperatorsByType.call(this, condition.type);
                $fieldSelect = createFieldSelect.call(this, condition.field, false);
            } else {
                $fieldSelect = createFieldSelect.call(this, condition.field, true);
            }

            $operatorSelect = createOperatorSelect.call(this, condition.operator, filteredOperators, false);

            // TODO 1 field selected?
            //$valueComponent = createValueInput.call(this, conditionGroup);

            this.sandbox.dom.append($row, $deleteButton);
            this.sandbox.dom.append($row, $fieldSelect);
            this.sandbox.dom.append($row, $operatorSelect);
            //this.sandbox.dom.append($row, $valueComponent); // TODO

            this.sandbox.dom.append(this.$container, $row);
        },

        /**
         * Creates a select for operators
         * @param selectedOperator
         * @param operators
         * @param prependEmpty
         */
        createOperatorSelect = function(selectedOperator, operators, prependEmpty){
            return createSelect.call(
                this,
                selectedOperator,
                'operator',
                'name',
                operators,
                constants.operatorSelectClass,
                'grid-col-3',
                prependEmpty
            );
        },

        /**
         * Creates a select for fields
         * @param selectedField
         * @param prependEmpty
         */
        createFieldSelect = function(selectedField, prependEmpty) {
            return createSelect.call(
                this,
                selectedField,
                'name',
                'translation',
                this.fields,
                constants.fieldSelectClass,
                'grid-col-4',
                prependEmpty
            );
        },

        /**
         * Creates the input(s) depending on the condition group and the type of the selected field
         * @param conditionGroup
         */
        createValueInput = function(conditionGroup){
            // TODO
            if(conditionGroup.conditions.length === 2) {

            } else {

            }
        },

        /**
         * Filters operators by type
         * @param type default is STRING_TYPE
         */
        filterOperatorsByType = function(type) {
            var result = [];
            type = type || STRING_TYPE;

            this.operators.forEach(function(operator) {
                if (operator.type === type) {
                    result.push(operator);
                }
            }.bind(this));
            return result;
        },

        /**
         * Creates a select for given values, wraps it in a grid col and returns it
         *
         * @param selected selected element
         * @param valueProperty name of the property which should be the value for each option
         * @param displayProperty name of the property which holds the value that should be displayed
         * @param values array of objects to display in select
         * @param cssClass css class for the select
         * @param gridColClass class for a column of the grid which is used to wrapp the select
         * @param prependEmpty prepend an empty option
         */
        createSelect = function(selected, valueProperty, displayProperty, values, cssClass, gridColClass, prependEmpty) {
            var options = [],
                translateText = null,
                $wrapper = this.sandbox.dom.createElement('<div class="' + gridColClass + '"></div>'),
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
            this.sandbox.dom.append($wrapper, $select);
            return $wrapper;
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
        },

        bindCustomEvents = function(){
            // TODO
        },

        /**
         * Adds a new condition row
         * @param event
         */
        addConditionEventHandler = function(event){
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


