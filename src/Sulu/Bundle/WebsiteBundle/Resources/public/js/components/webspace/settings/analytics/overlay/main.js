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
            saveCallback: function() {
            }
        },
        templates: {
            form: form,
            skeleton: '<div id="webspace-analytics-overlay"/>',
            url: '/admin/api/webspaces/<%= webspaceKey %>/analytic-keys<% if (!!id) { %>/<%= id %><% } %>'
        },
        translations: {
            overlayTitle: 'website.webspace.settings.edit.title',
            title: 'public.title',
            type: 'public.type',
            domains: 'website.webspace.settings.domains',
            content: 'website.webspace.settings.content',
            allDomains: 'website.webspace.settings.all-domains',
            useForAllDomains: 'website.webspace.settings.use-for-all-domains',

            google: 'public.google',
            piwik: 'public.piwik',
            custom: 'public.custom'
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
                                    this.options.saveCallback(this.options.id, this.sandbox.form.getData('#analytics-form'));
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

        initializeFormComponents: function() {
            // TODO init datagrid

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
