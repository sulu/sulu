/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'text!/admin/content/template/content/seo.html'], function($, formTemplate) {

    'use strict';

    var formId = '#seo-form',

        seoTab = {

            name: 'seo-tab',

            defaults: {
                options: {
                    maxTitleCharacters: 55,
                    maxDescriptionCharacters: 155,
                    maxKeywords: 5,
                    keywordsSeparator: ',',
                    excerptUrlPrefix: 'www.yoursite.com'
                },
                templates: {
                    form: formTemplate
                }
            },

            layout: {
                extendExisting: true,
                content: {
                    width: 'fixed',
                    rightSpace: true,
                    leftSpace: true
                }
            },

            initialize: function() {
                this.tabInitialize();

                this.title = {
                    $el: null,
                    $counter: null
                };
                this.description = {
                    $el: null,
                    $counter: null
                };
                this.keywords = {
                    $el: null,
                    $counter: null,
                    count: 0
                };

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
                this.sandbox.dom.on(this.$el, 'keyup', this.updateExcerpt.bind(this));
            },

            submit: function(action) {
                if (this.sandbox.form.validate(formId)) {
                    this.data = this.sandbox.form.getData(formId);
                    this.save(this.data, action);
                }
            },

            render: function(data) {
                this.data = data;
                this.$el.html(
                    this.templates.form({
                        translate: this.sandbox.translate,
                        siteUrl: this.getUrl(data)
                    })
                );

                this.createForm(data);
                this.listenForChange();
            },

            createForm: function(data) {
                this.sandbox.form.create(formId).initialized.then(function() {
                    this.sandbox.form.setData(formId, data).then(function() {
                        this.listenForChange();
                        this.updateExcerpt();
                        this.initializeTitleCounter();
                        this.initializeDescriptionCounter();
                        this.initializeKeywordsCounter();
                    }.bind(this));
                }.bind(this));
            },

            initializeKeywordsCounter: function() {
                this.keywords.$el = this.$find('#seo-keywords');
                this.keywords.$counter = this.$find('#keywords-left');
                this.updateKeywordsCounter();
                this.sandbox.dom.on(this.keywords.$el, 'keyup', this.updateKeywordsCounter.bind(this));
            },

            updateExcerpt: function() {
                // update title
                this.sandbox.dom.html(this.$find('#seo-excerpt-title'), this.sandbox.dom.val(this.$find('#seo-title')));

                // update description
                this.sandbox.dom.html(
                    this.$find('#seo-excerpt-description'),
                    this.sandbox.dom.val(this.$find('#seo-description'))
                );
            },

            initializeTitleCounter: function() {
                this.title.$el = this.$find('#seo-title');
                this.title.$counter = this.$find('#title-left');
                this.updateTitleCounter();
                this.sandbox.dom.on(this.title.$el, 'keyup', this.updateTitleCounter.bind(this));
            },

            updateTitleCounter: function() {
                var charsLeft = this.options.maxTitleCharacters - this.sandbox.dom.val(this.title.$el).length;
                this.sandbox.dom.html(this.title.$counter, ' ' + charsLeft + ' ');
                this.toggleWarning(this.title.$counter.parent(), (charsLeft <= 0));
            },

            initializeDescriptionCounter: function() {
                this.description.$el = this.$find('#seo-description');
                this.description.$counter = this.$find('#description-left');
                this.updateDescriptionCounter();
                this.sandbox.dom.on(this.description.$el, 'keyup', this.updateDescriptionCounter.bind(this));
            },

            updateDescriptionCounter: function() {
                var charsLeft = this.options.maxDescriptionCharacters - this.sandbox.dom.val(this.description.$el).length;
                this.sandbox.dom.html(this.description.$counter, ' ' + charsLeft + ' ');
                this.toggleWarning(this.description.$counter.parent(), (charsLeft <= 0));
            },

            updateKeywordsCounter: function() {
                var value = this.sandbox.dom.trim(
                    this.sandbox.dom.trim(this.sandbox.dom.val(this.keywords.$el)),
                    this.options.keywordsSeparator
                    ),
                    keywords = value.split(this.options.keywordsSeparator),
                    keywordsLeft = this.options.maxKeywords;
                // remove empty entries
                keywords = keywords.filter(function(value) {
                    return !!value;
                });
                this.keywords.count = keywords.length;
                keywordsLeft = keywordsLeft - this.keywords.count;
                this.sandbox.dom.html(this.keywords.$counter, keywordsLeft);
                this.toggleWarning(this.keywords.$counter.parent(), (keywordsLeft < 0));
            },

            listenForChange: function() {
                this.sandbox.dom.on(formId, 'keyup change', function() {
                    this.setHeaderBar();
                }.bind(this), '.trigger-save-button');
            },

            toggleWarning: function($el, warn) {
                if (warn) {
                    $el.addClass('seo-warning');
                } else {
                    $el.removeClass('seo-warning');
                }
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
             * This method function can to be overwritten by the implementation to convert the data from "options.data".
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
            },

            /**
             * This method function has to be overwritten by the implementation to generate the seo-url.
             *
             * @param {object} data
             */
            getUrl: function(data) {
                throw new Error('"getUrl" not implemented');
            }
        };

    return {
        name: seoTab.name,

        initialize: function(app) {
            app.components.addType(seoTab.name, seoTab);
        }
    };
});
