/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!/security/template/role/form'], function(Template) {
    return {

        name: 'Sulu Security Role Form',

        view: true,

        initialize: function() {
            // FIXME Automate this call
            this.sandbox.off();
            this.initializeHeader();
            this.render();
        },

        initializeHeader: function() {
            this.sandbox.emit('husky.header.button-type', 'saveDelete');

            this.sandbox.on('husky.button.save.click', function() {
                this.save();
            }.bind(this));
        },

        save: function() {
            // FIXME  Use datamapper instead
            var data = {
                name: this.sandbox.dom.$('#name').val(),
                system: this.sandbox.dom.$('#system').val()
            };

            this.sandbox.emit('sulu.roles.save', data);
        },

        render: function() {
            this.sandbox.dom.html(this.options.el, Template);
        }
    }
});