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

    var sandbox;

    return {

        initialize: function() {

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {

            } else {
                throw 'display type wrong';
            }

//            this.sandbox.on('sulu.contact.save', this.save, this)
        },

        renderList: function() {

            // fetch all contacts

            this.sandbox.start([
                {name: 'contacts/components/list@sulucontact', options: { el: this.$el}}
            ]);
        },


        renderForm: function() {

            if(!!this.options.id){
                // fetch

                this.sandbox.start([
                    {name: 'contacts/components/form@sulucontact', options: { el: this.$el, data: this.model.toJSON()}}
                ]);


            }else{

            }
        }





    };
});
