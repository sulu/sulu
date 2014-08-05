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

        view: true,

        layout: {
            sidebar: {
                width: 'fixed',
                cssClasses: 'sidebar-padding-50'
            }
        },

        templates: ['/admin/contact/template/account/documents'],

        initialize: function() {

            this.form = '#documents-form';

            this.setHeaderBar(true);
            this.render();

            if (!!this.options.data && !!this.options.data.id) {
                this.initSidebar('/admin/widget-groups/account-detail?account=', this.options.data.id);
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

        setForm: function(data){
            this.sandbox.form.setData(this.form, data).fail(function(error) {
                this.sandbox.logger.error("An error occured when setting data!", error);
            }.bind(this));
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.toolbar.save', function() {
                this.submit();
            }, this);

            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.contacts.accounts.list');
            }, this);

            this.sandbox.on('sulu.media-selection.document-selection.data-changed', function() {
                this.setHeaderBar(false);
            }, this);

            this.sandbox.on('sulu.contacts.accounts.medias.saved', function(data){
                this.setHeaderBar(true);
                this.setForm(data);
            }, this);
        },

        /**
         * Submits the selection depending on the type
         */
        submit: function() {
            if (this.sandbox.form.validate(this.form)) {
                var data = this.sandbox.form.getData(this.form);

                if (this.options.params.type === 'account') {
                    this.sandbox.emit('sulu.contacts.accounts.medias.save', this.options.data.id, data.medias.ids);
                } else if (this.options.params.type === 'contact') {
                    this.sandbox.emit('sulu.contacts.contacts.medias.save', this.options.data.id, data.medias.ids);
                } else {
                    this.sandbox.logger.error('Undefined type for documents component!');
                }
            }
        },

        /** @var Bool saved - defines if saved state should be shown */
        setHeaderBar: function(saved) {
            if (saved !== this.saved) {
                var type = (!!this.options.data && !!this.options.data.id) ? 'edit' : 'add';
                this.sandbox.emit('sulu.header.toolbar.state.change', type, saved, true);
            }
            this.saved = saved;
        }
    };
});
