/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore'], function(_) {

    'use strict';

    return {

        defaults: {
            options: {
                value: null,
                operator: null,
                parameters: {}
            }
        },

        initialize: function() {
            this.$container = $('<div/>');
            this.instanceName = _.uniqueId('condition-tags-');
            this.value = this.options.value;

            this.$el.append(this.$container);
            this.$el.data('value', this.options.value);

            this.sandbox.start(
                [
                    {
                        name: 'auto-complete@husky',
                        options: {
                            el: this.$container,
                            instanceName: this.instanceName,
                            value: this.data,
                            prefetchUrl: this.options.parameters.prefetchUrl,
                            remoteUrl: this.options.parameters.remoteUrl,
                            getParameter: this.options.parameters.searchParameter || 'search',
                            resultKey: this.options.parameters.resultKey,
                            valueKey: this.options.parameters.valueKey
                        }
                    }
                ]
            );

            this.sandbox.on('husky.auto-complete.' + this.instanceName + '.select', function(item) {
                this.value = item.id;
                this.$el.data('value', this.value);
                this.$el.trigger('change');
            }.bind(this));
        },

        loadComponentData: function() {
            var def = $.Deferred();

            if (!this.options.value) {
                def.resolve();

                return def.promise();
            }

            this.sandbox.util.load(this.options.parameters.singleUrl.replace('{id}', this.options.value))
                .then(function(data) {
                    def.resolve(data);
                }.bind(this));

            return def.promise();
        }
    };
});
