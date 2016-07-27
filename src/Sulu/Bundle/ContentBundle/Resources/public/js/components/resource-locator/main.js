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
            url: null,
            historyApi: null,
            deleteApi: null
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
                return [
                    '<div class="resource-locator">',
                    '   <span id="' + options.ids.url + '" class="grey-font">', (!!options.url) ? options.url : '', '</span>',
                    '   <span id="' + options.ids.tree + '" class="grey-font"></span>',
                    '   <input type="text" id="' + options.ids.input + '" class="form-element"/>',
                    '   <span class="show pointer small-font" id="', options.ids.toggle, '">',
                    '       <span class="fa-history icon"></span>',
                    '       <span>', options.showHistoryText, '</span>',
                    '   </span>',
                    '   <div class="loader" id="', options.ids.loader, '"></div>',
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
                toggle: 'resource-locator-' + this.options.instanceName + '-toggle',
                loader: 'resource-locator-' + this.options.instanceName + '-loader'
            };
            this.options.showHistoryText = this.sandbox.translate('public.show-history');
            this.sandbox.dom.html(this.$el, skeleton(this.options));

            setValue.call(this);

            bindDomEvents.call(this);
        },

        startOptionsLoader = function($element) {
            $element = $element.parents('.overlay-content');

            this.sandbox.dom.append($element, this.sandbox.dom.createElement('<div class="loader"/>'));
            this.sandbox.dom.css($element.find('.resource-locator-history'), 'display', 'none');

            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: this.sandbox.dom.find('.loader', $element),
                        size: '16px',
                        color: '#666666'
                    }
                }
            ]);
        },

        stopOptionsLoader = function($element) {
            $element = $element.parents('.overlay-content');

            this.sandbox.dom.css($element.find('.resource-locator-history'), 'display', 'block');
            this.sandbox.stop(this.sandbox.dom.find('.loader', $element));
        },

        startLoader = function() {
            var $element = this.sandbox.dom.createElement('<div/>');
            this.sandbox.dom.html(getId.call(this, 'loader'), $element);

            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: $element,
                        size: '16px',
                        color: '#666666'
                    }
                }
            ]);
        },

        stopLoader = function() {
            this.sandbox.stop(getId.call(this, 'loader') + ' > div');
        },

        bindDomEvents = function() {
            // set value
            this.sandbox.dom.on(this.$el, 'data-changed', function (e, value) {
                setValue.call(this, value);
            }.bind(this));

            // load history
            this.sandbox.dom.on(getId.call(this, 'toggle'), 'click', loadHistory.bind(this));

            // value change
            this.sandbox.dom.on(getId.call(this, 'input'), 'change', setDataValue.bind(this));
            this.sandbox.dom.on(getId.call(this, 'input'), 'change', function() {
                this.sandbox.emit('sulu.content.changed');
            }.bind(this));
            this.sandbox.dom.on(getId.call(this, 'input'), 'focusout', function() {
                this.$el.trigger('focusout');
            }.bind(this));

            // delete
            this.sandbox.dom.on(this.$el, 'click', deleteUrl.bind(this), '.options-delete');
        },

        deleteUrl = function(e) {
            var $currentElement = this.sandbox.dom.$(e.currentTarget),
                $element = this.sandbox.dom.parent($currentElement),
                id = this.sandbox.dom.data($element, 'id');

            startOptionsLoader.call(this, $element);

            this.sandbox.util.save(this.items[id]._links.delete, 'DELETE')
                .then(function() {
                    stopOptionsLoader.call(this, $element);
                    this.sandbox.dom.remove($element);
                }.bind(this))
                .fail(function() {
                    // FIXME message
                    stopOptionsLoader.call(this, $element);
                });
        },

        setValue = function(value) {
            if (!value) {
                value = this.sandbox.dom.data(this.$el, 'value');
                if (!value) {
                    value = '';
                }
            }
            var parts = value.split('/'),
                part = parts.pop();

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

        /**
         * Creates the content for the history overlay
         */
        renderHistories = function(histories) {
            this.items = [];

            if (histories.length > 0) {
                var html = ['<ul class="resource-locator-history">'];

                this.sandbox.util.foreach(histories, function(history) {
                    this.items[history.id] = history;
                    html.push(
                            '<li data-id="' + history.id + '" data-path="' + history.resourceLocator + '">' +
                            '   <span class="url">' + this.sandbox.util.cropMiddle(history.resourceLocator, 30) + '</span>' +
                            '   <span class="date">' + this.sandbox.date.format(history.created) + '</span>' +
                            '   <span class="options-delete"><i class="fa fa-trash-o pointer"></i></span>' +
                            '</li>'
                    );
                }.bind(this));
                html.push('</ul>');
                return html.join('');
            } else {
                return '<p>' + this.sandbox.translate('public.url-history.none') + '</p>';
            }
        },

        /**
         * Starts the history-overlay
         */
        startOverlay = function(content) {
            var $element = this.sandbox.dom.createElement('<div/>');
            this.sandbox.dom.append(this.$el, $element);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $element,
                        container: $element,
                        title: this.sandbox.translate('public.url-history'),
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'url-history',
                        skin: 'wide',
                        data: content
                    }
                }
            ]);
        },

        loadHistory = function() {
            startLoader.call(this);
            this.sandbox.util.load(this.options.historyApi).then(function(data) {
                stopLoader.call(this);
                var content = renderHistories.call(this, data._embedded.resourcelocators);
                startOverlay.call(this, content);
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
