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
            url: '/admin/api/webspaces/<%= webspace.key %>/custom-urls<% if (!!id) { %>/<%= id %><% } %><% if (!!ids) { %>?ids=<%= ids.join(",") %><% } %>'
        },
        translations: {
            title: 'public.title',
            published: 'public.published',
            baseDomain: 'custom-urls.base-domain',
            customUrl: 'custom-urls.custom-url',
            target: 'custom-urls.target',
            locale: 'custom-urls.locale',
            canonical: 'custom-urls.canonical',
            redirect: 'custom-urls.redirect',
            noIndex: 'custom-urls.no-index',
            noFollow: 'custom-urls.no-follow',
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

        /**
         * Initializes component.
         */
        initialize: function() {
            this.render();
        },

        /**
         * Render component skeleton.
         */
        render: function() {
            this.html(this.templates.skeleton());
            this.startDatagrid();
        },

        /**
         * Start datagrid and toolbar.
         */
        startDatagrid: function(){
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
                            deleteSelected: {
                                options: {callback: this.delete.bind(this)}
                            }
                        })
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: '#webspace-custom-url-list',
                        url: this.templates.url({webspace: this.data, id: null, ids: null}),
                        resultKey: 'custom-urls',
                        actionCallback: this.edit.bind(this),
                        pagination: 'infinite-scroll',
                        idKey: 'uuid',
                        viewOptions: {
                            table: {
                                actionIconColumn: 'title'
                            }
                        },
                        matchings: [
                            {
                                attribute: 'title',
                                name: 'title',
                                content: this.translations.title
                            },
                            {
                                attribute: 'published',
                                name: 'published',
                                content: this.translations.published,
                                type: 'checkbox_readonly'
                            }
                        ]
                    }
                }
            ]);
        },

        /**
         * Start edit overlay for given id.
         *
         * @param {Integer} id
         */
        edit: function(id) {
            this.sandbox.start([
                {
                    name: 'webspace/settings/custom-url/overlay@sulucustomurl',
                    options: {
                        el: '#webspace-custom-url-form-overlay',
                        id: id,
                        webspace: this.data,
                        saveCallback: this.save.bind(this),
                        translations: this.translations
                    }
                }
            ]);
        },

        /**
         * Deletes selected records from datagrid after asking for confirmation.
         */
        delete: function() {
            var ids = this.sandbox.util.deepCopy($('#webspace-custom-url-list').data('selected'));

            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (!!confirmed) {
                    this.sandbox.util.save(
                        this.templates.url({webspace: this.data, id: null, ids: ids}),
                        'DELETE'
                    ).then(function() {
                        for (var i = 0, length = ids.length; i < length; i++) {
                            var id = ids[i];
                            this.sandbox.emit('husky.datagrid.record.remove', id);
                        }
                    }.bind(this));
                }
            }.bind(this));
        },

        /**
         * Save record identified by id with given data.
         * If id is null a new record will be created.
         *
         * @param {Integer} id
         * @param {{}} data
         */
        save: function(id, data) {
            this.sandbox.util.save(
                this.templates.url({webspace: this.data, id: id, ids: null}), !!id ? 'PUT' : 'POST', data
            ).then(function(response) {
                var event = 'husky.datagrid.record.add';
                if (!!id) {
                    event = 'husky.datagrid.records.change';
                }

                this.sandbox.emit(event, response);
                this.sandbox.emit('sulu.labels.success.show', this.translations.successMessage, this.translations.successLabel);
            }.bind(this));
        },

        /**
         * Load data.
         *
         * @returns {{}}
         */
        loadComponentData: function() {
            var deferred = this.sandbox.data.deferred();

            deferred.resolve(this.options.data());

            return deferred.promise();
        }
    };
});
