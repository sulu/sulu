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
    'sulutranslate/model/package',
    'sulutranslate/model/catalogue'
], function($, Backbone, Router, Package, Catalogue) {

    'use strict';

    var translatePackage;

    return Backbone.View.extend({

        events: {

        },

        initialize: function() {
            this.render();
        },

        getTabs: function(id) {

        },

        render: function() {
            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/translate/template/catalogue/form'], function(Template) {
                var template;
                if (!this.options.id) {
                    translatePackage = new Package();
                    template = _.template(Template, {name: '', catalogues: []});
                    this.$el.html(template);
                } else {
                    translatePackage = new Package({id: this.options.id});
                    translatePackage.fetch({
                        success: function(translatePackage) {
                            template = _.template(Template, translatePackage.toJSON());
                            this.$el.html(template);
                        }.bind(this)
                    });
                }

                App.Navigation.trigger('navigation:item:column:show', {
                    data: this.getTabs(translatePackage.get('id'))
                });
            }.bind(this));
        },

        submitForm: function(event) {

        }
    });
});
