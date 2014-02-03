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

    var bindCustomEvents = function() {

        this.sandbox.on('sulu.list-toolbar.add', function(){
            this.sandbox.emit('husky.datagrid.row.add',{ id: '', name: '', changed: '', created: '' });
        }.bind(this));

        this.sandbox.on('sulu.list-toolbar.save', function(){
            this.sandbox.emit('husky.datagrid.data.save');
        }.bind(this));

        //sulu.list-toolbar.save
        //sulu.list-toolbar.delete


        // husky.search.saveToolbar
        // husky.search.saveToolbar.reset
    };

    return {

        view: true,

        templates: ['/admin/tag/template/tag/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/tag/template/tag/list'));

            this.sandbox.start([
                {
                    name: 'list-toolbar@suluadmin',
                    options: {
                        el: '#list-toolbar-container',
                        template: 'defaultEditableList',
                        listener: 'defaultEditableList',
                        instanceName: 'saveToolbar'
                    }
                }
            ]);

            // datagrid
            this.sandbox.start([
                {
                    name: 'datagrid@husky',
                    options: {
                        el: this.sandbox.dom.find('#tags-list', this.$el),
                        url: '/admin/api/tags?flat=true&fields=id,name,changed',
                        editable: true,
                        paginationOptions: {
                            pageSize: 4
                        },
                        pagination: true,
                        selectItem: {
                            type: 'checkbox'
                        },
                        removeRow: false,
                        sortable: true,
                        columns: [
                            {content: this.sandbox.translate('tag.tag'), width:'40%', attribute:'name',editable: true},
                            {content: this.sandbox.translate('tag.author'), width:'30%', attribute: ''},
                            {content: this.sandbox.translate('tag.changed'),width:'30%', attribute:'changed'}
                        ],
                        excludeFields: ['id']
                    }
                }
            ]);
        }
    };
});
