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
    'sulutranslate/model/catalogue'
], function ($, Backbone, Router, Code, Translations, Translation, Catalogue) {

    'use strict';

    var translations;
    var catalogue;
    var updatedTranslations;

    return Backbone.View.extend({

        events: {
            'submit #codes-form': 'submitForm',
            'click .addCode': 'addRowForNewCode',
            'click .icon-remove': 'removeRowAndModel',
            'click .form-element[readonly]': 'unlockFormElement'
        },

        initialize: function () {

            this.render();
        },

        render: function () {



            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/translate/template/translation/form'], function (Template) {

                var translateCatalogueId = this.options.id;

                catalogue = new Catalogue({id: translateCatalogueId});
//                console.log(translateCatalogueId, 'render: options id 1');

                // load translations only with a valid catalogue
                catalogue.fetch({
                    success: function(){
//                        console.log(translateCatalogueId, 'render: options id 2');

                        this.loadTranslations(Template, translateCatalogueId);


//                        console.log(catalogue.toJSON(), 'render: catalogue loaded');
                    }.bind(this)
                });

            }.bind(this));
        },

        loadTranslations: function(Template, translateCatalogueId){

            translations = new Translations({translateCatalogueId: translateCatalogueId});

            //console.log(translateCatalogueId, 'load translations: options id 3');

            translations.fetch({
                success:function(){
                    var template = _.template(Template, {translations: translations.toJSON(),catalogue: catalogue.toJSON()});
                    this.$el.html(template);
//                    console.log('load translations: template filled');
                }.bind(this)
            });
        },

        addRowForNewCode: function(event) {
            var sectionId = $(event.currentTarget).data('target-element');
            var $lastTableRow = $('#' + sectionId + ' tbody:last-child');
            $lastTableRow.append(this.templates.rowTemplate());
        },

        removeRowAndModel: function (event) {

            // TODO - API methode gibts noch nicht

            var $tableRow = $(event.currentTarget).parent().parent().parent();
            var translationId = $tableRow.data('id');

            console.log(translationId,'translation id');

            $tableRow.remove();

            if(!!translationId) {
//                var translation = translations.get(translationId);
                var codeId = translations.get(translationId).get('code')['id'];
                var code = new Code({id: codeId});
                console.log(code);

                code.destroy({
                    success: function () {
                        console.log("remove: deleted code and translation");
                    }
                });

                // TODO delete codes -> patch?

//                translation.destroy({
//                    success: function () {
//                        console.log("remove: deleted translation");
//                    }
//                });
            }
        },

        // TODO fields by default editable?
        unlockFormElement: function(event){
            var $element = $(event.currentTarget);
            $($element).prop('readonly', false);

        },

        submitForm: function (event) {

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
        },

        templates: {

            rowTemplate: function () {
                return [
                    '<tr>',
                        '<td class="grid-col-4">',
                            '<textarea class="form-element"></textarea>',
                        '</td>',
                        '<td class="grid-col-4">',
                            '<textarea class="form-element vertical"></textarea>',
                            '<small>[Max. 128 chars]</small>',
                        '</td>',
                        '<td class="grid-col-4">',
                            '<p>[Lorem Ipsum dolor set]</p>',
                        '</td>',
                    '</tr>'].join('')
            }
        }
    });
});
