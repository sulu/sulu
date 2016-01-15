/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore', 'text!./form.html'], function(_, form) {

    'use strict';

    const formSelector = '#custom-url-form';

    var defaults = {
        options: {
            saveCallback: function() {
            }
        },
        templates: {
            form: form,
            skeleton: '<div id="webspace-custom-urls-overlay"/>',
            url: '/admin/api/webspaces/<%= webspaceKey %>/custom-urls<% if (!!id) { %>/<%= id %><% } %>'
        },
        translations: {
            overlayTitle: 'custom-urls.webspace.settings.edit.title',
            customUrlDefaultValue: 'custom-urls.custom-url.default-value',
            localeDefaultValue: 'custom-urls.locale.default-value',
            chooseTarget: 'custom-urls.choose-target',
            chooseTargetCancel: 'custom-urls.choose-target.cancel'
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

            this.sandbox.dom.on(this.$el, 'click', function() {
                this.sandbox.emit('husky.overlay.custom-urls.slide-to', 1);
            }.bind(this), '#custom-url-target-button');
        },

        startOverlay: function() {
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: '#webspace-custom-urls-overlay',
                        instanceName: 'custom-urls',
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
                            },
                            {
                                title: this.translations.overlayTitle,
                                data: '<div id="target-select" class="data-source-content"/>',
                                cssClass: 'data-source-slide',
                                okInactive: true,
                                buttons: [
                                    {
                                        type: 'cancel',
                                        text: this.translations.chooseTargetCancel,
                                        align: 'center'
                                    }
                                ],
                                cancelCallback: function() {
                                    this.sandbox.emit('husky.overlay.custom-urls.slide-to', 0);

                                    return false;
                                }.bind(this)
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
            var data = this.sandbox.form.getData(formSelector);
            data.target = this.target;

            return data;
        },

        initializeFormComponents: function() {
            this.sandbox.start(
                [
                    {
                        name: 'toggler@husky',
                        options: {
                            el: '#custom-url-published'
                        }
                    },
                    {
                        name: 'webspace/settings/custom-url/input@sulucustomurl',
                        options: {
                            el: '#custom-url-input',
                            baseDomain: this.data.baseDomain
                        }
                    },
                    {
                        name: 'select@husky',
                        options: {
                            el: '#custom-url-base-domain',
                            isNative: true,
                            data: _.map(this.options.webspace.customUrls, function(item) {
                                return item.url;
                            }),
                            defaultLabel: this.translations.customUrlDefaultValue,
                            selectCallback: function(key, item) {
                                $('#custom-url-input-container').show();
                                this.sandbox.emit('sulu.webspace-settings.custom-url.set-base-domain', item);
                            }.bind(this)
                        }
                    },
                    {
                        name: 'select@husky',
                        options: {
                            el: '#custom-url-locale',
                            isNative: true,
                            data: _.map(this.options.webspace.localizations, function(item) {
                                return item.localization;
                            }),
                            defaultLabel: this.translations.localeDefaultValue
                        }
                    },
                    {
                        name: 'toggler@husky',
                        options: {
                            el: '#custom-url-multilingual'
                        }
                    },
                    {
                        name: 'toggler@husky',
                        options: {
                            el: '#custom-url-canonical'
                        }
                    },
                    {
                        name: 'toggler@husky',
                        options: {
                            el: '#custom-url-redirect'
                        }
                    },
                    {
                        name: 'content-datasource@sulucontent',
                        options: {

                            el: '#target-select',
                            selected: this.data.target,
                            webspace: this.options.webspace.key,
                            locale: 'de', // FIXME which locale? user? when user-locale not in webspace?
                            instanceName: 'custom-urls',
                            rootUrl: '/admin/api/nodes?webspace={webspace}&language={locale}&fields=title,order&webspace-nodes=single',
                            selectedUrl: '/admin/api/nodes/{datasource}?tree=true&webspace={webspace}&language={locale}&fields=title,order&webspace-nodes=single',
                            resultKey: 'nodes',
                            selectCallback: function(id, fullQualifiedTitle) {
                                $('#custom-url-target-value').html(this.sandbox.translate(fullQualifiedTitle));

                                this.target = id;
                                this.sandbox.emit('husky.overlay.custom-urls.slide-to', 0);
                            }.bind(this)
                        }
                    }
                ]
            );

            if (!!this.data.baseDomain) {
                $('#custom-url-input-container').show();
            }
        },

        loadComponentData: function() {
            var deferred = this.sandbox.data.deferred();
            if (!this.options.id) {
                deferred.resolve({});

                return deferred.promise();
            }

            this.sandbox.util.load(
                this.templates.url({webspaceKey: this.options.webspace.key, id: this.options.id})
            ).then(function(data) {
                deferred.resolve(data);
            });

            return deferred;
        }
    };
});
