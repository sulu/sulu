/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define({

    name: 'Sulu Security Role Form',

    view: true,

    template: function() {
        return [
            '<form id="role-form">',
                '<div class="grid-row">',
                    '<label>Title*</label>',
                '</div>',
            '</form>'
        ].join('');
    },

    initialize: function() {
        this.initializeHeader();
        this.render();
    },

    initializeHeader: function() {
        this.sandbox.emit('husky.header.button-type', 'saveDelete');

        this.sandbox.on('husky.button.save.click', function() {
            this.sandbox.emit('sulu.roles.save');
        }.bind(this));
    },

    render: function() {
        this.$el.html(this.template());
    }
});