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
    'sulutranslate/model/code',
    'sulutranslate/collection/translations',
    'sulutranslate/model/translation',
    'sulutranslate/model/catalogue',
    'sulutranslate/collection/catalogues'
], function ($, Backbone, Router, Code, Translations, Translation, Catalogue, Catalogues) {

    'use strict';

    var translations;

    var updatedTranslations;
    var codesToDelete;

    var selectedCatalogue;
    var catalogues;

    var $operationsLeft;
    var $operationsRight;

    return Backbone.View.extend({

        events: {
            'submit #codes-form': 'submitForm',
            'click .addCode': 'addRowForNewCode',
            'click .icon-remove': 'removeRowAndModel',
            'click .form-element[readonly]': 'unlockFormElement'
        },

        initialize: function () {
            codesToDelete = new Array();

            this.initOperations();
            this.render();
        },

        // gets a list of catalogues to the package
        render: function () {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/translate/template/translation/form'], function (Template) {

                var packageId = this.options.id;

                catalogues = new Catalogues({
                    packageId: packageId,
                    fields: 'id,locale'
                })

                catalogues.fetch({
                    success: function(){
                       selectedCatalogue = catalogues.toJSON()[0];
                       this.loadTranslations(Template);

                    }.bind(this)
                });

            }.bind(this));
        },

        // loads translations and inits the selectbox with the change event
        loadTranslations: function(Template){

            translations = new Translations({translateCatalogueId: selectedCatalogue.id});
            translations.fetch({
                success:function(){

                    var template = _.template(Template, {translations: translations.toJSON(),catalogue: selectedCatalogue});
                    this.$el.html(template);

                    var $selectCatalogue = $('#languageCatalogue').huskySelect({
                        selected: {id: selectedCatalogue.id},
                        data: catalogues.toJSON(),
                        valueName: 'locale'
                    });

                    // TODO event of huky when implemented
                    $selectCatalogue.change(function(){

                        selectedCatalogue = null;

                        var selectedId = $selectCatalogue.find(":selected").val();

                        _.each(catalogues.toJSON(), function(cat){
                            if(parseInt(cat.id) === parseInt(selectedId)) {
                                selectedCatalogue = cat;
                            }
                        });

                        if(selectedCatalogue === null ) {
                            console.log("selected catalogue not found!");
                        } else {
                            this.loadTranslations(Template);
                        }

                    }.bind(this));


                }.bind(this)
            });
        },

        // removes a row
        removeRowAndModel: function (event) {

            var $tableRow = $(event.currentTarget).parent().parent();

            console.log($(event.currentTarget).parent().parent(), "tablerow");

            var translationId = $tableRow.data('id');

            console.log(translationId,'translation id');

            $tableRow.remove();

            if(!!translationId) {
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
        },

        unlockFormElement: function(event){
            var $element = $(event.currentTarget);
            $($element).prop('readonly', false);

        },

        submitForm: function (event) {

            // TODO fettes TODO
            console.log("save");

            event.preventDefault();
            updatedTranslations = new Array();

            var formId = $(event.currentTarget).attr('id');
            var $rows = $('#' + formId + ' table tbody tr');

            _.each($rows, function($row){

                var id = $($row).data('id');
                var values = $($row).find('textarea');

                var code;
                var translation;

                if(!!id) {
                    translation = translations.get(id);
                    var currentValue = translation.get('value');

                    // did the value change
                    if(currentValue != values[0].value) {
                        translation.set('value',values[0].value);
                        updatedTranslations.push(translation);
                        console.log(updatedTranslations, 'submit: updated array of changed elements');

                    }

                } else {

                    // new translation and new code
                    code = new Code();
                    code.set('code',values[0].value);

                    translation = new Translation();
                    translation.set('value',values[1].value);

                    translation.set('code', code);
                    updatedTranslations.push(translation);

                }

            });

            if(updatedTranslations.length > 0 ) {
                console.log(updatedTranslations, 'items to update');
                translations.save(updatedTranslations);
            }

            if(codesToDelete.length > 0) {
                codesToDelete.forEach(function(code) {
                    code.destroy({
                        success: function () {
                        console.log("remove: deleted translation");
                         }
                    });
                });
            }

            //Router.navigate('settings/translate');

        },

        // TODO abstract ---------------------------------------

        // Initialize operations in headerbar
        initOperations: function(){
            this.initOperationsLeft();
            this.initOperationsRight();
        },

        // Initializes the operations on the top (save)
        initOperationsRight:function(){
            $operationsRight = $('#headerbar-mid-right');
            $operationsRight.empty();
        },

        // Initializes the operations on the top (save)
        initOperationsLeft:function(){

            $operationsLeft = $('#headerbar-mid-left');
            $operationsLeft.empty();

            var $saveButton = this.templates.saveButton('Save', '');
            $operationsLeft.append($saveButton);


            // TODO leaving view scope?
            $('#headerbar-mid-left').on('click', '#saveButton', function(){
                this.submitForm(event);
            }.bind(this));
        },

        // TODO abstract end ---------------------------------------

        // Template for smaller components (button, ...)
        templates: {

            saveButton: function(text, route){
                return '<div id="saveButton" class="pull-left pointer"><span class="icon-circle-ok pull-left block"></span><span class="m-left-5 bold pull-left m-top-2 block">'+text+'</span></div>';
            },

            rowTemplate: function () {
                return [
                    '<tr>',
                        '<td class="grid-col-3">',
                            '<input class="form-element"/>',
                        '</td>',
                        '<td class="grid-col-4">',
                            '<textarea class="form-element vertical"></textarea>',
                        '</td>',
                        '<td class="grid-col-4">',
                            '<p class="grey"></p>',
                        '</td>',
                        '<td class="grid-col-1">',
                            '<p class="icon-remove m-left-5"></p>',
                        '</td>',
                    '</tr>',
                    '<tr class="additionalOptions">',
                        '<td>',
                            '<div class="grid-row">',
                                '<div class="grid-col-3">',
                                    '<span>Length</span>',
                                    '<input class="form-element" value=""/>',
                                '</div>',
                                '<div class="grid-col-2 m-top-35"><input type="checkbox" class="custom-checkbox"><span class="custom-checkbox-icon"></span><span class="m-left-5">Frontend</span></div>',
                                '<div class="grid-col-2  m-top-35"><input type="checkbox" class="custom-checkbox"><span class="custom-checkbox-icon"></span><span class="m-left-5">Backend</span></div>',
                            '</div>',
                        '</td>',
                    '</tr>'].join('')
            }
        }
    });
});
