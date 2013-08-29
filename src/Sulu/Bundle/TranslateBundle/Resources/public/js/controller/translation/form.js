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

                console.log(translateCatalogueId, 'render: options view');

                // load translations only with a valid catalogue
                catalogue.fetch({
                    success: function(){
                        //this.loadTranslations(Template, translateCatalogueId);
                        console.log(catalogue.toJSON(), 'render: catalogue loaded');
                    }.bind(this)
                });

            }.bind(this));
        },

        loadTranslations: function(Template, translateCatalogueId){

            translations = new Translations([], {translateCatalogueId: translateCatalogueId});
            translations.fetch({
                success:function(){
                    var template = _.template(Template, {translations: translations.toJSON(),catalogue: catalogue.toJSON()});
                    this.$el.html(template);
                    console.log('load translations: template filled');
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
            $tableRow.remove();

            if(!!translationId) {
                var translation = translations.get(translationId);
                translation.destroy({
                    success: function () {
                        console.log("remove: deleted translation");
                    }
                });
            }
        },

        // TODO fields by default editable?
        unlockFormElement: function(event){
            var $element = $(event.currentTarget);
            $($element).prop('readonly', false);

        },

        submitForm: function (event) {

            event.preventDefault();

            var formId = $(event.currentTarget).attr('id');
            var $rows = $('#' + formId + ' table tbody tr');

            _.each($rows, function($row){

                var id = $($row).data('id');
                var values = $($row).find('textarea');

                var code;
                var translation;

                console.log(id, 'submit: translation id');

                updatedTranslations = new Array();

                if(!!id) {
                    translation = translations.get(id);
                    var currentValue = translation.get('value');

                    // did the value change
                    if(!_.isEqual(currentValue, values[0].value)) {
                        translation.set('value',values[0].value);
                        updatedTranslations.push(translation);
                        console.log(updatedTranslations, 'submit: updated array of changed elements');

                    }

                    console.log(translation.toJSON(), 'submit: existing translation');
                    console.log(code.toJSON(), 'submit: existing code');

                } else {

                    // new translation and new code
                    code = new Code();
                    code.set('code',values[0].value);

                    translation = new Translation();
                    translation.set('value',values[1].value);

                    translation.set('code', code);

                    console.log(translation.toJSON(), 'submit: new translation');
                    console.log(code.toJSON(), 'submit: new code');

                    updatedTranslations.push(translation);

                    console.log(updatedTranslations, 'submit: updated array of changed elements');

                }

            });

            translations.save(updatedTranslations);

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
