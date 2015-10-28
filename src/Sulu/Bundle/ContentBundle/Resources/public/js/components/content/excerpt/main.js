/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    return {

        layout: function() {
            return {
                extendExisting: true,
                content: {
                    width: 'fixed',
                    rightSpace: false,
                    leftSpace: false
                }
            };
        },

        initialize: function() {
            this.sandbox.emit('sulu.app.ui.reset', { navigation: 'small', content: 'auto'});
            this.sandbox.emit('husky.toolbar.header.item.disable', 'template', false);

            this.formId = '#content-form';
            this.load();
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            // content save
            this.sandbox.on('sulu.toolbar.save', this.submit.bind(this));
        },

        submit: function(action) {
            this.sandbox.logger.log('save Model');
            if (this.sandbox.form.validate(this.formId)) {
                this.data.ext.excerpt =  this.sandbox.form.getData(this.formId);
                this.sandbox.emit('sulu.content.contents.save', this.data, action);
            }
        },

        load: function() {
            // get content data
            this.sandbox.emit('sulu.content.contents.get-data', function(data) {
                this.render(data);
            }.bind(this));
        },

        render: function(data) {
            this.data = data;
            require(['text!/admin/content/template/form/excerpt.html?webspace=' + this.options.webspace + '&language=' + this.options.language], function(template) {
                var context = {
                        translate: this.sandbox.translate,
                        options: this.options
                    },
                    tpl = this.sandbox.util.template(template, context);

                this.sandbox.dom.html(this.$el, tpl);

                // avoid using this form elements for preview
                this.sandbox.dom.removeClass('.preview-update', 'preview-update');

                this.dfdListenForChange = this.sandbox.data.deferred();
                this.createForm(this.initData(data));
                this.listenForChange();
            }.bind(this));
        },

        initData: function(data) {
            return data.ext.excerpt;
        },

        createForm: function(data) {
            this.sandbox.form.create(this.formId).initialized.then(function() {
                this.sandbox.form.setData(this.formId, data).then(function() {
                    this.sandbox.start(this.$el, {reset: true});
                    this.dfdListenForChange.resolve();

                    this.sandbox.emit('sulu.preview.initialize');
                }.bind(this));
            }.bind(this));
        },

        listenForChange: function() {
            this.dfdListenForChange.then(function() {
                this.sandbox.dom.on(this.formId, 'keyup change', function() {
                    this.setHeaderBar(false);
                }.bind(this), '.trigger-save-button');

                this.sandbox.on('sulu.content.changed', function() {
                    this.setHeaderBar(false);
                }.bind(this));
            }.bind(this));
        },

        setHeaderBar: function(saved) {
            this.sandbox.emit('sulu.content.contents.set-header-bar', saved);
        }
    };
});
