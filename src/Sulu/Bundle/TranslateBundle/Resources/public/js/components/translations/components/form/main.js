/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'text!/translate/template/translation/form',
    'mvc/relationalstore'
], function(formTemplate, RelationalStore) {

    'use strict';
    var catalogueFormId = '#catalogue-form';

    return {

        name: 'Sulu Translate Package Form',
        view: true,

        initialize: function() {
            this.sandbox.off(); // FIXME automate this call
            this.initializeHeader();
            this.render();
        },

        render: function() {
            RelationalStore.reset();
//            this.$el.removeData('Husky.Ui.DataGrid'); // FIXME: jquery

            var template = this.sandbox.template.parse(formTemplate, {
                package: this.options.data.package,
                catalogue: this.options.data.selectedCatalogue,
                translations: this.options.data.translations
            });
            this.sandbox.dom.html(this.options.el, template);

//            this.sandbox.validation.create(catalogueFormId);
            this.initFormEvents();
        },



        initFormEvents: function() {

//            this.$el.on('click', '#add-catalogue-row', function(event) { // FIXME: jquery
//                this.sandbox.emit('husky.datagrid.row.add', { id: '', isDefault: false, locale: '', translations: [] });
//            }.bind(this));

        },

        initializeHeader: function() {

            this.sandbox.emit('husky.header.button-type', 'saveDelete');

            this.sandbox.on('husky.button.save.click', function(event) {
                console.log("save");
//                this.submit();
            }, this);

            this.sandbox.on('husky.button.delete.click', function(event) {
                console.log("delete");
                //this.sandbox.emit('sulu.translate.package.delete');
            }, this);
        }

//        getCatalogueById: function(id) {
//
//            var catalogues = this.options.data.catalogues;
//
//            this.sandbox.util.each(this.options.data.catalogues, function(index) {
//
//                if (parseInt(catalogues[index].id) === parseInt(id)) {
//
//                    this.cataloguesToDelete.push(catalogues[index].id);
//                    catalogues.splice(index,1);
//                    return;
//                }
//
//            }.bind(this));
//        },
//
//        submit: function() {
//
//            // TODO validation
//            if(this.sandbox.validation.validate(catalogueFormId)) {
//
//                if(!this.options.data) {
//                    this.options.data = {};
//                    this.options.data.id;
//                }
//
//                this.options.data.name = this.sandbox.dom.val('#name');
//                this.options.data.catalogues = this.getChangedCatalogues();
//
//                this.sandbox.emit('sulu.translate.package.save', this.options.data, this.cataloguesToDelete);
//            }
//        }



    };
});
