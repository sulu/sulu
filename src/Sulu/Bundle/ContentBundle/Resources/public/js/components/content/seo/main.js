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
        templates: ['/admin/content/template/content/seo'],

        initialize: function() {
            this.sandbox.emit('sulu.app.ui.reset', { navigation: 'small', content: 'auto'});

            this.formId = '#seo-form';
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
            var data;

            if (this.sandbox.form.validate(this.formId)) {
                data = {extensions: {seo: this.sandbox.form.getData(this.formId)}};

                this.sandbox.logger.log('data', data);

                this.data = this.sandbox.util.extend(true, {}, this.data, data);
                this.sandbox.emit('sulu.content.contents.save', this.data);
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
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/content/template/content/seo'));

            this.createForm(this.initData(data));
            this.listenForChange();
        },

        initData: function(data) {
            return data.extensions.seo;
        },

        createForm: function(data) {
            this.sandbox.form.create(this.formId).initialized.then(function() {
                this.sandbox.form.setData(this.formId, data);
                this.listenForChange();
            }.bind(this));
        },

        listenForChange: function() {
            this.sandbox.dom.on(this.formId, 'keyup change', function() {
                this.setHeaderBar(false);
                this.contentChanged = true;
            }.bind(this), '.trigger-save-button');
        },

        setHeaderBar: function(saved) {
            this.sandbox.emit('sulu.content.contents.set-header-bar', saved);
        }
    };
});
