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
    'sulutranslate/model/translation'
], function ($, Backbone, Router, Code, Codes, Translation) {

    'use strict';

    var codes;

    return Backbone.View.extend({

        events: {

        },

        initialize: function () {
            this.render();

        },

        render: function () {

            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/translate/template/code/form'], function (Template) {

                var template;
                var translatePackageId = this.options.id;
                var translateCatalogueId = 96; // TODO catalogue id

                // collection
                codes = new Codes([], {translateCatalogueId: translateCatalogueId});
                codes.fetch({
                    success: function () {
                        template = _.template(Template, {codes: codes.toJSON()});
                        this.$el.html(template);
                        this.initializeCustomEvents();
                    }.bind(this)
                });

            }.bind(this));
        },


        initializeCustomEvents: function () {

            $('.addCode').click(function(event) {
                var sectionId = $(event.currentTarget).data('target-element');
                var $lastTableRow = $('#' + sectionId + ' tbody:last-child');
                $lastTableRow.append(this.templates.rowTemplate());
            }.bind(this));

            $('.icon-remove').click(function(event){
                var $tableRow = $(event.currentTarget).parent().parent().parent();
                var codeId = $tableRow.data('id');
                $tableRow.remove();
                this.removeModel(codeId);
            }.bind(this));

        },


        removeModel: function (id) {
            if(!!id) {
                console.log(codes.toJSON());
                console.log(id);
                var model = codes.get(id);
                console.log(model);
                model.destroy({
                    success: function () {
                        console.log("deleted model");
                    }
                });
                console.log(codes.toJSON());
            }
        },

        submitForm: function (event) {


        },

        templates: {

            rowTemplate: function () {
                return [
                    '<tr>',
                        '<td class="grid-col-4">',
                            '<input class="form-element" value=""/>',
                            '<small>[Max. 128 chars]</small>',
                        '</td>',
                        '<td class="grid-col-4">',
                            '<input class="form-element" value=""/>',
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
