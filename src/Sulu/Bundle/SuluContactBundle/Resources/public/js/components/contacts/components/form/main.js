/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'text!/contact/template/contact/form',
    'sulucontact/model/contact'
], function(listTemplate, Contact) {

    'use strict';


    return {

        view: true,

        initialize: function() {
            this.render();
        },

        render: function() {

            var contact = this.options.model;
//            var emails = contact.get('email');


            var template = this.sandbox.template.parse(listTemplate);
//            this.sandbox.dom.html(this.$el, template);



            // for each address:
            this.html('<div data-aura-component="addresses@sulucontact">');


            console.log("test it");

        }


    };
});
