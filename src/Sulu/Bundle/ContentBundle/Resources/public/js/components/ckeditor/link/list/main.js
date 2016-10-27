/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Selection for link-provider which can be listed in a list.
 *
 * @class ckeditor/link/list
 * @constructor
 */
define(['underscore', 'services/husky/expression'], function(_, Expression) {

    'use strict';

    return {

        defaults: {
            options: {
                link: {},
                locale: null,
                webspace: null,
                url: '',
                hrefUrl: '',
                idKey: 'id',
                titleKey: 'title',
                publishedExpression: [],
                resultKey: null,
                searchFields: [],
                matchings: [],
                setHref: function(id, title, published) {
                },
                selectCallback: function(id, title) {
                }
            },

            templates: {
                skeleton: [
                    '<div class="grid">',
                    '   <div class="grid-row search-row">',
                    '       <div class="grid-col-8"/>',
                    '       <div class="grid-col-4 link-provider-search"/>',
                    '   </div>',
                    '   <div class="grid-row">',
                    '       <div class="grid-col-12 link-provider-list" style="max-height: 500px; overflow: scroll;"/>',
                    '   </div>',
                    '</div>'
                ].join('')
            }
        },

        initialize: function() {
            this.resolveHref();
            this.render();
        },

        resolveHref: function() {
            if (!this.options.link.href) {
                this.options.setHref();

                return;
            }

            var url = _.template(this.options.hrefUrl, this.options);
            this.sandbox.util.load(url).then(function(data) {
                this.options.setHref(
                    data[this.options.idKey], data[this.options.titleKey], this.isPublished(data)
                );
            }.bind(this));
        },

        render: function() {
            var $container = $(this.templates.skeleton());
            this.$el.append($container);

            this.sandbox.start([
                {
                    name: 'search@husky',
                    options: {
                        el: '.link-provider-search',
                        appearance: 'white small',
                        instanceName: 'link-provider-search'
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: '.link-provider-list',
                        instanceName: 'link-provider',
                        url: this.options.url,
                        resultKey: this.options.resultKey,
                        sortable: false,
                        clickCallback: function(id, item) {
                            this.options.selectCallback(
                                item[this.options.idKey], item[this.options.titleKey], this.isPublished(item)
                            );
                        }.bind(this),
                        selectedCounter: false,
                        searchInstanceName: 'link-provider-search',
                        searchFields: this.options.searchFields,
                        paginationOptions: {
                            dropdown: {
                                limit: 20
                            }
                        },
                        viewOptions: {
                            table: {
                                selectItem: false
                            }
                        },
                        matchings: this.options.matchings
                    }
                }
            ]);
        },

        typeChange: function(item) {
            for (var type in config.types) {
                if (config.types.hasOwnProperty(type) && config.types[type].title === item.name && this.type !== type) {
                    this.type = type;
                    return this.sandbox.emit('husky.datagrid.article-link.url.update', {type: type});
                }
            }

            this.type = null;
            this.sandbox.emit('husky.datagrid.article-link.url.update', {type: null});
        },

        isPublished: function(context) {
            return Expression.evaluate(this.options.publishedExpression, context);
        }
    };
});
