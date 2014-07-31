/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var SHOW_GHOST_PAGES_KEY = 'column-navigation-show-ghost-pages',

        /**
         * constant for move button id
         * @type {number}
         */
        MOVE_BUTTON_ID = 3,

        /**
         * constant for copy button id
         * @type {number}
         */
        COPY_BUTTON_ID = 4,

        /**
         * constant for delete button id
         * @type {number}
         */
        DELETE_BUTTON_ID = 1,

        templates = {
            toggler: [
                '<div id="show-ghost-pages"></div>',
                '<label class="inline spacing-left" for="show-ghost-pages"><%= label %></label>'
            ].join(''),

            columnNavigation: function() {
                return[
                    '<div id="child-column-navigation"/>',
                ].join('');
            }
        };

    return {

        view: true,

        layout: {
            changeNothing: true
        },

        initialize: function() {
            this.render();
            // shows a delete success label. If a node just got deleted
            this.sandbox.sulu.triggerDeleteSuccessLabel();

            this.showGhostPages = true;
            this.setShowGhostPages();
        },

        /**
         * Sets the show-ghost-pages configuration to stored user settings if there is one
         */
        setShowGhostPages: function() {
            var showGhostPages = this.sandbox.sulu.getUserSetting(SHOW_GHOST_PAGES_KEY);
            if (showGhostPages !== null) {
                this.showGhostPages = JSON.parse(showGhostPages);
            }
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.column-navigation.node.add', function(parent) {
                this.sandbox.emit('sulu.content.contents.new', parent);
            }, this);

            this.sandbox.on('husky.column-navigation.node.edit', function(item) {
                this.sandbox.emit('sulu.content.contents.load', item.id);
            }, this);

            this.sandbox.on('husky.column-navigation.node.selected', function(item) {
                this.sandbox.sulu.saveUserSetting(this.options.webspace + 'ColumnNavigationSelected', item.id);
            }, this);

            this.sandbox.on('sulu.content.localizations', function(localizations) {
                this.localizations = localizations;
            }, this);

            this.sandbox.on('husky.toggler.show-ghost-pages.changed', function(checked) {
                this.showGhostPages = checked;
                this.sandbox.sulu.saveUserSetting(SHOW_GHOST_PAGES_KEY, this.showGhostPages);
                this.startColumnNavigation();
            }, this);

            this.sandbox.on('husky.column-navigation.node.settings', function(dropdownItem, selectedItem) {
                if (dropdownItem.id === MOVE_BUTTON_ID) {
                    this.moveSelected(selectedItem);
                }
            }.bind(this));

            // adjust position of overlay after column-navigation has initialized
            this.sandbox.on('husky.column-navigation.overlay.initialized', function() {
                this.sandbox.emit('husky.overlay.overlay.set-position');
            }.bind(this));
        },

        moveSelected: function(selectedItem) {
            // wait once to initialize overlay
            this.sandbox.once('husky.overlay.overlay.initialized', function() {
                this.initColumnNavigation(selectedItem.id);
            }.bind(this));

            var $element = this.sandbox.dom.createElement('<div class="overlay-container"/>');
            this.sandbox.dom.append(this.$el, $element);
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        openOnStart: true,
                        removeOnClose: true,
                        cssClass: 'overlay',
                        el: $element,
                        container: this.$el,
                        instanceName: 'overlay',
                        skin: 'wide',
                        slides: [
                            {
                                title: this.sandbox.translate('content.contents.settings.move.title'),
                                data: templates.columnNavigation(),
                                buttons: [
                                    {
                                        type: 'cancel'
                                    }
                                ]
                            }
                        ]
                    }
                }
            ]);

        },

        copySelected: function() {
        },

        deleteSelected: function() {
        },

        /**
         * initialize column navigation
         */
        initColumnNavigation: function(id) {
            var url = '/admin/api/nodes{/id}?tree=true&webspace=' + this.options.webspace + '&language=' + this.options.language + '&webspace-node=true';

            this.sandbox.start(
                [
                    {
                        name: 'column-navigation@husky',
                        options: {
                            el: '#child-column-navigation',
                            selected: this.data,
                            url: url.replace('{/id}', (!!id ? '/' + id : '')),
                            instanceName: 'overlay',
                            editIcon: 'fa-check-circle',
                            resultKey: this.options.resultKey,
                            showEdit: false,
                            showStatus: false,
                            responsive: false,
                            skin: 'fixed-height-small'
                        }
                    }
                ]
            );
        },

        startColumnNavigation: function() {
            this.sandbox.stop(this.$find('#content-column'));
            this.sandbox.dom.append(this.$el, '<div id="content-column"></div>');

            this.sandbox.start([
                {
                    name: 'column-navigation@husky',
                    options: {
                        el: this.$find('#content-column'),
                        instanceName: 'node',
                        selected: this.getLastSelected(),
                        resultKey: 'nodes',
                        url: this.getUrl(),
                        data: [
                            {
                                id: DELETE_BUTTON_ID,
                                name: this.sandbox.translate('content.contents.settings.delete')
                            },
                            {
                                id: 2,
                                divider: true
                            },
                            {
                                id: MOVE_BUTTON_ID,
                                name: this.sandbox.translate('content.contents.settings.move')
                            },
                            {
                                id: COPY_BUTTON_ID,
                                name: this.sandbox.translate('content.contents.settings.copy')
                            }
                        ]
                    }
                }
            ]);
        },

        getLocalizationForId: function(id) {
            id = parseInt(id, 10);
            for (var i = -1, length = this.localizations.length; ++i < length;) {
                if (this.localizations[i].id === id) {
                    return this.localizations[i].localization;
                }
            }
            return null;
        },

        getLastSelected: function() {
            return this.sandbox.sulu.getUserSetting(this.options.webspace + 'ColumnNavigationSelected');
        },

        getUrl: function() {
            if (this.getLastSelected() !== null) {
                return '/admin/api/nodes/' + this.getLastSelected() + '?tree=true&webspace=' + this.options.webspace + '&language=' + this.options.language + '&exclude-ghosts=' + (!this.showGhostPages ? 'true' : 'false');
            } else {
                return '/admin/api/nodes?depth=1&webspace=' + this.options.webspace + '&language=' + this.options.language + '&exclude-ghosts=' + (!this.showGhostPages ? 'true' : 'false');
            }
        },

        render: function() {
            this.bindCustomEvents();

            require(['text!/admin/content/template/content/column/' + this.options.webspace + '/' + this.options.language + '.html'], function(template) {
                var defaults = {
                        translate: this.sandbox.translate
                    },
                    context = this.sandbox.util.extend({}, defaults),
                    tpl = this.sandbox.util.template(template, context);

                this.sandbox.dom.html(this.$el, tpl);

                this.addToggler();

                // start column-navigation
                this.startColumnNavigation();
            }.bind(this));
        },

        /**
         * Generates the toggler and adds it to the header
         */
        addToggler: function() {
            this.sandbox.emit('sulu.header.set-bottom-content', this.sandbox.util.template(templates.toggler)({
                label: this.sandbox.translate('content.contents.show-ghost-pages')
            }));

            this.sandbox.start([
                {
                    name: 'toggler@husky',
                    options: {
                        el: '#show-ghost-pages',
                        checked: this.showGhostPages,
                        outline: true
                    }
                }
            ]);
        }
    };
});
