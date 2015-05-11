/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * handles operator selection
 *
 * @class OperatorSelection
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

    'use strict';

    var UNDEFINED_TYPE = 0,
        STRING_TYPE = 1,
        NUMBER_TYPE = 2,
        DATETIME_TYPE = 3,

        defaults = {
            operatorsUrl: null,
            fieldsUrl: null,
            eventNamespace: 'sulu.operator-selection',
            dataAttribute: 'operator-selection',
            instanceName: 'operator',
            translations: {
                addButton: 'resource.filter.add-condition'
            },
            data: []
        },

        templates = {
            container: function(id) {
                return '<div class="operator-container" id="' + id + '" style="display:none"></div>';
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
            row: function(id) {
                return ['<div class="operator-row grid-row" data-id="', id , '"></div>'].join('');
            },
            removeButton: function(cssClass){
                return [
                    '<div class="grid-col-1 align-center pointer ', cssClass, '">',
                        '<span class="fa-minus-circle m-top-7"></span>',
                    '</div>'
                ].join('');
            }
        },

        constants = {
            operatorSelectSelector: 'operator-select',
            fieldSelectSelector: 'field-select',
            removeButtonClass: 'operator-remove'
        },

        /**
         * raised when all overlay components returned their value
         * @event sulu.operator-selection.initialized
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
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
         * Renders a row for a condition group
         * @param conditionGroup
         */
        renderRow = function(conditionGroup) {
            var condition = conditionGroup.conditions[0],
                $row = this.sandbox.dom.createElement(templates.row(conditionGroup.id)),
                $deleteButton = this.sandbox.dom.createElement(templates.removeButton(constants.removeButtonClass)),
                $fieldSelect = createSelect.call(
                    this,
                    condition.field,
                    'name',
                    'translation',
                    this.fields,
                    constants.fieldSelectSelector,
                    'grid-col-4'
                ),
                $operatorSelect = createSelect.call(
                    this,
                    condition.operator,
                    'operator',
                    'name',
                    this.operators,
                    constants.operatorSelectSelector,
                    'grid-col-3'
                ), // todo filter operators

                $valueComponent;

            // todo
            // 2 conditions
            // value is select field due to operator
            // createValueInput(conditionGroup.conditions);
            //if(conditionGroup.length === 1){
            //    switch(conditionGroup[0].type){
            //        case DATETIME_TYPE:
            //            // TODO
            //            break;
            //        default:
            //
            //            break;
            //    }
            //}

            this.sandbox.dom.append($row, $deleteButton);
            this.sandbox.dom.append($row, $fieldSelect);
            this.sandbox.dom.append($row, $operatorSelect);
            //this.sandbox.dom.append($row, $valueComponent);

            this.sandbox.dom.append(this.$container, $row);
        },

        /**
         * Creates a select for given values, wraps it in a grid col and returns it
         *
         * @param selected selected element
         * @param valueProperty name of the property which should be the value for each option
         * @param displayProptery name of the property which holds the value that should be displayed
         * @param values array of objects to display in select
         * @param cssClass css class for the select
         * @param gridColClass class for a column of the grid which is used to wrapp the select
         */
        createSelect = function(selected, valueProperty, displayProptery, values, cssClass, gridColClass) {
            var options = [],
                translateText = null,
                $wrapper = this.sandbox.dom.createElement('<div class="' + gridColClass + '"></div>'),
                $select = this.sandbox.dom.createElement('<select class="form-element ' + cssClass + '"></select>');

            values.forEach(function(value) {
                translateText = this.sandbox.translate(value[displayProptery]);
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
            this.sandbox.dom.append(this.$container, $addButton);
        };

    return {

        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.options.ids = {
                loader: 'operator-selection-' + this.options.instanceName + '-loader',
                container: 'operator-selection-' + this.options.instanceName + '-container',
                addButton: 'operator-selection-' + this.options.instanceName + '-add-button'
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
            this.$container = this.sandbox.dom.createElement(templates.container(this.options.ids.container));
            this.sandbox.dom.append(this.options.el, this.$container);

            if (!!this.options.data) {
                renderRows.call(this);
            }
            renderAddButton.call(this);

            stopLoader.call(this);
            this.sandbox.dom.show(this.$container);
            this.sandbox.emit(INITIALIZED.call(this));
        }
    };
});


