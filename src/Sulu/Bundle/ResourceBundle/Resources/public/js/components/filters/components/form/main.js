/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['filtersutil/header'], function(HeaderUtil) {

    'use strict';

    var formSelector = '#filter-form';

    return {

        name: 'Sulu Filter Form',

        view: true,

        templates: ['/admin/resource/template/filter/form'],

        header: function() {
            return {
                toolbar: {
                    template: 'default',
                    languageChanger: {
                        preSelected: this.options.locale
                    }
                }
            };
        },

        initialize: function() {
            this.saved = true;
            this.initializeValidation();
            this.bindCustomEvents();
            this.setHeaderBar(true);
            this.render();
            this.listenForChange();
        },

        bindCustomEvents: function() {
            // filter save
            this.sandbox.on('sulu.header.toolbar.save', function() {
                this.save();
            }.bind(this));

            // filter delete
            this.sandbox.on('sulu.header.toolbar.delete', function() {
                this.sandbox.emit('sulu.resource.filters.delete', this.sandbox.dom.val('#id'), this.options.type);
            }.bind(this));

            // filter saved
            this.sandbox.on('sulu.resource.filters.saved', function(model) {
                this.options.data = model;
                this.setHeaderBar(true);
                this.setHeaderInformation();
            }, this);

            // back to list
            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.resource.filters.list', this.options.type);
            }, this);
        },

        /**
         * Initializes the filter form for validation
         */
        initializeValidation: function() {
            this.sandbox.form.create(formSelector);
        },

        save: function() {
            if (this.sandbox.form.validate(formSelector)) {
                var data = this.sandbox.form.getData(formSelector);

                if (data.id === '') {
                    delete data.id;
                }

                data.conjunction = data.conjunction.id;
                data.entityName = this.options.type;

                this.sandbox.emit('sulu.resource.filters.save', data);
            }
        },

        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/resource/template/filter/form'));

            this.setHeaderInformation();

            this.initForm(this.options.data);
        },

        /**
         * Initializes form and sets the form data
         * @param data
         */
        initForm: function(data) {
            // set form data
            var formObject = this.sandbox.form.create(formSelector);
            formObject.initialized.then(function() {
                this.setFormData(data);
            }.bind(this));
        },

        /**
         * Sets form data and starts the form component
         * @param data
         */
        setFormData: function(data) {
            this.sandbox.form.setData(formSelector, data).then(function() {
                this.sandbox.start(formSelector);
            }.bind(this)).fail(function(error) {
                this.sandbox.logger.error("An error occured when setting data!", error);
            }.bind(this));
        },

        /**
         * Sets header information like title and breadcrumb
         */
        setHeaderInformation: function() {
            var name = this.options.data ? this.options.data.name : null,
                id = this.options.data ? this.options.data.id : null;

            HeaderUtil.setTitle(this.sandbox, name);
            HeaderUtil.setBreadCrumb(this.sandbox, this.options.type, id);
        },

        /**
         * Defines if saved state should be shown
         * @param saved boolean
         */
        setHeaderBar: function(saved) {
            if (saved !== this.saved) {
                var type = (!!this.options.data && !!this.options.data.id) ? 'edit' : 'add';
                this.sandbox.emit('sulu.header.toolbar.state.change', type, saved, true);
            }
            this.saved = saved;
        },

        /**
         * Listen for change to update save button
         */
        listenForChange: function() {
            this.sandbox.dom.on('#filter-form', 'change', function() {
                this.setHeaderBar(false);
            }.bind(this), 'select');
            this.sandbox.dom.on('#filter-form', 'keyup', function() {
                this.setHeaderBar(false);
            }.bind(this), 'input, textarea');
            this.sandbox.on('husky.select.conjunction.selected.item', function() {
                this.setHeaderBar(false);
            }.bind(this));
        }
    };
});
