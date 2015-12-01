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

    return function(app) {
        /**
         * Gets executed every time BEFORE a component gets initialized.
         * Loads data if needed and start executing component handlers.
         */
        app.components.before('initialize', function() {
            if (!this.defaults) {
                return;
            }

            // merge options
            if (!!this.defaults.options) {
                this.options = this.sandbox.util.extend(true, {}, this.defaults.options, this.options);
            }

            // merge translations and translate the values
            if (!!this.defaults.translations) {
                var translations = this.sandbox.util.extend(true, {}, this.defaults.translations, this.options.translations || {});
                this.translations = this.sandbox.util.object(
                    this.sandbox.util.arrayMap(translations, function(item, key) {
                        return [key, this.sandbox.translate(item)];
                    }.bind(this))
                );
            }

            // merge templates and prepare template functions
            if (!!this.defaults.templates) {
                var templates = this.sandbox.util.extend(true, {}, this.defaults.templates, this.options.templates || {});
                this.templates = this.sandbox.util.object(
                    this.sandbox.util.arrayMap(templates, function(template, key) {
                        return [key, this.sandbox.util.template(template)];
                    }.bind(this))
                );
            }
        });
    };
});
