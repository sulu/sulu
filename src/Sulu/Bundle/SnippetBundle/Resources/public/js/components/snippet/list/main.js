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
    'sulucontent/components/copy-locale-overlay/main',
    'sulucontent/components/open-ghost-overlay/main'
], function(BaseSnippet, CopyLocale, OpenGhost) {

    'use strict';

    var template = [
            '<div id="list-toolbar-container" class="list-toolbar-container"></div>',
            '<div id="snippet-list" class="datagrid-container"></div>',
            '<div id="dialog"></div>'
        ].join(''),

        SnippetList = function() {
            BaseSnippet.call(this);

            return this;
        };

    // inheritance
    SnippetList.prototype = Object.create(BaseSnippet.prototype);
    SnippetList.prototype.constructor = BaseSnippet;

    SnippetList.prototype.view = true;
    SnippetList.prototype.stickyToolbar = true;
    SnippetList.prototype.layout = {
        content: {
            width: 'max'
        },
        sidebar: false
    };

    SnippetList.prototype.header = function() {
        return {
            noBack: true,

            title: 'snippets.snippet.title',
            underline: false,

            toolbar: {
                buttons: {
                    add: {},
                    deleteSelected: {},
                    export: {
                        options: {
                            urlParameter: {
                                flat: true
                            },
                            url: '/admin/api/snippets.csv'
                        }
                    }
                },
                languageChanger: {
                    preSelected: this.options.language
                }
            }
        };
    };

    SnippetList.prototype.initialize = function() {
        this.bindModelEvents();
        this.bindCustomEvents();

        this.render();
    };

    SnippetList.prototype.bindCustomEvents = function() {
        // delete clicked
        this.sandbox.on('sulu.toolbar.delete', function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.emit('sulu.snippets.snippets.delete', ids);
            }.bind(this));
        }, this);

        // add clicked
        this.sandbox.on('sulu.toolbar.add', function() {
            this.sandbox.emit('sulu.snippets.snippet.new');
        }, this);

        // checkbox clicked
        this.sandbox.on('husky.datagrid.number.selections', function(number) {
            var postfix = number > 0 ? 'enable' : 'disable';
            this.sandbox.emit('sulu.header.toolbar.item.' + postfix, 'deleteSelected', false);
        }.bind(this));
    };

    SnippetList.prototype.render = function() {
        this.sandbox.dom.html(this.$el, template);

        // init list-toolbar and datagrid
        this.sandbox.sulu.initListToolbarAndList.call(this, 'snippets', '/admin/api/snippet/fields',
            {
                el: this.$find('#list-toolbar-container'),
                instanceName: 'snippets'
            },
            {
                el: this.sandbox.dom.find('#snippet-list', this.$el),
                url: '/admin/api/snippets?language=' + this.options.language,
                searchInstanceName: 'snippets',
                storageName: 'snippets',
                searchFields: ['title'], // TODO ???
                resultKey: 'snippets',
                actionCallback: function(id, item) {
                    if (!item.type || item.type.name !== 'ghost') {
                        this.sandbox.emit('sulu.snippets.snippet.load', id);
                    } else {
                        OpenGhost.openGhost.call(this, item, this.translations.openGhostOverlay).then(
                            function(copy, src) {
                                if (!!copy) {
                                    CopyLocale.copyLocale.call(
                                        this,
                                        item.id,
                                        src,
                                        [this.options.language],
                                        function() {
                                            this.sandbox.emit('sulu.snippets.snippet.load', id);
                                        }.bind(this)
                                    );
                                } else {
                                    this.sandbox.emit('sulu.snippets.snippet.load', id);
                                }
                            }.bind(this)
                        );
                    }
                }.bind(this),
                viewOptions: {
                    table: {
                        badges: [
                            {
                                column: 'title',
                                callback: function(item, badge) {
                                    if (!!item.type &&
                                        item.type.name === 'ghost' &&
                                        item.type.value !== this.options.language
                                    ) {
                                        badge.title = item.type.value;

                                        return badge;
                                    }

                                    return false;
                                }.bind(this)
                            }
                        ]
                    }
                }
            }
        );
    };

    return new SnippetList();
});
