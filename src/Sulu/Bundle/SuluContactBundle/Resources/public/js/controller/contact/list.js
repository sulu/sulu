/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'jquery',
    'backbone',
    'router',
    'sulucontact/model/contact'
], function ($, Backbone, Router, Contact) {

    'use strict';

    return Backbone.View.extend({
        events: {
            'click .remove-row': 'removeContact'
        },

        initialize: function () {
            this.render();
        },

        render: function () {
            this.$el.removeData('Husky.Ui.DataGrid');

            require(['text!sulucontact/templates/contact/table-row.html'], function (RowTemplate) {
                var dataGrid = this.$el.huskyDataGrid({
                    url: '/contact/api/contacts/list?field=id,fistName,lastName',
                    pagination: false,
                    showPages: 6,
                    pageSize: 4,
                    template: {
                        row: RowTemplate
                    },
                    selectItems: {
                        type: 'checkbox'
                    }
                });

                dataGrid.data('Husky.Ui.DataGrid').on('data-grid:item:select', function (item) {
                    Router.navigate('settings/translate/edit:' + item);
                });
            }.bind(this));
        },

        removeContact: function (event) {
            var $element = $(event.currentTarget);
            var $parent = $element.parent();
            var id = $parent.data('id');

            var contact = new Contact({id: id});
            contact.destroy({
                success: function () {
                    console.log("deleted model");
                }
            });
        }
    });
});