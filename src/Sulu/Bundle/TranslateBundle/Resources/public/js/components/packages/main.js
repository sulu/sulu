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
    'mvc/relationalstore'
], function(Package, Catalogue, RelationalStore) {

    'use strict';
    var packageIdsDelete,
        navigateToList,
        sandbox,

        // TODO refactor this.sandbox vs sandbox

        delPackagesSubmit = function() {
            sandbox.emit('husky.dialog.hide');

            RelationalStore.reset();

            if(navigateToList) {

                var packageModel = new Package({id: packageIdsDelete[0]});
                packageModel.destroy({
                    success: function() {
                        sandbox.emit('sulu.router.navigate', 'settings/translate');
                    },
                    error: function() {
                        // TODO Output error message
                    }
                });

                sandbox.emit('husky.datagrid.row.remove',packageIdsDelete[0]);

            } else {

                var clone = packageIdsDelete.slice(0);
                sandbox.util.each(clone, function(index,value) {

                    var packageModel = new Package({id: value});
                    packageModel.destroy({
                        error: function() {
                            // TODO Output error message
                        }
                    });

                    sandbox.emit('husky.datagrid.row.remove',value);

                }.bind(this));

            }

            unbindDialogListener();
            sandbox.emit('husky.header.button-state', 'standard');
            packageIdsDelete = [];
        },

        hideDialog = function() {
            sandbox.emit('husky.dialog.hide');
            unbindDialogListener();
        },

        bindDialogListener = function(){
            sandbox.on('husky.dialog.submit', delPackagesSubmit);
            sandbox.on('husky.dialog.cancel', hideDialog);
        },

        unbindDialogListener = function(){
            sandbox.off('husky.dialog.submit', delPackagesSubmit);
            sandbox.off('husky.dialog.cancel', hideDialog);
        };

    return {

        initialize: function() {

            sandbox = this.sandbox;

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.renderForm();
            } else {
                throw 'display type wrong';
            }

            this.bindCustomEvents();

        },

        bindCustomEvents: function() {
            // load existing
            this.sandbox.on('sulu.translate.package.load', function(item) {
                this.sandbox.emit('husky.header.button-state', 'loading-add-button');
                this.sandbox.emit('sulu.router.navigate', 'settings/translate/edit:' + item + '/settings');
            }, this);

            // add new
            this.sandbox.on('sulu.translate.package.new', function() {
                this.sandbox.emit('husky.header.button-state', 'loading-add-button');
                this.sandbox.emit('sulu.router.navigate', 'settings/translate/add');
            }, this);

            // save
            this.sandbox.on('sulu.translate.package.save', function(data,cataloguesToDelete) {
                this.savePackage(data,cataloguesToDelete);
            }, this);

            // delete packages
            this.sandbox.on('sulu.translate.packages.delete', function(packageIds, navigateToList) {
                this.deletePackages(packageIds, navigateToList);
            }, this);

        },

        renderList: function() {

            this.sandbox.start([
                {name: 'packages/components/list@sulutranslate', options: { el: this.$el}}
            ]);
        },

        renderForm: function() {

            if (!!this.options.id) {

                var packageModel = new Package({id: this.options.id});
                packageModel.fetch({
                    success: function(packageModel) {
                        this.sandbox.start([
                            {name: 'packages/components/form@sulutranslate', options: { el: this.$el, data: packageModel.toJSON()}}
                        ]);
                    }.bind(this)

                    // TODO errormessage
                });

            } else {

                this.sandbox.start([
                    {name: 'packages/components/form@sulutranslate', options: { el: this.$el} }
                ]);
            }
        },

        savePackage: function(data, cataloguesToDelete) {

            this.sandbox.emit('husky.header.button-state', 'loading-save-button');

            this.sandbox.util.each(cataloguesToDelete, function(id) {
                var cat = new Catalogue({id:cataloguesToDelete[id]});
                cat.destroy({
                    success: function() {
                    }
                });
            }.bind(this));

            var packageModel = new Package(data);
            packageModel.save(null, {

                success: function() {
                    this.sandbox.emit('sulu.router.navigate', 'settings/translate');
                }.bind(this),

                error: function() {
                    // TODO Output error message
                    this.sandbox.emit('husky.header.button-state', 'disable');
                }.bind(this)
            });

        },

        deletePackages: function(packageIds, navigate){
            packageIdsDelete = packageIds;
            navigateToList = navigate;

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
