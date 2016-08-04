/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'jquery',
    'underscore',
    'config',
    'text!./form.html',
    'text!./input.html',
    'text!./textarea.html',
    'text!./piwik.html'
], function($, _, Config, form, input, textarea, piwik) {

    'use strict';

    var formSelector = '#analytics-form',
        contentSelector = '.analytics-content-wrapper',
        defaults = {
        options: {
            saveCallback: function() {
            },
            types: [
                {
                    id: 'google',
                    title: 'website.webspace.settings.type.google',
                    input: 'input',
                    labels: ['website.webspace.settings.key'],
                    inputTemplate: input
                },
                {
                    id: 'google_tag_manager',
                    title: 'website.webspace.settings.type.google_tag_manager',
                    input: 'input',
                    labels: ['website.webspace.settings.key'],
                    inputTemplate: input
                },
                {
                    id: 'piwik',
                    title: 'website.webspace.settings.type.piwik',
                    input: 'input',
                    labels: ['website.webspace.settings.url', 'website.webspace.settings.site-id'],
                    inputTemplate: piwik
                },
                {
                    id: 'custom',
                    title: 'website.webspace.settings.type.custom',
                    input: 'textarea',
                    labels: ['website.webspace.settings.script'],
                    inputTemplate: textarea
                }
            ]
        },
        templates: {
            form: form,
            skeleton: '<div id="webspace-analytics-overlay"/>',
            url: '/admin/api/webspaces/<%= webspaceKey %>/analytics<% if (!!id) { %>/<%= id %><% } %>'
        },
        translations: {
            overlayTitle: 'website.webspace.settings.edit.title',
            script: 'website.webspace.settings.script',

            domain: 'website.webspace.settings.edit.domain',
            environment: 'website.webspace.settings.edit.environment',

            pleaseChoose: 'dropdown.please-choose'
        }
    };

    return {

        defaults: defaults,

        initialize: function() {
            this.$el.html(this.templates.skeleton);

            this.startOverlay();
        },

        bindDomEvents: function() {
            this.sandbox.dom.on('#analytics-all-domains', 'change', function() {
                $('#analytics-domains-container').toggle();
            });
        },

        startOverlay: function() {
            var security = Config.get('sulu_security.contexts')['sulu.webspace_settings.' + this.options.webspaceKey + '.analytics'],
                buttons = [
                    {
                        type: 'cancel',
                        inactive: false,
                        align: 'center'
                    }
                ];

            if ((!!this.options.id && security.edit)
                || (!this.options.id && security.add)
            ) {
                buttons = [
                    {
                        type: 'ok',
                        inactive: false,
                        align: 'right'
                    },
                    {
                        type: 'cancel',
                        inactive: false,
                        align: 'left'
                    }
                ];
            }

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
                                }.bind(this),
                                buttons: buttons
                            }
                        ]
                    }
                }
            ]).then(function() {
                this.sandbox.form.create(formSelector).initialized.then(function() {
                    this.sandbox.form.setData(formSelector, this.data).then(this.initializeFormComponents.bind(this));
                    this.bindDomEvents();
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
                        isNative: true,
                        multipleSelect: false,
                        valueName: 'title',
                        instanceName: 'analytics-overlay',
                        defaultLabel: this.translations.pleaseChoose,
                        data: this.options.types,
                        selectCallback: function(typeId) {
                            this.changeType(typeId);
                        }.bind(this),
                        preselectCallback: function() {
                            // do nothing
                        }
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

            if (!!this.data.allDomains) {
                $('#analytics-domains-container').hide();
            }
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

            if (data.content !== this.data.content) {
                data.content = null;
            }

            this.sandbox.form.removeField(formSelector, contentSelector);
            $(contentSelector).children().remove();

            $(contentSelector).html(
                _.template(type.inputTemplate, {
                        labels: _.map(type.labels, function(item) {
                            return this.sandbox.translate(item);
                        }.bind(this))
                    }
                )
            );

            var deferreds = [];
            $(contentSelector).find('*[data-mapper-property]').each(function(index, item) {
                deferreds.push(this.sandbox.form.addField(formSelector, $(item)).initialized);
            }.bind(this));

            $.when(deferreds).then(function() {
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
