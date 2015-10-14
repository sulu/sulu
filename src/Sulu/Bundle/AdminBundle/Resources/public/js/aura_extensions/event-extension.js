/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    /**
     * Create event name with namespace and postfix.
     * @param {String} eventNamespace
     * @param {String} postFix
     * @param {String} instanceName
     * @returns {string}
     */
    var createEventName = function(eventNamespace, postFix, instanceName) {
            return eventNamespace + (!!instanceName ? instanceName + '.' : '') + postFix;
        },

        /**
         * Returns function which throws event with given namespace and postfix.
         * @param {String} eventNamespace
         * @param {String} postFix
         * @returns {function}
         */
        emitEventFactory = function(eventNamespace, postFix) {
            var eventName = createEventName.call(this, eventNamespace, postFix, this.options.instanceName);

            return function() {
                var args = [].splice.call(arguments, 0);
                args.splice(0, 0, eventName);
                this.sandbox.emit.apply(this, args);
            }.bind(this)
        },

        /**
         * Creates eventListener with given eventName and callback.
         * @param {String} eventNamespace
         * @param {String} postFix
         * @returns {function}
         */
        onEventFactory = function(eventNamespace, postFix) {
            var eventName = createEventName.call(this, eventNamespace, postFix, this.options.instanceName);

            return function(callback) {
                this.sandbox.on(eventName, callback);
            }.bind(this)
        },

        /**
         * Creates once eventListener with given eventName and callback.
         * @param {String} eventNamespace
         * @param {String} postFix
         * @returns {function}
         */
        onceEventFactory = function(eventNamespace, postFix) {
            var eventName = createEventName.call(this, eventNamespace, postFix, this.options.instanceName);

            return function(callback) {
                this.sandbox.once(eventName, callback);
            }.bind(this)
        };

    return function(app) {

        /**
         * Create event name with namespace and postfix.
         * @type {Function}
         */
        app.sandbox.events.createEventName = createEventName;

        /**
         * Gets executed every time BEFORE a component gets initialized.
         * Loads data if needed and start executing component handlers
         */
        app.components.before('initialize', function() {
            if (!this.events) {
                return;
            }

            this.events = this.sandbox.util.object(
                this.sandbox.util.arrayMap(this.events.names, function(event, key) {
                    if (event.type === 'on') {
                        return [key, onEventFactory.call(this, this.events.namespace, event.postFix, event.callback)];
                    } else if (event.type === 'once') {
                        return [key, onceEventFactory.call(this, this.events.namespace, event.postFix, event.callback)];
                    }

                    return [key, emitEventFactory.call(this, this.events.namespace, event.postFix)];
                }.bind(this))
            );
        });
    };
});
