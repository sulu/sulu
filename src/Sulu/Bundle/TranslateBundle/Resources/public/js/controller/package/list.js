/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app', 'router', 'backbone', 'husky', 'sulutranslate/model/package'], function(App, Router, Backbone, Husky, Package) {

    'use strict';

    var $dialog, packages;

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

                $dialog = $('#dialog').huskyDialog({
                    backdrop: true,
                    width: '650px'
                });

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

            // show dialogbox for removing data
            packages.data('Husky.Ui.DataGrid').on('data-grid:row:removed', function(id,event) {
               this.initDialogBox(id);
            });
        },

        initOperationsRight:function(){

            var $optionsRight = $('#headerbar-mid-right');
            $optionsRight.empty();
            $optionsRight.append(this.template.button('Add', '#settings/translate/add'));

        },

        // fills dialogbox and displays existing references
        initDialogBox: function(id){

            $dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
                template: {
                    content: '<h3><%= title %></h3><p><%= content %></p>',
                    footer: '<button class="btn btn-black closeButton"><%= buttonCancelText %></button><button class="btn btn-black deleteButton"><%= buttonSaveText %></button>',
                    header: '<button type="button" class="close">×</button>'
                },
                data: {
                    content: {
                        title:  "Warning" ,
                        content: "Do you really want to delete the package? All data is going to be lost."
                    },
                    footer: {
                        buttonCancelText: "Abort",
                        buttonSaveText: "Delete"
                    }
                }
            });

            $dialog.on('click', '.closeButton', function() {
                $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
            });

            $dialog.on('click', '.deleteButton', function() {
                // remove package
                var pkg = new Package({id: id});
                pkg.destroy({
                    success: function () {
                        console.log('deleted model');
                    }
                });

                $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
            });
        },

        template: {
            button: function(text, route) {
                return '<a class="btn" href="'+route+'">'+text+'</a>';
            }
        }
    });
});
