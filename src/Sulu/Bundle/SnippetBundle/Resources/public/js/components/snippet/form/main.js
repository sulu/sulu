/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulusnippet/components/snippet/main',
    'sulusnippet/model/snippet'
], function(BaseSnippet, Snippet) {

    'use strict';

    var component = {
        view: true,

        layout: {
            sidebar: false,

            navigation: {
                collapsed: true
            },

            content: {
                width: 'fixed',
                shrinkable: false
            }
        },

        header: function() {
            return{
                breadcrumb: this.breadcrumb,

                toolbar: {
                    parentTemplate: 'default',

                    languageChanger: {
                        // FIXME no webspace
                        url: '/admin/content/languages/sulu_io',
                        preSelected: this.options.language
                    },

                    template: [
                        {
                            id: 'state',
                            group: 'left',
                            position: 100,
                            type: 'select',
                            itemsOption: {
                                markable: true
                            },
                            items: [
                                {
                                    id: 2,
                                    title: this.sandbox.translate('toolbar.state-publish'),
                                    icon: 'husky-publish',
                                    callback: function() {
                                        this.sandbox.emit('sulu.dropdown.state.item-clicked', 2);
                                    }.bind(this)
                                },
                                {
                                    id: 1,
                                    title: this.sandbox.translate('toolbar.state-test'),
                                    icon: 'husky-test',
                                    callback: function() {
                                        this.sandbox.emit('sulu.dropdown.state.item-clicked', 1);
                                    }.bind(this)
                                }
                            ]
                        },
                        {
                            id: 'template',
                            icon: 'pencil',
                            iconSize: 'large',
                            group: 'left',
                            position: 10,
                            type: 'select',
                            title: '',
                            hidden: false,
                            itemsOption: {
                                url: '/admin/api/snippet/types',
                                titleAttribute: 'title',
                                idAttribute: 'template',
                                translate: false,
                                markable: true,
                                callback: function(item) {
                                    this.template = item.template;
                                    this.sandbox.emit('sulu.dropdown.template.item-clicked', item);
                                }.bind(this)
                            }
                        }
                    ]
                }
            };
        },

        initialize: function() {
            this.type = (!!this.options.id ? 'edit' : 'add');

            this.bindModelEvents();
            this.bindCustomEvents();

            this.loadData();
        },

        bindCustomEvents: function() {
            // back button
            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.snippets.snippet.list');
            }.bind(this));
        },

        loadData: function() {
            if (!this.content) {
                this.content = new Snippet({id: this.options.id});
            }

            if (this.options.id !== undefined) {
                this.content.fullFetch(
                    this.options.language,
                    {
                        success: function(data) {
                            this.render(data.toJSON());
                        }.bind(this)
                    }
                );
            } else {
                this.render(this.content.toJSON());
            }
        },

        render: function(data) {
            this.data = data;

            this.setTitle(data.title);
        },

        /**
         * Sets the title of the page and if in edit mode calls a method to set the breadcrumb
         * @param {Object} title
         */
        setTitle: function(title) {
            var breadcrumb = [
                {title: 'navigation.snippets'},
                {title: 'snippets.snippet.title'}
            ];

            if (!!this.options.id && title !== '') {
                this.sandbox.emit('sulu.header.set-title', this.sandbox.util.cropMiddle(title, 40));

                // breadcrumb
                breadcrumb.push({title: title});
                this.sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
            } else {
                this.sandbox.emit('sulu.header.set-title', this.sandbox.translate('snippets.snippet.title'));
                this.sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
            }
        }
    };

    // inheritance
    component.__proto__ = BaseSnippet;

    return component;
});
