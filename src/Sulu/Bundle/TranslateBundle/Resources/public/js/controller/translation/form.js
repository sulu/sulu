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
    'sulutranslate/model/code',
    'sulutranslate/collection/translations',
    'sulutranslate/model/translation',
    'sulutranslate/model/catalogue',
    'sulutranslate/collection/catalogues',
    'sulutranslate/model/package'
], function($, Backbone, Router, Parsley, Code, Translations, Translation, Catalogue, Catalogues, Package) {

    'use strict';

    var translations,
        updatedTranslations,
        codesToDelete,
        selectedCatalogue,
        catalogues,
        $operationsLeft,
        $operationsRight,
        $form,
        $dialog,
        packageModel;


    return Backbone.View.extend({

        events: {
            'click .addCode': 'addRowForNewCode',
            'click .icon-remove': 'removeRowAndModel',
            'click .form-element[readonly]': 'unlockFormElement'
//            'click #saveButton': 'submitForm',
//            'click #deleteButton': 'deleteCatalogue'
        },

        initialize: function() {
            codesToDelete = new Array();

            this.initOperations();
            this.render();

        },

        initValidation: function() {
            $form = this.$('form[data-validate="parsley"]');
            $form.parsley({validationMinlength: 0});
        },

        // gets a list of catalogues to the package
        render: function() {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/translate/template/translation/form'], function(Template) {

                var packageId = this.options.id;
                packageModel = new Package({id: packageId});

                catalogues = new Catalogues({
                    packageId: packageId,
                    fields: 'id,locale'
                });

                packageModel.fetch({
                    success: function(){

                        catalogues.fetch({
                            success: function() {
                                selectedCatalogue = catalogues.toJSON()[0];
                                this.loadTranslations(Template);

                            }.bind(this)
                        });
                    }.bind(this)
                });

                this.initializeDialog();

            }.bind(this));
        },

        // loads translations and inits the selectbox with the change event
        loadTranslations: function(Template) {

            translations = new Translations({translateCatalogueId: selectedCatalogue.id});
            translations.fetch({
                success: function() {

                    var template = _.template(Template, {translations: translations.toJSON(), catalogue: selectedCatalogue, package: packageModel.toJSON()});
                    this.$el.html(template);

                    var $selectCatalogue = $('#languageCatalogue').huskySelect({
                        selected: {id: selectedCatalogue.id},
                        data: catalogues.toJSON(),
                        valueName: 'locale'
                    });

                    this.autoHeightTextareas();
                    this.initValidation();

                    // TODO event of husky when implemented
                    $selectCatalogue.change(function() {

                        selectedCatalogue = null;
                        codesToDelete = new Array();

                        Backbone.Relational.store.reset();

                        var selectedId = $selectCatalogue.find(":selected").val();

                        _.each(catalogues.toJSON(), function(cat) {
                            if (parseInt(cat.id) === parseInt(selectedId)) {
                                selectedCatalogue = cat;
                            }
                        });

                        if (selectedCatalogue === null) {
                            console.log("selected catalogue not found!");
                        } else {
                            this.loadTranslations(Template);
                        }

                    }.bind(this));

                    this.initVisibilityOptions();

                }.bind(this)
            });
        },

        autoHeightTextareas: function(){

            var $textareas = $('#codes-form textarea');

            _.each($textareas, function($element){
                $($element).css('height',$element.scrollHeight);
            });

        },

        initVisibilityOptions: function() {

            $('.showOptions').on('click', function() {
                $(this).toggleClass('icon-arrow-right').toggleClass('icon-arrow-down');
                $(this).parent().parent().next('.additionalOptions').toggleClass('hidden');
            });

        },

        // removes a row
        removeRowAndModel: function(event) {

            var $tableRow = $(event.currentTarget).parent().parent();
            var translationId = $tableRow.data('id');

            console.log(translationId, 'translation id');

            $tableRow.next('.additionalOptions').remove();
            $tableRow.remove();

            if (!!translationId) {
                var codeId = translations.get(translationId).get('code')['id'];
                var code = new Code({id: codeId});
                codesToDelete.push(code);
            }
        },

        // appends a new row to the table
        addRowForNewCode: function(event) {

            var sectionId = $(event.currentTarget).data('target-element');
            var $lastTableRow = $('#' + sectionId + ' tbody:last-child');
            $lastTableRow.append(this.templates.rowTemplate());

            $form.parsley('addItem', $('#section1 tbody tr:last').prev().find('input.inputCode'));
        },

        unlockFormElement: function(event) {
            var $element = $(event.currentTarget);
            $($element).prop('readonly', false);

        },

        submitForm: function() {

            event.preventDefault();
            console.log($form.parsley('validate'), "parsley form validation");
            if ($form.parsley('validate')) {
                updatedTranslations = new Array();
                var $rows = $('#codes-form table tbody tr');

                for (var i = 0; i < $rows.length;) {

                    var $translation = $rows[i];
                    var $options = $rows[i + 1];
                    var id = $($rows[i]).data('id');

                    var newCode    = $($translation).find('.inputCode').val();
                    var newTranslation = $($translation).find('.textareaTranslation').val();

                    var newLength = $($options).find('.inputLength').val();
                    var newFrontend = $($options).find('.checkboxFrontend').is(':checked');
                    var newBackend = $($options).find('.checkboxBackend').is(':checked');

                    var translationModel = null;

                    if (!!id) {

                        translationModel = translations.get(id);

                        var currentCode = translationModel.get('code').code;
                        var currentTranslation = translationModel.get('value');
                        var currentLength = translationModel.get('code').length;
                        var currentFrontend = translationModel.get('code').frontend;
                        var currentBackend = translationModel.get('code').backend;


                        if (newCode != currentCode ||
                            newTranslation != currentTranslation ||
                            newLength != currentLength ||
                            newFrontend != currentFrontend ||
                            newBackend != currentBackend) {

                            translationModel.get('code').code = newCode;
                            translationModel.set('value', newTranslation);
                            translationModel.get('code').length = newLength;
                            translationModel.get('code').frontend = newFrontend;
                            translationModel.get('code').backend = newBackend;

                            updatedTranslations.push(translationModel);
                        }

                    } else {

                        // new translation and new code
                        if (newCode != undefined && newCode != "") {

                            var codeModel = new Code();
                            codeModel.set('code', newCode);
                            codeModel.set('length', newLength);
                            codeModel.set('frontend', newFrontend);
                            codeModel.set('backend', newBackend);

                            translationModel = new Translation();
                            translationModel.set('value', newTranslation);

                            translationModel.set('code', codeModel);
                            updatedTranslations.push(translationModel);
                        } else {
                            //console.log("code missing");
                        }
                    }
                    i = i + 2;

                }

                if (updatedTranslations.length > 0) {
                    translations.save(updatedTranslations);
                }

                if (codesToDelete.length > 0) {
                    codesToDelete.forEach(function(code) {
                        code.destroy({
                            success: function() {
                                console.log("remove: deleted translation");
                            }
                        });
                    });
                }

                this.removeHeaderbarEvents();
                Router.navigate('settings/translate');
            }
        },

        deleteCatalogue: function() {


            var catalogue = catalogues.get(selectedCatalogue.id);

            $dialog.data('Husky.Ui.Dialog').trigger('dialog:show', {
                data: {
                    content: {
                        title: "Warning",
                        content: "Do you really want to delete this catalogue?"
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


            $dialog.on('click', '.saveButton', function() {
                this.removeHeaderbarEvents();
                $dialog.data('Husky.Ui.Dialog').trigger('dialog:hide');
                catalogue.destroy({
                    success: function () {
                        Router.navigate('settings/translate');
                    }
                });
            }.bind(this));



        },

        removeHeaderbarEvents: function() {
            $('#headerbar-mid-right').off();
            $('#headerbar-mid-left').off();
            console.log("removed headerbar event - translations");
        },

        // Initializes the dialog
        initializeDialog: function(){
            $dialog = $('#dialog').huskyDialog({
                backdrop: true,
                width: '800px'
            });
        },


        // TODO abstract ---------------------------------------

        // Initialize operations in headerbar
        initOperations: function() {
            this.removeHeaderbarEvents();
            this.initOperationsLeft();
            this.initOperationsRight();
        },

        // Initializes the operations on the top (save)
        initOperationsRight: function() {
            $operationsRight = $('#headerbar-mid-right');
            $operationsRight.empty();

            var $deleteButton = this.templates.deleteButton('Delete');
            $operationsRight.append($deleteButton);

            // TODO leaving view scope?
            $('#headerbar-mid-right').on('click', '#deleteButton', function() {
                this.deleteCatalogue();
            }.bind(this));
        },

        // Initializes the operations on the top (save)
        initOperationsLeft: function() {

            $operationsLeft = $('#headerbar-mid-left');
            $operationsLeft.empty();

            var $saveButton = this.templates.saveButton('Save', '');
            $operationsLeft.append($saveButton);


            // TODO leaving view scope?
            $('#headerbar-mid-left').on('click', '#saveButton', function() {
                this.submitForm(event);
            }.bind(this));
        },

        // TODO abstract end ---------------------------------------

        // Template for smaller components (button, ...)
        templates: {

            saveButton: function(text, route) {
                return '<div id="saveButton" class="pull-left pointer"><span class="icon-circle-ok pull-left block"></span><span class="m-left-5 bold pull-left m-top-2 block">' + text + '</span></div>';
            },

            deleteButton: function(text) {
                return '<div id="deleteButton" class="pull-right pointer"><span class="icon-circle-remove pull-left block"></span><span class="m-left-5 bold pull-left m-top-2 block">'+text+'</span></div>';
            },

            rowTemplate: function() {
                return [
                    '<tr>',
                        '<td width="20%">',
                            '<input class="form-element inputCode" value="" data-trigger="focusout" data-required="true"/>',
                        '</td>',
                        '<td width="37%">',
                            '<textarea class="form-element vertical textareaTranslation"></textarea>',
                        '</td>',
                        '<td width="37%">',
                            '<p class="grey"></p>',
                        '</td>',
                        '<td width="6%">',
                            '<p class="icon-remove m-left-5"></p>',
                        '</td>',
                    '</tr>',
                    '<tr class="additionalOptions">',
                        '<td colspan="4">',
                            '<div class="grid-row">',
                                '<div class="grid-col-3">',
                                    '<span>Length</span>',
                                    '<input class="form-element inputLength" value=""/>',
                            '   </div>',
                                '<div class="grid-col-2 m-top-35"><input type="checkbox" class="custom-checkbox checkboxFrontend"><span class="custom-checkbox-icon"></span><span class="m-left-5">Frontend</span></div>',
                                '<div class="grid-col-2  m-top-35"><input type="checkbox" class="custom-checkbox checkboxBackend"><span class="custom-checkbox-icon"></span><span class="m-left-5">Backend</span></div>',
                            '</div>',
                        '</td>',
                    '</tr>'].join('')
            }
        }
    });
});
