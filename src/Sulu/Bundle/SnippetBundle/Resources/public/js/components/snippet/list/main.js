/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulusnippet/components/snippet/main'
], function(BaseSnippet) {

    'use strict';

    var template = [
            '<div id="list-toolbar-container"></div>',
            '<div id="snippet-list"></div>',
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
    SnippetList.prototype.layout = {
        content: {
            width: 'max',
            leftSpace: false,
            rightSpace: false
        },
        sidebar: false
    };
    SnippetList.prototype.header = {
        title: 'snippets.snippet.title',
        noBack: true,

        breadcrumb: [
            {title: 'navigation.snippets'}
        ]
    };

    SnippetList.prototype.initialize = function() {
        this.bindModelEvents();
        this.bindCustomEvents();

        this.render();
    };

    SnippetList.prototype.bindCustomEvents = function() {
        // delete clicked
        this.sandbox.on('sulu.list-toolbar.delete', function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.emit('sulu.snippets.snippets.delete', ids);
            }.bind(this));
        }, this);

        // add clicked
        this.sandbox.on('sulu.list-toolbar.add', function() {
            this.sandbox.emit('sulu.snippets.snippet.new');
        }, this);

    };

    SnippetList.prototype.render = function() {
        this.sandbox.dom.html(this.$el, template);

        // init list-toolbar and datagrid
        this.sandbox.sulu.initListToolbarAndList.call(this, 'snippets', '/admin/api/snippet/fields',
            {
                el: this.$find('#list-toolbar-container'),
                instanceName: 'snippets',
                inHeader: true
            },
            {
                el: this.sandbox.dom.find('#snippet-list', this.$el),
                url: '/admin/api/snippets?language=' + this.options.language,
                searchInstanceName: 'snippets',
                searchFields: ['title'], // TODO ???
                resultKey: 'snippets',
                viewOptions: {
                    table: {
                        icons: [
                            {
                                icon: 'pencil',
                                column: 'title',
                                align: 'left',
                                callback: function(id) {
                                    this.sandbox.emit('sulu.snippets.snippet.load', id);
                                }.bind(this)
                            }
                        ],
                        highlightSelected: true,
                        fullWidth: true
                    }
                }
            }
        );
    };

    return new SnippetList();
});
