/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app', 'router', 'backbone', 'husky'], function(App, Router, Backbone, Husky) {

    'use strict';

    return Backbone.View.extend({
        initialize: function() {
            this.render();
        },

        render: function() {

            require(['text!/translate/template/package/list'], function (Template) {
                var template;
                template = _.template(Template);
                this.$el.html(template);
                this.initPackageList();
            }.bind(this));

            this.initOperationsRight();
        },

        initPackageList: function() {

            var packages = $('#packageList').huskyDataGrid({
                url: '/translate/api/packages?fields=id,name',
                pagination: false,
                showPages: 6,
                pageSize: 4,
                selectItemType: 'checkbox',
                removeRow: true,
                tableHead: [
                    {content: 'Title'},
                    {content: ''}
                ],
                excludeFields: ['id']
            });

            packages.data('Husky.Ui.DataGrid').on('data-grid:item:select', function(item) {
                packages.data('Husky.Ui.DataGrid').off();
                Router.navigate('settings/translate/edit:' + item+'/settings');
            });
        },

        initOperationsRight:function(){

            var $optionsRight = $('#headerbar-mid-right');
            $optionsRight.empty();
            $optionsRight.append(this.template.button('Add', '#settings/translate/add'));

        },

        template: {
            button: function(text, route) {
                return '<a class="btn" href="'+route+'">'+text+'</a>';
            }
        }
    });
});
