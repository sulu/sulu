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

    var renderForm = function() {
            var $form = this.sandbox.dom.createElement('<div id="permission-form-container"/>'),
                component = {
                    name: 'permission-tab/components/form@sulusecurity',
                    options: {
                        el: $form,
                        id: this.options.id,
                        type: this.options.type,
                        securityContext: this.options.securityContext,
                        inOverlay: this.options.inOverlay
                    }
                };

            this.html($form);
            this.sandbox.start([component]);
        },

        bindCustomEvents = function() {
            this.sandbox.on('sulu.permission-form.save', save.bind(this));

            this.sandbox.on('sulu.permission-tab.saved', function() {
                // TODO would be better to only update the toolbar, instead of reloading the page
                this.sandbox.emit('sulu.router.navigate', this.sandbox.mvc.history.fragment, true, true);
            }.bind(this));
        },

        wrapTabEvents = function() {
            this.sandbox.on('husky.permission-form.changed', function() {
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
            }.bind(this));

            this.sandbox.on('sulu.permission-form.save', function() {
                this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
            }.bind(this));

            this.sandbox.on('sulu.permission-tab.error', function() {
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'save');
            }.bind(this));

            // forward toolbar save event
            this.sandbox.on('sulu.toolbar.save', function() {
                this.sandbox.emit('sulu.permission-tab.save');
            }.bind(this));
        },

        save = function(permissionData, action) {
            this.sandbox.util.ajax(
                '/admin/api/permissions',
                {
                    method: 'POST',
                    data: permissionData,
                    success: function() {
                        this.sandbox.emit('sulu.labels.success.show', 'labels.success.permission-save-desc');
                        this.sandbox.emit('sulu.permission-tab.saved', permissionData, action);
                    }.bind(this),
                    error: function() {
                        this.sandbox.emit('sulu.labels.error.show');
                        this.sandbox.emit('sulu.permission-tab.error');
                    }.bind(this)
                }
            );
        };

    return {
        initialize: function() {
            renderForm.call(this);
            bindCustomEvents.call(this);
            if (!this.options.inOverlay) {
                wrapTabEvents.call(this);
            }
        }
    };
});
