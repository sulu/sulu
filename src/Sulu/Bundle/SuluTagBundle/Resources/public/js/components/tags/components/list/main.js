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
        this.sandbox.on('sulu.list-toolbar.add', function(){
            this.sandbox.emit('husky.datagrid.row.add',{ id: '', name: '', changed: '', created: '', author: ''});
        }.bind(this));

        // save clicked
        this.sandbox.on('sulu.list-toolbar.save', function(){
            this.sandbox.emit('husky.toolbar.'+instanceNameToolbar+'.item.disable', 'save');
            this.sandbox.emit('husky.datagrid.data.save');
        }.bind(this));

        // delete clicked
        this.sandbox.on('sulu.list-toolbar.delete', function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.emit('sulu.tags.delete', ids);
            }.bind(this));
        }, this);

    };

    return {

        view: true,
        instanceNameToolbar: 'saveToolbar',

        templates: ['/admin/tag/template/tag/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this, this.instanceNameToolbar);
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/tag/template/tag/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'tagsFields', '/admin/api/tags/fields',
                {
                    el: '#list-toolbar-container',
                    template: 'defaultEditableList',
                    listener: 'defaultEditableList',
                    instanceName: this.instanceNameToolbar
                },
                {
                    el: this.sandbox.dom.find('#tags-list', this.$el),
                    url: '/admin/api/tags?flat=true',
                    editable: true,
                    validation: true,
                    paginationOptions: {
                        pageSize: 4
                    },
                    pagination: true,
                    selectItem: {
                        type: 'checkbox'
                    },
                    removeRow: false,
                    sortable: true
                }
            );

        }
    };
});
