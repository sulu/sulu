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
    'sulutranslate/model/catalogue'
], function(Package, Catalogue) {

    'use strict';

    return {

        initialize: function() {

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
                        console.log("deleted catalogue");
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
                    console.log("error while trying to save");
                    this.sandbox.emit('husky.header.button-state', 'disable');
                }.bind(this)
            });

        }

    };
});
