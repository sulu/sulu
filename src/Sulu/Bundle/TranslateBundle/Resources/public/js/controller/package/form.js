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
], function ($, Backbone, Router, Package, Catalogue) {

    'use strict';

    var translatePackage;
    var cataloguesToDelete;

    return Backbone.View.extend({

        events: {
            'submit #catalogue-form': 'submitForm',
            'click .icon-remove': 'deleteRow',
            'click .addRow': 'addRow'
        },

        initialize: function () {
            this.render();
        },

        getTabs: function (id) {
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

        render: function () {

            Backbone.Relational.store.reset(); //FIXME really necessary?
            require(['text!/translate/template/catalogue/form'], function (Template) {
                var template;

                cataloguesToDelete = new Array();

                if (!this.options.id) {
                    translatePackage = new Package();
                    template = _.template(Template, {name: '', locale: '', catalogues: []});
                    this.$el.html(template);
                } else {
                    translatePackage = new Package({id: this.options.id});
                    translatePackage.fetch({
                        success: function (translatePackage) {
                            template = _.template(Template, translatePackage.toJSON());
                            var catalogues = this.getArrayFromCatalogues(translatePackage.get('catalogues').models);
                            this.initializeCatalogueList(catalogues);
                            this.$el.html(template);

                        }.bind(this)
                    });
                }

                App.Navigation.trigger('navigation:item:column:show', {
                    data: this.getTabs(translatePackage.get('id'))
                });
            }.bind(this));
        },

        getArrayFromCatalogues: function (models) {

            var data = new Array();

            $.each(models, function (model) {
                data.push(models[model].attributes);
            });

            return data;
        },


        submitForm: function (event) {

            var that = this;

            event.preventDefault();

            translatePackage.set({name: this.$('#name').val()});

            var $rows = $('#catalogues tbody tr');


            // create catalogues if necessary and add them

            for (var i = 1; i <= $rows.length; i++) {
                var catalogue = translatePackage.get('catalogues').at(i - 1);
                if (!catalogue) {
                    catalogue = new Catalogue();
                }

                var locale = $('#catalogues tbody tr:nth-child(' + i + ') td:nth-child(2) input').val();

                catalogue.set({'locale': locale});
                translatePackage.get('catalogues').add(catalogue);
            }

            // send delete request for models which should be deleted

            console.log(cataloguesToDelete, "these will be deleted");

            cataloguesToDelete.forEach(function (id) {
                var model = translatePackage.get('catalogues').get(id);
                model.destroy({
                    success: function () {
                        console.log("deleted model");
                    }
                });
            });


            translatePackage.save(null, {
                success: function () {
                    that.undelegateEvents();
                    console.log("save translatepackage");
                    //Router.navigate('settings/translate');
                }
            });
        },

        initializeCatalogueList: function (data) {
            var dataGrid;

            require(['text!sulutranslate/templates/package/table-row.html'], function (RowTemplate) {
                dataGrid = $('#catalogues').huskyDataGrid({
                    pagination: false,
                    showPages: 6,
                    pageSize: 4,
                    template: {
                        row: RowTemplate
                    },
                    data: {
                        items: data
                    }
                });


                $('#addCatalogueRow').on('click', function () {
                    dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:add', { id: '', locale: '', translations: [] });
                });

                $('#catalogues').on('click', '.remove-row > span', function (event) {
                    dataGrid.data('Husky.Ui.DataGrid').trigger('data-grid:row:remove', event);

                    var id = $(event.currentTarget).parent().parent().data('id');
                    console.log(id, 'id');
                    cataloguesToDelete.push(id);
                });

            }.bind(this));
        }

    });
});
