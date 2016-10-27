/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Selection for page-provider.
 *
 * @class ckeditor/link/page
 * @constructor
 */
define(function() {

    'use strict';

    return {
        defaults: {
            options: {
                link: {},
                locale: null,
                webspace: null,
                setHref: function(id, title, published) {
                },
                selectCallback: function(id, title) {
                }
            },

            templates: {
                contentDatasource: '<div id="href-select" class="data-source-content"/>'
            }
        },

        initialize: function() {
            var $container = $(this.templates.contentDatasource());
            this.$el.append($container);

            this.sandbox.start(
                [
                    {
                        name: 'content-datasource@sulucontent',
                        options: {
                            el: $container,
                            selected: this.options.link.href,
                            webspace: this.options.webspace,
                            locale: this.options.locale,
                            selectedUrl: '/admin/api/nodes/{datasource}?tree=true&webspace={webspace}&language={locale}&fields=title,order,published&webspace-nodes=all',
                            rootUrl: '/admin/api/nodes?webspace={webspace}&language={locale}&fields=title,order,published&webspace-nodes=all',
                            resultKey: 'nodes',
                            instanceName: 'internal-link',
                            instanceNamePrefix: '',
                            showStatus: true,
                            selectCallback: function(id, path, title, item) {
                                this.options.selectCallback(id, title, !!item.published);
                            }.bind(this)
                        }
                    }
                ]
            ).then(function() {
                if (!this.options.link.href) {
                    this.options.setHref();

                    return;
                }

                this.sandbox.once('husky.column-navigation.internal-link.loaded', function() {
                    this.sandbox.emit('husky.column-navigation.internal-link.get-breadcrumb', function(breadcrumb) {
                        if (breadcrumb.length === 0) {
                            this.options.setHref();

                            return;
                        }

                        var item = breadcrumb[breadcrumb.length - 1];
                        this.options.setHref(item.id, item.title, !!item.published);
                    }.bind(this));
                }.bind(this));
            }.bind(this));
        }
    };
});
