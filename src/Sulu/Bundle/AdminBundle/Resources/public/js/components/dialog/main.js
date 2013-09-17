/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define({
    name: 'Sulu Dialog',

    initialize: function() {
        this.bindCustomEvents();
    },

    bindCustomEvents: function() {
        this.sandbox.on('sulu.dialog.confirmation.show', function(data) {
            this.showConfirmationDialog(data);
        }.bind(this));
    },

    showConfirmationDialog: function(data) {
        this.html(
            '<div data-aura-component="dialog@husky"' +
                ' data-aura-data-content-title="' + data.content.title + '' +
                ' data-aura-data-content-content="' +data.content.content + '"' +
                ' data-aura-data-footer-buttonCancelText="' +data.footer.buttonCancelText + '"' +
                ' data-aura-data-footer-buttonSubmitText="' +data.footer.buttonSubmitText + '"' +
                '/>'
        );
    }
});