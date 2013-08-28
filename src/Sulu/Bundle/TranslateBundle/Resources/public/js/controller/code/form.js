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
    'sulutranslate/collection/codes',
    'sulutranslate/model/translation',
    'sulutranslate/model/catalogue'
], function ($, Backbone, Router, Code, Codes, Translation, Catalogue) {

    'use strict';

    var codes;
    var catalogue;

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
            require(['text!/translate/template/code/form'], function (Template) {


                var translatePackageId = this.options.id;
                var translateCatalogueId = 96; // TODO catalogue id

                catalogue = new Catalogue({id: translateCatalogueId});
                catalogue.fetch({
                    success: function(){
                        console.log(catalogue.toJSON(), 'catalogue loaded');
                        this.loadCodes(Template, translateCatalogueId);
                    }.bind(this)
                });



            }.bind(this));
        },

        loadCodes: function(Template, translateCatalogueId){

            // collection
            codes = new Codes([], {translateCatalogueId: translateCatalogueId});
            codes.fetch({
                success: function () {
                    console.log(codes, 'codes loaded');
                    var template = _.template(Template, {codes: codes.toJSON(),catalogue: catalogue.toJSON()});
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
            var codeId = $tableRow.data('id');
            $tableRow.remove();

            if(!!codeId) {
                var model = codes.get(codeId);
                model.destroy({
                    success: function () {
                        console.log("deleted model");
                    }
                });
            }
        },

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

                var model;
                var translation;

                if(!!id) {
                    model = codes.get(id);
//                    console.log(values[0].value);

                } else {
                    model = new Code();
                    model.set('code',values[0].value);
                    // "backend":true,"frontend":true,"length":25,
                    console.log(values[1].value);
                    translation = new Translation();
                    translation.set('value',values[1].value);
                    translation.set('')

                    console.log(model.get('translations').add());

                }

                //console.log(model);


            });

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
