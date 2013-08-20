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
            'submit #catalogue-form': 'submitForm'
        },

        initialize: function() {
            this.render();
        },

        getTabs: function(id) {
            //TODO Simplify this task for bundle developer?
            var cssId = id || 'new';

            // TODO translate
            var navigation = {
                'title': 'Catalogue',
                'header': {
                    'title': 'Catalogue'
                },
                'hasSub': 'true',
                //TODO id mandatory?
                'sub': {
                    'items': []
                }
            };

            if (!!id) {
                navigation.sub.items.push({
                    'title': 'Details',
                    'action': 'settings/translate/details:translate-package-' + cssId,
                    'hasSub': false,
                    'type': 'content',
                    'id': 'translate-package-details-' + cssId
                });
            }

            navigation.sub.items.push({
                'title': 'Settings',
                'action': 'settings/translate/settings:translate-package-' + cssId,
                'hasSub': false,
                'type': 'content',
                'id': 'translate-package-settings-' + cssId
            });

            return navigation;
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
            event.preventDefault();
            translatePackage.set({name: this.$('#name').val()});
            for (var i = 1; i <= 2; i++) {
                var catalogue = translatePackage.get('catalogues').at(i - 1);
                if (!catalogue) {
                    catalogue = new Catalogue();
                }
                catalogue.set({'locale': $('#locale' + i).val()});
                translatePackage.get('catalogues').add(catalogue);
            }

            translatePackage.save(null, {
                success: function() {
                    Router.navigate('settings/translate');
                }
            });
        }
    });
});
