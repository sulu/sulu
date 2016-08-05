/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'underscore',
    'config',
    'text!./skeleton.html',
    'services/sulucustomurl/custom-url-manager'
], function(_, Config, skeleton, CustomUrlManager) {

    'use strict';

    var defaults = {
        templates: {
            skeleton: skeleton
        },
        translations: {
            title: 'public.title',
            published: 'custom-urls.activated',
            baseDomain: 'custom-urls.base-domain',
            customUrl: 'custom-urls.custom-url',
            target: 'custom-urls.target',
            locale: 'custom-urls.target-locale',
            canonical: 'custom-urls.canonical',
            redirect: 'custom-urls.redirect',
            noIndex: 'custom-urls.no-index',
            noFollow: 'custom-urls.no-follow',
            successLabel: 'labels.success',
            successMessage: 'labels.success.save-desc',

            created: 'public.created',
            creator: 'public.creator',
            changed: 'public.changed',
            changer: 'public.changer',

            targetTitle: 'custom-urls.target-title',
            noTarget: 'custom-urls.no-target'
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
        startDatagrid: function() {
            var security = Config.get('sulu_security.contexts')['sulu.webspace_settings.' + this.data.key + '.custom-urls'],
                buttons = {},
                locale = this.sandbox.sulu.getDefaultContentLocale(),
                components = [
                    {
                        name: 'datagrid@husky',
                        options: {
                            el: '#webspace-custom-url-list',
                            url: CustomUrlManager.generateUrl(this.data, null, null) + '?locale=' + locale,
                            resultKey: 'custom-urls',
                            actionCallback: this.edit.bind(this),
                            pagination: 'infinite-scroll',
                            idKey: 'uuid',
                            viewOptions: {
                                table: {
                                    actionIconColumn: 'title',
                                    actionIcon: (!!security.edit ? 'pencil' : 'eye')
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
                                },
                                {
                                    attribute: 'customUrl',
                                    name: 'customUrl',
                                    content: this.translations.customUrl
                                },
                                {
                                    attribute: 'targetTitle',
                                    name: 'targetTitle',
                                    content: this.translations.targetTitle,
                                    type: function(content) {
                                        if (content === '') {
                                            return this.translations.noTarget;
                                        }

                                        return content;
                                    }.bind(this)
                                },
                                {
                                    attribute: 'changed',
                                    name: 'changed',
                                    content: this.translations.changed,
                                    type: 'datetime'
                                },
                                {
                                    attribute: 'changerFullName',
                                    name: 'changerFullName',
                                    content: this.translations.changer
                                },
                                {
                                    attribute: 'created',
                                    name: 'created',
                                    content: this.translations.created,
                                    type: 'datetime'
                                },
                                {
                                    attribute: 'creatorFullName',
                                    name: 'creatorFullName',
                                    content: this.translations.creator
                                }
                            ]
                        }
                    }
                ];

            if (!!security.add) {
                buttons.add = {options: {callback: this.edit.bind(this)}};
            }
            if (!!security.delete) {
                buttons.deleteSelected = {options: {callback: this.del.bind(this)}};
            }

            if (!_.isEmpty(buttons)) {
                components.push({
                    name: 'list-toolbar@suluadmin',
                    options: {
                        el: '#webspace-custom-url-list-toolbar',
                        instanceName: 'custom-url',
                        hasSearch: false,
                        template: this.sandbox.sulu.buttons.get(buttons)
                    }
                });
            }

            this.sandbox.start(components);
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
        del: function() {
            var ids = this.sandbox.util.deepCopy($('#webspace-custom-url-list').data('selected'));

            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (!!confirmed) {
                    CustomUrlManager.del(ids, this.data).then(function() {
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
            return CustomUrlManager.save(id, data, this.data).then(function(response) {
                var event = 'husky.datagrid.record.add';
                if (!!id) {
                    event = 'husky.datagrid.records.change';
                }

                this.sandbox.emit(event, response);
                this.sandbox.emit(
                    'sulu.labels.success.show',
                    this.translations.successMessage,
                    this.translations.successLabel
                );
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
