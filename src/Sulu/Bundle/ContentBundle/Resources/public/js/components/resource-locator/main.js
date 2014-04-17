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
 * @class AutoComplete
 * @constructor
 */
define([], function() {

    'use strict';

    var defaults = {
            instanceName: null,
            url: null
        },

        skeleton = function(options) {
            return [
                '<div class="resource-locator ">',
                '<span class="icon-cogwheel pointer" id="', options.ids.edit, '"></span>',
                (!!options.url) ? '   <span id="' + options.ids.url + '" class="url">' + options.url + '</span>' : '',
                '   <span id="' + options.ids.tree + '" class="tree"></span>',
                '   <input type="text" readonly="readonly" id="' + options.ids.input + '" class="form-element preview-update trigger-save-button"/>',
                '   <span class="icon-chevron-right pointer" id="', options.ids.toggle, '"></span>',
                '   <div id="', options.ids.history, '" class="hidden">',
                '   </div>',
                '</div>'
            ].join('');
        },

        getId = function(type) {
            return '#' + this.options.ids[type];
        },

        render = function() {
            this.options.ids = {
                url: 'resource-locator-' + this.options.instanceName + '-url',
                tree: 'resource-locator-' + this.options.instanceName + '-tree',
                input: 'resource-locator-' + this.options.instanceName + '-input',
                edit: 'resource-locator-' + this.options.instanceName + '-edit',
                toggle: 'resource-locator-' + this.options.instanceName + '-toggle',
                history: 'resource-locator-' + this.options.instanceName + '-history'
            };
            this.sandbox.dom.html(this.$el, skeleton(this.options));

            setValue.call(this);

            bindDomEvents.call(this);
        },

        bindDomEvents = function() {
            this.sandbox.dom.on(this.$el, 'data-changed', setValue.bind(this));
            this.sandbox.dom.on(getId.call(this, 'edit'), 'click', editClicked.bind(this));
            this.sandbox.dom.on(getId.call(this, 'toggle'), 'click', toggleClicked.bind(this));
            this.sandbox.dom.on(getId.call(this, 'input'), 'change', setDataValue.bind(this));
        },

        setValue = function(value) {
            if (!value) {
                value = this.sandbox.dom.data(this.$el, 'value');
            }
            var parts = value.split('/');
            this.sandbox.dom.val(getId.call(this, 'input'), parts.pop());
            this.sandbox.dom.html(getId.call(this, 'tree'), parts.join('/') + '/');
        },

        editClicked = function() {
            this.sandbox.dom.removeAttr(getId.call(this, 'input'), 'readonly');
        },

        toggleClicked = function() {
            var toggleId = getId.call(this, 'toggle');
            if (this.historyClosed) {
                this.sandbox.dom.removeClass(toggleId, 'icon-chevron-right');
                this.sandbox.dom.removeClass(toggleId, 'pointer');
                this.sandbox.dom.addClass(toggleId, 'icon-chevron-down');
                this.sandbox.dom.addClass(toggleId, 'pointer');
                this.historyClosed = false;

                loadHistory.call(this);
            } else {
                this.sandbox.dom.removeClass(toggleId, 'icon-chevron-down');
                this.sandbox.dom.removeClass(toggleId, 'pointer');
                this.sandbox.dom.addClass(toggleId, 'icon-chevron-right');
                this.sandbox.dom.addClass(toggleId, 'pointer');
                this.sandbox.dom.addClass(getId.call(this, 'history'), 'hidden');
                this.historyClosed = true;
            }
        },

        setDataValue = function() {
            var input = this.sandbox.dom.val(getId.call(this, 'input')),
                tree = this.sandbox.dom.html(getId.call(this, 'tree'));
            this.sandbox.dom.data(this.$el, 'value', tree + input);
        },

        loadHistory = function() {

            this.sandbox.util.load(this.options.historyApi).then(function(data) {
                var items = data._embedded,
                    html = ['<ul>'];

                this.sandbox.util.foreach(items, function(item) {
                    html.push('<li>' + item.resourceLocator + ' (' + item.created + ')</li>');
                }.bind(this));
                html.push('</ul>');

                this.sandbox.dom.html(getId.call(this, 'history'), html.join(''));
                this.sandbox.dom.removeClass(getId.call(this, 'history'), 'hidden');
            }.bind(this));
        };

    return {
        historyClosed: true,

        initialize: function() {
            // extend default options
            this.options = this.sandbox.util.extend({}, defaults, this.options);

            render.call(this);
        }
    };
});
