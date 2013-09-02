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
    'sulucontact/model/account'
], function ($, Backbone, Router, Account) {

    'use strict';

    return Backbone.View.extend({

        initialize: function () {
            this.render();
        },

        render: function () {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            this.$el.removeData('Husky.Ui.DataGrid');

            require(['text!sulucontact/templates/account/table-row.html'], function (RowTemplate) {
                var dataGrid = this.$el.huskyDataGrid({
                    url: '/contact/api/accounts/list?field=name',
                    pagination: true,
                    showPages: 6,
                    pageSize: 4,
                    template: {
                        row: RowTemplate
                    },
                    selectItemType: 'radio'
                });

                dataGrid.data('Husky.Ui.DataGrid').on('data-grid:item:select', function (item) {
                    Router.navigate('contacts/companies/edit:' + item);
                });

                this.$el.on('click', '.remove-row > span', function (event) {
                    dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:remove', event);
                    var $element = $(event.currentTarget);
                    var $parent = $element.parent().parent();
                    var id = $parent.data('id');

                    var account = new Account({id: id});
                    account.destroy();
                });

                this.initOperationsRight();

            }.bind(this));
        },

        initOperationsRight: function(){

            var $operationsRight = $('#headerbar-mid-right');
            $operationsRight.empty();
            $operationsRight.append(this.template.button('#contacts/companies/add','Add...'));
        },

        template: {
            button: function(url, name){

                return '<a class="btn" href="'+url+'" target="_top" title="Add">'+name+'</a>';
            }
        }
    });
});