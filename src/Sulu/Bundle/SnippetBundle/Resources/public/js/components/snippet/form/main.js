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
    'sulusnippet/model/snippet',
    'app-config'
], function(BaseSnippet, Snippet, AppConfig) {

    'use strict';

    var STATE_TEST = 1,

        STATE_PUBLISHED = 2,

        component = {
            view: true,

            layout: {
                sidebar: false,

                navigation: {
                    collapsed: false
                },

                content: {
                    width: 'fixed',
                    shrinkable: false
                }
            },

            header: function() {
                return{
                    breadcrumb: this.breadcrumb,

                    tabs: {
                        url: '/admin/snippet/navigation/snippet'
                    },

                    toolbar: {
                        parentTemplate: 'default',

                        languageChanger: {
                            url: '/admin/api/languages',
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
                                        id: STATE_PUBLISHED,
                                        title: this.sandbox.translate('toolbar.state-publish'),
                                        icon: 'husky-publish',
                                        callback: function() {
                                            this.state = STATE_PUBLISHED;
                                            this.sandbox.emit('sulu.dropdown.state.item-clicked', STATE_PUBLISHED);
                                        }.bind(this)
                                    },
                                    {
                                        id: STATE_TEST,
                                        title: this.sandbox.translate('toolbar.state-test'),
                                        icon: 'husky-test',
                                        callback: function() {
                                            this.state = STATE_TEST;
                                            this.sandbox.emit('sulu.dropdown.state.item-clicked', STATE_TEST);
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
                this.config = AppConfig.getSection('sulu-snippet');
                this.defaultType = this.config.defaultType;
                this.template = this.defaultType;
                this.state = STATE_TEST;

                this.type = (!!this.options.id ? 'edit' : 'add');

                this.headerDef = this.sandbox.data.deferred();
                this.dataDef = this.sandbox.data.deferred();

                this.bindModelEvents();
                this.bindCustomEvents();

                this.loadData();
            },

            bindCustomEvents: function() {
                // back button
                this.sandbox.on('sulu.header.back', function() {
                    this.sandbox.emit('sulu.snippets.snippet.list');
                }.bind(this));

                // header initialize to set-title
                this.sandbox.on('husky.toolbar.header.initialized', function() {
                    this.headerDef.resolve();
                }.bind(this));

                // get content data
                this.sandbox.on('sulu.snippets.snippet.get-data', function(callback) {
                    this.dataDef.then(function() {
                        callback(this.data);
                    }.bind(this));
                }.bind(this));

                // setter for header bar buttons
                this.sandbox.on('sulu.snippets.snippet.set-header-bar', function(saved) {
                    this.setHeaderBar(saved);
                }.bind(this));

                // setter for state bar buttons
                this.sandbox.on('sulu.snippets.snippet.set-state', function(data) {
                    this.setState(data);
                }.bind(this));

                // content saved
                this.sandbox.on('sulu.snippets.snippet.saved', function(data) {
                    this.data = data;
                    this.setHeaderBar(true);
                    this.setTitle(this.data.title);

                    this.sandbox.emit('sulu.labels.success.show', 'labels.success.content-save-desc', 'labels.success');
                }, this);

                // content save-error
                this.sandbox.on('sulu.snippets.snippet.save-error', function() {
                    this.sandbox.emit('sulu.labels.error.show', 'labels.error.content-save-desc', 'labels.error');
                    this.setHeaderBar(false);
                }, this);

                // content delete
                this.sandbox.on('sulu.header.toolbar.delete', function() {
                    this.sandbox.emit('sulu.snippets.snippet.delete', this.data.id);
                }, this);
            },

            /**
             * Sets state to header
             * @param {Object} data
             */
            setState: function(data) {
                if (!!data.nodeState) {
                    this.state = data.nodeState;

                    if (this.state !== '' && this.state !== undefined && this.state !== null) {
                        this.sandbox.emit('sulu.header.toolbar.item.change', 'state', data.nodeState);
                    }
                }
            },

            /**
             * Sets header bar
             * @param {Boolean} saved
             */
            setHeaderBar: function(saved) {
                if (saved !== this.saved) {
                    var type = (!!this.data && !!this.data.id) ? 'edit' : 'add';
                    this.sandbox.emit('sulu.header.toolbar.state.change', type, saved, this.highlightSaveButton);
                    this.sandbox.emit('sulu.preview.state.change', saved);
                }
                this.saved = saved;
                if (this.saved) {
                    this.contentChanged = false;
                }
            },

            loadData: function() {
                if (!this.model) {
                    this.model = new Snippet({id: this.options.id});
                }

                if (this.options.id !== undefined) {
                    this.model.fullFetch(
                        this.options.language,
                        {
                            success: function(data) {
                                this.render(data.toJSON());
                                this.dataDef.resolve();
                            }.bind(this)
                        }
                    );
                } else {
                    this.render(this.model.toJSON());
                    this.dataDef.resolve();
                }
            },

            render: function(data) {
                this.data = data;

                this.headerDef.then(function() {
                    this.setTitle(data.title);
                }.bind(this));
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
