/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['config', 'app-config'], function(Config, AppConfig) {

    'use strict';

    var defaults = {
        options: {
            snippetTypesUrl: '/admin/api/snippet-types?defaults=true&webspace=<%= webspace %>',
            snippetTypeDefaultUrl: '/admin/api/snippet-types/<%= type %>/default?webspace=<%= webspace %>',
            snippetsUrl: '/admin/api/snippets?type=<%= type %>&language=<%= locale %>'
        },
        templates: {
            datagrid: '<div id="<%= ids.datagrid %>"></div><div id="<%= ids.overlayContainer %>"></div>',
            overlay: [
                '<div class="grid">',
                '   <div class="grid-row search-row">',
                '       <div class="grid-col-8"/>',
                '       <div class="grid-col-4" id="<%= ids.overlayDatagridSearch %>"/>',
                '   </div>',
                '   <div class="grid-row">',
                '       <div class="grid-col-12" id="<%= ids.overlayDatagrid %>"/>',
                '   </div>',
                '</div>'
            ].join('')
        },
        translations: {
            snippetType: 'snippets.defaults.type',
            defaultSnippet: 'snippets.defaults.default',
            overlayTitle: 'snippets.defaults.default'
        }
    };

    return {

        defaults: defaults,

        ids: {
            datagrid: 'snippet-types',
            overlayContainer: 'overlay',
            overlayDatagrid: 'snippets',
            overlayDatagridSearch: 'snippets-search'
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
            this.html(this.templates.datagrid({
                ids: this.ids
            }));

            this.startDatagrid();
        },

        startDatagrid: function() {
            var security = Config.get('sulu_security.contexts')['sulu.webspace_settings.' + this.data.webspace.key + '.default-snippets'],
                icons = [];

            if (!!security.edit) {
                icons = [
                    {
                        icon: 'plus-circle',
                        column: 'defaultTitle',
                        align: 'right',
                        cssClass: 'no-hover',
                        disableCallback: function(record) {
                            return !record.defaultUuid;
                        },
                        callback: this.openOverlay.bind(this)
                    },
                    {
                        icon: 'times',
                        column: 'defaultTitle',
                        align: 'right',
                        cssClass: 'no-hover simple',
                        disableCallback: function(record) {
                            return !!record.defaultUuid;
                        },
                        callback: this.removeDefault.bind(this)
                    }
                ];
            }

            this.sandbox.start([
                {
                    name: 'datagrid@husky',
                    options: {
                        el: this.$find('#' + this.ids.datagrid),
                        instanceName: 'snippets',
                        idKey: 'template',
                        viewOptions: {
                            table: {
                                selectItem: false,
                                icons: icons
                            }
                        },
                        matchings: [
                            {
                                attribute: 'title',
                                content: this.translations.snippetType
                            },
                            {
                                attribute: 'defaultTitle',
                                content: this.translations.defaultSnippet
                            }
                        ],
                        data: this.data.types
                    }
                }
            ]);
        },

        openOverlay: function(type) {
            var $container = $('<div/>');

            this.$find('#' + this.ids.overlayContainer).append($container);

            this.sandbox.start([{
                name: 'overlay@husky',
                options: {
                    el: $container,
                    instanceName: 'snippets',
                    openOnStart: true,
                    slides: [
                        {
                            title: this.translations.overlayTitle,
                            data: this.templates.overlay({ids: this.ids}),
                            buttons: [
                                {type: 'cancel', align: 'center'}
                            ]
                        }
                    ]
                }
            }]).then(function() {
                this.startSnippetDatagrid(type);
            }.bind(this));
        },

        startSnippetDatagrid: function(type) {
            this.sandbox.start(
                [
                    {
                        name: 'search@husky',
                        options: {
                            el: this.$find('#' + this.ids.overlayDatagridSearch),
                            appearance: 'white small',
                            instanceName: this.ids.overlayDatagridSearch
                        }
                    },
                    {
                        name: 'datagrid@husky',
                        options: {
                            el: this.$find('#' + this.ids.overlayDatagrid),
                            url: _.template(this.options.snippetsUrl, {
                                type: type,
                                locale: this.sandbox.sulu.getDefaultContentLocale()
                            }),
                            resultKey: 'snippets',
                            sortable: false,
                            searchInstanceName: this.ids.overlayDatagridSearch,
                            viewOptions: {
                                table: {
                                    selectItem: false,
                                    icons: [
                                        {
                                            icon: 'check-circle',
                                            column: 'title',
                                            callback: function(item) {
                                                this.saveDefault(type, item);
                                            }.bind(this)
                                        }
                                    ]
                                }
                            },
                            matchings: [
                                {
                                    content: 'Title',
                                    type: 'title',
                                    width: '100%',
                                    name: 'title',
                                    editable: true,
                                    sortable: true
                                }
                            ]
                        }
                    }
                ]
            );
        },

        saveDefault: function(type, id) {
            var url = _.template(this.options.snippetTypeDefaultUrl, {type: type, webspace: this.options.webspace});

            this.sandbox.util.save(url, 'PUT', {default: id}).then(function(data) {
                this.sandbox.emit('husky.overlay.snippets.close');
                this.sandbox.emit('husky.datagrid.snippets.records.change', data);

                this.sandbox.emit('sulu.labels.success.show', 'labels.success.content-save-desc', 'labels.success');
            }.bind(this));
        },

        removeDefault: function(type, id) {
            var url = _.template(this.options.snippetTypeDefaultUrl, {type: type, webspace: this.options.webspace});

            this.sandbox.util.save(url, 'DELETE', {default: id}).then(function(data) {
                this.sandbox.emit('husky.datagrid.snippets.records.change', data);

                this.sandbox.emit('sulu.labels.success.show', 'labels.success.content-save-desc', 'labels.success');
            }.bind(this));
        },

        loadComponentData: function() {
            var deferred = this.sandbox.data.deferred();

            this.sandbox.util.load(
                _.template(this.options.snippetTypesUrl, {webspace: this.options.webspace})
            ).then(function(data) {
                deferred.resolve({webspace: this.options.data(), types: data._embedded});
            }.bind(this));

            return deferred.promise();
        }
    };
});
