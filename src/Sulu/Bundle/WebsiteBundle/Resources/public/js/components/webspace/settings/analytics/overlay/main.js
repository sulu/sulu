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

    const formSelector = '#analytics-form',
        contentSelector = '#analytics-content';

    var defaults = {
        options: {
            saveCallback: function() {
            },
            types: [
                {
                    id: 'google',
                    title: 'website.webspace.settings.type.google',
                    input: 'input',
                    label: 'website.webspace.settings.key'
                },
                {
                    id: 'piwik',
                    title: 'website.webspace.settings.type.piwik',
                    input: 'input',
                    label: 'website.webspace.settings.key'
                },
                {
                    id: 'custom',
                    title: 'website.webspace.settings.type.custom',
                    input: 'textarea',
                    label: 'website.webspace.settings.script'
                }
            ]
        },
        templates: {
            form: form,
            skeleton: '<div id="webspace-analytics-overlay"/>',
            url: '/admin/api/webspaces/<%= webspaceKey %>/analytics<% if (!!id) { %>/<%= id %><% } %>',
            input: [
                '<input id="analytics-content" type="text" name="content" class="form-element"',
                'data-mapper-property="content" data-validation-required="true" data-validation-min-length="3"/>'
            ].join(''),
            textarea: [
                '<textarea id="analytics-content" name="content" class="form-element" data-mapper-property="content"',
                'data-validation-required="true" data-validation-min-length="3"></textarea>'
            ].join('')
        },
        translations: {
            overlayTitle: 'website.webspace.settings.edit.title',
            script: 'website.webspace.settings.script',

            domain: 'website.webspace.settings.edit.domain',
            environment: 'website.webspace.settings.edit.environment'
        }
    };

    return {

        defaults: defaults,

        initialize: function() {
            this.$el.html(this.templates.skeleton);

            this.startOverlay();
        },

        startOverlay: function() {
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: '#webspace-analytics-overlay',
                        openOnStart: true,
                        removeOnClose: true,
                        slides: [
                            {
                                title: this.translations.overlayTitle,
                                data: this.templates.form({translations: this.translations}),
                                okCallback: function() {
                                    if (this.sandbox.form.validate(formSelector)) {
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
                this.sandbox.form.create(formSelector).initialized.then(function() {
                    this.sandbox.form.setData(formSelector, this.data).then(this.initializeFormComponents.bind(this));
                }.bind(this));
            }.bind(this));
        },

        getData: function() {
            var data = this.sandbox.form.getData(formSelector),
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
                        data: this.options.types,
                        selectCallback: this.changeType.bind(this)
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: '#analytics-domains',
                        instanceName: 'analytics-overlay',
                        data: this.options.urls,
                        idKey: 'url',
                        preselected: preselected,
                        matchings: [
                            {attribute: 'url', content: this.translations.domain}
                        ]
                    }
                }
            ]);

            this.changeType(this.data.type, this.data);
        },

        changeType: function(typeId, data) {
            var type = _.find(this.options.types, function(type) {
                return type.id === typeId;
            });

            if (!type) {
                return;
            }

            if (!data) {
                data = this.getData();
            }

            this.sandbox.form.removeField(formSelector, contentSelector);
            $(contentSelector).remove();

            $('.analytics-content-wrapper').append($(this.templates[type.input]()));
            $('#analytics-content-label').text(this.sandbox.translate(type.label));
            this.sandbox.form.addField(formSelector, contentSelector).initialized.then(function() {
                this.sandbox.form.setData(formSelector, data);
            }.bind(this));
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
