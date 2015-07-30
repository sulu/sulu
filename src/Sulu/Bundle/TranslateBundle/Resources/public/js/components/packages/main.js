/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulutranslate/models/package',
    'sulutranslate/models/code',
    'sulutranslate/collections/catalogues',
    'sulutranslate/collections/translations'
], function(Package, Code, Catalogues, Translations) {

    'use strict';

    return {

        initialize: function() {
            this.bindCustomEvents();

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'details') {
                this.renderDetails();
            } else if (this.options.display === 'settings') {
                this.renderSettings();
            } else {
                throw 'display type wrong';
            }
        },

        bindCustomEvents: function() {
            // delete contact
            this.sandbox.on('sulu.translate.package.delete', function(id) {
                this.del(id);
            }, this);

            // save the current package
            this.sandbox.on('sulu.translate.package.save', function(data) {
                this.save(data);
            }, this);

            // wait for navigation events
            this.sandbox.on('sulu.translate.package.load', function(id) {
                this.load(id);
            }, this);

            // wait for navigation events
            this.sandbox.on('sulu.translate.package.list', function() {
                this.sandbox.emit('sulu.router.navigate', 'settings/translate');
            }, this);

            // add new contact
            this.sandbox.on('sulu.translate.package.new', function() {
                this.add();
            }, this);

            // save translations
            this.sandbox.on('sulu.translate.translations.save', function(updatedTranslations, codesToDelete) {
                this.saveTranslations(updatedTranslations, codesToDelete);
            }, this);

            // delete selected contacts
            this.sandbox.on('sulu.translate.packages.delete', function(ids) {
                this.delPackages(ids);
            }, this);

            // selected catalogue in select-field changed
            this.sandbox.on('sulu.translate.catalogue.changed', function(packageId, catalogueId) {
                this.sandbox.emit('sulu.router.navigate', 'settings/translate/edit:' + packageId + '/details:' + catalogueId);
            }, this);
        },

        getModel: function(id) {
            // FIXME: fixed challenge Cannot instantiate more than one Backbone.RelationalModel with the same id per type!
            var packageModel = Package.findOrCreate(id);
            if (!packageModel) {
                packageModel = new Package({
                    id: id
                });
            }
            return packageModel;
        },

        renderList: function() {
            var $list = this.sandbox.dom.createElement('<div id="roles-list-container"/>');
            this.html($list);
            this.sandbox.start([
                {name: 'packages/components/list@sulutranslate', options: { el: $list}}
            ]);
        },

        renderSettings: function() {
            var $settings = this.sandbox.dom.createElement('<div id="roles-settings-container"/>');
            // show navigation submenu
            this.html($settings);
            var packageModel;
            if (!!this.options.id) {
                packageModel = this.getModel(this.options.id);

                packageModel.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {
                                name: 'packages/components/settings@sulutranslate',
                                options: {
                                    el: $settings, data: model.toJSON()
                                }
                            }
                        ]);
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                    }.bind(this)
                });
            } else {
                packageModel = new Package();
                this.sandbox.start([
                    {
                        name: 'packages/components/settings@sulutranslate',
                        options: {
                            el: $settings, data: packageModel.toJSON()
                        }
                    }
                ]);
            }
        },

        renderDetails: function() {
            // show navigation submenu
            if (!!this.options.id) {
                var packageModel = this.getModel(this.options.id);

                packageModel.fetch({
                    success: function(model) {
                        this.loadCatalogues(model);
                    }.bind(this),
                    error: function() {
                        this.sandbox.logger.log("error while fetching contact");
                    }.bind(this)
                });
            }
        },

        add: function() {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'settings/translate/add');
        },

        load: function(id) {
            // TODO: show loading icon
            this.sandbox.emit('sulu.router.navigate', 'settings/translate/edit:' + id + '/details');
        },

        save: function(data) {
            var packageModel = new Package();
            if (!!data.id) {
                packageModel = this.getModel(data.id);
            }

            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
            packageModel.set(data);
            packageModel.save(null, {
                // on success save contacts id
                success: function(response) {
                    var model = response.toJSON();
                    if (!!data.id) {
                        this.sandbox.emit('sulu.translate.package.saved', model.id, model);
                    } else {
                        this.sandbox.emit('sulu.router.navigate', 'settings/translate/edit:' + model.id + '/settings');
                    }
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving profile");
                }.bind(this)
            });
        },

        del: function(id) {
            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    var packageModel = this.getModel(id);

                    // TODO: show loading icon
                    packageModel.destroy({
                        success: function() {
                            this.sandbox.emit('sulu.router.navigate', 'settings/translate');
                        }.bind(this)
                    });
                }
            }.bind(this));
        },

        loadCatalogues: function(packageModel) {
            var catalogues = packageModel.get('catalogues'),
                selectedCatalogue;

            if (!!catalogues.at(0)) {
                // when id for catalogue in url
                if (!!this.options.catalogue) {
                    selectedCatalogue = catalogues.get(this.options.catalogue);
                } else {
                    selectedCatalogue = catalogues.findWhere({isDefault: true});

                    if (!selectedCatalogue) {
                        selectedCatalogue = catalogues.at(0);
                    }
                }
                this.loadTranslations(packageModel, selectedCatalogue);

            } else {
                // TODO no catalogue exists
                throw 'error: no catalogue exists';
            }
        },

        loadTranslations: function(packageModel, selectedCatalogue) {
            var $details = this.sandbox.dom.createElement('<div id="roles-details-container"/>');
            this.html($details);

            this.translations = new Translations({translateCatalogueId: selectedCatalogue.get('id')});
            this.translations.fetch({
                success: function() {
                    this.sandbox.start([
                        {name: 'packages/components/details@sulutranslate', options: {
                            el: $details,
                            data: packageModel.toJSON(),
                            selectedCatalogue: selectedCatalogue.toJSON(),
                            translations: this.translations.toJSON()
                        }}
                    ]);
                }.bind(this),

                error: function() {
                    // TODO errormessage
                }.bind(this)
            });
        },

        saveTranslations: function(updatedTranslations, codesToDelete) {

            var dfd = new $.Deferred(),
                promiseCounter = 0,
                resolvePromise = function() {
                    promiseCounter--;
                    if (promiseCounter === 0) {
                        dfd.resolve();
                    }
                };

            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');

            this.sandbox.util.each(codesToDelete, function(index) {
                promiseCounter++;
                var code = new Code({id: codesToDelete[index]});
                code.destroy({
                    success: function() {
                        this.sandbox.logger.log('Code deleted');
                        resolvePromise();
                    }.bind(this),
                    error: function() {
                        // TODO errormessage/-handling
                    }
                });
            }.bind(this));

            if (updatedTranslations.length > 0) {
                promiseCounter++;
                this.translations.save(this.sandbox, updatedTranslations, {
                    success: function() {
                        resolvePromise();
                    }.bind(this)
                });
            }

            dfd.then(function() {
                //FIXME try to get this formular working without page "refresh"
                this.sandbox.mvc.history.loadUrl(this.sandbox.mvc.history.fragment);
            }.bind(this));
        },

        delPackages: function(ids) {
            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    // TODO: show loading icon

                    ids.forEach(function(id) {
                        var packageModel = this.getModel(id);

                        packageModel.destroy({
                            success: function() {
                                this.sandbox.emit('husky.datagrid.record.remove', id);
                            }.bind(this)
                        });
                    }.bind(this));
                }
            });
        },

        /**
         * @var ids - array of ids to delete
         * @var callback - callback function returns true or false if data got deleted
         */
        confirmDeleteDialog: function(callbackFunction) {
            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }

            // show warning dialog
            this.sandbox.emit('sulu.overlay.show-warning',
                'sulu.overlay.be-careful',
                'sulu.overlay.delete-desc',

                function() {
                    // cancel callback
                    callbackFunction.call(this, false);
                }.bind(this),

                function() {
                    // ok callback
                    callbackFunction.call(this, true);
                }.bind(this)
            );
        }
    };
});
