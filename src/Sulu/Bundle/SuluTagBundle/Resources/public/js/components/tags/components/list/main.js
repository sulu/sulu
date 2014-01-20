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
                        el: '#list-toolbar-container'
                    }
                }
            ]);

            // datagrid
            this.sandbox.start([
                {
                    name: 'datagrid@husky',
                    options: {
                        el: this.sandbox.dom.find('#tags-list', this.$el),
                        url: '/admin/api/tags?flat=true&fields=id,name',
                        pagination: false,
                        selectItem: {
                            type: 'checkbox'
                        },
                        removeRow: false,
                        sortable: true,
                        tableHead: [
                            {content: this.sandbox.translate('tag.tag'), attribute:'name'}
                        ],
                        excludeFields: ['id']
                    }
                }
            ]);
        }
    };
});
