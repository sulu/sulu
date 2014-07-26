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
        templates: ['/admin/content/template/form/external-link'],

        initialize: function() {
            this.sandbox.emit('sulu.app.ui.reset', { navigation: 'small', content: 'auto'});
            this.sandbox.emit('husky.toolbar.header.item.disable', 'template', false);

            this.formId = '#content-form';
            this.load();
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            // content save
            this.sandbox.on('sulu.header.toolbar.save', function() {
                this.submit();
            }, this);
        },

        submit: function() {
            this.sandbox.logger.log('save Model');
            if (this.sandbox.form.validate(this.formId)) {
                var data = this.sandbox.form.getData(this.formId);
                this.data = this.sandbox.util.extend({}, this.data, data);

                this.sandbox.emit('sulu.content.contents.save', this.data, 'external-link');
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
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/content/template/form/external-link', {options: this.options}));

            this.dfdListenForChange = this.sandbox.data.deferred();
            this.createForm(this.initData(data));
            this.listenForChange();
        },

        initData: function(data) {
            return data;
        },

        createForm: function(data) {
            this.sandbox.form.create(this.formId).initialized.then(function() {
                this.sandbox.form.setData(this.formId, data).then(function() {
                    this.sandbox.start(this.$el, {reset: true});
                    this.dfdListenForChange.resolve();
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
