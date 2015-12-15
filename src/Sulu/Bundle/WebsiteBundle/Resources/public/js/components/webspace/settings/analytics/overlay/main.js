/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'underscore', 'text!./form.html'], function($, _, form) {

    'use strict';

    var defaults = {
        options: {
            saveCallback: function() {
            }
        },
        templates: {
            form: form,
            skeleton: '<div id="webspace-analytics-overlay"/>',
            url: '/admin/api/webspaces/<%= webspaceKey %>/analytics<% if (!!id) { %>/<%= id %><% } %>'
        },
        translations: {
            overlayTitle: 'website.webspace.settings.edit.title',
            content: 'website.webspace.settings.content',

            google: 'website.webspace.settings.type.google',
            piwik: 'website.webspace.settings.type.piwik',
            custom: 'website.webspace.settings.type.custom',

            domain: 'website.webspace.settings.edit.domain',
            environment: 'website.webspace.settings.edit.environment'
        }
    };

    return {

        defaults: defaults,

        initialize: function() {
            this.$el.html(this.templates.skeleton);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: '#webspace-analytics-overlay',
                        openOnStart: true,
                        removeOnClose: true,
                        slides: [
                            {
                                title: this.translations.title,
                                data: this.templates.form({translations: this.translations}),
                                okCallback: function() {
                                    if (this.sandbox.form.validate('#analytics-form')) {
                                        this.options.saveCallback(this.options.id, this.getData());
                                    } else {
                                        return false;
                                    }
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]).then(function() {
                this.sandbox.form.create('#analytics-form').initialized.then(function() {
                    this.sandbox.form.setData('#analytics-form', this.data).then(this.initializeFormComponents.bind(this));
                }.bind(this));
            }.bind(this));
        },

        getData: function() {
            var data = this.sandbox.form.getData('#analytics-form'),
                domains = $('#analytics-domains').data('selected');
            data.domains = _.map(domains, this.findDomainByUrl.bind(this));

            return data;
        },

        findDomainByUrl: function(url) {
            return _.find(this.options.urls, function(item) {
                return item['url'] === url;
            });
        },

        initializeFormComponents: function() {
            var preselected = [];

            if (!!this.data.domains) {
                preselected = _.map(this.data.domains, function(item) {
                    return item.url;
                });
            }

            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: '#analytics-type',
                        multipleSelect: false,
                        valueName: 'title',
                        instanceName: 'analytics-overlay',
                        data: [
                            {id: 'google', title: this.translations.google},
                            {id: 'piwik', title: this.translations.piwik},
                            {id: 'custom', title: this.translations.custom}
                        ]
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: '#analytics-domains',
                        data: this.options.urls,
                        idKey: 'url',
                        preselected: preselected,
                        matchings: [
                            {attribute: 'url', content: this.translations.domain}
                        ]
                    }
                }
            ]);
        },

        loadComponentData: function() {
            var deferred = this.sandbox.data.deferred();

            if (!this.options.id) {
                deferred.resolve({});

                return deferred.promise();
            }

            this.sandbox.util.load(
                this.templates.url(this.options)
            ).then(function(data) {
                deferred.resolve(data);
            });

            return deferred.promise();
        }
    };
});
