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

    var $dialog, packages, $operationsRight, $operationsLeft;

    return Backbone.View.extend({
        initialize: function() {
            this.initOperations();
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

        },

        initPackageList: function() {
            
            var packages = $('#packageList').huskyDataGrid({
                // FIXME use list function with fields
                url: '/translate/api/packages',
                pagination: false,
                showPages: 6,
                pageSize: 4,
                selectItemType: 'checkbox',
                //removeRow: true,
                tableHead: [
                    {content: 'Title'},
                    //{content: ''}
                ],
                excludeFields: ['id']
            });

            packages.data('Husky.Ui.DataGrid').on('data-grid:item:click', function(item) {
                packages.data('Husky.Ui.DataGrid').off();
                this.removeHeaderbarEvents();
                Router.navigate('settings/translate/edit:' + item+'/settings');
            }.bind(this));

            // show dialogbox for removing data
            packages.data('Husky.Ui.DataGrid').on('data-grid:row:removed', function(id,event) {
               this.initDialogBox(id);
            }.bind(this));
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
                        content: "Do you really want to delete the package?<br/>All data and corresponding language catalogues as well as corresponding translations are going to be lost."
                    },
                    footer: {
                        buttonCancelText: "Abort",
                        buttonSaveText: "Delete"
                    }
                }
            });

            // TODO
            $dialog.off();

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

        // TODO abstract ---------------------------------------

        // Initialize operations in headerbar
        initOperations: function(){
            this.initOperationsLeft();
            this.initOperationsRight();
        },

        // Initializes the operations on the top (...)
        initOperationsRight:function(){
            $operationsRight = $('#headerbar-mid-right');
            $operationsRight.empty();

        },

        // Initializes the operations on the top (delete, export)
        initOperationsLeft:function(){

            $operationsLeft = $('#headerbar-mid-left');
            $operationsLeft.empty();

            var $addButton = this.templates.addButton('Add');
            $operationsLeft.append($addButton);


            // TODO leaving view scope?
            $('#headerbar-mid-left').on('click', '#addButton', function(){
                this.removeHeaderbarEvents();
                Router.navigate('settings/translate/add');
            }.bind(this));
        },


        removeHeaderbarEvents: function() {
            $('#headerbar-mid-right').off();
            $('#headerbar-mid-left').off();
            console.log("removed headerbar event - package list");
        },

        // Template for smaller components (button, ...)
        templates: {
            addButton: function(text){
                return '<div id="addButton" class="pull-left pointer"><span class="icon-add pull-left block"></span><span class="m-left-5 bold pull-left m-top-2 block">'+text+'</span></div>';
            }
        }

        // TODO abstract end ---------------------------------------
    });
});
