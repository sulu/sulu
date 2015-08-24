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

        layout: {
            content: {
                width: 'max'
            }
        },

        header: function() {
            return {
                noBack: true
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
            this.sandbox.sulu.initListToolbarAndList.call(this, 'packages', '/admin/api/packages/fields',
                {
                    el: '#list-toolbar-container',
                    instanceName: 'package'
                },
                {
                    el: this.$find('#package-list'),
                    url: '/admin/api/packages?flat=true',
                    resultKey: 'packages',
                    actionCallback: function(item) {
                        this.sandbox.emit('sulu.translate.package.load', item);
                    }.bind(this)
                }
            );
        }
    };
});
