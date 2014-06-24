/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontent/model/content'
], function(Content) {

    'use strict';

    return {

        initialize: function() {
            this.load();
        },

        load: function() {
            this.model = new Content({id: this.options.id});

            this.model.fullFetch(
                this.options.webspace,
                this.options.language,
                true,
                {
                    success: function(model) {
                        this.render(model.toJSON());
                    }.bind(this)
                }
            )
        },

        render: function(data) {
            this.data = data;
            this.setTitle(data);
            this.setBreadcrumb(data);
            this.setTemplate(data);
        },

        setTemplate: function(data) {
            this.sandbox.emit('sulu.header.toolbar.item.change', 'template', data.template);
            this.sandbox.emit('sulu.header.toolbar.item.show', 'template');
        },

        /**
         * Sets the title of the page and if in edit mode calls a method to set the breadcrumb
         * @param {Object} data
         */
        setTitle: function(data) {
            if (!!this.options.id && data['sulu.node.name'] !== '') {
                this.sandbox.emit('sulu.header.set-title', data['sulu.node.name']);
            } else {
                this.sandbox.emit('sulu.header.set-title', this.sandbox.translate('content.contents.title'));
            }
        },

        /**
         * Sets the breadcrump of the selected node
         * @param data
         */
        setBreadcrumb: function(data) {
            if (!!data.breadcrumb) {
                var breadcrumb = [
                    {
                        title: this.options.webspace.replace(/_/g, '.'),
                        event: 'sulu.content.contents.list'
                    }
                ], length, i;

                // loop through breadcrumb skip home-page
                for (i = 0, length = data.breadcrumb.length; ++i < length;) {
                    breadcrumb.push({
                        title: data.breadcrumb[i].title,
                        link: this.getBreadcrumbRoute(data.breadcrumb[i].uuid)
                    });
                }

                this.sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
            }
        },

        /**
         * Returns routes for the breadcrumbs. Replaces the current uuid with a passed one in the active URI
         * @param uuid {string} uuid to replace the current one with
         * @returns {string} the route for the breadcrumb
         */
        getBreadcrumbRoute: function(uuid) {
            return this.sandbox.mvc.history.fragment.replace(this.options.id, uuid);
        },

        header: function() {
            var noBack = (this.options.id === 'index') ? true : false;

            return {
                noBack: noBack,

                tabs: {
                    url: '/admin/content/navigation/content'
                },

                toolbar: {
                    parentTemplate: 'default',

                    languageChanger: {
                        url: '/admin/content/languages/' + this.options.webspace,
                        preSelected: this.options.language
                    },

                    template: [
                        {
                            'id': 'state',
                            'group': 'left',
                            'position': 100,
                            'type': 'select',
                            items: [
                                {
                                    'id': 'publish',
                                    'title': this.sandbox.translate('toolbar.state-publish'),
                                    'icon': 'husky-publish',
                                    'callback': function() {
                                        this.sandbox.emit('sulu.dropdown.state.item-clicked', 2);
                                    }.bind(this)
                                },
                                {
                                    'id': 'test',
                                    'title': this.sandbox.translate('toolbar.state-test'),
                                    'icon': 'husky-test',
                                    'callback': function() {
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
                            hidden: true,
                            itemsOption: {
                                url: '/admin/content/template',
                                titleAttribute: 'template',
                                idAttribute: 'template',
                                translate: true,
                                languageNamespace: 'template.',
                                callback: function(item) {
                                    this.sandbox.emit('sulu.dropdown.template.item-clicked', item);
                                }.bind(this)
                            }
                        }
                    ]
                }
            };
        }
    };
});
