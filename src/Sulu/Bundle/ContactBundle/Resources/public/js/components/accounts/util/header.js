/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app-config'], function(AppConfig) {

    'use strict';

    // sets headlines and breadcrumb
    var setHeadlinesAndBreadCrumb = function(account) {
            var breadcrumb = [
                    {title: 'navigation.contacts'},
                    {title: 'contact.accounts.title', event: 'sulu.contacts.accounts.list', eventArgs: account}
                ],
                title = this.sandbox.translate('contact.accounts.title');

            if (account.number) {
                breadcrumb.push({title: '#' + account.number});
                title = account.name;
            }

            this.sandbox.emit('sulu.header.set-title', title);
            this.sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
        },

        /**
         * Sets header toolbar with conversion options according to configuration
         */
        setHeaderToolbar = function() {
            var items = [],
                options = {
                    icon: 'gear',
                    iconSize: 'large',
                    group: 'left',
                    id: 'options-button',
                    position: 30,
                    items: []
                };

            // save button
            items.push({
                id: 'save-button',
                icon: 'floppy-o',
                iconSize: 'large',
                class: 'highlight',
                position: 1,
                group: 'left',
                disabled: true,
                callback: function() {
                    this.sandbox.emit('sulu.header.toolbar.save');
                }.bind(this)
            });

            // delete select item
            options.items.push({
                title: this.sandbox.translate('toolbar.delete'),
                callback: function() {
                    this.sandbox.emit('sulu.header.toolbar.delete');
                }.bind(this)
            });

            items.push(options);
            this.sandbox.emit('sulu.header.set-toolbar', {data: items});
        };

    return {
        /**
         * sets header data: breadcrumb, headline and content tabs for account
         * @param {Object} account Backbone-Entity
         */
        setHeader: function(account) {
            // parse to json
            account = account.toJSON();

            // set headline based on type and account
            setHeadlinesAndBreadCrumb.call(this, account);
            setHeaderToolbar.call(this);
        }
    };
});
