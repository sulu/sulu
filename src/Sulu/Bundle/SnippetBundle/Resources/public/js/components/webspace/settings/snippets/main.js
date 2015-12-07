/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!./form.html'], function(form) {

    'use strict';

    var defaults = {
        options: {
            snippetsUrl: '/admin/api/snippet/types?defaults=true&webspace=<%= webspace %>'
        },
        templates: {
            form: form
        },
        translations: {
            snippetType: 'snippets.defaults.type',
            defaultSnippet: 'snippets.defaults.default'
        }
    };

    return {

        defaults: defaults,

        ids: {
            datagrid: 'snippet-types'
        },

        tabOptions: function() {
            return {
                title: this.data.webspace.title
            };
        },

        layout: {
            content: {
                leftSpace: true,
                rightSpace: true
            }
        },

        initialize: function() {
            this.render();
        },

        render: function() {
            this.html(this.templates.form({
                translations: this.translations,
                ids: this.ids
            }));

            this.startDatagrid();
        },

        startDatagrid: function() {
            this.sandbox.start([
                {
                    name: 'datagrid@husky',
                    options: {
                        el: this.$find('#' + this.ids.datagrid),
                        idKey: 'template',
                        viewOptions: {
                            table: {
                                selectItem: false,
                                icons: [
                                    {
                                        icon: 'plus-circle',
                                        column: 'default',
                                        callback: function(item) {
                                            // TODO open overlay and select snippet
                                        }
                                    }
                                ]
                            }
                        },
                        matchings: [
                            {
                                attribute: 'title',
                                content: this.translations.snippetType
                            },
                            {
                                attribute: 'default',
                                content: this.translations.defaultSnippet
                            }
                        ],
                        data: this.data.types
                    }
                }
            ]);
        },

        loadComponentData: function() {
            var deferred = this.sandbox.data.deferred();

            this.sandbox.util.load(
                _.template(this.options.snippetsUrl, {webspace: this.options.webspace})
            ).then(function(data) {
                deferred.resolve({webspace: this.options.data(), types: data._embedded});
            }.bind(this));

            return deferred.promise();
        }
    };
});
