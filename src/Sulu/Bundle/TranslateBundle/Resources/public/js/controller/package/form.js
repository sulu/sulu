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
    'parsley',
    'sulutranslate/model/package',
    'sulutranslate/model/catalogue'
], function ($, Backbone, Router, Parsley, Package, Catalogue) {

    'use strict';

    var translatePackage;
    var cataloguesToDelete;
    var dataGrid;
    var $dialog;

    return Backbone.View.extend({

        events: {
            'submit #catalogue-form': 'submitForm',
            'click .icon-remove': 'deleteRow',
            'click .addRow': 'addRow'
        },

        initialize: function () {
            this.initOperationsRight();
            this.render();
        },

        getTabs: function (id) {
            //TODO Simplify this task for bundle developer?
            var cssId = id || 'new';

            // TODO translate
            var navigation = {
                'title': 'Package',
                'header': {
                    'title': 'Package'
                },
                'hasSub': 'true',
                //TODO id mandatory?
                'sub': {
                    'items': []
                }
            };

            if (!!id) {
                navigation.sub.items.push({
                    'title': 'Details',
                    'action': 'settings/translate/edit:' + cssId + '/details',
                    'hasSub': false,
                    'type': 'content',
                    'id': 'translate-package-details-' + cssId
                });
            }

            navigation.sub.items.push({
                'title': 'Settings',
                'action': 'settings/translate/edit:' + cssId + '/settings',
                'hasSub': false,
                'type': 'content',
                'id': 'translate-package-settings-' + cssId
            });

            return navigation;
        },

        render: function () {

            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/translate/template/catalogue/form'], function (Template) {
                var template;

                cataloguesToDelete = new Array();

                if (!this.options.id) {
                    translatePackage = new Package();
                    template = _.template(Template, {name: '', locale: '', catalogues: []});
                    var catalogues = this.getArrayFromCatalogues(translatePackage.get('catalogues').models);
                    this.$el.html(template);
                    this.initializeCatalogueList(catalogues);
                } else {
                    translatePackage = new Package({id: this.options.id});
                    translatePackage.fetch({
                        success: function (translatePackage) {
                            template = _.template(Template, translatePackage.toJSON());
                            var catalogues = this.getArrayFromCatalogues(translatePackage.get('catalogues').models);
                            this.$el.html(template);
                            this.initializeCatalogueList(catalogues);
                        }.bind(this)
                    });
                }



                App.Navigation.trigger('navigation:item:column:show', {
                    data: this.getTabs(translatePackage.get('id'))
                });
            }.bind(this));
        },

        initValidation: function() {
            this.$form = this.$('form[data-validate="parsley"]');
            this.initParsley();
        },

        initParsley: function() {
            this.$form.parsley({validationMinlength: 0});
        },

        getArrayFromCatalogues: function (models) {

            var data = new Array();

            $.each(models, function (model) {
                data.push(models[model].attributes);
            });

            return data;
        },

        submitForm: function (event) {

            var that = this;

            event.preventDefault();

            translatePackage.set({name: this.$('#name').val()});

            var rows = $('#catalogues tbody tr');


            // create catalogues if necessary and add them

            for (var i = 1; i <= rows.length; i++) {

                var id = $(rows[i - 1]).data('id');
                var locale = $('#catalogues tbody tr:nth-child(' + i + ') td:nth-child(2) input').val();
                if (locale != "") {
                    var catalogue;

                    if (id) {
                        catalogue = translatePackage.get('catalogues').get(id);
                    } else {
                        catalogue = new Catalogue();
                        translatePackage.get('catalogues').add(catalogue);
                    }
                    catalogue.set({'locale': locale});
                }
            }



            // send delete request for models which should be deleted

            cataloguesToDelete.forEach(function (id) {
                var model = translatePackage.get('catalogues').get(id);
                model.destroy({
                    success: function () {
                        console.log("deleted model");
                    }
                });
            });


            if (this.$form.parsley('validate')) {
                translatePackage.save(null, {
                    success: function() {
                        that.undelegateEvents();
                        dataGrid.data('Husky.Ui.DataGrid').off();
                        Router.navigate('settings/translate');
                    }
                });
            }
        },

        initializeCatalogueList: function (data) {

            this.initializeDialog();

            require(['text!sulutranslate/templates/package/table-row.html'], function (RowTemplate) {
                dataGrid = $('#catalogues').huskyDataGrid({
                    pagination: false,
                    showPages: 6,
                    pageSize: 4,
                    selectItemType: 'radio',
                    template: {
                        row: RowTemplate
                    },
                    data: {
                        items: data
                    }
                });

                $('#addCatalogueRow').on('click', function () {
                    dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:add', { id: '', locale: '', translations: [] });
                    this.$form.parsley('addItem', '#catalogues table tr:last input[type="text"]');
                }.bind(this));

                $('#catalogues').on('click', '.remove-row > span', function (event) {

                    $dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
                        template: {
                            content: '<h3><%= title %></h3><p><%= content %></p>',
                            footer: '<button class="btn btn-black closeButton"><%= buttonCancelText %></button><button class="btn btn-black agreeButton"><%= buttonSaveText %></button>',
                            header: '<button type="button" class="close">×</button>'
                        },
                        data: {
                            content: {
                                title: "Warning",
                                content: "Do you really want to delete this entry?"
                            },
                            footer: {
                                buttonCancelText: "No",
                                buttonSaveText: "Yes"
                            }
                        }

                    });

                    // TODO - Event Problem
                    $dialog.off();

                    $dialog.on('click', '.closeButton', function() {
                        $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
                    });


                    $dialog.on('click', '.agreeButton', function() {
                        $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
                        dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:remove', event);
                        var id = $(event.currentTarget).parent().parent().data('id');

                        if(id) {
                            console.log(id, "element id to delete");
                            cataloguesToDelete.push(id);
                        }
                    });

                });

                this.initValidation();


            }.bind(this));
        },

        initializeDialog: function(){
           $dialog = $('#dialog').huskyDialog({
               backdrop: true,
               width: '800px'
           });

            console.log("dialog init!");
        },

        initOperationsRight:function(){

            var $optionsRight = $('#headerbar-mid-right');
            $optionsRight.empty();
            $optionsRight.append(this.template.button('Save', ''));

        },

        template: {
            button: function(text, route) {
                return '<a class="btn" href="'+route+'">'+text+'</a>';
            }
        }

    });
});
