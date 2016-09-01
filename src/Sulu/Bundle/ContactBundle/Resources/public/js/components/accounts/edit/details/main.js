/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'config',
    'services/sulucontact/account-manager'
], function(Config, AccountManager) {

    'use strict';

    var fields = ['urls', 'emails', 'faxes', 'phones', 'notes', 'addresses'],

        constants = {
            tagsId: '#tags',
            addressAddId: '#address-add',
            bankAccountAddId: '#bank-account-add',
            addAddressWrapper: '.grid-row',
            addBankAccountsWrapper: '.grid-row',
            editFormSelector: '#contact-edit-form',
            formSelector: '#account-form',
            formContactFields: '#contact-fields',
            logoImageId: '#image-content',
            logoDropzoneSelector: '#image-dropzone',
            logoDeleteSelector: '#image-delete',
            avatarDownloadSelector: '#image-download',
            logoThumbnailFormat: '400x400-inset'
        },

        customTemplates = {
            addBankAccountsIcon: [
                '<div class="grid-row">',
                '   <div class="grid-col-12">',
                '       <div id="bank-account-add" class="addButton bank-account-add m-left-140"></div>',
                '   </div>',
                '</div>'
            ].join(''),
            addAddressesIcon: [
                '<div class="grid-row">',
                '   <div class="grid-col-12">',
                '       <div id="address-add" class="addButton address-add m-left-140"></div>',
                '   </div>',
                '</div>'
            ].join('')
        };

    return {

        tabOptions: {
            noTitle: true
        },

        layout: function() {
            return {
                content: {
                    width: 'max',
                    leftSpace: false,
                    rightSpace: false
                }
            };
        },

        templates: ['/admin/contact/template/account/form'],

        initialize: function() {
            this.data = this.options.data();
            this.formOptions = Config.get('sulu.contact.form');

            this.autoCompleteInstanceName = 'contacts-';
            this.dfdListenForChange = this.sandbox.data.deferred();
            this.dfdFormIsSet = this.sandbox.data.deferred();

            this.render();
            this.listenForChange();
        },

        destroy: function() {
            this.sandbox.emit('sulu.header.toolbar.item.hide', 'disabler');
            this.cleanUp();
        },

        render: function() {
            this.sandbox.emit('sulu.header.toolbar.item.show', 'disabler');
            this.sandbox.once('sulu.contacts.set-defaults', this.setDefaults.bind(this));
            this.sandbox.once('sulu.contacts.set-types', this.setTypes.bind(this));

            this.html(this.renderTemplate('/admin/contact/template/account/form', {
                categoryLocale: this.sandbox.sulu.getDefaultContentLocale()
            }));

            var formData = this.initAccountData();
            this.initForm(formData);
            this.initLogoContainer(formData);
            this.setTags();
            this.bindCustomEvents();
            this.bindTagEvents(formData);
        },

        /**
         * show tags and activate keylistener
         */
        setTags: function() {
            var uid = this.sandbox.util.uniqueId();
            if (this.data.id) {
                uid += '-' + this.data.id;
            }
            this.autoCompleteInstanceName += uid;

            this.dfdFormIsSet.then(function() {
                this.sandbox.start([
                    {
                        name: 'auto-complete-list@husky',
                        options: {
                            el: '#tags',
                            instanceName: this.autoCompleteInstanceName,
                            getParameter: 'search',
                            itemsKey: 'tags',
                            remoteUrl: '/admin/api/tags?flat=true&sortBy=name&searchFields=name',
                            completeIcon: 'tag',
                            noNewTags: true
                        }
                    }
                ]);
            }.bind(this));
        },

        bindTagEvents: function(data) {
            if (!!data.tags && data.tags.length > 0) {
                // set tags after auto complete list was initialized
                this.sandbox.on('husky.auto-complete-list.' + this.autoCompleteInstanceName + '.initialized', function() {
                    this.sandbox.emit('husky.auto-complete-list.' + this.autoCompleteInstanceName + '.set-tags', data.tags);
                }.bind(this));
                // listen for change after items have been added
                this.sandbox.on('husky.auto-complete-list.' + this.autoCompleteInstanceName + '.items-added', function() {
                    this.dfdListenForChange.resolve();
                }.bind(this));
            } else {
                this.dfdListenForChange.resolve();
            }
        },

        /**
         * Initialize logo container: display logo image if provided and start logo-upload dropzone
         * @param data
         */
        initLogoContainer: function(data) {
            // if logo is selected and is not a "dummy"
            if (!!data.logo && !!data.logo.id) {
                this.updateLogoContainer(data.logo.id, data.logo.thumbnails[constants.logoThumbnailFormat], data.logo.url);
            }

            /**
             * Function to generate suitable postUrl according to the current account status
             * If account already has a logo, generate an url to upload new logo as new version
             * Else upload logo as new media
             */
            var getPostUrl = function() {
                var curMediaId = this.sandbox.dom.data(constants.logoImageId, 'mediaId');
                var url = (!!curMediaId) ?
                    '/admin/api/media/' + curMediaId + '?action=new-version' :
                    '/admin/api/media?collection=' + this.formOptions.accountAvatarCollection;
                
                url = url + '&locale=' + encodeURIComponent(this.sandbox.sulu.getDefaultContentLocale());

                // if possible, change the title of the logo to the name of the account
                if (!!data.name) {
                    url = url + '&title=' + encodeURIComponent(data.name);
                }

                return url;
            }.bind(this);

            this.sandbox.start([
                {
                    name: 'dropzone@husky',
                    options: {
                        el: constants.logoDropzoneSelector,
                        maxFilesize: Config.get('sulu-media').maxFilesize,
                        instanceName: 'account-logo',
                        titleKey: '',
                        descriptionKey: 'contact.accounts.logo-dropzone-text',
                        url: getPostUrl,
                        skin: 'overlay',
                        method: 'POST',
                        paramName: 'fileVersion',
                        showOverlay: false,
                        maxFiles: 1
                    }
                }
            ]);

            this.sandbox.dom.on(constants.logoDeleteSelector, 'click', function() {
                var curMediaId = this.sandbox.dom.data(constants.logoImageId, 'mediaId');

                this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                    if (!!confirmed) {
                        this.sandbox.util.save('/admin/api/media/' + curMediaId, 'DELETE').done(function() {
                            this.clearLogoContainer();
                            this.sandbox.emit('sulu.labels.success.show', 'contact.accounts.logo.saved');
                        }.bind(this));
                    }
                }.bind(this));
            }.bind(this));
        },

        /**
         * Display given picture in logo container. Set logo-div data to media id which is read on saving.
         * @param mediaId
         * @param url
         * @param fullUrl
         */
        updateLogoContainer: function(mediaId, url, fullUrl) {
            var $imageContent = this.sandbox.dom.find(constants.logoImageId);
            this.sandbox.dom.data($imageContent, 'mediaId', mediaId);
            this.sandbox.dom.css($imageContent, 'background-image', 'url(' + url + ')');
            this.sandbox.dom.addClass($imageContent.parent(), 'no-default');
            this.sandbox.dom.attr(constants.avatarDownloadSelector, 'href', fullUrl);
        },

        /**
         * Remove picture in logo container.
         */
        clearLogoContainer: function() {
            var $imageContent = this.sandbox.dom.find(constants.logoImageId);
            $imageContent.removeData('mediaId');
            this.sandbox.dom.css($imageContent, 'background-image', '');
            this.sandbox.dom.removeClass($imageContent.parent(), 'no-default');
        },

        /**
         * Assign uploaded logo to account by saving account with given media id
         * @param mediaResponse media upload response
         */
        saveLogoData: function(mediaResponse) {
            if (!!this.sandbox.dom.data(constants.logoImageId, 'mediaId')) {
                // logo was uploaded as new version of existing logo
                this.sandbox.emit('sulu.labels.success.show', 'contact.accounts.logo.saved');
            } else if (!!this.data.id) {
                var data = this.getData();
                data.logo = {id: mediaResponse.id};

                AccountManager.saveLogo(data).then(function(savedData) {
                    this.sandbox.emit('sulu.tab.data-changed', savedData);
                }.bind(this));
            }
        },

        /**
         * is getting called when template is initialized
         * @param defaultTypes
         */
        setDefaults: function(defaultTypes) {
            this.defaultTypes = defaultTypes;
        },

        /**
         * is getting called when template is initialized
         * @param types
         */
        setTypes: function(types) {
            this.fieldTypes = types;
        },

        /**
         * Takes an array of fields and fills it up with empty fields till a minimum amount
         * @param field {Object} array of fields to manipulate
         * @param minAmount {Number} minimum amount of fields to exist
         * @param value {Object} empty object to insert (for minimum amount of fields)
         * @returns {Object} manipulated fields array
         */
        fillFields: function(field, minAmount, value) {
            var i = -1, length = field.length, attributes;

            // if minimum fields stated is bigger than the actual length loop more times
            if (length < minAmount) {
                length = minAmount;
            }

            for (; ++i < length;) {
                // construct the attributes object for fields under and equal the minimum amount
                if ((i + 1) > minAmount) {
                    attributes = {};
                } else {
                    attributes = {
                        permanent: true
                    };
                }

                // if no more fields exists push new, empty fields
                if (!field[i]) {
                    field.push(value);
                    field[field.length - 1].attributes = attributes;
                } else {
                    field[i].attributes = attributes;
                }
            }

            return field;
        },

        initAccountData: function() {
            var accountJson = this.data;

            this.sandbox.util.foreach(fields, function(field) {
                if (!accountJson.hasOwnProperty(field)) {
                    accountJson[field] = [];
                }
            });

            this.fillFields(accountJson.urls, 1, {
                id: null,
                url: '',
                urlType: this.defaultTypes.urlType
            });
            this.fillFields(accountJson.emails, 1, {
                id: null,
                email: '',
                emailType: this.defaultTypes.emailType
            });
            this.fillFields(accountJson.phones, 1, {
                id: null,
                phone: '',
                phoneType: this.defaultTypes.phoneType
            });
            this.fillFields(accountJson.faxes, 1, {
                id: null,
                fax: '',
                faxType: this.defaultTypes.faxType
            });
            this.fillFields(accountJson.notes, 1, {
                id: null,
                value: ''
            });
            return accountJson;
        },

        initForm: function(data) {
            var options = Config.get('sulucontact.components.autocomplete.default.account');
            options.el = '#company';
            options.value = !!data.parent ? data.parent : null;
            options.instanceName = 'companyAccount' + data.id;

            this.sandbox.start([
                {
                    name: 'auto-complete@husky',
                    options: options
                },
                {
                    name: 'input@husky',
                    options: {
                        el: '#vat',
                        instanceName: 'vat-input',
                        value: !!data.uid ? data.uid : ''
                    }
                }
            ]);

            this.numberOfAddresses = data.addresses.length;
            this.updateAddressesAddIcon(this.numberOfAddresses);

            // when  contact-form is initalized
            this.sandbox.on('sulu.contact-form.initialized', function() {
                // set form data
                var formObject = this.sandbox.form.create(constants.formSelector);
                formObject.initialized.then(function() {
                    this.formInitializedHandler(data);
                }.bind(this));
            }.bind(this));

            // initialize contact form
            this.sandbox.start([
                {
                    name: 'contact-form@sulucontact',
                    options: {
                        el: constants.editFormSelector,
                        fieldTypes: this.fieldTypes,
                        defaultTypes: this.defaultTypes
                    }
                }
            ]);
        },

        formInitializedHandler: function(data) {
            this.setFormData(data);
        },

        setFormData: function(data) {
            // add collection filters to form
            this.sandbox.emit('sulu.contact-form.add-collectionfilters', constants.formSelector);

            this.numberOfBankAccounts = !!data.bankAccounts ? data.bankAccounts.length : 0;
            this.updateBankAccountAddIcon(this.numberOfBankAccounts);

            this.sandbox.form.setData(constants.formSelector, data).then(function() {
                this.sandbox.start(constants.formContactFields);
                this.sandbox.emit('sulu.contact-form.add-required', ['email']);
                this.sandbox.emit('sulu.contact-form.content-set');
                this.dfdFormIsSet.resolve();
            }.bind(this));
        },

        /**
         * Adds or removes icon to add addresses
         * @param numberOfAddresses
         */
        updateAddressesAddIcon: function(numberOfAddresses) {
            var $addIcon = this.$find(constants.addressAddId),
                addIcon;

            if (!!numberOfAddresses && numberOfAddresses > 0 && $addIcon.length === 0) {
                addIcon = this.sandbox.dom.createElement(customTemplates.addAddressesIcon);
                this.sandbox.dom.after(this.$find('#addresses'), addIcon);
            } else if (numberOfAddresses === 0 && $addIcon.length > 0) {
                this.sandbox.dom.remove(this.sandbox.dom.closest($addIcon, constants.addAddressWrapper));
            }
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.contact-form.added.address', function() {
                this.numberOfAddresses += 1;
                this.updateAddressesAddIcon(this.numberOfAddresses);
            }, this);

            this.sandbox.on('sulu.contact-form.removed.address', function() {
                this.numberOfAddresses -= 1;
                this.updateAddressesAddIcon(this.numberOfAddresses);
            }, this);

            // account saved
            this.sandbox.on('sulu.tab.save', this.save, this);

            this.sandbox.on('sulu.contact-form.added.bank-account', function() {
                this.numberOfBankAccounts += 1;
                this.updateBankAccountAddIcon(this.numberOfBankAccounts);
            }, this);

            this.sandbox.on('sulu.contact-form.removed.bank-account', function() {
                this.numberOfBankAccounts -= 1;
                this.updateBankAccountAddIcon(this.numberOfBankAccounts);
            }, this);

            this.sandbox.on('husky.dropzone.account-logo.success', function(file, response) {
                this.saveLogoData(response);
                this.updateLogoContainer(response.id, response.thumbnails[constants.logoThumbnailFormat], response.url);
            }, this);
        },

        /**
         * Does some cleanup with aura components
         */
        cleanUp: function() {
            // stop contact form before leaving
            this.sandbox.stop(constants.editFormSelector);
        },

        /**
         * Copies array of objects
         * @param data
         * @returns {Array}
         */
        copyArrayOfObjects: function(data) {
            var newArray = [];
            this.sandbox.util.foreach(data, function(el) {
                newArray.push(this.sandbox.util.extend(true, {}, el));
            }.bind(this));

            return newArray;
        },

        getData: function() {
            var data = this.sandbox.util.extend(false, {}, this.data, this.sandbox.form.getData(constants.formSelector));
            if (!data.id) {
                delete data.id;
            }
            data.logo = {
                id: this.sandbox.dom.data(constants.logoImageId, 'mediaId')
            };

            data.tags = this.sandbox.dom.data(this.$find(constants.tagsId), 'tags');
            // FIXME auto complete in mapper
            data.parent = {
                id: this.sandbox.dom.attr('#company input', 'data-id')
            };

            return data;
        },

        save: function() {
            if (this.sandbox.form.validate(constants.formSelector)) {
                this.sandbox.emit('sulu.tab.saving');
                var data = this.getData();
                AccountManager.save(data).then(function(savedData) {
                    this.data = savedData;
                    var formData = this.initAccountData();
                    this.setFormData(formData);
                    this.sandbox.emit('sulu.tab.saved', savedData, true);
                }.bind(this));
            }
        },

        listenForChange: function() {
            this.dfdListenForChange.then(function() {
                this.sandbox.dom.on(constants.formSelector, 'change keyup', function() {
                    this.sandbox.emit('sulu.tab.dirty');
                }.bind(this), 'select, input, textarea, .trigger-save-button');

                // if a field-type gets changed or a field gets deleted
                this.sandbox.on('sulu.contact-form.changed', function() {
                    this.sandbox.emit('sulu.tab.dirty');
                }.bind(this));
            }.bind(this));
        },

        /**
         * Adds or removes icon to add bank accounts depending on the number of bank accounts
         * @param numberOfBankAccounts
         */
        updateBankAccountAddIcon: function(numberOfBankAccounts) {
            var $addIcon = this.$find(constants.bankAccountAddId),
                addIcon;

            if (!!numberOfBankAccounts && numberOfBankAccounts > 0 && $addIcon.length === 0) {
                addIcon = this.sandbox.dom.createElement(customTemplates.addBankAccountsIcon);
                this.sandbox.dom.after(this.$find('#bankAccounts'), addIcon);
            } else if (numberOfBankAccounts === 0 && $addIcon.length > 0) {
                this.sandbox.dom.remove(this.sandbox.dom.closest($addIcon, constants.addBankAccountsWrapper));
            }
        }
    };
});
