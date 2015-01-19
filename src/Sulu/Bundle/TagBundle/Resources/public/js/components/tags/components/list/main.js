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

    var bindCustomEvents = function(instanceNameToolbar) {
            // add clicked
            this.sandbox.on('sulu.list-toolbar.add', function() {
                this.sandbox.emit('husky.datagrid.record.add', { id: '', name: '', changed: '', created: '', author: ''});
            }.bind(this));

            // delete clicked
            this.sandbox.on('sulu.list-toolbar.delete', function() {
                this.sandbox.emit('husky.toolbar.' + instanceNameToolbar + '.item.disable', 'delete');
                this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                    this.sandbox.emit('sulu.tags.delete', ids);
                }.bind(this));
            }, this);

            // error - non unique tag name
            this.sandbox.on('husky.datagrid.data.save.failed', function(resp) {
                if(!!resp.responseJSON && !!resp.responseJSON.code) {
                    showErrorLabel.call(this,resp.responseJSON.code);
                }
            }, this);
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
        view: true,
        instanceNameToolbar: 'saveToolbar',

        layout: {
            content: {
                width: 'max',
                leftSpace: false,
                rightSpace: false
            }
        },

        header: function() {
            return {
                title: 'tag.tags.title',
                noBack: true,

                breadcrumb: [
                    {title: 'navigation.settings'},
                    {title: 'tag.tags.title'}
                ]
            };
        },

        templates: ['/admin/tag/template/tag/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this, this.instanceNameToolbar);
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/tag/template/tag/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'tags', '/admin/api/tags/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    template: 'default',
                    listener: 'default',
                    instanceName: this.instanceNameToolbar,
                    inHeader: true
                },
                {
                    el: this.sandbox.dom.find('#tags-list', this.$el),
                    url: '/admin/api/tags?flat=true',
                    resultKey: 'tags',
                    searchFields: ['name'],
                    viewOptions: {
                        table: {
                            editable: true,
                            validation: true,
                            fullWidth: true
                        }
                    }
                }
            );
        }
    };
});
