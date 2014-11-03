/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    var bindCustomEvents = function() {
        // navigate to edit contact
        this.sandbox.on('husky.datagrid.item.click', function(item) {
            this.sandbox.emit('sulu.translate.package.load', item);
        }, this);

        // delete clicked
        this.sandbox.on('sulu.list-toolbar.delete', function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.emit('sulu.translate.packages.delete', ids);
            }.bind(this));
        }, this);

        // add clicked
        this.sandbox.on('sulu.list-toolbar.add', function() {
            this.sandbox.emit('sulu.translate.package.new');
        }, this);
    };

    return {

        view: true,

        layout: {
            content: {
                width: 'max',
                leftSpace: false,
                rightSpace: false
            }
        },

        header: function() {
            return {
                title: 'translate.package.title',
                noBack: true,

                breadcrumb: [
                    {title: 'navigation.settings'},
                    {title: 'translate.package.title'}
                ]
            };
        },

        templates: ['/admin/translate/template/package/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);
        },

        render: function() {

            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/translate/template/package/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'packagesFields', '/admin/api/packages/fields',
                {
                    el: '#list-toolbar-container',
                    instanceName: 'package',
                    inHeader: true
                },
                {
                    el: this.$find('#package-list'),
                    url: '/admin/api/packages?flat=true',
                    resultKey: 'packages',
                    viewOptions: {
                        table: {
                            fullWidth: true
                        }
                    }
                }
            );
        }
    };
});
