/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'backbone', 'router', 'sulutranslate/model/package'], function ($, Backbone, Router, Package) {

    'use strict';

    var translatePackage;

    return Backbone.View.extend({

        events: {
            'submit #catalogue-form': 'submitForm'
        },

        initialize: function () {
            this.render();
        },

        render: function () {
            require(['text!/translate/template/catalogue/form'], function (Template) {
                var template;
                if (!this.options.id) {
                    translatePackage = new Package();
                    template = _.template(Template, {});
                    this.$el.html(template);
                } else {
                    translatePackage = new Package({id: this.options.id});
                    translatePackage.fetch({
                        success: function (translatePackage) {
                            template = _.template(Template, {name: translatePackage.get('name')});
                            this.$el.html(template);
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        submitForm: function (event) {
            event.preventDefault();
            translatePackage.save({name: $('#name').val()}, {
                success: function (translatePackage) {
                    Router.navigate('settings/translate/form/' + translatePackage.get('id'));
                }
            });
        }
    });
});