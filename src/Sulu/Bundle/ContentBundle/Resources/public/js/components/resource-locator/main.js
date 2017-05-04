/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * handles resource locator
 *
 * @class ResourceLocator
 * @constructor
 */
define(['config'], function(Config) {

    'use strict';

    var defaults = {
            instanceName: null,
            url: null,
            historyApi: null,
            historyResultKey: 'resourcelocators',
            pathKey: 'resourceLocator',
            deleteApi: null,
            inputType: null,
            webspaceKey: null
        },

        skeleton = function(options) {
            if (options.hasOwnProperty('value') && options.value === '/') {
                return [
                    '<div class="resource-locator">',
                    '   <span id="' + options.ids.url + '" class="grey-font">', (!!options.url) ? options.url : '', '</span>',
                    '   <span id="' + options.ids.tree + '" class="grey-font"></span>',
                    '</div>'
                ].join('');
            } else {
                var history = [
                    '<div style="display: inline-block;"',
                    '     data-aura-component="resource-locator/history@sulucontent" ',
                    '     data-aura-url="', options.historyApi, '"',
                    '     data-aura-result-key="', options.historyResultKey, '"',
                    '     data-aura-path-key="', options.pathKey, '"/>'
                ];

                return [
                    '<div class="resource-locator">',
                    '   <span id="' + options.ids.url + '" class="grey-font">', (!!options.url) ? options.url : '', '</span>',
                    '   <span id="' + options.ids.tree + '" class="grey-font"></span>',
                    '   <input type="text" id="' + options.ids.input + '" class="form-element"/>',
                    !!options.historyApi ? history.join('') : '',
                    '</div>'
                ].join('');
            }
        },

        getId = function(type) {
            return '#' + this.options.ids[type];
        },

        render = function() {
            this.options.ids = {
                url: 'resource-locator-' + this.options.instanceName + '-url',
                input: 'resource-locator-' + this.options.instanceName + '-input',
                tree: 'resource-locator-' + this.options.instanceName + '-tree',
                toggle: 'resource-locator-' + this.options.instanceName + '-toggle'
            };
            this.html(skeleton(this.options));

            setValue.call(this);

            bindDomEvents.call(this);
        },

        bindDomEvents = function() {
            // set value
            this.sandbox.dom.on(this.$el, 'data-changed', function (e, value) {
                setValue.call(this, value);
            }.bind(this));

            // value change
            this.sandbox.dom.on(getId.call(this, 'input'), 'keyup', setDataValue.bind(this));
            this.sandbox.dom.on(getId.call(this, 'input'), 'change', function() {
                this.sandbox.emit('sulu.content.changed');
            }.bind(this));
            this.sandbox.dom.on(getId.call(this, 'input'), 'focusout', function() {
                this.$el.trigger('focusout');
            }.bind(this));
        },

        setValue = function(value) {
            if (!value) {
                value = this.sandbox.dom.data(this.$el, 'value');
                if (!value) {
                    value = '';
                }
            }
            var parts, part;
            if (getInputType.call(this, this.options.webspaceKey) === 'leaf') {
                parts = value.split('/');
                part = parts.pop();
            } else {
                parts = [];
                part = value.substring(1);
            }

            this.sandbox.dom.data(this.$el, 'part', part);

            this.sandbox.dom.val(getId.call(this, 'input'), part);
            this.sandbox.dom.html(getId.call(this, 'tree'), parts.join('/') + '/');
        },

        setDataValue = function() {
            var input = this.sandbox.dom.val(getId.call(this, 'input')),
                tree = this.sandbox.dom.html(getId.call(this, 'tree'));

            this.sandbox.dom.data(this.$el, 'part', input);
            this.sandbox.dom.data(this.$el, 'value', tree + input);
        },

        getInputType = function(webspaceKey) {
            if (!!this.options.inputType) {
                return this.options.inputType;
            }

            return Config.get('sulu_content.webspace_input_types')[webspaceKey];
        };

    return {
        initialize: function() {
            // extend default options
            this.options = this.sandbox.util.extend({}, defaults, this.options);

            render.call(this);
        }
    };
});
