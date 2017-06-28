/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Handles history-routes.
 *
 * @class resource-locator/history
 * @constructor
 */
define(['text!./skeleton.html', 'text!./overlay.html'], function(skeletonTemplate, overlayTemplate) {

    'use strict';

    return {

        defaults: {
            options: {
                resultKey: 'resourcelocators',
                url: null,
                pathKey: 'resourceLocator'
            },
            translations: {
                showHistory: 'public.show-history',
                title: 'public.url-history',
                noHistory: 'public.url-history.none'
            },
            templates: {
                skeleton: skeletonTemplate,
                overlay: overlayTemplate
            }
        },

        initialize: function() {
            this.$el.html(this.templates.skeleton({translations: this.translations}));
            this.bindDomEvents();
        },

        bindDomEvents: function() {
            this.$el.on('click', '.options-delete', this.deleteUrl.bind(this));
            this.$el.find('.pointer').click(this.handleClick.bind(this));
        },

        deleteUrl: function(e) {
            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (!confirmed) {
                    return;
                }

                var $currentElement = this.sandbox.dom.$(e.currentTarget),
                    $element = this.sandbox.dom.parent($currentElement),
                    id = this.sandbox.dom.data($element, 'id'),
                    link = this.items[id]._links.delete;

                if (typeof link === 'object') {
                    link = link.href;
                }

                this.sandbox.util.save(link, 'DELETE').then(function() {
                    delete this.items[id];

                    if (Object.keys(this.items).length === 0) {
                        this.sandbox.emit('husky.overlay.url-history.close');
                    }

                    this.sandbox.dom.remove($element);
                }.bind(this));
            }.bind(this), 'public.delete', 'sulu-content.resource-locator.delete');
        },

        handleClick: function() {
            this.load().then(this.startOverlay.bind(this));
        },

        load: function() {
            this.startLoader();
            return this.sandbox.util.load(this.options.url)
                .then(function(data) {
                    var items = data._embedded[this.options.resultKey];

                    this.items = {};
                    for (var i = 0, length = items.length; i < length; i++) {
                        this.items[items[i].id] = items[i];
                    }

                    return items;
                }.bind(this)).done(function() {
                    this.stopLoader.call(this);
                }.bind(this));
        },

        startOverlay: function(data) {
            var $element = $('<div/>');
            this.$el.append($element);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $element,
                        container: $element,
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: 'url-history',
                        skin: 'large',
                        slides: [{
                            title: this.translations.title,
                            buttons: [{type: 'ok', align: 'center'}],
                            data: this.templates.overlay({
                                translations: this.translations,
                                histories: data,
                                crop: this.sandbox.util.cropMiddle,
                                dateFormat: this.sandbox.date.format.bind(this.sandbox.date),
                                pathKey: this.options.pathKey
                            })
                        }]
                    }
                }
            ]);
        },

        startLoader: function() {
            var $element = $('<div/>');
            this.$el.find('.loader').html($element);

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

        stopLoader: function() {
            this.sandbox.stop(this.$el.find('.loader div'));
        }
    };
});
