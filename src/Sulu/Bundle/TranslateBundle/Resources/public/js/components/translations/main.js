/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulutranslate/model/package',
    'sulutranslate/model/catalogue',
    'mvc/relationalstore',
    'sulutranslate/components/translations/collections/catalogues',
    'sulutranslate/components/translations/collections/translations'
], function(Package, Catalogue, RelationalStore, Catalogues, Translations) {

    'use strict';
    var sandbox,
        packageModel,
        catalogues,
        selectedCatalogue,
        defaultCatalogue,
        translations;


//        delPackagesSubmit = function() {
//            sandbox.emit('husky.dialog.hide');
//            //sandbox.emit('husky.header.button-state', 'disable');
//
//            RelationalStore.reset();
//
//            console.log(packageIdsDelete, "packageIdsDelete");
//
//            sandbox.util.each(packageIdsDelete, function(index) {
//
//                var packageModel = new Package({id: packageIdsDelete[index]});
//                packageModel.destroy({
//                    error: function() {
//                        // TODO Output error message
//                        console.log("error when deleting packages");
//                    }
//                });
//
//                sandbox.emit('husky.datagrid.row.remove', packageIdsDelete[index]);
//
//            }.bind(this));
//
//            unbindDialogListener();
//            sandbox.emit('husky.header.button-state', 'standard');
//            packageIdsDelete = new Array();
//        },
//
//        hideDialog = function() {
//            sandbox.emit('husky.dialog.hide');
//            unbindDialogListener();
//        },
//
//        bindDialogListener = function() {
//            sandbox.on('husky.dialog.submit', delPackagesSubmit);
//            sandbox.on('husky.dialog.cancel', hideDialog);
//        },
//
//        unbindDialogListener = function() {
//            sandbox.off('husky.dialog.submit', delPackagesSubmit);
//            sandbox.off('husky.dialog.cancel', hideDialog);
//        };

    return {

        initialize: function() {

            sandbox = this.sandbox;

            if (this.options.display === 'list') {
                // nothing to do
            } else if (this.options.display === 'form') {
                this.renderForm();
            } else {
                throw 'display type wrong';
            }

            this.bindCustomEvents();

        },

        bindCustomEvents: function() {

            this.sandbox.on('sulu.translate.catalogue.changed', function(id){

                // selected catalogue changed
                // load new translations
                // render form?


                selectedCatalogue = catalogues.get(id);
                console.log(catalogues.toJSON(), "catalogues");
                console.log(selectedCatalogue.toJSON(), "selectedCatalogue");

                if(!!selectedCatalogue) {
                    this.loadTranslations(selectedCatalogue.get('id'));
                } else {
                    // TODO
                    console.log("error - unknown catalogue")
                }

            }, this);

//            // load existing
//            this.sandbox.on('sulu.translate.catalogue.load', function(item) {
//                this.sandbox.emit('husky.header.button-state', 'loading-add-button');
//                this.sandbox.emit('sulu.router.navigate', 'settings/translate/edit:' + item + '/details');
//            }, this);
//
//            // save
//            this.sandbox.on('sulu.translate.package.save', function(data,cataloguesToDelete) {
//                this.savePackage(data,cataloguesToDelete);
//            }, this);
//
//            // delete packages
//            this.sandbox.on('sulu.translate.packages.delete', function(packageIds) {
//                this.deletePackages(packageIds);
//            }, this);

        },


        renderForm: function() {

            if (!!this.options.id) {

                packageModel = new Package({id: this.options.id});
                packageModel.fetch({

                    success: function() {
                        this.loadCatalogues(packageModel.get('id'));
                    }.bind(this),

                    error: function() {
                        // TODO errormessage
                    }.bind(this)
                });

            } else {

                // TODO error message
            }
        },

        loadCatalogues: function(packageId) {

            catalogues = new Catalogues({
                packageId: packageId,
                fields: 'id,locale,isDefault'
            });

            catalogues.fetch({
                success: function() {

                    if (!!catalogues.at(0)) {

                        defaultCatalogue = catalogues.findWhere({isDefault: true});

                        if (!defaultCatalogue) {
                            defaultCatalogue = catalogues.at(0);
                        }

                        selectedCatalogue = defaultCatalogue;
                        this.loadTranslations(selectedCatalogue.get('id'));

                    } else {
                        // TODO no catalogue exists
                        console.log("no existing catalogue");
                    }

                }.bind(this),

                error: function() {
                    // TODO errormessage
                }.bind(this)
            });



        },

        loadTranslations: function(catalogueId) {

            translations = new Translations({translateCatalogueId: selectedCatalogue.id});
            translations.fetch({
                success: function() {

                    this.options.data = {};
                    this.options.data.package = packageModel.toJSON();
                    this.options.data.catalogues = catalogues.toJSON();
                    this.options.data.selectedCatalogue = selectedCatalogue.toJSON();
                    this.options.data.translations = translations.toJSON();

                    this.sandbox.start([
                        {name: 'translations/components/form@sulutranslate', options: { el: this.$el, data: this.options.data}}
                    ]);

                }.bind(this),

                error: function() {
                    // TODO errormessage
                }.bind(this)
            });
        }


//        savePackage: function(data, cataloguesToDelete) {
//
//            this.sandbox.emit('husky.header.button-state', 'loading-save-button');
//
//            this.sandbox.util.each(cataloguesToDelete, function(id) {
//                var cat = new Catalogue({id: cataloguesToDelete[id]});
//                cat.destroy({
//                    success: function() {
//                        console.log("deleted catalogue");
//                    }
//                });
//            }.bind(this));
//
//            var packageModel = new Package(data);
//            packageModel.save(null, {
//
//                success: function() {
//                    this.sandbox.emit('sulu.router.navigate', 'settings/translate');
//                }.bind(this),
//
//                error: function() {
//                    // TODO Output error message
//                    console.log("error while trying to save");
//                    this.sandbox.emit('husky.header.button-state', 'disable');
//                }.bind(this)
//            });
//
//        },

//        deletePackages: function(packageIds) {
//            packageIdsDelete = packageIds;
//
//            // show dialog and call delete only when user confirms
//            this.sandbox.emit('sulu.dialog.confirmation.show', {
//                content: {
//                    title: 'Be careful!',
//                    content: [
//                        '<p>',
//                        'This operation you are about to do will delete data. <br /> This is not undoable!',
//                        '</p>',
//                        '<p>',
//                        ' Please think about it and accept or decline.',
//                        '</p>'
//                    ].join('')
//                },
//                footer: {
//                    buttonCancelText: 'Don\'t do it',
//                    buttonSubmitText: 'Do it, I understand'
//                }
//            });
//
//            console.log("here!");
//
//            bindDialogListener();
//
//        }

    };
});
