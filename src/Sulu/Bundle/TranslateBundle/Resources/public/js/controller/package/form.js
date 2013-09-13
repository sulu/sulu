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
], function($, Backbone, Router, Parsley, Package, Catalogue) {

    'use strict';

    var translatePackage;
    var cataloguesToDelete;
    var dataGrid;
    var $dialog;

    var $operationsLeft;
    var $operationsRight;

    return Backbone.View.extend({

        events: {
            'click .icon-remove': 'deleteRow',
            'click .add-row': 'addRow'
//            'click #saveButton' : 'submitForm',
//            'click #deleteButton': 'deletePackage'
        },

        initialize: function() {
            this.initOperations();
            this.render();
        },

        // Navigation
        getTabs: function(id) {
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

        // Renders the form with all its components
        render: function() {

            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/translate/template/catalogue/form'], function(Template) {
                var template;

                cataloguesToDelete = [];

                if (!this.options.id) {
                    translatePackage = new Package();
                    template = _.template(Template, {name: '', locale: '', catalogues: []});
                    var catalogues = this.getArrayFromCatalogues(translatePackage.get('catalogues').models);
                    this.$el.html(template);
                    this.initializeCatalogueList(catalogues);
                } else {
                    translatePackage = new Package({id: this.options.id});
                    translatePackage.fetch({
                        success: function(translatePackage) {
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


                this.initializeDialog();

            }.bind(this));
        },

        initValidation: function() {
            this.$form = this.$('form[data-validate="parsley"]');
            this.initParsley();
        },

        initParsley: function() {
            this.$form.parsley({validationMinlength: 0});
        },

        getArrayFromCatalogues: function(models) {

            var data = [];

            $.each(models, function(model) {
                data.push(models[model].attributes);
            });

            return data;
        },

        // Submits the form (includes deletes of catalogues and save of the package)
        submitForm: function(event) {

            var that = this;

            event.preventDefault();

            translatePackage.set({name: this.$('#name').val()});

            var rows = $('#catalogues').find('tbody tr');

            // create catalogues if necessary and add them

            for (var i = 1; i <= rows.length; i++) {

                var row = $(rows[i-1]),
                    id = row.data('id'),
                    isDefault = row.find('input[type=radio]').is(':checked'),
                    locale = row.find('input.input-locale').val();

                if (locale != "") {
                    var catalogue;

                    if (id) {
                        catalogue = translatePackage.get('catalogues').get(id);
                    } else {
                        catalogue = new Catalogue();
                        translatePackage.get('catalogues').add(catalogue);
                    }
                    catalogue.set({
                        locale: locale,
                        isDefault: isDefault
                    });
                }
            }


            // send delete request for models which should be deleted
            cataloguesToDelete.forEach(function(id) {
                var model = translatePackage.get('catalogues').get(id);
                model.destroy({
                    success: function() {
                        console.log("deleted model");
                    }
                });
            });

            if (this.$form.parsley('validate')) {
                translatePackage.save(null, {
                    success: function() {
                        that.undelegateEvents();
                        that.removeHeaderbarEvents();
                        Router.navigate('settings/translate');
                    }
                });
            } else {
                $('#saveButton').removeClass('loading');
            }
        },

        // Initializes the catalogue list
        initializeCatalogueList: function(data) {

            require(['text!sulutranslate/templates/package/table-row.html'], function(RowTemplate) {
                var $catalogues = $('#catalogues');
                dataGrid = $catalogues.huskyDataGrid({
                    pagination: false,
                    showPages: 6,
                    pageSize: 4,
                    className: 'catalogueList',

                    tableHead: [
                        {
                            content: 'Default Language',
                            width: '15%'
                        },
                        {content: 'Language'},
                        {content: ''}
                    ],
                    template: {
                        row: RowTemplate
                    },
                    data: {
                        items: data
                    }
                });

                // add row
                $('#add-catalogue-row').on('click', function() {
                    dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:add', { id: '', isDefault: false, locale: '', translations: [] });
                    this.$form.parsley('addItem', '#catalogues table tr:last input[type="text"]');
                }.bind(this));

                // remove row
                $catalogues.on('click', '.remove-row > span', function(event) {

                    dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:remove', event);
                    var id = $(event.currentTarget).parent().parent().data('id');

                    if (id) {
                        console.log(id, "element id to delete");
                        cataloguesToDelete.push(id);
                    }

                });

                // init radio buttons - default language
                $('#catalogues').on('click', 'input[type=radio]', function(event) {
                    var element = $(event.currentTarget),
                        id = element.closest('tr').data('id'),
                        radios = $('#catalogues').find('input[type=radio]');

                    _.each(radios, function(e){
                        $(e).removeClass('is-selected').prop('checked', false);
                    });

                    element.addClass('is-selected').prop('checked', true);

                }.bind(this));


                this.initValidation();

            }.bind(this));
        },

        // Initializes the dialog
        initializeDialog: function() {
            $dialog = $('#dialog').huskyDialog({
                backdrop: true,
                width: '650px'
            });
        },

        deletePackage: function() {

            $dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
                data: {
                    content: {
                        title: "Be careful!",
                        content: "<p>The operation you are about to do will delete data.<br />This is not undoable!</p><p>Please think about it and accept or decline</p>"
                    },
                    footer: {
                        buttonCancelText: "Don't do it",
                        buttonSubmitText: "Do it, I understand"
                    }
                }
            });

            // TODO - Event Problem
            $dialog.off();

            $dialog.on('click', '.dialogButtonCancel', function() {
                this.initOperations();
                $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
            }.bind(this));


            // TODO naming buttons dialog
            $dialog.on('click', '.dialogButtonSubmit', function() {
                $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');

                translatePackage.destroy({
                    success: function() {
                        this.removeHeaderbarEvents();
                        Router.navigate('settings/translate');
                    }.bind(this)
                });
            }.bind(this));

        },

        // TODO abstract ---------------------------------------

        // Initialize operations in headerbar
        initOperations: function() {

            this.removeHeaderbarEvents();
            $('#headerbar-mid').off();


            this.initOperationsLeft();
            this.initOperationsRight();
        },

        // Initializes the operations on the top (delete,export)
        initOperationsRight: function() {
            $operationsRight = $('#headerbar-mid-right');
            $operationsRight.empty();

            var $deleteButton = this.templates.deleteButton('Delete');
            $operationsRight.append($deleteButton);

            $operationsRight.on('click', '#deleteButton', function(event) {

                var deleteButton = event.currentTarget;

                if (!$(deleteButton).hasClass('loading')) {
                    $(deleteButton).addClass('loading');


                    $('#headerbar-mid-left').find('#saveButton').hide();
                }
                this.deletePackage();
            }.bind(this));

        },

        // Initializes the operations on the top (save)
        initOperationsLeft: function() {

            $operationsLeft = $('#headerbar-mid-left');
            $operationsLeft.empty();

            var $saveButton = this.templates.saveButton('Save', '');
            $operationsLeft.append($saveButton);

            // TODO leaving view scope?
            $operationsLeft.on('click', '#saveButton', function(event) {
                var button = event.currentTarget;

                if (!$(button).hasClass('loading')) {
                    $(button).addClass('loading');
                }

                this.submitForm(event);
            }.bind(this));

        },

        removeHeaderbarEvents: function() {
            $('#headerbar-mid-right').off();
            $('#headerbar-mid-left').off();
        },

        // Template for smaller components (button, ...)
        templates: {

            saveButton: function(text) {
                return $('<div id="saveButton" class="pull-left pointer"><div class="loading-content"><span class="icon-caution pull-left block"></span><span class="m-left-5 bold pull-left m-top-2 block">' + text + '</span></div></div>');
            },

            deleteButton: function(text) {
                return '<div id="deleteButton" class="pull-right pointer"><div class="loading-content"><span class="icon-circle-remove pull-left block"></span><span class="m-left-5 bold pull-left m-top-2 block">' + text + '</span></div></div>';
            }
        }

        // TODO abstract end ---------------------------------------

    });
});
