/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'backbone', 'sulutranslate/model/package'], function ($, Backbone, Package) {

    'use strict';

    return Backbone.View.extend({

        events: {
            'submit #catalogue-form': 'submitForm'
        },

        initialize: function () {
            this.render();
        },

        render: function () {
            require(['text!/translate/template/catalogue/form'], function (Template) {
                var template = _.template(Template, {});
                this.$el.html(template);
            }.bind(this));
        },

        submitForm: function (event) {
            event.preventDefault();

            var translatePackage = new Package({name: $('#name').val()});
            translatePackage.save();
        }
    });
});