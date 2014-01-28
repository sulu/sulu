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
                        url: '/admin/api/tags?flat=false&fields=id,name,changed,creator',
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
                            {content: this.sandbox.translate('tag.author'), width:'30%', attribute: ['firstname','lastname'],editable: true},
                            {content: this.sandbox.translate('tag.changed'),width:'30%', attribute:'changed'}
                        ],
                        excludeFields: ['id']
                    }
                }
            ]);
        },

        bindCustomEvents: function(){
            //sulu.list-toolbar.add
            //sulu.list-toolbar.save
            //sulu.list-toolbar.delete
        }
    };
});
