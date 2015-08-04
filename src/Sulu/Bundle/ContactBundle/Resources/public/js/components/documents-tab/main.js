/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['widget-groups'], function(WidgetGroups) {

    'use strict';

    return {

        view: true,

        layout: function() {
            return {
                content: {
                    width: 'fixed'
                },
                sidebar: {
                    width: 'max',
                    cssClasses: 'sidebar-padding-50'
                }
            };
        },

        templates: ['/admin/contact/template/basic/documents'],

        initialize: function() {

            this.form = '#documents-form';
            this.newSelections = [];
            this.removedSelections = [];
            this.saved = true;

            this.currentSelection = this.getPropertyFromArrayOfObject(this.options.data.medias, 'id');

            this.setHeaderBar(true);
            this.render();

            if (!!this.options.data && !!this.options.data.id) {
                if (this.options.type === 'contact' && WidgetGroups.exists('contact-detail')) {
                    this.initSidebar('/admin/widget-groups/contact-detail?contact=', this.options.data.id);
                } else if (this.options.type === 'account' && WidgetGroups.exists('account-detail')) {
                    this.initSidebar('/admin/widget-groups/account-detail?account=', this.options.data.id);
                }
            }
        },

        getPropertyFromArrayOfObject: function(data, propertyName) {
            if (this.sandbox.util.typeOf(data) === 'array' &&
                data.length > 0 &&
                this.sandbox.util.typeOf(data[0]) === 'object') {
                var values = [];
                this.sandbox.util.foreach(data, function(el) {
                    values.push(el[propertyName]);
                }.bind(this));
                return values;
            } else {
                return data;
            }
        },

        initSidebar: function(url, id) {
            this.sandbox.emit('sulu.sidebar.set-widget', url + id);
        },

        render: function() {
            var data = this.options.data;
            this.html(this.renderTemplate(this.templates[0]));
            this.initForm(data);

            this.bindCustomEvents();
        },

        initForm: function(data) {
            var formObject = this.sandbox.form.create(this.form);
            formObject.initialized.then(function() {
                this.setForm(data);
            }.bind(this));
        },

        setForm: function(data) {
            this.sandbox.form.setData(this.form, data).fail(function(error) {
                this.sandbox.logger.error("An error occured when setting data!", error);
            }.bind(this));
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.toolbar.save', function(action) {
                this.submit(action);
            }, this);

            this.sandbox.on('sulu.toolbar.delete', function() {
                if (this.options.type === 'account') {
                    this.sandbox.emit('sulu.contacts.account.delete', this.options.data.id);
                } else {
                    this.sandbox.emit('sulu.contacts.contact.delete', this.options.data.id);
                }
            }, this);

            this.sandbox.on('sulu.header.back', function() {
                if (this.options.type === 'account') {
                    this.sandbox.emit('sulu.contacts.accounts.list', this.options.data);
                } else {
                    this.sandbox.emit('sulu.contacts.contacts.list');
                }
            }, this);

            this.sandbox.on('sulu.media-selection.document-selection.data-changed', function() {
                this.setHeaderBar(false);
            }, this);

            this.sandbox.on('sulu.contacts.contacts.medias.removed', this.resetAndRemoveFromCurrent.bind(this));
            this.sandbox.on('sulu.contacts.accounts.medias.removed', this.resetAndRemoveFromCurrent.bind(this));

            this.sandbox.on('sulu.contacts.accounts.medias.saved', this.resetAndAddToCurrent.bind(this));
            this.sandbox.on('sulu.contacts.contacts.medias.saved', this.resetAndAddToCurrent.bind(this));

            this.sandbox.on('sulu.media-selection.document-selection.record-selected', this.selectItem.bind(this));
            this.sandbox.on('sulu.media-selection.document-selection.record-deselected', this.deselectItem.bind(this));
            this.sandbox.on('husky.dropzone.media-selection-document-selection.files-added', this.addedItems.bind(this));
        },

        resetAndRemoveFromCurrent: function(data) {
            this.setHeaderBar(true);
            this.newSelections = [];
            this.removedSelections = [];
            this.sandbox.util.foreach(data, function(id) {
                if (this.currentSelection.indexOf(id) > -1) {
                    this.currentSelection.splice(this.currentSelection.indexOf(id), 1);
                }
            }.bind(this));

            this.setForm(this.currentSelection);
        },

        resetAndAddToCurrent: function(data) {
            this.setHeaderBar(true);
            this.newSelections = [];
            this.removedSelections = [];
            this.currentSelection = this.currentSelection.concat(data);
            this.setForm(this.currentSelection);
        },

        deselectItem: function(id) {
            // when an element is in current selection and was deselected
            if (this.currentSelection.indexOf(id) > -1 && this.removedSelections.indexOf(id) === -1) {
                this.removedSelections.push(id);
            }

            if (this.newSelections.indexOf(id) > -1) {
                this.newSelections.splice(this.newSelections.indexOf(id), 1);
            }
        },

        /**
         * Processes an array of items
         * @param items - array of items
         */
        addedItems: function(items) {
            this.sandbox.util.foreach(items, function(item) {
                if (!!item && !!item.id) {
                    this.selectItem(item.id);
                }
            }.bind(this));
        },

        selectItem: function(id) {
            // add element when it is really new and not already selected
            if (this.currentSelection.indexOf(id) < 0 && this.newSelections.indexOf(id) < 0) {
                this.newSelections.push(id);
            }

            if (this.removedSelections.indexOf(id) > -1) {
                this.removedSelections.splice(this.removedSelections.indexOf(id), 1);
            }
        },

        /**
         * Submits the selection depending on the type
         * @param action {String} the action after save
         */
        submit: function(action) {
             if (this.sandbox.form.validate(this.form)) {
                if (this.options.type === 'account' || this.options.type === 'contact') {
                    this.sandbox.emit('sulu.contacts.accounts.medias.save', this.options.data.id, this.newSelections, this.removedSelections, action);
                } else {
                    this.sandbox.logger.error('Undefined type for documents component!');
                }
            }
        },

        /** @var Bool saved - defines if saved state should be shown */
        setHeaderBar: function(saved) {
            if (saved !== this.saved) {
                if (!!saved) {
                    this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
                } else {
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
                }
            }
            this.saved = saved;
        }
    };
});
