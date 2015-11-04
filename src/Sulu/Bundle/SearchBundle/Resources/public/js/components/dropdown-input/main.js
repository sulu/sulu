/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

/**
 * @class DropdownInput
 * @constructor
 *
 * @param {Object} [options] Configuration object
 * @param {String} [options.instanceName] The instance name of the sidebar
 * @param {Array} [options.data] The content of the dropdown menu
 * @param {String} [options.valueName] The property name to get the option label
 * @param {Number} [options.preSelectedElement] The preselcted element
 * @param {Object} [options.icons] Collection of all icons
 * @param {Bool} [options.focused] Focus the input on render
 */
define(['text!sulusearch/components/dropdown-input/main.html'], function(mainTemplate) {

    'use strict';

    var defaults = {
            instanceName: '',
            data: [],
            valueName: 'name',
            preSelectedElement: 0,
            focused: false,
            icons: {
                inputIcon: 'fa-search'
            }
        },

        createEventName = function(postfix) {
            return 'sulu.dropdown-input.' + ((!!this.options.instanceName) ? this.options.instanceName + '.' : '') + postfix;
        },

        /**
         * trigger after initialization has finished
         * @event sulu.dropdown-input.[INSTANCE_NAME].initialized
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        },

        /**
         * set the value of the drop down
         * @event sulu.dropdown-input.[INSTANCE_NAME].set
         */
        SET = function() {
            return createEventName.call(this, 'set');
        },

        /**
         * focus the input field on this event
         * @event sulu.dropdown-input.[INSTANCE_NAME].focus
         */
        FOCUS = function() {
            return createEventName.call(this, 'focus');
        },

        /**
         * emit that the action button was clicked
         * @event sulu.dropdown-input.[INSTANCE_NAME].action
         */
        ACTION = function() {
            return createEventName.call(this, 'action');
        },

        /**
         * emit that dropdown value has changed
         * @event sulu.dropdown-input.[INSTANCE_NAME].change
         */
        CHANGE = function() {
            return createEventName.call(this, 'change');
        },

        /**
         * emit that dropdown value was cleared
         * @event sulu.dropdown-input.[INSTANCE_NAME].clear
         */
        CLEAR = function() {
            return createEventName.call(this, 'clear');
        };

    return {
        /**
         * @method initialize
         */
        initialize: function() {
            // merge defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.mainTemplate = this.sandbox.util.template(mainTemplate);

            this.render();
            this.storeSelectors();
            this.bindEvents();
            this.bindDomEvents();
            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * @method bindEvents
         */
        bindEvents: function() {
            this.sandbox.on(FOCUS.call(this), this.focusInputhandler.bind(this));
            this.sandbox.on(SET.call(this), this.setSelected.bind(this));
            this.sandbox.on('husky.select.' + this.dropDownInstance + '.selected.item', this.dropdownChangeHandler.bind(this));
        },

        /**
         * @method bindDomEvents
         */
        bindDomEvents: function() {
            this.$el.on('click', '.dropdown-input-action-icon', this.inputActionHandler.bind(this));
            this.$el.on('click', '.dropdown-input-clear', this.clearInput.bind(this));
            this.$el.on('input', '.dropdown-input-entry', this.inputChangeHandler.bind(this));
            this.$el.on('keydown', '.dropdown-input-entry', this.inputKeyDownHandler.bind(this));
        },

        /**
         * @method setSelected
         * @param {String} category
         */
        setSelected: function(category) {
            this.sandbox.dom.find('.husky-select', this.$el).data({
                selection: category.id,
                selectionValues: category.name
            }).trigger('data-changed');

            this.selectedElement = category.id;

            this.sandbox.emit(CHANGE.call(this), {
                value: this.$input.val(),
                selectedElement: this.selectedElement
            });
        },

        /**
         * @method storeSelectors
         */
        storeSelectors: function() {
            this.$input = this.$el.find('.dropdown-input-entry');
            this.$dropdownContainer = this.$el.find('.dropdown-input-container');
        },

        /**
         * @method render
         */
        render: function() {
            var template = this.mainTemplate({
                icons: this.options.icons
            });

            this.$el.html(template);
            this.storeSelectors();
            this.updatePlaceHolder();
            this.createDropdown();

            if (this.options.focused) {
                this.focusInputhandler();
            }
        },

        /**
         * @method createDropdown
         */
        createDropdown: function() {
            this.dropDownInstance = this.options.instanceName + 'Select';

            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: this.$el.find('.dropdown-input-trigger-container'),
                        instanceName: this.options.instanceName + 'Select',
                        valueName: this.options.valueName,
                        multipleSelect: false,
                        emitValues: false,
                        preSelectedElements: [this.options.preSelectedElement],
                        data: this.options.data
                    }
                }
            ]);
        },

        /**
         * @method updatePlaceHolder
         */
        updatePlaceHolder: function() {
            var selected;

            if (this.selectedElement !== void 0) {
                selected = this.selectedElement;
            } else {
                selected = this.options.preSelectedElement;
            }

            selected = _.where(this.options.data, {id: selected})[0];

            this.$input.attr({
                'placeholder': this.sandbox.translate('search-overlay.placeholder').replace('%s', selected.name) || ''
            });
        },

        /**
         * @method dropdownChangeHandler
         * @param {Object} item
         */
        dropdownChangeHandler: function(item) {
            this.selectedElement = item;

            this.sandbox.emit(CHANGE.call(this), {
                value: this.$input.val(),
                selectedElement: this.selectedElement
            });

            this.updatePlaceHolder();
        },

        /**
         * @method inputChangeHandler
         */
        inputChangeHandler: function() {
            if (!this.$input.val()) {
                this.$dropdownContainer.removeClass('is-filled');
            } else {
                this.$dropdownContainer.addClass('is-filled');
            }
        },

        /**
         * @method inputKeyDownHandler
         */
        inputKeyDownHandler: function(event) {
            if (event.which === 13) {
                event.preventDefault();

                this.inputActionHandler();
            }
        },

        /**
         * @method clearInput
         */
        clearInput: function() {
            this.$input.val('');
            this.$input.focus();
            this.$dropdownContainer.removeClass('is-filled');
            this.sandbox.emit(CLEAR.call(this));
        },

        /**
         * @method focusInputhandler
         */
        focusInputhandler: function() {
            this.$input.focus();
        },

        /**
         * @method inputActionHandler
         */
        inputActionHandler: function() {
            this.sandbox.emit(ACTION.call(this), {
                value: this.$input.val(),
                selectedElement: this.selectedElement
            });
        }
    };
});
