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

//            this.sandbox.on('sulu.contact.save', this.save, this)
        },

        renderList: function() {

            this.sandbox.start([
                {name: 'accounts/components/list@sulucontact', options: { el: this.$el}}
            ]);

            // wait for navigation events
            this.sandbox.on('sulu.companies.load', function(id) {
                this.sandbox.emit('sulu.router.navigate', 'contacts/companies/edit:'+id);
            }, this);

            this.sandbox.on('sulu.companies.new', function(id) {
                this.sandbox.emit('sulu.router.navigate', 'contacts/companies/add');
            }, this);

        },


        renderForm: function() {

            if(!!this.options.id){
                // fetch

                this.sandbox.start([
                    {name: 'accounts/components/form@sulucontact', options: { el: this.$el, data: this.model.toJSON()}}
                ]);


            }else{

            }
        }





    };
});
