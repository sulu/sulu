/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * handles internal links
 *
 * @class InternalLinks
 * @constructor
 */
define([], function() {

    'use strict';

    var defaults = {
            eventNamespace: 'sulu.internal-links',
            resultKey: 'nodes',
            idKey: 'uuid',
            locale: null,
            webspace: null,
            hideConfigButton: true,
            hidePositionElement: true,
            dataAttribute: 'internal-links',
            actionIcon: 'fa-link',
            dataDefault: [],
            navigateEvent: 'sulu.router.navigate',
            publishedStateName: 'publishedState',
            publishedName: 'published',
            translations: {
                noContentSelected: 'internal-links.nolinks-selected',
                addLinks: 'internal-links.add',
                visible: 'public.visible',
                of: 'public.of',
                unpublished: 'public.unpublished',
                publishedWithDraft: 'public.published-with-draft'
            }
        },

        templates = {
            data: function(options) {
                return [
                    '<div id="', options.ids.columnNavigation, '"/>'
                ].join('');
            },

            contentItem: function(id, value, icons) {
                return [
                    '<span class="icons">' + icons + '</span>',
                    '<a href="#" data-id="', id, '" class="link">',
                    '    <span class="value">', value, '</span>',
                    '</a>'
                ].join('');
            },

            icons: {
                draft: function(title) {
                    return '<span class="draft-icon" title="' + title + '"/>';
                },
                published: function(title) {
                    return [
                        '<span class="published-icon" title="' + title + '"/>'
                    ].join('');
                }
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
                'husky.overlay.internal-links.' + this.options.instanceName + '.add.initialized',
                initColumnNavigation.bind(this)
            );

            this.sandbox.on('husky.column-navigation.' + this.options.instanceName + '.action', selectLink.bind(this));

            this.sandbox.dom.on(this.$el, 'click', function(e) {
                var id = this.sandbox.dom.data(e.currentTarget, 'id');

                this.sandbox.emit(
                    this.options.navigateEvent,
                    'content/contents/' + this.options.webspace + '/' + this.options.locale + '/edit:' + id + '/content'
                );

                return false;
            }.bind(this), 'a.link');
        },

        /**
         * Handles the selection of a link
         * @param item {Object} the object of the link node
         */
        selectLink = function(item) {
            var data = this.getData();

            if (data.indexOf(item.id) === -1) {
                // FIXME return of node api returns for column-navigation id and for "filter by id" uuid as id key
                item.uuid = item.id;

                data.push(item.id);

                this.setData(data, false);
                this.addItem(item);
            }
        },

        /**
         * initialize column navigation
         */
        initColumnNavigation = function() {
            var data = this.getData();

            this.sandbox.start(
                [
                    {
                        name: 'column-navigation@husky',
                        options: {
                            el: getId.call(this, 'columnNavigation'),
                            url: getColumnNavigationUrl.call(this),
                            linkedName: 'linked',
                            typeName: 'type',
                            hasSubName: 'hasChildren',
                            instanceName: this.options.instanceName,
                            actionIcon: 'fa-plus-circle',
                            resultKey: this.options.resultKey,
                            showOptions: false,
                            responsive: false,
                            skin: 'fixed-height-small',
                            markable: true,
                            sortable: false,
                            premarkedIds: data
                        }
                    }
                ]
            );
        },

        /**
         * returns url for main column-navigation
         *
         * @returns {String}
         */
        getColumnNavigationUrl = function() {
            var url = '/admin/api/nodes',
                urlParts = [
                    'language=' + this.options.locale,
                    'fields=title,order,published',
                    'webspace-nodes=all'
                ];

            if (!!this.options.webspace) {
                urlParts.push('webspace=' + this.options.webspace);
            }

            return url + '?' + urlParts.join('&');
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
                        cssClass: 'internal-links-overlay',
                        el: $element,
                        container: this.$el,
                        removeOnClose: false,
                        instanceName: 'internal-links.' + this.options.instanceName + '.add',
                        skin: 'responsive-width',
                        contentSpacing: false,
                        slides: [
                            {
                                title: this.sandbox.translate(this.options.translations.addLinks),
                                cssClass: 'internal-links-overlay-add',
                                data: templates.data(this.options)
                            }
                        ]
                    }
                }
            ]);
        };

    return {
        type: 'itembox',

        initialize: function() {
            // extend default options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // init ids
            this.options.ids = {
                container: 'internal-links-' + this.options.instanceName + '-container',
                addButton: 'internal-links-' + this.options.instanceName + '-add',
                configButton: 'internal-links-' + this.options.instanceName + '-config',
                displayOption: 'internal-links-' + this.options.instanceName + '-display-option',
                content: 'internal-links-' + this.options.instanceName + '-content',
                chooseTab: 'internal-links-' + this.options.instanceName + '-choose-tab',
                columnNavigation: 'internal-links-' + this.options.instanceName + '-column-navigation'
            };

            this.render();

            // sandbox event handling
            bindCustomEvents.call(this);

            // init overlays
            startAddOverlay.call(this);
        },

        getUrl: function(data) {
            var delimiter = (this.options.url.indexOf('?') === -1) ? '?' : '&';

            return [this.options.url, delimiter, this.options.idsParameter, '=', (data || []).join(',')].join('');
        },

        getItemContent: function(item) {
            return templates.contentItem(
                item[this.options.idKey],
                item.title,
                this.getItemIcons(item)
            );
        },

        /**
         * Returns the icons of an item for given item data
         *
         * @param {Object} itemData The data of the item
         * @returns {string} the html string of icons
         */
        getItemIcons: function(itemData) {
            if (itemData[this.options.publishedStateName] === undefined) {
                return '';
            }

            var icons = '',
                tooltip = this.sandbox.translate(this.options.translations.unpublished);
            if (!itemData[this.options.publishedStateName] && !!itemData[this.options.publishedName]) {
                tooltip = this.sandbox.translate(this.options.translations.publishedWithDraft);
                icons += templates.icons.published(tooltip);
            }
            if (!itemData[this.options.publishedStateName]) {
                icons += templates.icons.draft(tooltip);
            }

            return icons;
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

            this.sandbox.emit('husky.column-navigation.' + this.options.instanceName + '.unmark', id);

            this.setData(data, false);
        }
    };
});
