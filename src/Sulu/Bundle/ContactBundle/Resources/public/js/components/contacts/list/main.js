/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require.config({
    paths: {
        'decorators/contact': '../../sulucontact/js/components/contacts/components/list/decorators/contact-view'
    }
});

define(['widget-groups'], function(WidgetGroups) {

    'use strict';

    var constants = {
            datagridInstanceName: 'contacts'
        },

        bindCustomEvents = function() {
            // delete clicked
            this.sandbox.on('sulu.toolbar.delete', function() {
                this.sandbox.emit('husky.datagrid.' + constants.datagridInstanceName + '.items.get-selected', function(ids) {
                    this.sandbox.emit('sulu.contacts.contacts.delete', ids);
                }.bind(this));
            }, this);

            // add clicked
            this.sandbox.on('sulu.toolbar.add', function() {
                this.sandbox.emit('sulu.contacts.contacts.new');
            }, this);

            // checkbox clicked
            this.sandbox.on('husky.datagrid.' + constants.datagridInstanceName + '.number.selections', function(number) {
                var postfix = number > 0 ? 'enable' : 'disable';
                this.sandbox.emit('sulu.header.toolbar.item.' + postfix, 'delete', false);
            }.bind(this));
        },

        clickCallback = function(item) {
            // show sidebar for selected item
            this.sandbox.emit('sulu.sidebar.set-widget', '/admin/widget-groups/contact-info?contact=' + item);
        },

        acitonCallback = function(id) {
            this.sandbox.emit('sulu.contacts.contacts.load', id);
        };

    return {
        view: true,

        layout: {
            content: {
                width: 'max'
            },
            sidebar: {
                width: 'fixed',
                cssClasses: 'sidebar-padding-50'
            }
        },

        header: {
            noBack: true,
            toolbar: {
                buttons: {
                    add: {},
                    delete: {}
                }
            }
        },

        templates: ['/admin/contact/template/contact/list'],

        initialize: function() {
            this.render();
            bindCustomEvents.call(this);
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/contact/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'contacts', '/admin/api/contacts/fields',
                {
                    el: this.$find('#list-toolbar-container'),
                    instanceName: 'contacts',
                    template: 'default'
                },
                {
                    el: this.sandbox.dom.find('#people-list', this.$el),
                    url: '/admin/api/contacts?flat=true',
                    searchInstanceName: 'contacts',
                    view: 'table',
                    //view: 'decorators/contact', //TODO: implement api for contact-decorator
                    searchFields: ['fullName'],
                    resultKey: 'contacts',
                    instanceName: constants.datagridInstanceName,
                    clickCallback: (WidgetGroups.exists('contact-info')) ? clickCallback.bind(this) : null,
                    actionCallback: acitonCallback.bind(this)
                },
                'contacts',
                '#people-list-info'
            );
        }
    };
});
