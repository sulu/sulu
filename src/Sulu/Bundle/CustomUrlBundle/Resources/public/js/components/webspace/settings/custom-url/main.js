/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!./skeleton.html'], function(skeleton) {

    'use strict';

    var defaults = {
        templates: {
            skeleton: skeleton,
            url: '/admin/api/webspaces/<%= webspace.key %>/custom-urls<% if (!!id) { %>/<%= id %><% } %>'
        },
        translations: {
            title: 'public.title',
            successLabel: 'labels.success',
            successMessage: 'labels.success.save-desc'
        }
    };

    return {

        defaults: defaults,

        tabOptions: {
            title: function() {
                return this.data.title;
            }
        },

        layout: {
            content: {
                width: 'max',
                leftSpace: true,
                rightSpace: true
            }
        },

        initialize: function() {
            this.render();

            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
        },

        render: function() {
            this.html(this.templates.skeleton());

            this.sandbox.start([
                {
                    name: 'list-toolbar@suluadmin',
                    options: {
                        el: '#webspace-custom-url-list-toolbar',
                        instanceName: 'custom-url',
                        hasSearch: false,
                        template: this.sandbox.sulu.buttons.get({
                            add: {
                                options: {callback: this.edit.bind(this)}
                            },
                            deleteSelected: {}
                        })
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: '#webspace-custom-url-list',
                        url: this.templates.url({webspace: this.data, id: null}),
                        resultKey: 'custom-urls',
                        actionCallback: this.edit.bind(this),
                        pagination: 'infinite-scroll',
                        viewOptions: {
                            table: {
                                actionIconColumn: 'title'
                            }
                        },
                        matchings: [
                            {
                                attribute: 'title',
                                content: this.translations.title
                            }
                        ]
                    }
                }
            ]);
        },

        edit: function(id) {
            this.sandbox.start([
                {
                    name: 'webspace/settings/custom-url/overlay@sulucustomurl',
                    options: {
                        el: '#webspace-custom-url-form-overlay',
                        id: id,
                        webspaceKey: this.data.key,
                        saveCallback: this.save.bind(this),
                        translations: this.translations
                    }
                }
            ]);
        },

        save: function(id, data) {
            this.sandbox.util.save(
                this.templates.url({webspace: this.data, id: id}), !!id ? 'PUT' : 'POST', data
            ).then(function(response) {
                var event = 'husky.datagrid.record.add';
                if (!!id) {
                    event = 'husky.datagrid.records.change';
                }

                this.sandbox.emit(event, response);
                this.sandbox.emit('sulu.labels.success.show', this.translations.successMessage, this.translations.successLabel);
            }.bind(this));
        },

        loadComponentData: function() {
            var deferred = this.sandbox.data.deferred();

            deferred.resolve(this.options.data());

            return deferred.promise();
        }
    };
});
