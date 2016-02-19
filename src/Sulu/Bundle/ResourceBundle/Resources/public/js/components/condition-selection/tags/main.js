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
                operator: null
            }
        },

        tagToId: {},

        initialize: function() {
            this.$container = $('<div/>');
            this.instanceName = _.uniqueId('condition-tags-');
            this.value = this.options.value;

            this.$el.append(this.$container);
            this.$el.data('value', this.options.value);

            this.sandbox.start(
                [
                    {
                        name: 'auto-complete-list@husky',
                        options: {
                            el: this.$container,
                            instanceName: this.instanceName,
                            items: this.data,
                            remoteUrl: '/admin/api/tags?flat=true&sortBy=name&searchFields=name',
                            getParameter: 'search',
                            itemsKey: 'tags',
                            autocomplete: true,
                            noNewTags: true
                        }
                    }
                ]
            );

            this.sandbox.on('husky.auto-complete.' + this.instanceName + '.remote-data', this.addTags.bind(this));
            this.sandbox.on('husky.auto-complete-list.' + this.instanceName + '.item-added', this.updateData.bind(this));
            this.sandbox.on('husky.auto-complete-list.' + this.instanceName + '.item-deleted', this.updateData.bind(this));
        },

        addTags: function(tags) {
            for (var i = 0, length = tags.length; i < length; i++) {
                this.addTag(tags[i]);
            }
        },

        addTag: function(tag) {
            this.tagToId[tag.name] = tag.id;
        },

        updateData: function() {
            this.sandbox.emit('husky.auto-complete-list.' + this.instanceName + '.get-tags', function(tags) {
                this.$el.data(
                    'value',
                    _.map(tags, function(tag) {
                            return this.tagToId[tag];
                        }.bind(this)
                    )
                ).trigger('change');
            }.bind(this));
        },

        loadComponentData: function() {
            var def = $.Deferred();

            if (this.options.value.length === 0) {
                def.resolve([]);

                return def.promise();
            }

            this.sandbox.util.load('/admin/api/tags?flat=true&ids=' + this.options.value.join(',')).then(function(data) {
                def.resolve(_.map(data._embedded.tags, function(tag) {
                    this.addTag(tag);

                    return tag.name;
                }.bind(this)));
            }.bind(this));

            return def.promise();
        }
    };
});
