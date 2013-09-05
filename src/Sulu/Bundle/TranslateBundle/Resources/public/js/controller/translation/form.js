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
    var codesToDelete;

    return Backbone.View.extend({

        events: {
            'submit #codes-form': 'submitForm',
            'click .addCode': 'addRowForNewCode',
            'click .icon-remove': 'removeRowAndModel',
            'click .form-element[readonly]': 'unlockFormElement'
        },

        initialize: function () {
            codesToDelete = new Array();
            this.render();
        },

        render: function () {

            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/translate/template/translation/form'], function (Template) {

                var translateCatalogueId = this.options.id;
                catalogue = new Catalogue({id: translateCatalogueId});
                catalogue.fetch({
                    success: function(){
                        this.loadTranslations(Template, translateCatalogueId);
                    }.bind(this)
                });

            }.bind(this));
        },

        loadTranslations: function(Template, translateCatalogueId){

            translations = new Translations({translateCatalogueId: translateCatalogueId});

            translations.fetch({
                success:function(){
                    var template = _.template(Template, {translations: translations.toJSON(),catalogue: catalogue.toJSON()});
                    this.$el.html(template);
                }.bind(this)
            });
        },

        addRowForNewCode: function(event) {
            var sectionId = $(event.currentTarget).data('target-element');
            var $lastTableRow = $('#' + sectionId + ' tbody:last-child');
            $lastTableRow.append(this.templates.rowTemplate());
        },

        removeRowAndModel: function (event) {

            var $tableRow = $(event.currentTarget).parent().parent().parent();
            var translationId = $tableRow.data('id');

            console.log(translationId,'translation id');

            $tableRow.remove();

            if(!!translationId) {
                var codeId = translations.get(translationId).get('code')['id'];
                var code = new Code({id: codeId});
                codesToDelete.push(code);
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

            if(codesToDelete.length > 0) {
                codesToDelete.forEach(function(code) {
                    code.destroy({
                        success: function () {
                        console.log("remove: deleted translation");
                         }
                    });
                });
            }

            Router.navigate('settings/translate');
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
