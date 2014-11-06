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
    var setHeadlinesAndBreadCrumb = function(accountType, accountName, accountNumber) {
            var breadcrumb = [
                    {title: 'navigation.contacts'},
                    {title: 'contact.accounts.title', event: 'sulu.contacts.accounts.list'}
                ],
                title = this.sandbox.translate('contact.accounts.title'),
                typeTranslation;

            if (!!accountType) {
                typeTranslation = this.sandbox.translate(accountType.translation);
            } else {
                typeTranslation = this.sandbox.translate('contact.account.type.basic');
            }

            if (accountNumber) {
                breadcrumb.push({title: typeTranslation + ' #' + accountNumber});
                title = accountName;
            } else {
                breadcrumb.push({title: typeTranslation});
            }

            this.sandbox.emit('sulu.header.set-title', title);
            this.sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
        },

    // enables tabs based on account type
        enableTabsByType = function(accountType) {
            var index;

            if (!accountType && !accountType.hasownProperty('tabs')) { // no account type specified
                return;
            }

            for (index in accountType.tabs) {
                if (accountType.tabs[index] === true) {
                    this.sandbox.emit('husky.tabs.header.item.show', index);
                }
            }
        },

    // get account type based on information given
        getAccountType = function(data, accountTypeName) {
            var typeInfo, compareAttribute, i, type,
                accountType = 0,
                accountTypes,
                section = AppConfig.getSection('sulu-contact'); // get account types

            if (!section || section.length > 0 || !section.hasOwnProperty('accountTypes')) {
                return false;
            } else {
                accountTypes = section.accountTypes;
            }

            if (!!data && data.hasOwnProperty('id') && data.hasOwnProperty('type')) {
                typeInfo = data.type;
                compareAttribute = 'id';
            } else if (accountTypeName) {
                typeInfo = accountTypeName;
                compareAttribute = 'name';
            } else {
                typeInfo = 0;
                compareAttribute = 'id';
            }

            // get account type information
            for (i in accountTypes) {
                type = accountTypes[i];
                if (type[compareAttribute] === typeInfo) {
                    accountType = type;
                    break;
                }
            }

            return accountType;
        },

        /**
         * Generates array of conversion options for a specific account type
         * @param accountType of specific account
         * @param accountTypes
         * @returns {Array}
         */
        getItemsForConvertOperation = function(accountType, accountTypes) {
            var items = [];
            this.sandbox.util.each(accountType.convertableTo, function(key, enabled) {
                if (!!enabled) {
                    var item = getHeaderItem.call(this, accountTypes, key);
                    items.push(item);
                }
            }.bind(this));

            return items;
        },

        /**
         * Returns items for header
         * @param accountTypes
         * @param key
         * @returns {Object}
         */
        getHeaderItem = function(accountTypes, key) {

            var item;
            this.sandbox.util.each(accountTypes, function(name, el) {
                if (el.name === key) {
                    item = {
                        title: this.sandbox.translate(el.translation + '.conversion'),
                        callback: function() {
                            this.sandbox.emit('sulu.contacts.account.convert', el);
                        }.bind(this)
                    };
                    return false;
                }
            }.bind(this));

            return item;
        },

        /**
         * Sets header toolbar with conversion options according to configuration
         */
            setHeaderToolbar = function(accountType, accountTypes) {

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

            // only for saved accounts
            if (!!this.account.id) {
                options.items = getItemsForConvertOperation.call(this, accountType, accountTypes);
            }

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
         * @param {String} [accountTypeName] Name of account entity
         */
        setHeader: function(account, accountTypeName) {

            var accountType,
                accountTypes = AppConfig.getSection('sulu-contact').accountTypes;

            // parse to json
            account = account.toJSON();

            // get account type
            accountType = getAccountType.call(this, account, accountTypeName);
            // enable tabs based on type
            enableTabsByType.call(this, accountType);
            // set headline based on type and account
            setHeadlinesAndBreadCrumb.call(this, accountType, account.name, account.number);

            setHeaderToolbar.call(this, accountType, accountTypes);

            this.sandbox.emit('sulu.contacts.account.types', {accountType: accountType, accountTypes: accountTypes});

        },

        /**
         * returns account Type-Object of a given account
         * @param account account to get type from
         * @param accountTypeName if just name of type is given
         * @returns {}
         */
        getAccountType: function(account, accountTypeName) {
            return getAccountType.call(this, account, accountTypeName);
        },

        /**
         * returns account-type–ID based on account-type-name
         * @param accountTypeName
         * @returns {}
         */
        getAccountTypeIdByTypeName: function(accountTypeName) {
            return getAccountType.call(this, null, accountTypeName).id;
        },

        getAccountTypeById: function(id) {
            return getAccountType.call({ id: id, type: id});
        }
    };
});
