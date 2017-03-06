/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore', 'jquery'], function(_, $) {

    'use strict';

    var tab = {

        name: 'form-tab',

        layout: {
            extendExisting: true,
            content: {
                width: 'fixed',
                rightSpace: true,
                leftSpace: true
            }
        },

        initialize: function() {
            this.formId = this.getFormId();

            this.tabInitialize();

            this.render(this.data);
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.tab.save', this.submit.bind(this));
        },

        /**
         * Validates form and returns if it's valid.
         *
         * @returns {bool}
         */
        validate: function() {
            if (!this.sandbox.form.validate(this.formId)) {
                return false;
            }

            return true;
        },

        submit: function() {
            if (!this.validate()) {
                // Resets save button.
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);

                return;
            }

            var data = this.sandbox.form.getData(this.formId);
            _.each(data, function(value, key) {
                this.data[key] = value;
            }.bind(this));

            this.save(this.data);
        },

        render: function(data) {
            this.data = data;
            this.$el.html(this.getTemplate());

            this.createForm(data);

            this.rendered();
        },

        createForm: function(data) {
            this.sandbox.form.create(this.formId).initialized.then(function() {
                this.sandbox.form.setData(this.formId, data).then(function() {
                    this.sandbox.start(this.formId).then(this.listenForChange.bind(this));
                }.bind(this));
            }.bind(this));
        },

        listenForChange: function() {
            this.sandbox.dom.on(this.formId, 'change keyup', this.setDirty.bind(this));
            this.sandbox.on('husky.ckeditor.changed', this.setDirty.bind(this));
        },

        /**
         * This function can be overwritten by the implementation to load the component.
         *
         * For best-practice the default implementation should be used.
         */
        loadComponentData: function() {
            var promise = $.Deferred();

            promise.resolve(this.parseData(this.options.data()));

            return promise;
        },

        /**
         * This function can be overwritten by the implementation to initialize the component.
         *
         * For best-practice the default implementation should be used.
         */
        tabInitialize: function() {
            this.sandbox.emit('sulu.tab.initialize', this.name);
        },

        /**
         * This function can be overwritten by the implementation.
         */
        rendered: function() {
            this.sandbox.emit('sulu.tab.rendered', this.name);
        },

        /**
         * This function can be overwritten by the implementation to enable save-button.
         *
         * For best-practice the default implementation should be used.
         */
        setDirty: function() {
            this.sandbox.emit('sulu.tab.dirty');
        },

        /**
         * This function can be overwritten by the implementation to process the data which was returned
         * by the rest-api.
         *
         * For best-practice the default implementation should be used.
         *
         * @param {object} data
         */
        saved: function(data) {
            this.sandbox.emit('sulu.tab.saved', data);
        },

        /**
         * This function has to be overwritten by the implementation to convert the data from "options.data".
         *
         * @param {object} data
         */
        parseData: function(data) {
            throw new Error('"parseData" not implemented');
        },

        /**
         * This function has to be overwritten by the implementation to save the data.
         *
         * @param {object} data
         */
        save: function(data) {
            throw new Error('"save" not implemented');
        },

        /**
         * This function has to be overwritten by the implementation to generate the form-template.
         */
        getTemplate: function() {
            throw new Error('"getTemplate" not implemented');
        },

        /**
         * This function has to be overwritten by the implementation. It should return the id for the form.
         */
        getFormId: function() {
            throw new Error('"getFormId" not implemented');
        }
    };

    return {
        name: tab.name,

        initialize: function(app) {
            app.components.addType(tab.name, tab);
        }
    };
});
