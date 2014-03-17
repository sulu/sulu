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

    return {

        view: true,

        initialize: function() {
            this.render();
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
        },

        bindDomEvents: function() {
            this.sandbox.dom.on('#show-ghost-pages', 'change', function(event) {
                this.sandbox.dom.remove('#content-column');
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
            }.bind(this));
        },

        getUrl: function() {
            var exclude = this.sandbox.dom.find('#show-ghost-pages').is(':checked');
            return '/admin/api/nodes?depth=1&webspace=' + this.options.webspace + '&language=' + this.options.language + '&exclude-ghosts=' + (!exclude ? 'true' : 'false');
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

                this.sandbox.dom.html(this.$el, tpl);
                this.bindDomEvents();

                // datagrid && tabs
                this.sandbox.start([
                    {
                        name: 'dropdown@husky',
                        options: {
                            el: '#language-selector',
                            alignment: 'right',
                            data: this.localizations,
                            clickCallback: function(item) {
                                this.changeLanguage(item.localization);
                            }.bind(this)
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
