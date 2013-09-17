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
            this.initializeHeader();
            this.render();
        },

        initializeHeader: function() {
            if (!!this.options.data.id) {
                this.sandbox.emit('husky.header.button-type', 'saveDelete');
            } else {
                this.sandbox.emit('husky.header.button-type', 'save');
            }

            this.sandbox.on('husky.button.save.click', function() {
                this.save();
            }.bind(this));

            this.sandbox.on('husky.button.delete.click', function() {
                this.sandbox.emit('sulu.roles.delete', this.sandbox.dom.val('#id'));
            }.bind(this));
        },

        save: function() {
            // FIXME  Use datamapper instead
            var data = {
                id: this.sandbox.dom.val('#id'),
                name: this.sandbox.dom.val('#name'),
                system: this.sandbox.dom.val('#system')
            };

            this.sandbox.emit('sulu.roles.save', data);
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.sandbox.template.parse(Template, {data: this.options.data}));
        }
    }
});