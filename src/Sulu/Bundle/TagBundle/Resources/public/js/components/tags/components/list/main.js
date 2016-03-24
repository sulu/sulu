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

    var constants = {
            datagridInstanceName: 'tags',
            instanceNameToolbar: 'saveToolbar'
        },

        bindCustomEvents = function() {
            // add clicked
            this.sandbox.on('sulu.toolbar.add', function() {
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.record.add', {
                    id: '',
                    name: '',
                    changed: '',
                    created: '',
                    author: ''
                });
            }.bind(this));

            // delete clicked
            this.sandbox.on('sulu.toolbar.delete', function() {
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.items.get-selected', function(ids) {
                    this.sandbox.emit('sulu.tags.delete', ids);
                }.bind(this));
            }, this);

            // checkbox clicked
            this.sandbox.on('husky.datagrid.' + constants.datagridInstanceName + '.number.selections', function(number) {
                var postfix = number > 0 ? 'enable' : 'disable';
                this.sandbox.emit('husky.toolbar.' + constants.instanceNameToolbar + '.item.' + postfix, 'deleteSelected', false);
            }.bind(this));

            // error - non unique tag name
            this.sandbox.on('husky.datagrid.' + constants.datagridInstanceName + '.data.save.failed', function(resp) {
                if (!!resp.responseJSON && !!resp.responseJSON.code) {
                    showErrorLabel.call(this, resp.responseJSON.code);
                }
            }, this);

            // checkbox clicked
            this.sandbox.on('husky.datagrid.' + constants.datagridInstanceName + '.number.selections', function(number) {
                var postfix = number > 0 ? 'enable' : 'disable';
                this.sandbox.emit('sulu.header.toolbar.item.' + postfix, 'deleteSelected', false);
            }.bind(this));
        },

        showErrorLabel = function(code) {
            var translationKeyForError = '';
            switch (code) {
                case 1101:
                    translationKeyForError = 'tag.error.notUnique';
                    break;
                default:
                    break;
            }

            this.sandbox.emit('sulu.labels.error.show',
                translationKeyForError,
                'labels.error',
                ''
            );
        };

    return {

        stickyToolbar: true,

        layout: {
            content: {
                width: 'max'
            }
        },

        header: {
            noBack: true,

            title: 'tag.tags.title',
            underline: false,

            toolbar: {
                buttons: {
                    add: {},
                    deleteSelected: {},
                    export: {
                        options: {
                            urlParameter: {
                                flat: true
                            },
                            url: '/admin/api/tags.csv'
                        }
                    }
                }
            }
        },

        templates: ['/admin/tag/template/tag/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/tag/template/tag/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'tags', '/admin/api/tags/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    template: 'default',
                    listener: 'default',
                    instanceName: constants.instanceNameToolbar
                },
                {
                    el: this.sandbox.dom.find('#tags-list', this.$el),
                    url: '/admin/api/tags?flat=true',
                    resultKey: 'tags',
                    searchFields: ['name'],
                    instanceName: constants.datagridInstanceName,
                    viewOptions: {
                        table: {
                            editable: true,
                            validation: true
                        }
                    }
                },
                'tags',
                '#tags-list-info'
            );
        }
    };
});
