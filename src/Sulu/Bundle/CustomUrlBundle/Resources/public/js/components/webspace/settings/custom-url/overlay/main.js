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
                urlList: '<div><div id="webspace-custom-urls-url-list-toolbar"/><div id="webspace-custom-urls-url-list"/></div>',
                skeleton: '<div id="webspace-custom-urls-overlay"/>',
                url: '/admin/api/webspaces/<%= webspaceKey %>/custom-urls<% if (!!id) { %>/<%= id %><% } %>',
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
            targetRootUrl: '/admin/api/nodes?webspace={webspace}&language={locale}&fields=title,order&webspace-nodes=single',
            targetSelectedUrl: '/admin/api/nodes/{datasource}?tree=true&webspace={webspace}&language={locale}&fields=title,order&webspace-nodes=single'
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
            var tabs = [
                {
                    title: this.translations.titleDetails,
                    data: this.templates.form({translations: this.translations})
                }
            ];

            if (!!this.data.routes && _.size(this.data.routes) > 0) {
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
                                        this.options.saveCallback(this.options.id, this.getData()).done(function() {
                                            this.sandbox.emit('husky.overlay.custom-urls.close');
                                        }.bind(this)).fail(function(a) {
                                            switch (a.responseJSON.code) {
                                                case 9001:
                                                    var $title = $('#custom-url-title');
                                                    $title.parent().addClass('husky-validate-error');
                                                    $title.focus();
                                                    break;
                                                case 9002:
                                                    var $customUrl = $('#custom-url-input');
                                                    $customUrl.parent().addClass('husky-validate-error');
                                                    break;
                                            }
                                        }.bind(this));
                                    }

                                    return false;
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

        /**
         * Return data received from form.
         *
         * @returns {{}}
         */
        getData: function() {
            var data = this.sandbox.form.getData(formSelector),
                targetUuid = this.target || null;

            if (!!targetUuid && !!this.data.target) {
                targetUuid = this.data.target.uuid;
            }

            data.target = !!targetUuid ? {uuid: targetUuid} : null;

            return data;
        },

        /**
         * Initializes sub-components.
         */
        initializeFormComponents: function() {
            var routeDisabledItems = [],
                routeData = _.map(this.data.routes, function(routeDocument, route) {
                    if (!routeDocument.history) {
                        routeDisabledItems.push(routeDocument.uuid);
                    }

                    return {uuid: routeDocument.uuid, route: route, history: routeDocument.history};
                });

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
                            }.bind(this),
                            emitPreSelect: false
                        }
                    },
                    {
                        name: 'select@husky',
                        options: {
                            el: '#custom-url-target-locale',
                            isNative: true,
                            data: _.map(this.options.webspace.localizations, function(item) {
                                return item.localization;
                            }),
                            defaultLabel: this.translations.localeDefaultValue,
                            emitPreSelect: false
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
                            selected: (!!this.data.target ? this.data.target.uuid : null),
                            webspace: this.options.webspace.key,
                            locale: this.data.locale || this.options.webspace.localizations[0].localization,
                            instanceName: 'custom-urls',
                            rootUrl: constants.targetRootUrl,
                            selectedUrl: constants.targetSelectedUrl,
                            resultKey: 'nodes',
                            selectCallback: function(id, path, title) {
                                $('#custom-url-target-value').val(title);
                                $('#custom-url-target-button-clear').show();

                                this.target = id;
                                this.sandbox.emit('husky.overlay.custom-urls.slide-to', 0);
                            }.bind(this),
                            emitPreSelect: false
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
                                        inFirstCell: false,
                                        header: false
                                    },
                                    disabledItems: routeDisabledItems
                                }
                            },
                            matchings: [
                                {
                                    content: this.translations.customUrl,
                                    attribute: 'route'
                                },
                                {
                                    content: this.translations.history,
                                    attribute: 'history',
                                    type: 'checkbox_readonly'
                                }
                            ]
                        }
                    }
                ]
            );

            if (!!this.data.baseDomain) {
                $('#custom-url-input-container').show();
            }

            if (!!this.data.target) {
                $('#custom-url-target-value').val(this.data.target.title);
                $('#custom-url-target-button-clear').show();
            }
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
                this.templates.url({webspaceKey: this.options.webspace.key, id: this.options.id})
            ).then(function(data) {
                deferred.resolve(data);
            });

            return deferred;
        }
    };
});
