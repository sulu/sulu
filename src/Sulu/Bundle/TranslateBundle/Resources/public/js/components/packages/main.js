/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

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

        },

        renderList: function() {

            this.sandbox.start([
                {name: 'packages/components/list@sulutranslate', options: { el: this.$el}}
            ]);

            this.sandbox.on('sulu.translate.package.load', function(item){
                this.sandbox.emit('sulu.router.navigate', 'settings/translate/edit:' + item+'/settings');
            }, this);

            this.sandbox.on('sulu.translate.package.new', function(){
                this.sandbox.emit('sulu.router.navigate', 'settings/translate/add');
            }, this);

        },


        renderForm: function() {

            console.log(this.options.id, "id");

            if(!!this.options.id){

                // todo fetch

                this.sandbox.start([
                    {name: 'packages/components/form@sulutranslate', options: { el: this.$el, data: this.model.toJSON()}}
                ]);


            }else{

                this.sandbox.start([
                    {name: 'packages/components/form@sulutranslate', options: { el: this.$el}}
                ]);
            }
        }

    };
});
