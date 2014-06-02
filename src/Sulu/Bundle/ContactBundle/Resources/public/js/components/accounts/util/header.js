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
    var setHeadlinesAndBreadCrumb = function(accountType, accountName) {
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

            if (accountName) {
                breadcrumb.push({title: typeTranslation + ' #' + this.options.id});
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
        };

    return {
        /**
         * sets header data: breadcrumb, headline and content tabs for account
         * @param {Object} account Backbone-Entity
         * @param {String} [accountTypeName] Name of account entity
         */
        setHeader: function(account, accountTypeName) {

            var accountTypes = AppConfig.getSection('sulu-contact').accountTypes,
                accountType;

            // parse to json
            account = account.toJSON();

            // get account type
            accountType = getAccountType.call(this, account, accountTypeName);
            // enable tabs based on type
            enableTabsByType.call(this, accountType);
            // set headline based on type and account
            setHeadlinesAndBreadCrumb.call(this, accountType, account.name);

            this.sandbox.emit('sulu.contacts.account.types', {accountType: accountType, accountTypes: accountTypes});

        },

        /**
         * returns account Type-Object of a given account
         * @param account account to get type from
         * @param accountTypeName if just name of type is given
         * @returns {}
         */
        getAccountType: function(account, accountTypeName) {
            return getAccountType.call(this, account, accountTypeName) ;
        },

        /**
         * returns account-type–ID based on account-type-name
         * @param accountTypeName
         * @returns {}
         */
        getAccountTypeIdByTypeName: function(accountTypeName) {
            return getAccountType.call(this, null, accountTypeName).id;
        }
    };
});
