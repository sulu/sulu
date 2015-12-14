/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore', 'text!./skeleton.html'], function(_, skeleton) {

    'use strict';

    var defaults = {
        templates: {
            skeleton: skeleton,
            url: '/admin/api/webspaces/<%= webspace.key %>/analytic-keys<% if (!!id) { %>/<%= id %><% } %><% if (!!domain) { %>?domain=<%= domain %><% } %>'
        },
        translations: {
            title: 'public.title',
            type: 'public.type',
            domains: 'website.webspace.settings.domains',
            allDomains: 'website.webspace.settings.all-domains'
        }
    };

    return {

        defaults: defaults,

        tabOptions: {
            title: function() {
                return this.data.title;
            }
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
            this.html(this.templates.skeleton());

            this.sandbox.start([
                {
                    name: 'list-toolbar@suluadmin',
                    options: {
                        el: '#webspace-analytics-list-toolbar',
                        instanceName: 'analytics',
                        hasSearch: false,
                        template: this.sandbox.sulu.buttons.get({
                            add: {},
                            delete: {}
                        })
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: '#webspace-analytics-list',
                        url: this.templates.url({webspace: this.data, id: null, domain: null}),
                        resultKey: 'analytic-keys',
                        instanceName: 'analytics',
                        actionCallback: this.editAnalytics.bind(this),
                        pagination: 'infinite-scroll',
                        viewOptions: {
                            table: {
                                actionIconColumn: 'title'
                            }
                        },
                        matchings: [
                            {
                                attribute: 'title',
                                content: this.translations.title
                            },
                            {
                                attribute: 'type',
                                content: this.translations.type
                            },
                            {
                                attribute: 'domains',
                                content: this.translations.domains,
                                type: function(content) {
                                    var urls = _.map(content, function(item){return item['url'];});

                                    return urls.join(', ');
                                }
                            },
                            {
                                attribute: 'allDomains',
                                content: this.translations.allDomains,
                                type: 'checkbox_readonly'
                            }
                        ]
                    }
                }
            ]);
        },

        editAnalytics: function(id) {
            this.sandbox.start([
                {
                    name: 'webspace/settings/analytics/overlay@suluwebsite',
                    options: {
                        el: '#webspace-analytics-form-overlay',
                        id: id,
                        webspaceKey: this.data.key,
                        saveCallback: this.save.bind(this)
                    }
                }
            ])
        },

        save: function(id, data) {
            this.sandbox.util.save(
                this.templates.url({webspace: this.data, id: id, domain: null}), !!id ? 'PUT' : 'POST', data
            ).then(function(response) {
                // TODO update table
            });
        },

        loadComponentData: function() {
            var deferred = this.sandbox.data.deferred();

            deferred.resolve(this.options.data());

            return deferred.promise();
        }
    };
});
