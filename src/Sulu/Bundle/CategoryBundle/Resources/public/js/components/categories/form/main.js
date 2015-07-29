/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function () {

    'use strict';

    var defaults = {
            activeTab: null,
            data: {},
            instanceName: 'category',
            newCategoryTitle: 'sulu.category.new-category'
        },

        tabs = {
            DETAILS: 'details'
        },

        constants = {
            detailsFromSelector: '#category-form',
            lastClickedCategorySettingsKey: 'categoriesLastClicked'
        };

    return {

        view: true,

        templates: ['/admin/category/template/category/form/details'],

        /**
         * Initializes the collections list
         */
        initialize: function () {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.saved = true;

            this.bindCustomEvents();
            this.render();

            if (!!this.options.data.id) {
                this.sandbox.sulu.saveUserSetting(constants.lastClickedCategorySettingsKey, this.options.data.id);
            }
        },

        /**
         * Binds custom related events
         */
        bindCustomEvents: function () {
            this.sandbox.on('sulu.header.back', function () {
                this.sandbox.emit('sulu.category.categories.list');
            }.bind(this));

            this.sandbox.on('sulu.toolbar.save', this.saveDetails.bind(this));
            this.sandbox.on('sulu.toolbar.delete', this.deleteCategory.bind(this));
        },

        /**
         * Renders the component
         */
        render: function () {
            if (this.options.activeTab === tabs.DETAILS) {
                this.renderDetails();
            }
        },

        /**
         * Renderes the details tab
         */
        renderDetails: function () {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/category/template/category/form/details'));
            this.sandbox.form.create(constants.detailsFromSelector);
            this.sandbox.form.setData(constants.detailsFromSelector, this.options.data).then(function () {
                this.bindDetailsDomEvents();
            }.bind(this));
        },

        /**
         * Binds DOM-Events for the details tab
         */
        bindDetailsDomEvents: function () {
            // activate save-button on key input
            this.sandbox.dom.on(constants.detailsFromSelector, 'change keyup', function () {
                if (this.saved === true) {
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'save-button', false);
                    this.saved = false;
                }
            }.bind(this));
        },

        /**
         * Deletes the current category
         */
        deleteCategory: function () {
            if (!!this.options.data.id) {
                this.sandbox.emit('sulu.category.categories.delete', [this.options.data.id], null, function () {
                    this.sandbox.sulu.unlockDeleteSuccessLabel();
                    this.sandbox.emit('sulu.category.categories.list');
                }.bind(this));
            }
        },

        /**
         * Saves the details-tab
         */
        saveDetails: function (action) {
            if (this.sandbox.form.validate(constants.detailsFromSelector)) {
                var data = this.sandbox.form.getData(constants.detailsFromSelector);
                this.options.data = this.sandbox.util.extend(true, {}, this.options.data, data);
                this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');
                this.sandbox.emit('sulu.category.categories.save', this.options.data, this.savedCallback.bind(this, !this.options.data.id, action));
            }
        },

        /**
         * Method which gets called after the save-process has finished
         * @param {Boolean} toEdit if true the form will be navigated to the edit-modus
         * @param {String} action 'new', 'back' or 'edit
         * @param {Object} result the saved category model or the error model
         * @param {Boolean} success to trigger success callback, false to trigger error callback
         */
        savedCallback: function (toEdit, action, result, success) {
            if (success === true) {
                this.sandbox.emit('sulu.header.toolbar.item.disable', 'save-button', true);
                this.saved = true;
                if (action === 'back') {
                    this.sandbox.emit('sulu.category.categories.list');
                } else if (action === 'new') {
                    this.sandbox.emit('sulu.category.categories.form-add', this.options.parent);
                } else if (toEdit === true) {
                    this.sandbox.emit('sulu.category.categories.form', result.id);
                }
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.category-save-desc', 'labels.success');
            } else {
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'save-button', false);
                if (result.code === 1) {
                    this.sandbox.emit('sulu.labels.error.show', 'labels.error.category-unique-key', 'labels.error');
                } else {
                    this.sandbox.emit('sulu.labels.error.show', 'labels.success.category-save-error', 'labels.error');
                }
            }
        }
    };
});
