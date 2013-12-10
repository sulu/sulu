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

        templates: ['/admin/content/template/form/overview'],

        initialize: function() {
            this.currentType = this.currentState = '';

            this.formId = '#content-form';
            this.render();

            this.setHeaderBar(true);
            this.listenForChange();
        },

        render: function() {
            this.$el.html(this.renderTemplate('/admin/content/template/form/overview'));

            var data = this.initData();
            this.createForm(data);

            this.bindDomEvents();
            this.bindCustomEvents();
        },

        createForm: function(data) {
            var formObject = this.sandbox.form.create(this.formId);
            formObject.initialized.then(function() {
                this.sandbox.form.setData(this.formId, data);
            }.bind(this));
        },

        bindDomEvents: function() {
            this.sandbox.dom.keypress(this.formId, function(event) {
                if (event.which === 13) {
                    event.preventDefault();
                    this.submit();
                }
            }.bind(this));

            this.sandbox.dom.one('#title', 'focusout', this.setResourceLocator.bind(this));
        },

        setResourceLocator: function() {
            var title = this.sandbox.dom.val('#title'),
                url = '#url';

            this.sandbox.dom.addClass(url, 'is-loading');
            this.sandbox.dom.css(url, 'background-position', '99%');

            this.sandbox.emit('sulu.content.contents.getRL', title, function(rl) {
                    this.sandbox.dom.removeClass(url, 'is-loading');
                this.sandbox.dom.val(url, rl);
            }.bind(this));
        },

        bindCustomEvents: function() {
            // delete contact
            this.sandbox.on('husky.button.delete.click', function() {
                this.sandbox.emit('sulu.content.contents.delete', this.options.data.id);
            }, this);

            // contact saved
            this.sandbox.on('sulu.content.contents.saved', function(id) {
                this.setHeaderBar(true);
            }, this);

            // contact saved
            this.sandbox.on('husky.button.save.click', function() {
                this.submit();
            }, this);
        },

        initData: function() {
            return this.options.data;
        },

        submit: function() {
            this.sandbox.logger.log('save Model');

            if (this.sandbox.form.validate(this.formId)) {
                var data = this.sandbox.form.getData(this.formId);

                this.sandbox.logger.log('data', data);

                this.sandbox.emit('sulu.content.contents.save', data);
            }
        },

        // @var Bool saved - defines if saved state should be shown
        setHeaderBar: function(saved) {

            var changeType, changeState,
                ending = (!!this.options.data && !!this.options.data.id) ? 'Delete' : '';

            changeType = 'save' + ending;

            if (saved) {
                if (ending === '') {
                    changeState = 'hide';
                } else {
                    changeState = 'standard';
                }
            } else {
                changeState = 'dirty';
            }

            if (this.currentType !== changeType) {
                this.sandbox.emit('husky.header.button-type', changeType);
                this.currentType = changeType;
            }
            if (this.currentState !== changeState) {
                this.sandbox.emit('husky.header.button-state', changeState);
                this.currentState = changeState;
            }
        },


        listenForChange: function() {
            this.sandbox.dom.on(this.formId, 'change', function() {
                this.setHeaderBar(false);
            }.bind(this), "select, input");
            this.sandbox.dom.on(this.formId, 'keyup', function() {
                this.setHeaderBar(false);
            }.bind(this), "input");
        }

    };
});
