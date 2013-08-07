/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'jquery',
    'backbone',
    'router',
    'sulutranslate/model/package',
    'sulutranslate/model/catalogue'
], function ($, Backbone, Router, Package, Catalogue) {

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
            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/translate/template/catalogue/form'], function (Template) {
                var template;
                if (!this.options.id) {
                    translatePackage = new Package();
                    template = _.template(Template, {name: '', catalogues: []});
                    this.$el.html(template);
                } else {
                    translatePackage = new Package({id: this.options.id});
                    translatePackage.fetch({
                        success: function (translatePackage) {
                            template = _.template(Template, translatePackage.toJSON());
                            this.$el.html(template);
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        submitForm: function (event) {
            event.preventDefault();
            translatePackage.set({name: this.$('#name').val()});
            for (var i = 1; i <= 2; i++) {
                var catalogue = translatePackage.get('catalogues').at(i - 1);
                if (!catalogue) {
                    catalogue = new Catalogue();
                }
                catalogue.set({'code': $('#code' + i).val()});
                translatePackage.get('catalogues').add(catalogue);
            }

            translatePackage.save(null, {
                success: function (translatePackage) {
                    Router.navigate('settings/translate');
                }
            });
        }
    });
});