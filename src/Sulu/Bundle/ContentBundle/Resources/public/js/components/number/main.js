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
            instanceName: 'undefined',
            value: '',
            placeholder: '',
            inputId: '',
            inputName: '',
            disabled: false,
        },

        constants = {
            componentClass: 'husky-input',
            inputClass: 'input',
        },

        templates = {
            skeleton: '<input type="text" value="<%= value %>" placeholder="<%= placeholder %>" id="<%= id %>" name="<%= name %>" data-from="false" <%= disabled %>/>'
        },

        /**
         * namespace for events
         * @type {string}
         */
        eventNamespace = 'sulu.number.',


        /**
         * raised after initialization process
         * @event husky.input.<instance-name>.initialize
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        },

        /**
         * returns normalized event names
         */
        createEventName = function(postFix) {
            return eventNamespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        };

    return {
        initialize: function() {
            this.sandbox.logger.log('initialize', this);
            // extend default options
            this.options = this.sandbox.util.extend({}, defaults, this.options);

            this.input = {
                $input: null,
            };

            this.render();
            this.bindDomEvents();

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Renders the input component
         */
        render: function() {
            this.sandbox.dom.addClass(this.$el, constants.componentClass);


            var $container = this.sandbox.dom.createElement('<div class="' + constants.inputClass + '"/>');
            this.input.$input = this.sandbox.dom.createElement(this.sandbox.util.template(templates.skeleton)({
                name: (!!this.options.inputName) ? this.options.inputName : 'husky-input-' + this.options.instanceName,
                id: (!!this.options.inputId) ? this.options.inputId : 'husky-input-' + this.options.instanceName,
                value: this.options.value,
                placeholder: this.options.placeholder,
                disabled: (this.options.disabled === true) ? 'disabled' : ''
            }));
            this.sandbox.dom.append($container, this.input.$input);
            this.sandbox.dom.append(this.$el, $container);
            console.log(this.input.$input)
        },

        /**
         * Binds Dom-related events
         */
        bindDomEvents: function() {
            this.sandbox.dom.on(this.$el, 'change', function(event) {
                event.stopPropagation();
                event.preventDefault();

                this.$el.trigger('change');
            }.bind(this), 'input');

            this.sandbox.dom.on(this.$el, 'click', function() {
                this.sandbox.dom.focus(this.input.$input);
            }.bind(this));

            this.sandbox.dom.on(this.input.$input, 'click', function() {
                this.sandbox.dom.focus(this.input.$input);
            }.bind(this));

            // delegate labels on input
            if (!!this.sandbox.dom.attr(this.$el, 'id')) {
                this.sandbox.dom.on('label[for="' + this.sandbox.dom.attr(this.$el, 'id') + '"]', 'click', function() {
                    this.sandbox.dom.focus(this.input.$input);
                }.bind(this));
            }

            // change the input value if the data attribute got changed
            this.sandbox.dom.on(this.$el, 'data-changed', function() {
                this.updateValue();
            }.bind(this));
        },

        /**
         * Sets the input value
         * @param value {String} new value
         */
        setValue: function(value) {
            this.sandbox.dom.val(this.input.$input, value);
        },

        /**
         * Updates the value with what is in the
         * data attribute
         */
        updateValue: function() {
            this.setValue(this.sandbox.dom.data(this.$el, 'value'));
        }
    }
});