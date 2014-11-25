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
            instanceName: null,
            urlGet: null,
            idsParameter: 'ids',
            preselected: [],
            idKey: 'id',
            titleKey: 'title',
            resultKey: 'snippets',
            urlAll: null,
            language: null,
            snippetType: null,
            webspace: null,
            uniqueSnippets: true,
            translations: {
                noSnippetsSelected: 'snippet-content.nosnippets-selected',
                addSnippets: 'snippet-content.add'
            }
        },

        /**
         * namespace for events
         * @type {string}
         */
        eventNamespace = 'sulu.snippets.',

        /**
         * raised when the overlay data has been changed
         * @event sulu.internal-links.data-changed
         */
        DATA_CHANGED = function() {
            return createEventName.call(this, 'data-changed');
        },

        /**
         * returns normalized event names
         */
        createEventName = function(postFix) {
            return eventNamespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        },

        data = {
            ids: []
        },

        templates = {
            skeleton: function(options) {
                return [
                    '<div class="white-box form-element" id="', options.ids.container, '">',
                    '   <div class="header">',
                    '       <span class="fa-plus-circle icon left action" id="', options.ids.addButton, '"></span>',
                    '       <span class="fa-cog icon right border " id="', options.ids.configButton, '" style="display: none;"></span>',
                    '   </div>',
                    '   <div class="content" id="', options.ids.content, '"></div>',
                    '</div>'
                ].join('');
            },

            noContent: function(noContentString) {
                return [
                    '<div class="no-content">',
                    '   <span class="fa-coffee icon"></span>',
                    '   <div class="text">', noContentString, '</div>',
                    '</div>'
                ].join('');
            },

            data: function(options) {
                return [
                    '<div class="grid">',
                    '   <div class="grid-row">',
                    '       <div class="grid-col-8"/>',
                    '       <div class="grid-col-4" id="', options.ids.search, '"/>',
                    '   </div>',
                    '   <div class="grid-row">',
                    '       <div class="grid-col-12" id="', options.ids.snippetList, '"/>',
                    '   </div>',
                    '</div>'
                ].join('');
            },

            contentItem: function(id, num, value) {
                return [
                    '<li data-id="', id, '">',
                    '   <span class="num">', num, '</span>',
                    '   <span class="value">', value, '</span>',
                    '   <span class="fa-times remove"></span>',
                    '</li>'
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
         * render component
         */
        render = function() {
            // init ids
            this.options.ids = {
                container: 'snippet-content-' + this.options.instanceName + '-container',
                addButton: 'snippet-content-' + this.options.instanceName + '-add',
                configButton: 'snippet-content-' + this.options.instanceName + '-config',
                content: 'snippet-content-' + this.options.instanceName + '-content',
                snippetList: 'snippet-content-' + this.options.instanceName + '-column-navigation',
                search: 'snippet-content-' + this.options.instanceName + '-search'
            };
            this.sandbox.dom.html(this.$el, templates.skeleton(this.options));

            // init container
            this.$container = this.sandbox.dom.find(getId.call(this, 'container'), this.$el);
            this.$content = this.sandbox.dom.find(getId.call(this, 'content'), this.$el);
            this.$addButton = this.sandbox.dom.find(getId.call(this, 'addButton'), this.$el);
            this.$configButton = this.sandbox.dom.find(getId.call(this, 'configButton'), this.$el);

            // set preselected values
            if (!!this.sandbox.dom.data(this.$el, 'snippets')) {
                var data = this.sandbox.dom.data(this.$el, 'snippets');
                this.data.ids = data.ids;
            }

            renderStartContent.call(this);

            // sandbox event handling
            bindCustomEvents.call(this);

            this.URIGet = {
                str: '',
                hasChanged: false
            };
            this.URIGetAll = {
                str: '',
                hasChanged: false
            };

            // generate URIs for data
            setURIGet.call(this);
            setURIGetAll.call(this);

            // set display-option value
            setDisplayOption.call(this);

            // init overlays
            startAddOverlay.call(this);

            // load preselected items
            loadContent.call(this);

            // handle dom events
            bindDomEvents.call(this);
        },

        /**
         * Renders the content at the beginning
         * (with no items and before any request)
         */
        renderStartContent = function() {
            var label = this.sandbox.translate(this.options.translations.noSnippetsSelected);
            this.sandbox.dom.html(this.$content, templates.noContent(label));
        },

        /**
         * custom event handling
         */
        bindCustomEvents = function() {
            this.sandbox.on('husky.overlay.snippet-content.' + this.options.instanceName + '.add.initialized', initSnippetList.bind(this));

            this.sandbox.dom.on(getId.call(this, 'content'), 'click', removeSnippet.bind(this), 'li .remove');

            // adjust position of overlay after column-navigation has initialized
            this.sandbox.on('husky.datagrid.view.rendered', function() {
                this.sandbox.emit('husky.overlay.snippet-content.' + this.options.instanceName + '.add.set-position');
            }.bind(this));
        },

        /**
         * Handles the click on the remove icons
         * @param event
         */
        removeSnippet = function(event) {
            var $element = this.sandbox.dom.parents(event.currentTarget, 'li'),
                dataId = this.sandbox.dom.data($element, 'id');

            // remove element from dom
            this.sandbox.dom.remove($element);

            // from js-arrays
            this.data.ids.splice(this.data.ids.indexOf(dataId), 1);
            removeItemWithId.call(this, dataId);

            detachFooter.call(this);
            if (this.items.length === 0) {
                renderStartContent.call(this);
            } else {
                renderFooter.call(this);
            }
            this.sandbox.emit('husky.column-navigation.' + this.options.instanceName + '.unmark', dataId);
            this.sandbox.emit(DATA_CHANGED.call(this), this.data, this.$el);
        },

        /**
         * Removes an item for a given id
         * @param id {Number|String} the id of an item
         */
        removeItemWithId = function(id) {
            for (var i = -1, length = this.items.length; ++i < length;) {
                if (id === this.items[i].id) {
                    this.items.splice(i, 1);
                    return true;
                }
            }
            return false;
        },

        /**
         * initialize column navigation
         */
        initSnippetList = function() {
            this.sandbox.start([
                {
                    name: 'search@husky',
                    options: {
                        el: getId.call(this, 'search'),
                        instanceName: this.options.instanceName + '-search',
                        appearance: 'black small',
                        slide: false
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        url: this.URIGetAll.str,
                        preselected: this.data.ids,
                        resultKey: this.options.resultKey,
                        sortable: false,
                        columnOptionsInstanceName: '',
                        el: getId.call(this, 'snippetList'),
                        searchInstanceName: this.options.instanceName + '-search',
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
         * handle dom events
         */
        bindDomEvents = function() {
            this.sandbox.dom.on(this.$el, 'click', removeSnippet.bind(this), '.-list .remove');
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
        },

        /**
         * renders the content decides whether the footer is rendered or not
         */
        renderContent = function() {
            if (this.items.length !== 0) {
                this.linkList = this.sandbox.dom.createElement('<ul class="items-list"/>');

                this.sandbox.util.each(this.items, function(i) {
                    renderSnippetItem.call(this, this.items[i], this.linkList);
                }.bind(this));

                this.sandbox.dom.html(this.$content, this.linkList);
                renderFooter.call(this);
            } else {
                renderStartContent.call(this);
                detachFooter.call(this);
            }
        },

        /**
         * Renders a single link item
         * @param item
         */
        renderSnippetItem = function(item, container) {
            this.sandbox.dom.append(container,
                templates.contentItem(
                    item[this.options.idKey],
                    this.sandbox.dom.find('li', container).length + 1,
                    item[this.options.titleKey]
                )
            );
        },

        /**
         * renders the footer and calls a method to bind the events for itself
         */
        renderFooter = function() {
            this.itemsVisible = (this.items.length < this.itemsVisible) ? this.items.length : this.itemsVisible;

            if (this.$footer === null || this.$footer === undefined) {
                this.$footer = this.sandbox.dom.createElement('<div class="footer"/>');
            }

            this.sandbox.dom.append(this.$container, this.$footer);
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
                                okCallback: setSnippets.bind(this)
                            }
                        ]
                    }
                }
            ]);
        },

        setSnippets = function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(selected) {
                this.data.ids = selected;
                setData.call(this, this.data);
                setURIGet.call(this);
                loadContent.call(this);
                this.sandbox.emit(DATA_CHANGED.call(this), this.data, this.$el);
            }.bind(this));
        },

        /**
         * starts the loader component
         */
        startLoader = function() {
            detachFooter.call(this);

            var $loaderContainer = this.sandbox.dom.createElement('<div class="loader"/>');
            this.sandbox.dom.html(this.$content, $loaderContainer);

            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: $loaderContainer,
                        size: '100px',
                        color: '#e4e4e4'
                    }
                }
            ]);
        },

        /**
         * removes the footer
         */
        detachFooter = function() {
            if (this.$footer !== null) {
                this.sandbox.dom.remove(this.$footer);
            }
        },

        /**
         * load content from generated uri
         */
        loadContent = function() {
            //only request if URIGet has changed
            if (this.URIGet.hasChanged === true) {

                var thenFunction = function(data) {
                    if (data._embedded.snippets == undefined) {
                        throw "Invalid response from server, expected to find _embedded.snippets"
                    }
                    this.items = data._embedded.snippets;

                    renderContent.call(this);
                }.bind(this);

                startLoader.call(this);

                if (!!this.data.ids && this.data.ids.length > 0) {
                    this.sandbox.util.load(this.URIGet.str)
                        .then(thenFunction.bind(this))
                        .fail(function(error) {
                            this.sandbox.logger.log(error);
                        }.bind(this));
                } else {
                    thenFunction.call(this, {'_embedded': {'snippets': []}});
                }
            }
        },

        /**
         * set data of snippet-content
         */
        setData = function(data) {
            this.sandbox.dom.data(this.$el, 'snippets', this.data);
        },

        /**
         * generates the URI for getting snippets
         */
        setURIGet = function() {
            var newURIGet = [
                this.options.urlGet,
                '?ids=',
                (this.data.ids || []).join(','),
                '&language=',
                this.options.language
            ].join('');

            if (newURIGet !== this.URIGet.str) {
                this.URIGet.str = newURIGet;
                this.URIGet.hasChanged = true;
            } else {
                this.URIGet.hasChanged = false;
            }
        },

        /**
         * generates the URI for getting all the snippets of the configured type
         */
        setURIGetAll = function() {
            var delimiter = (this.options.urlAll.indexOf('?') === -1) ? '?' : '&',
                newURIGetAll = [
                    this.options.urlAll,
                    delimiter, 'language=',
                    this.options.language,
                    '&type=',
                    this.options.snippetType
                ].join('');

            if (newURIGetAll !== this.URIGetAll.str) {
                this.URIGetAll.str = newURIGetAll;
                this.URIGetAll.hasChanged = true;
            } else {
                this.URIGetAll.hasChanged = false;
            }
        },

        /**
         * set display option to element
         */
        setDisplayOption = function() {
            this.sandbox.dom.val(getId.call(this, 'displayOption'), this.data.displayOption);
        };

    return {
        initialize: function() {

            // extend default options
            this.options = this.sandbox.util.extend({}, defaults, this.options);

            // we add some "junk" data to the payload so that it will not resolve as "false" when there
            // are no IDs
            this.data = {junk: 'junk', ids: []};
            this.linkList = null;

            this.sandbox.util.each([
                'snippetType', 'language', 'webspace', 'urlGet', 'urlAll'
            ], function(key) {
                if (this.options[key] === null) {
                    throw 'you must specify the "' + key + '" option';
                }
            }.bind(this));

            render.call(this);
        }
    };
});
