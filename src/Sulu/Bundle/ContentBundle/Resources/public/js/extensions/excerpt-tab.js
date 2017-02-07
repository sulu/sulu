/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery'], function($) {

    'use strict';

    var excerptTab = {
            name: 'excerpt-tab',

            defaults: {
                options: {
                    formId: '#content-form'
                }
            },

            layout: {
                extendExisting: true,
                content: {
                    width: 'fixed',
                    rightSpace: false,
                    leftSpace: false
                }
            },

            initialize: function() {
                this.tabInitialize();
                this.render(this.data);
                this.bindCustomEvents();
                this.bindDomEvents();
            },

            bindCustomEvents: function() {
                this.sandbox.on('sulu.tab.save', function(action) {
                    this.submit(action);
                }, this);
            },

            bindDomEvents: function() {
            },

            submit: function(action) {
                if (this.sandbox.form.validate(this.options.formId)) {
                    this.data = this.sandbox.form.getData(this.options.formId);
                    this.save(this.data, action);
                }
            },

            render: function(data) {
                this.data = data;

                require([this.getTemplate()], function(template) {
                    this.$el.html(
                        this.sandbox.util.template(template, {
                            translate: this.sandbox.translate,
                            options: this.options,
                            categoryLocale: this.options.language
                        })
                    );

                    // avoid using this form elements for preview
                    this.sandbox.dom.removeClass('.preview-update', 'preview-update');
                    this.createForm(data);
                }.bind(this));
            },

            createForm: function(data) {
                this.sandbox.form.create(this.options.formId).initialized.then(function() {
                    this.sandbox.form.setData(this.options.formId, data).then(function() {
                        this.sandbox.start(this.options.formId);
                        this.listenForChange();
                    }.bind(this));
                }.bind(this));
            },

            listenForChange: function() {
                this.sandbox.dom.on(this.options.formId, 'keyup change', function() {
                    this.setHeaderBar();
                }.bind(this), '.trigger-save-button');

                this.sandbox.on('sulu.content.changed', function() {
                    this.setHeaderBar();
                }.bind(this));
            },

            loadComponentData: function() {
                var promise = $.Deferred();

                promise.resolve(this.parseData(this.options.data()));

                return promise;
            },

            /**
             * This method function can be overwritten by the implementation to initialize the component.
             *
             * For best-practice the default implementation should be used.
             */
            tabInitialize: function() {
                this.sandbox.emit('sulu.tab.initialize', this.name);
            },

            /**
             * This method function can be overwritten by the implementation to enable save-button.
             *
             * For best-practice the default implementation should be used.
             */
            setHeaderBar: function() {
                this.sandbox.emit('sulu.tab.dirty');
            },

            /**
             * This method function can be overwritten by the implementation to enable save-button.
             *
             * For best-practice the default implementation should be used.
             */
            getTemplate: function() {
                return 'text!/admin/content/template/form/excerpt.html?language=' + this.options.language
            },

            /**
             * This method function can be overwritten by the implementation to process the data which was returned
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
             * This method function can be overwritten by the implementation to convert the data from "options.data".
             *
             * @param {object} data
             */
            parseData: function(data) {
                return data;
            },

            /**
             * This method function has to be overwritten by the implementation to save the data.
             *
             * @param {object} data
             * @param {string} action
             */
            save: function(data, action) {
                throw new Error('"save" not implemented');
            }
        };

    return {
        name: excerptTab.name,

        initialize: function(app) {
            app.components.addType(excerptTab.name, excerptTab);
        }
    };
});
