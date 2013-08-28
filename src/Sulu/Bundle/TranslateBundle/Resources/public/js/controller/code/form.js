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

                var translatePackageId =  this.options.id;
                var translateCatalogueId = 96; // TODO catalogue id

                // collection
                codes = new Codes([], {translatePackageId: translatePackageId,translateCatalogueId: translateCatalogueId});
                codes.fetch();

                //console.log(codes);

            }.bind(this));
        },

        submitForm: function (event) {

        }
    });
});
