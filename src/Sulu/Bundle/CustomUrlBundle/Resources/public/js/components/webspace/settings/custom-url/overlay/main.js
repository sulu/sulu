/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore', 'config', 'text!./form.html'], function(_, Config, form) {

    'use strict';

    var formSelector = '#custom-url-form',
        defaults = {
            options: {
                saveCallback: function() {
                }
            },
            templates: {
                form: form,
                urlList: '<div><div id="webspace-custom-urls-url-list-toolbar"/><div id="webspace-custom-urls-url-list"/></div>',
                skeleton: '<div id="webspace-custom-urls-overlay"/>',
                url: '/admin/api/webspaces/<%= webspaceKey %>/custom-urls<% if (!!id) { %>/<%= id %><% } %>?locale=<%= locale %>',
                routeUrl: '/admin/api/webspaces/<%= webspaceKey %>/custom-urls/<%= id %>/routes?ids=<%= ids.join(",") %>'
            },
            translations: {
                overlayTitle: 'custom-urls.webspace.settings.edit.title',
                chooseTargetCancel: 'custom-urls.choose-target.cancel',

                customUrlDefaultValue: 'custom-urls.custom-url.default-value',
                localeDefaultValue: 'public.please-choose',

                titleDetails: 'public.details',
                titleUrls: 'custom-urls.urls-title',
                history: 'custom-urls.history',

                descriptionCanonical: 'custom-urls.canonical.description',
                descriptionRedirect: 'custom-urls.redirect.description',
                descriptionNoIndex: 'custom-urls.no-index.description',
                descriptionNoFollow: 'custom-urls.no-follow.description'
            }
        },
        constants = {
            targetRootUrl: '/admin/api/nodes?webspace={webspace}&language={locale}&fields=title,order,published&webspace-nodes=single',
            targetSelectedUrl: '/admin/api/nodes/{datasource}?tree=true&webspace={webspace}&language={locale}&fields=title,order,published&webspace-nodes=single'
        };

    return {

        defaults: defaults,

        /**
         * Initializes component.
         */
        initialize: function() {
            this.bindCustomEvents();

            this.$el.html(this.templates.skeleton);

            this.startOverlay();
        },

        /**
         * Bind sandbox events.
         */
        bindCustomEvents: function() {
            this.sandbox.on('husky.datagrid.custom-urls-overlay.number.selections', function(selection) {
                var action = selection > 0 ? 'enable' : 'disable';
                this.sandbox.emit('husky.toolbar.custom-urls-overlay.item.' + action, 'delete', false);
            }.bind(this));

            this.sandbox.on('husky.toggler.custom-url-redirect.changed', function(state) {
                if (state) {
                    $('.redirect-hide').hide();
                } else {
                    $('.redirect-hide').show();
                }
            }.bind(this));
        },

        /**
         * Bind dom events.
         */
        bindDomEvents: function() {
            this.sandbox.dom.on('#analytics-all-domains', 'change', function() {
                $('#analytics-domains-container').toggle();
            });

            this.sandbox.dom.on(this.$el, 'click', function() {
                this.sandbox.emit('husky.overlay.custom-urls.slide-to', 1);

                return false;
            }.bind(this), '.custom-url-target, #custom-url-target-button');

            this.sandbox.dom.on(this.$el, 'click', function() {
                this.target = null;
                $('#custom-url-target-button-clear').hide();
                $('#custom-url-target-value').val('');

                return false;
            }.bind(this), '#custom-url-target-button-clear');
        },

        /**
         * Start overlay container for form.
         */
        startOverlay: function() {
            var security = Config.get('sulu_security.contexts')['sulu.webspace_settings.' + this.options.webspace.key + '.custom-urls'],
                tabs = [
                    {
                        title: this.translations.titleDetails,
                        data: this.templates.form({translations: this.translations})
                    }
                ],
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

            if (!!this.data.routes && _.size(this.data.routes) > 0 && security.edit) {
                tabs.push({
                    title: this.translations.titleUrls,
                    data: this.templates.urlList({translations: this.translations})
                });
            }

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
                                tabs: tabs,
                                okCallback: function() {
                                    if (this.sandbox.form.validate(formSelector)) {
                                        this.sandbox.emit('husky.overlay.custom-urls.show-loader');
                                        this.options.saveCallback(this.options.id, this.getData()).done(function() {
                                            this.sandbox.emit('husky.overlay.custom-urls.close');
                                            this.sandbox.emit('husky.overlay.custom-urls.hide-loader');
                                        }.bind(this)).fail(function(a) {
                                            this.sandbox.emit('husky.overlay.custom-urls.hide-loader');

                                            switch (a.responseJSON.code) {
                                                case 9001:
                                                    var $title = $('#custom-url-title');
                                                    $title.parent().addClass('husky-validate-error');
                                                    $title.focus();
                                                    break;
                                                case 1103:
                                                case 9003:
                                                    $('#custom-url-input').parent().addClass('husky-validate-error');
                                                    break;
                                            }
                                        }.bind(this));
                                    }

                                    return false;
                                }.bind(this),
                                buttons: buttons
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

        /**
         * Return data received from form.
         *
         * @returns {{}}
         */
        getData: function() {
            var data = this.sandbox.form.getData(formSelector),
                targetUuid = this.target || null;

            if (!targetUuid && !!this.data.targetDocument) {
                targetUuid = this.data.targetDocument.id;
            }

            data.targetDocument = !!targetUuid ? {uuid: targetUuid} : null;

            return data;
        },

        /**
         * Initializes sub-components.
         */
        initializeFormComponents: function() {
            this.customUrls = {};

            var routeData = _.chain(this.data.routes).map(function(routeDocument, route) {
                    if (!routeDocument.history) {
                        return null;
                    }

                    return {uuid: routeDocument.uuid, route: route, created: routeDocument.created};
                }).filter(function(route) {
                    return route !== null;
                }).value(),
                baseDomains = _.map(this.options.webspace.customUrls, function(item) {
                    this.customUrls[item.url] = item;

                    return item.url;
                }.bind(this)),
                targetLocales = this.filterConcreteLocales(
                    this.data.baseDomain,
                    (!!this.data.targetDocument ? (this.data.targetDocument.concreteLanguages || []) : [])
                );

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
                            data: baseDomains,
                            defaultLabel: this.translations.customUrlDefaultValue,
                            selectCallback: function(key, item) {
                                $('#custom-url-input-container').show();
                                this.sandbox.emit('sulu.webspace-settings.custom-url.set-base-domain', item);

                                var targetItem = $('#custom-url-target-value').data('item'),
                                    locales;

                                if (!!targetItem) {
                                    locales = this.filterConcreteLocales(this.getData().baseDomain, targetItem.concreteLanguages);
                                    this.sandbox.emit('husky.select.target-locale.update', locales, [locales[0]], false);
                                }
                            }.bind(this),
                            preselectCallback: function() {
                                // do nothing
                            }
                        }
                    },
                    {
                        name: 'select@husky',
                        options: {
                            el: '#custom-url-target-locale',
                            instanceName: 'target-locale',
                            isNative: true,
                            data: targetLocales,
                            defaultLabel: this.translations.localeDefaultValue,
                            preselectCallback: function() {
                                // do nothing
                            }
                        }
                    },
                    {
                        name: 'toggler@husky',
                        options: {
                            el: '#custom-url-redirect',
                            instanceName: 'redirect'
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
                            el: '#custom-url-no-index'
                        }
                    },
                    {
                        name: 'toggler@husky',
                        options: {
                            el: '#custom-url-no-follow'
                        }
                    },
                    {
                        name: 'content-datasource@sulucontent',
                        options: {
                            el: '#target-select',
                            selected: (!!this.data.targetDocument ? this.data.targetDocument.uuid : null),
                            webspace: this.options.webspace.key,
                            locale: this.sandbox.sulu.getDefaultContentLocale(),
                            instanceName: 'custom-urls',
                            rootUrl: constants.targetRootUrl,
                            selectedUrl: constants.targetSelectedUrl,
                            resultKey: 'nodes',
                            selectCallback: function(id, path, title, item) {
                                var $value = $('#custom-url-target-value');
                                $value.val(title);
                                $value.data('item', item);
                                $('#custom-url-target-button-clear').show();

                                var locales = this.filterConcreteLocales(this.getData().baseDomain, item.concreteLanguages);
                                this.sandbox.emit('husky.select.target-locale.update', locales, [locales[0]], false);

                                this.target = id;
                                this.sandbox.emit('husky.overlay.custom-urls.slide-to', 0);
                            }.bind(this)
                        }
                    },
                    {
                        name: 'list-toolbar@suluadmin',
                        options: {
                            el: '#webspace-custom-urls-url-list-toolbar',
                            hasSearch: false,
                            instanceName: 'custom-urls-overlay',
                            template: this.sandbox.sulu.buttons.get({
                                delete: {
                                    options: {disabled: true, callback: this.deleteUrl.bind(this)}
                                }
                            })
                        }
                    },
                    {
                        name: 'datagrid@husky',
                        options: {
                            el: '#webspace-custom-urls-url-list',
                            instanceName: 'custom-urls-overlay',
                            data: routeData,
                            idKey: 'uuid',
                            viewOptions: {
                                table: {
                                    selectItem: {
                                        type: 'checkbox',
                                        inFirstCell: false
                                    }
                                }
                            },
                            matchings: [
                                {
                                    content: this.translations.customUrl,
                                    attribute: 'route'
                                },
                                {
                                    content: this.translations.created,
                                    attribute: 'created',
                                    type: 'datetime'
                                }
                            ]
                        }
                    }
                ]
            );

            if (!!this.data.baseDomain) {
                $('#custom-url-input-container').show();
            }

            if (!!this.data.targetDocument) {
                $('#custom-url-target-value').val(this.data.targetDocument.title);
                $('#custom-url-target-button-clear').show();
            }

            if (!!this.data.redirect) {
                $('.redirect-hide').hide();
            }
        },

        filterConcreteLocales: function(baseDomain, concreteLocales) {
            if (!baseDomain || !concreteLocales) {
                return [];
            }

            var localizations = [];
            for (var localization in this.customUrls[baseDomain].locales) {
                var locale = this.customUrls[baseDomain].locales[localization].localization;
                if (concreteLocales.indexOf(locale) !== -1) {
                    localizations.push(locale);
                }
            }

            return localizations;
        },

        /**
         * Delete custom-url-route.
         */
        deleteUrl: function() {
            var ids = this.sandbox.util.deepCopy($('#webspace-custom-urls-url-list').data('selected'));

            // TODO how to handle overlays in overlays? error and question?

            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (!!confirmed) {
                    this.sandbox.util.save(
                        this.templates.routeUrl({
                            webspaceKey: this.options.webspace.key,
                            id: this.options.id,
                            ids: ids
                        }),
                        'DELETE'
                    ).done(function() {
                        for (var i = 0, length = ids.length; i < length; i++) {
                            var id = ids[i];
                            this.sandbox.emit('husky.datagrid.custom-urls-overlay.record.remove', id);
                        }
                    }.bind(this));
                }
            }.bind(this));
        },

        /**
         * Load data.
         *
         * @returns {{}}
         */
        loadComponentData: function() {
            var deferred = this.sandbox.data.deferred();
            if (!this.options.id) {
                deferred.resolve({
                    canonical: true
                });

                return deferred.promise();
            }

            this.sandbox.util.load(
                this.templates.url({
                    webspaceKey: this.options.webspace.key,
                    id: this.options.id,
                    locale: this.sandbox.sulu.getDefaultContentLocale()
                })
            ).then(function(data) {
                deferred.resolve(data);
            });

            return deferred;
        }
    };
});
