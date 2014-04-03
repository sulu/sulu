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

    var SHOW_GHOST_PAGES_KEY = 'column-navigation-show-ghost-pages';

    return {

        view: true,

        initialize: function() {
            this.render();
            this.showLabels();

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

        /**
         * Shows desired labels
         */
        showLabels: function() {
            if (this.sandbox.sulu.viewStates.nodeDeleted === true) {
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.content-deleted-desc', 'labels.success');
                delete this.sandbox.sulu.viewStates.nodeDeleted;
            }
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.column-navigation.add', function(parent) {
                this.sandbox.emit('sulu.content.contents.new', parent);
            }, this);

            this.sandbox.on('husky.column-navigation.edit', function(item) {
                this.sandbox.emit('sulu.content.contents.load', item.id);
            }, this);

            this.sandbox.on('sulu.content.localizations', function(localizations) {
                this.localizations = localizations;
            }, this);

            this.sandbox.on('husky.toggler.show-ghost-pages.changed', function(checked) {
                this.showGhostPages = checked;
                this.sandbox.sulu.saveUserSetting(SHOW_GHOST_PAGES_KEY, this.showGhostPages);
                this.restartColumnNavigation();
            }, this);

            this.sandbox.on('husky.select.language.selected.item', function(localeId) {
                this.changeLanguage(this.getLocalizationForId(localeId));
            }, this);
        },

        restartColumnNavigation: function() {
            this.sandbox.stop('#content-column');
            this.sandbox.dom.append('#contacts-column-container', '<div id="content-column"></div>');

            this.sandbox.start([
                {
                    name: 'column-navigation@husky',
                    options: {
                        el: '#content-column',
                        url: this.getUrl()
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

        getUrl: function() {
            return '/admin/api/nodes?depth=1&webspace=' + this.options.webspace + '&language=' + this.options.language + '&exclude-ghosts=' + (!this.showGhostPages ? 'true' : 'false');
        },

        changeLanguage: function(language) {
            this.sandbox.emit('sulu.content.contents.list', this.options.webspace, language);
        },

        render: function() {
            this.bindCustomEvents();

            require(['text!/admin/content/template/content/column/' + this.options.webspace + '/' + this.options.language + '.html'], function(template) {
                var defaults = {
                        translate: this.sandbox.translate
                    },
                    context = this.sandbox.util.extend({}, defaults),
                    tpl = this.sandbox.util.template(template, context);

                this.html(tpl);

                // datagrid && tabs
                this.sandbox.start([
                    {
                        name: 'select@husky',
                        options: {
                            el: '#language-selector',
                            instanceName: 'language',
                            data: this.localizations,
                            style: 'big',
                            preSelectedElements: [this.options.language]
                        }
                    },
                    {
                        name: 'toggler@husky',
                        options: {
                            el: '#show-ghost-pages',
                            checked: this.showGhostPages
                        }
                    },
                    {
                        name: 'column-navigation@husky',
                        options: {
                            el: '#content-column',
                            url: this.getUrl()
                        }
                    }
                ]);
            }.bind(this));
        }
    };
});
