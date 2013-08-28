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

    var codeModels;

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
                var translatePackageId =  this.options.id;
                var translateCatalogueId = 96; // TODO catalogue id

                // collection
                var codes = new Codes([], {translatePackageId: translatePackageId,translateCatalogueId: translateCatalogueId});
                codes.fetch({
                    success: function() {
                        template = _.template(Template, {codes: codes.toJSON()});
                        this.$el.html(template);

                    }.bind(this)
                });

            }.bind(this));
        },

        submitForm: function (event) {

        }
    });
});
