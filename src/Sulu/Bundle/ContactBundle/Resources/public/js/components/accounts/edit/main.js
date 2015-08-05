/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['services/sulucontact/account-router'], function(AccountRouter) {

    'use strict';

    return {
        header: function() {
            return {
                tabs: {
                    url: '/admin/content-navigations?alias=account'
                },
                toolbar: {
                    buttons: {
                        save: {
                            parent: 'saveWithOptions'
                        },
                        settings: {
                            options: {
                                dropdownItems: {
                                    delete: {
                                        options: {
                                            callback: this.deleteAccount.bind(this)
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        },

        initialize: function() {
            this.bindCustomEvents();
            this.afterSaveAction = '';
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.back', AccountRouter.toList);
            this.sandbox.on('sulu.tab.saved', this.afterSave.bind(this));
            this.sandbox.on('sulu.tab.dirty', this.tabDirty.bind(this));
            this.sandbox.on('sulu.toolbar.save', this.save.bind(this));
        },

        deleteAccount: function() {
            if (!!this.options.id) {
                alert('delete');
            }
        },

        save: function(action) {
            this.afterSaveAction = action;
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
            this.sandbox.emit('sulu.tab.save');
        },

        tabDirty: function() {
            this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
        },

        afterSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
            if (this.afterSaveAction == 'back') {
                AccountRouter.toList();
            } else if (this.afterSaveAction == 'new') {
                AccountRouter.toAdd();
            }
        }
    };
});
