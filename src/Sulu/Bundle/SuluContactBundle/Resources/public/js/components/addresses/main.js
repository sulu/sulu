/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!./templates/address.html'], function(tpl) {

    'use strict';

    return {
        initialize: function() {

            var defaults = {
                street: "test"
            };
            var options = this.sandbox.util.extend(true, {}, defaults, this.options.address);


            var template = this.sandbox.template.parse(tpl);
//            this.sandbox.dom.html(this.options.address, template);
            this.html(template(options));

            console.log("this is the address component");
        }
    }
});