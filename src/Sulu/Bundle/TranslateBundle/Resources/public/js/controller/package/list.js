/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app', 'router', 'backbone', 'husky', 'sulutranslate/model/package'],
    function(App, Router, Backbone, Husky, Package) {

        'use strict';

        var $dialog, packages, $operationsRight, $operationsLeft, packagesDatagrid;

        return Backbone.View.extend({
            initialize: function() {
                this.initOperations();
                this.render();
            },

            render: function() {

                require(['text!/translate/template/package/list'], function(Template) {
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

                packagesDatagrid = $('#packageList').huskyDataGrid({
                    // FIXME use list function with fields
                    url: '/translate/api/packages',
                    pagination: false,
                    showPages: 6,
                    pageSize: 4,
                    selectItemType: 'checkbox',
                    //removeRow: true,
                    tableHead: [
                        {content: 'Title'}
                        //{content: ''}
                    ],
                    excludeFields: ['id']
                });

                packagesDatagrid.data('Husky.Ui.DataGrid').on('data-grid:item:click', function(item) {
                    packagesDatagrid.data('Husky.Ui.DataGrid').off();
                    this.removeHeaderbarEvents();
                    Router.navigate('settings/translate/edit:' + item + '/settings');
                }.bind(this));

                this.$el.on('click', '.dropdown-toggle', function() {
                    $('.dropdown-menu').toggle();
                });

                this.$el.on('click', '#edit-remove', function() {
                    $('.dropdown-menu').hide();
                    this.initDialogBoxRemoveMultiple(packagesDatagrid.data('Husky.Ui.DataGrid').selectedItemIds);
                }.bind(this));
            },

            // fills dialogbox
            initDialogBoxRemoveMultiple: function(ids) {

                $dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
                    data: {
                        content: {
                            title: "Warning",
                            content: "Do you really want to delete the selected packages? All data is going to be lost."
                        },
                        footer: {
                            buttonCancelText: "Cancel",
                            buttonSaveText: "Delete"
                        }
                    }
                });

                // TODO
                $dialog.off();

                $dialog.on('click', '.closeButton', function() {
                    $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
                });

                $dialog.on('click', '.saveButton', function() {
                    ids.forEach(function(id) {
                        Backbone.Relational.store.reset();
                        var pkg = new Package({id: id});
                        pkg.destroy({
                            success: function() {
                                console.log('deleted model');
                                Router.navigate('settings/translate');
                                packagesDatagrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:remove', id);
                            }
                        });
                    }.bind(this));

                    $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
                });
            },

            // TODO abstract ---------------------------------------

            // Initialize operations in headerbar
            initOperations: function() {
                this.initOperationsLeft();
                this.initOperationsRight();
            },

            // Initializes the operations on the top (...)
            initOperationsRight: function() {
                $operationsRight = $('#headerbar-mid-right');
                $operationsRight.empty();

            },

            // Initializes the operations on the top (delete, export)
            initOperationsLeft: function() {

                $operationsLeft = $('#headerbar-mid-left');
                $operationsLeft.empty();

                var $addButton = this.templates.addButton('Add');
                $operationsLeft.append($addButton);


                // TODO leaving view scope?
                $operationsLeft.on('click', '#addButton', function() {
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
                addButton: function(text) {
                    return '<div id="addButton" class="pull-left pointer"><span class="icon-add pull-left block"></span><span class="m-left-5 bold pull-left m-top-2 block">' + text + '</span></div>';
                }
            }

            // TODO abstract end ---------------------------------------
        });
    });
