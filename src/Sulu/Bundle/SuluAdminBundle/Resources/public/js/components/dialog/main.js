/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var $dialog;

    return {
        name: 'Sulu Dialog',

        initialize: function() {
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.dialog.confirmation.show', function(data, templateType) {
                this.showConfirmationDialog(data, templateType);
            }.bind(this));

            this.sandbox.on('sulu.dialog.error.show', function(message) {
                this.showErrorDialog(message);
            }.bind(this));

            this.sandbox.on('sulu.dialog.ok.show', function(title, message) {
                this.showOkDialog(title, message);
            }.bind(this));
        },

        createDialogElement: function() {
            $dialog = this.sandbox.dom.createElement('<div id="dialog"></div>');
            this.sandbox.dom.append(this.$el, $dialog);
        },

        startComponent: function(component) {
            this.sandbox.start([component], { reset: true });
        },

        showConfirmationDialog: function(data, templateType) {
            this.createDialogElement();
            this.startComponent({
                name: 'dialog@husky',
                options: {
                    el: $dialog,
                    templateType: templateType,
                    data: data
                }
            });
        },

        showErrorDialog: function(message) {
            this.createDialogElement();
            this.startComponent({
                name: 'dialog@husky',
                options: {
                    el: $dialog,
                    templateType: 'okDialog',
                    data: {
                        content: {
                            title: "An error occured!",
                            content: message
                        }
                    }
                }
            });

            this.sandbox.once('husky.dialog.cancel', function() {
                this.sandbox.emit('husky.dialog.hide');
            }.bind(this));

        },

        showOkDialog: function(title,message) {
            this.createDialogElement();
            this.startComponent({
                name: 'dialog@husky',
                options: {
                    el: $dialog,
                    templateType: 'okDialog',
                    data: {
                        content: {
                            title: title,
                            content: message
                        }
                    }
                }
            });

            this.sandbox.once('husky.dialog.cancel', function() {
                this.sandbox.emit('husky.dialog.hide');
            }.bind(this));
        }
    };
});
