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
    'services/sulucontact/contact-manager'
], function(Config, ContactManager) {

    'use strict';

    var formSelector = '#contact-form',
        fields = ['urls', 'emails', 'faxes', 'phones', 'notes'],

        constants = {
            tagsId: '#tags',
            addressAddId: '#address-add',
            bankAccountAddId: '#bank-account-add',
            addAddressWrapper: '.grid-row',
            addBankAccountsWrapper: '.grid-row',
            editFormSelector: '#contact-edit-form',
            avatarImageId: '#image-content',
            avatarDropzoneSelector: '#image-dropzone',
            avatarDeleteSelector: '#image-delete',
            avatarDownloadSelector: '#image-download',
            imageFormat: '400x400'
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

        templates: ['/admin/contact/template/contact/form'],

        initialize: function() {
            this.data = this.options.data();
            this.formOptions = Config.get('sulu.contact.form');

            this.autoCompleteInstanceName = 'accounts-';
            this.dfdAllFieldsInitialized = this.sandbox.data.deferred();
            this.dfdListenForChange = this.sandbox.data.deferred();
            this.dfdFormIsSet = this.sandbox.data.deferred();
            this.dfdBirthdayIsSet = this.sandbox.data.deferred();

            // define when all fields are initialized
            this.sandbox.data.when(this.dfdListenForChange, this.dfdBirthdayIsSet).then(function() {
                this.dfdAllFieldsInitialized.resolve();
            }.bind(this));

            this.render();
            this.listenForChange();
        },

        destroy: function() {
            // stop contact form before leaving
            this.sandbox.stop(constants.editFormSelector);
        },

        render: function() {
            this.sandbox.once('sulu.contacts.set-defaults', this.setDefaults.bind(this));
            this.sandbox.once('sulu.contacts.set-types', this.setTypes.bind(this));
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/contact/template/contact/form', {
                categoryLocale: this.sandbox.sulu.getDefaultContentLocale()
            }));
            this.sandbox.on('husky.dropdown.type.item.click', this.typeClick.bind(this));

            var formData = this.initContactData();
            this.companyInstanceName = 'companyContact' + formData.id;
            this.initForm(formData);
            this.initAvatarContainer(formData);
            this.setTags();
            this.bindCustomEvents();
            this.bindTagEvents(formData);
        },

        // show tags and activate keylistener
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

        /**
         * Initialize avatar container: display avatar image if provided and start avatar-upload dropzone
         * @param data
         */
        initAvatarContainer: function(data) {
            // if avatar is selected and is not a "dummy"
            if (!!data.avatar && !!data.avatar.id) {
                this.updateAvatarContainer(data.avatar.id, data.avatar.thumbnails[constants.imageFormat], data.avatar.url);
            }

            /**
             * Function to generate suitable postUrl according to the current contact status
             * If contact already has an avatar, generate an url to upload new avatar as new version
             * Else upload avatar as new media
             */
            var getPostUrl = function() {
                var curMediaId = this.sandbox.dom.data(constants.avatarImageId, 'mediaId');
                var url = (!!curMediaId) ?
                    '/admin/api/media/' + curMediaId + '?action=new-version' :
                    '/admin/api/media?collection=' + this.formOptions.contactAvatarCollection;

                url = url + '&locale=' + encodeURIComponent(this.sandbox.sulu.getDefaultContentLocale());
                
                // if possible, change the title of the avatar to the name of the contact
                if (!!data.fullName) {
                    url = url + '&title=' + encodeURIComponent(data.fullName);
                }

                return url;
            }.bind(this);

            this.sandbox.start([
                {
                    name: 'dropzone@husky',
                    options: {
                        el: constants.avatarDropzoneSelector,
                        maxFilesize: Config.get('sulu-media').maxFilesize,
                        instanceName: 'contact-avatar',
                        titleKey: '',
                        descriptionKey: 'contact.contacts.avatar-dropzone-text',
                        url: getPostUrl,
                        skin: 'overlay',
                        method: 'POST',
                        paramName: 'fileVersion',
                        showOverlay: false,
                        maxFiles: 1
                    }
                }
            ]);

            this.sandbox.dom.on(constants.avatarDeleteSelector, 'click', function() {
                var curMediaId = this.sandbox.dom.data(constants.avatarImageId, 'mediaId');

                this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                    if (!!confirmed) {
                        this.sandbox.util.save('/admin/api/media/' + curMediaId, 'DELETE').done(function() {
                            this.clearAvatarContainer();
                            this.sandbox.emit('sulu.labels.success.show', 'contact.contacts.avatar.saved');
                        }.bind(this));
                    }
                }.bind(this));
            }.bind(this));
        },

        /**
         * Display given picture in avatar container. Set avatar-div data to media id which is read on saving.
         * @param mediaId
         * @param url
         * @param fullUrl
         */
        updateAvatarContainer: function(mediaId, url, fullUrl) {
            var $imageContent = this.sandbox.dom.find(constants.avatarImageId);
            this.sandbox.dom.data($imageContent, 'mediaId', mediaId);
            this.sandbox.dom.css($imageContent, 'background-image', 'url(' + url + ')');
            this.sandbox.dom.addClass($imageContent.parent(), 'no-default');
            this.sandbox.dom.attr(constants.avatarDownloadSelector, 'href', fullUrl);
        },

        /**
         * Remove picture in avatar container.
         */
        clearAvatarContainer: function() {
            var $imageContent = this.sandbox.dom.find(constants.avatarImageId);
            $imageContent.removeData('mediaId');
            this.sandbox.dom.css($imageContent, 'background-image', '');
            this.sandbox.dom.removeClass($imageContent.parent(), 'no-default');
        },

        /**
         * Assign uploaded avatar to contact by saving contact with given media id
         * @param mediaResponse media upload response
         */
        saveAvatarData: function(mediaResponse) {
            if (!!this.sandbox.dom.data(constants.avatarImageId, 'mediaId')) {
                // avatar was uploaded as new version of existing avatar
                this.sandbox.emit('sulu.labels.success.show', 'contact.contacts.avatar.saved');
            } else if (!!this.data.id) {
                var data = this.getData();
                data.avatar = {id: mediaResponse.id};

                ContactManager.saveAvatar(data).then(function(savedData) {
                    this.sandbox.emit('sulu.tab.data-changed', savedData);
                }.bind(this));
            }
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

        setFormData: function(data, startForm) {
            this.numberOfBankAccounts = !!data.bankAccounts ? data.bankAccounts.length : 0;
            this.updateBankAccountAddIcon(this.numberOfBankAccounts);

            // add collection filters to form
            this.sandbox.emit('sulu.contact-form.add-collectionfilters', formSelector);
            this.sandbox.form.setData(formSelector, data).then(function() {

                if (!!startForm) {
                    this.sandbox.start(formSelector);
                } else {
                    this.sandbox.start('#contact-fields');
                }

                this.sandbox.emit('sulu.contact-form.add-required', ['email']);
                this.sandbox.emit('sulu.contact-form.content-set');
                this.dfdFormIsSet.resolve();
            }.bind(this)).fail(function(error) {
                this.sandbox.logger.error('An error occured when setting data!', error);
            }.bind(this));
        },

        initForm: function(data) {
            var options = Config.get('sulucontact.components.autocomplete.default.account');
            options.el = '#company';
            options.value = !!data.account ? data.account : '';
            options.instanceName = this.companyInstanceName;

            this.sandbox.start([
                {
                    name: 'auto-complete@husky',
                    options: options
                }
            ]);

            this.numberOfAddresses = data.addresses.length;
            this.updateAddressesAddIcon(this.numberOfAddresses);

            // when  contact-form is initalized
            this.sandbox.on('sulu.contact-form.initialized', function() {
                // set form data
                var formObject = this.sandbox.form.create(formSelector);
                formObject.initialized.then(function() {
                    this.setFormData(data, true);
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
                this.sandbox.emit('sulu.contact-form.update.addAddressLabel', '#addresses');
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

            // contact save
            this.sandbox.on('sulu.tab.save', this.save, this);

            this.sandbox.on('husky.input.birthday.initialized', function() {
                this.dfdBirthdayIsSet.resolve();
            }, this);

            this.sandbox.once('husky.select.position-select.initialize', function() {
                if (!this.sandbox.dom.find('#' + this.companyInstanceName).val()) {
                    this.enablePositionDropdown(false);
                }
            }, this);

            this.sandbox.on('sulu.contact-form.added.bank-account', function() {
                this.numberOfBankAccounts += 1;
                this.updateBankAccountAddIcon(this.numberOfBankAccounts);
            }, this);

            this.sandbox.on('sulu.contact-form.removed.bank-account', function() {
                this.numberOfBankAccounts -= 1;
                this.updateBankAccountAddIcon(this.numberOfBankAccounts);
            }, this);

            this.initializeDropDownListener(
                'title-select',
                'api/contact/titles');
            this.initializeDropDownListener(
                'position-select',
                'api/contact/positions');

            this.sandbox.on('husky.dropzone.contact-avatar.success', function(file, response) {
                this.saveAvatarData(response);
                this.updateAvatarContainer(response.id, response.thumbnails[constants.imageFormat], response.url);
            }, this);
        },

        initContactData: function() {
            var contactJson = this.data;

            this.sandbox.util.foreach(fields, function(field) {
                if (!contactJson.hasOwnProperty(field)) {
                    contactJson[field] = [];
                }
            });

            contactJson.emails = this.fillFields(contactJson.emails, 1, {
                id: null,
                email: '',
                emailType: this.defaultTypes.emailType
            });
            contactJson.phones = this.fillFields(contactJson.phones, 1, {
                id: null,
                phone: '',
                phoneType: this.defaultTypes.phoneType
            });
            contactJson.faxes = this.fillFields(contactJson.faxes, 1, {
                id: null,
                fax: '',
                faxType: this.defaultTypes.faxType
            });
            contactJson.notes = this.fillFields(contactJson.notes, 1, {
                id: null,
                value: ''
            });
            contactJson.urls = this.fillFields(contactJson.urls, 0, {
                id: null,
                url: '',
                urlType: this.defaultTypes.urlType
            });

            return contactJson;
        },

        typeClick: function(event, $element) {
            $element.find('*.type-value').data('element').setValue(event);
        },

        /**
         * Takes an array of fields and fields it up with empty fields till a minimum amount
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

        getData: function() {
            var data = this.sandbox.util.extend(false, {}, this.data, this.sandbox.form.getData(formSelector));
            if (data.id === '') {
                delete data.id;
            }
            data.tags = this.sandbox.dom.data(this.$find(constants.tagsId), 'tags');
            data.avatar = {
                id: this.sandbox.dom.data(constants.avatarImageId, 'mediaId')
            };
            data.position = this.sandbox.form.element.getValue('#contact-position');
            data.title = this.sandbox.form.element.getValue('#contact-title');

            // FIXME auto complete in mapper
            // only get id, if auto-complete is not empty:
            data.account = {
                id: this.sandbox.dom.attr('#' + this.companyInstanceName, 'data-id')
            };

            return data;
        },

        save: function() {
            if (this.sandbox.form.validate(formSelector)) {
                this.sandbox.emit('sulu.tab.saving');
                var data = this.getData();
                ContactManager.save(data).then(function(savedData) {
                    this.data = savedData;
                    var formData = this.initContactData();
                    this.setFormData(formData);
                    this.sandbox.emit('sulu.tab.saved', savedData, true);
                }.bind(this));
            }
        },

        /**
         * Register events for editable drop downs
         * @param instanceName
         */
        initializeDropDownListener: function(instanceName) {
            var instance = 'husky.select.' + instanceName;
            this.sandbox.on(instance + '.selected.item', function(id) {
                if (id > 0) {
                    this.sandbox.emit('sulu.tab.dirty');
                }
            }.bind(this));
            this.sandbox.on(instance + '.deselected.item', function() {
                this.sandbox.emit('sulu.tab.dirty');
            }.bind(this));
            this.sandbox.on(instance + '.delete', this.deleteSelectData.bind(this, instanceName));
            this.sandbox.on(instance + '.save', this.saveSelectData.bind(this, instanceName));
        },

        /**
         * Saves the data of the editable selects
         * @param type The type of the editable select
         * @param data The data to save
         */
        saveSelectData: function(type, data) {
            var method = (type === 'title-select') ? ContactManager.saveTitles : ContactManager.savePositions;
            method(data).then(function(response) {
                this.sandbox.emit(
                    'husky.select.' + type + '.update',
                    response,
                    [response[response.length - 1].id], // preselected
                    true,
                    true
                );
            }.bind(this));
        },

        /**
         * Deletes the data of the editable selects
         * @param type The type of the editable select
         * @param ids The ids of the data to delete
         */
        deleteSelectData: function(type, ids) {
            var method = (type === 'title-select') ? ContactManager.deleteTitle : ContactManager.deletePosition;
            this.sandbox.util.foreach(ids, function(id) {
                method(id);
            }.bind(this));
        },

        /**
         * Enables or disables the position dropdown
         * @param enable
         */
        enablePositionDropdown: function(enable) {
            if (!!enable) {
                this.sandbox.emit('husky.select.position-select.enable');
            } else {
                this.sandbox.emit('husky.select.position-select.disable');
            }
        },

        // event listens for changes in form
        listenForChange: function() {
            // listen for change after TAGS and BIRTHDAY-field have been set
            this.sandbox.data.when(this.dfdAllFieldsInitialized).then(function() {

                this.sandbox.dom.on(formSelector, 'change keyup', function() {
                    this.sandbox.emit('sulu.tab.dirty');
                }.bind(this), 'select, input, textarea, .trigger-save-button, #birthday');

                this.sandbox.on('sulu.contact-form.changed', function() {
                    this.sandbox.emit('sulu.tab.dirty');
                }.bind(this));

                // disable position dropdown when company is empty
                this.sandbox.dom.on('#company', 'keyup', function(data) {
                    if (!data.target.value) {
                        this.enablePositionDropdown(false);
                    }
                }.bind(this));

                // enable position dropdown only if something got selected
                this.companySelected = 'husky.auto-complete.' + this.companyInstanceName + '.select';
                this.sandbox.on(this.companySelected, function() {
                    this.enablePositionDropdown(true);
                }.bind(this));

            }.bind(this));

            this.sandbox.on('husky.select.form-of-address.selected.item', function() {
                this.sandbox.emit('sulu.tab.dirty');
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
