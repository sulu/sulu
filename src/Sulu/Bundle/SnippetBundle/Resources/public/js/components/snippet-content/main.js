/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Snippet content type
 *
 * Allows selection of multiple snippets
 */
define([], function() {

    'use strict';

    var defaults = {
            eventNamespace: 'sulu.snippets',
            resultKey: 'snippets',
            snippetType: null,
            dataAttribute: 'snippets',
            dataDefault: [],
            actionIcon: 'fa-file-text-o',
            hidePositionElement: true,
            hideConfigButton: true,
            locale: null,
            navigateEvent: 'sulu.router.navigate',
            translations: {
                noContentSelected: 'snippet-content.nosnippets-selected',
                addSnippets: 'snippet-content.add'
            }
        },

        templates = {
            data: function(options) {
                return [
                    '<div class="grid">',
                    '   <div class="grid-row search-row">',
                    '       <div class="grid-col-8"/>',
                    '       <div class="grid-col-4" id="', options.ids.search, '"/>',
                    '   </div>',
                    '   <div class="grid-row">',
                    '       <div class="grid-col-12" id="', options.ids.snippetList, '"/>',
                    '   </div>',
                    '</div>'
                ].join('');
            },

            contentItem: function(id, value) {
                return [
                    '<a href="#" class="link" data-id="', id, '">',
                    '   <span class="value">', value, '</span>',
                    '</a>'
                ].join('');
            }
        },

        /**
         * returns id for given type
         */
        getId = function(type) {
            return '#' + this.options.ids[type];
        },

        /**
         * custom event handling
         */
        bindCustomEvents = function() {
            this.sandbox.on(
                'husky.overlay.snippet-content.' + this.options.instanceName + '.add.initialized',
                initSnippetList.bind(this)
            );

            this.sandbox.on(
                'husky.overlay.snippet-content.' + this.options.instanceName + '.add.opened',
                updateSnippetList.bind(this)
            );
        },

        /**
         * initialize column navigation
         */
        initSnippetList = function() {
            var data = this.getData();

            this.sandbox.start([
                {
                    name: 'search@husky',
                    options: {
                        appearance: 'white small',
                        instanceName: this.options.instanceName + '-search',
                        el: getId.call(this, 'search')
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        url: this.options.url,
                        preselected: data,
                        resultKey: this.options.resultKey,
                        sortable: false,
                        columnOptionsInstanceName: '',
                        el: getId.call(this, 'snippetList'),
                        searchInstanceName: this.options.instanceName + '-search',
                        paginationOptions: {
                            dropdown: {
                                limit: 99999
                            }
                        },
                        viewOptions: {
                            table: {
                                selectItem: {
                                    type: 'checkbox'
                                },
                                removeRow: false,
                                editable: false,
                                validation: false,
                                addRowTop: false,
                                showHead: true,
                                contentContainer: '#content',
                                highlightSelected: true
                            }
                        },
                        matchings: [
                            {
                                content: 'Title',
                                width: "100%",
                                name: "title",
                                editable: true,
                                sortable: true,
                                type: 'title',
                                validation: {
                                    required: false
                                }
                            }
                        ]
                    }
                }
            ]);
        },

        /**
         * Updates the datagrid when opening the overlay again
         */
        updateSnippetList = function() {
            var selectedItems = this.getData() || [];

            this.sandbox.emit(
                'husky.datagrid.selected.update',
                selectedItems
            );
        },

        /**
         * handle dom events
         */
        bindDomEvents = function() {
            this.sandbox.dom.on(this.$el, 'click', function() {
                return false;
            }.bind(this), '.search-icon');

            this.sandbox.dom.on(this.$el, 'keydown', function(e) {
                if (event.keyCode === 13) {
                    e.preventDefault();
                    e.stopPropagation();

                    return false;
                }
            }.bind(this), '.search-input');

            this.sandbox.dom.on(this.$el, 'click', function(e) {
                var id = this.sandbox.dom.data(e.currentTarget, 'id');

                this.sandbox.emit(
                    this.options.navigateEvent,
                    'snippet/snippets/' + this.options.locale + '/edit:' + id
                );

                return false;
            }.bind(this), 'a.link');
        },

        /**
         * starts the overlay component
         */
        startAddOverlay = function() {
            var $element = this.sandbox.dom.createElement('<div/>');

            this.sandbox.dom.append(this.$el, $element);
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        triggerEl: this.$addButton,
                        cssClass: 'snippet-content-overlay',
                        el: $element,
                        removeOnClose: false,
                        container: this.$el,

                        instanceName: 'snippet-content.' + this.options.instanceName + '.add',
                        skin: 'wide',
                        slides: [
                            {
                                title: this.sandbox.translate(this.options.translations.addSnippets),
                                cssClass: 'snippet-content-overlay-add',
                                data: templates.data(this.options),
                                okCallback: getAddOverlayData.bind(this)
                            }
                        ]
                    }
                }
            ]);
        },

        getAddOverlayData = function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(selected) {
                this.setData(selected);
            }.bind(this));
        };

    return {
        type: 'itembox',

        initialize: function() {
            // extend default options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // init ids
            this.options.ids = {
                container: 'snippet-content-' + this.options.instanceName + '-container',
                addButton: 'snippet-content-' + this.options.instanceName + '-add',
                configButton: 'snippet-content-' + this.options.instanceName + '-config',
                content: 'snippet-content-' + this.options.instanceName + '-content',
                snippetList: 'snippet-content-' + this.options.instanceName + '-column-navigation',
                search: 'snippet-content-' + this.options.instanceName + '-search'
            };

            // sandbox event handling
            bindCustomEvents.call(this);

            this.render();

            // init overlays
            startAddOverlay.call(this);

            // handle dom events
            bindDomEvents.call(this);
        },

        getUrl: function(data) {
            var delimiter = (this.options.url.indexOf('?') === -1) ? '?' : '&';

            return [
                this.options.url, delimiter, this.options.idsParameter, '=', (data || []).join(',')
            ].join('');
        },

        getItemContent: function(item) {
            return templates.contentItem(item.id, item.title);
        },

        sortHandler: function(ids) {
            this.setData(ids, false);
        },

        removeHandler: function(id) {
            var data = this.getData();
            for (var i = -1, length = data.length; ++i < length;) {
                if (id === data[i]) {
                    data.splice(i, 1);
                    break;
                }
            }

            this.setData(data, false);
        }
    };
});
