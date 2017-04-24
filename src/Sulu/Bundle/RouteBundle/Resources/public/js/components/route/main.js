/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Provides UI for route content-type.
 *
 * @class Route
 * @constructor
 */
define(['text!./skeleton.html'], function(skeletonTemplate) {
    return {

        defaults: {
            options: {
                instanceName: null,
                locale: null,
                inputType: 'leaf'
            },
            templates: {
                skeleton: skeletonTemplate
            },
            translations: {
                enablePageSelect: 'sulu_route.content-type.enable-page-select',
                showHistory: 'public.show-history'
            }
        },

        initialize: function() {
            this.bindCustomEvents();

            this.render();

            this.sandbox.start([
                {
                    name: 'toggler@husky',
                    options: {
                        el: this.$el.find('.toggler-container'),
                        instanceName: this.options.instanceName
                    }
                }
            ]);
        },

        render: function() {
            this.$el.html(this.templates.skeleton({translations: this.translations, togglerId: this.options.instanceName}));
            this.bindDomEvents();

            this.setInputValue(this.data);
        },

        setInputValue: function(data) {
            var parts = [],
                part = data.value.substring(1);

            if (this.options.inputType === 'leaf') {
                parts = data.value.split('/');
                part = parts.pop();
            }

            this.$el.find('.value').val(part);
            this.$el.find('.prefix').html(parts.join('/') + '/');
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.toggler.' + this.options.instanceName + '.changed', this.togglerChanged.bind(this));
        },

        bindDomEvents: function() {
            this.$el.find('.page-select').on('click', this.pageSelectClicked.bind(this))
        },

        togglerChanged: function(value) {
            if (value) {
                return this.pageSelectClicked().then(function() {
                    this.$el.find('.page-select').show();
                }.bind(this)).fail(function() {
                    this.sandbox.emit('husky.toggler.' + this.options.instanceName + '.change', false);
                }.bind(this));
            }

            this.$el.find('.page-select').hide();
        },

        pageSelectClicked: function() {
            var $container = $('<div/>'),
                deferred = $.Deferred();

            this.$el.append($container);

            this.sandbox.start(
                [
                    {
                        name: 'route/page-overlay@suluroute',
                        options: {
                            el: $container,
                            locale: this.options.locale,
                            selectCallback: function(item) {
                                this.setParentPage(item);
                                deferred.resolve();
                            }.bind(this),
                            cancelCallback: function() {
                                deferred.reject();
                            }.bind(this)
                        }
                    }
                ]
            );

            return deferred;
        },

        setParentPage: function(item) {
            var tree = item.url;

            this.$el.find('.prefix').text(tree.replace(/\/+$/g, '') + '/');
        },

        setData: function(data) {
            this.data = data;
            this.$el.data('value', data);
        },

        loadComponentData: function() {
            return {value: this.$el.data('value')};
        }
    };
});
