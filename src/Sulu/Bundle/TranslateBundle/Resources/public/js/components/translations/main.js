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
    'sulutranslate/components/translations/collections/translations',
    'sulutranslate/components/translations/models/code'
], function(Package, Catalogue, RelationalStore, Catalogues, Translations, Code) {

    'use strict';
    var sandbox,
        packageModel,
        catalogues,
        selectedCatalogue,
        defaultCatalogue,
        translations,
        catalogueIdToDelete,

        delCatalogueSubmit = function() {
            sandbox.emit('husky.dialog.hide');
            //sandbox.emit('husky.header.button-state', 'disable');

            RelationalStore.reset();

            console.log(catalogueIdToDelete, "packageIdsDelete");

            var catalogueModel = new Catalogue({id: catalogueIdToDelete});
            catalogueModel.destroy({
                error: function() {
                    // TODO Output error message
                    console.log("error when deleting packages");
                }
            });

            unbindDialogListener();
            sandbox.emit('husky.header.button-state', 'standard');
            sandbox.emit('sulu.router.navigate', 'settings/translate/edit:' + packageModel.get('id') + '/details');

            catalogues = null;
            selectedCatalogue = null;
            defaultCatalogue = null;
            translations = null;
            catalogueIdToDelete = null;
        },

        hideDialog = function() {
            sandbox.emit('husky.dialog.hide');
            unbindDialogListener();
        },

        bindDialogListener = function() {
            sandbox.on('husky.dialog.submit', delCatalogueSubmit);
            sandbox.on('husky.dialog.cancel', hideDialog);
        },

        unbindDialogListener = function() {
            sandbox.off('husky.dialog.submit', delCatalogueSubmit);
            sandbox.off('husky.dialog.cancel', hideDialog);
        };

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

            // selected catalogue in select-field changed
            this.sandbox.on('sulu.translate.catalogue.changed', function(catalogueId) {

                selectedCatalogue = catalogues.get(catalogueId);
                if (!!selectedCatalogue) {
                    this.sandbox.emit('sulu.router.navigate', 'settings/translate/edit:' + packageModel.get('id') + '/details:' + catalogueId);
                } else {
                    // TODO
                    console.log("error - unknown catalogue")
                }

            }, this);

            // save
            this.sandbox.on('sulu.translate.translations.save', function(updatedTranslations, codesToDelete) {
                this.saveTranslations(updatedTranslations, codesToDelete);
            }, this);

            // delete packages
            this.sandbox.on('sulu.translate.catalogue.delete', function(catalogueId) {
                this.deleteCatalogue(catalogueId);
            }, this);

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

                        // when id for catalogue in url
                        if (!!this.options.catalogue) {
                            selectedCatalogue = catalogues.get(this.options.catalogue);
                        } else {
                            selectedCatalogue = defaultCatalogue;
                        }
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

        loadTranslations: function() {

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
        },


        saveTranslations: function(updatedTranslations, codesToDelete) {

            this.sandbox.emit('husky.header.button-state', 'loading-save-button');

            this.sandbox.util.each(codesToDelete, function(index) {
                var code = new Code({id: codesToDelete[index]});
                code.destroy({
                    success: function() {
                        console.log("deleted code");
                    },
                    error: function() {
                        // TODO errormessage/-handling
                    }
                });
            }.bind(this));

            if (updatedTranslations.length > 0) {
                translations.save(this.sandbox, updatedTranslations);
            }

            sandbox.emit('sulu.router.navigate', 'settings/translate');

        },

        deleteCatalogue: function(catalogueId) {

            catalogueIdToDelete = catalogueId;

            // show dialog and call delete only when user confirms
            this.sandbox.emit('sulu.dialog.confirmation.show', {
                content: {
                    title: 'Be careful!',
                    content: [
                        '<p>',
                        'This operation you are about to do will delete data. <br /> This is not undoable!',
                        '</p>',
                        '<p>',
                        ' Please think about it and accept or decline.',
                        '</p>'
                    ].join('')
                },
                footer: {
                    buttonCancelText: 'Don\'t do it',
                    buttonSubmitText: 'Do it, I understand'
                }
            });

            bindDialogListener();

        }

    };
});
