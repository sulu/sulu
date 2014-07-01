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

        templates: ['/admin/content/template/content/settings'],

        initialize: function() {
            this.sandbox.emit('sulu.app.ui.reset', { navigation: 'small', content: 'auto'});
            this.sandbox.emit('husky.toolbar.header.item.disable', 'template', false);

            this.load();

            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            // content save
            this.sandbox.on('sulu.header.toolbar.save', function() {
                this.submit();
            }, this);
        },

        load: function() {
            // get content data
            this.sandbox.emit('sulu.content.contents.get-data', function(data) {
                this.render(data);
            }.bind(this));
        },

        render: function(data) {
            this.data = data;
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/content/template/content/settings'));

            this.setData(data);
            this.listenForChange();
        },

        setData: function(data) {
            if (data.nodeType === 1) {
                this.sandbox.dom.attr('#content-node-type', 'checked', true);
            } else if (data.nodeType === 2) {
                this.sandbox.dom.attr('#internal-link-node-type', 'checked', true);
            } else if (data.nodeType === 4) {
                this.sandbox.dom.attr('#external-link-node-type', 'checked', true);
            }

            this.sandbox.dom.attr('#show-in-navigation', 'checked', data.navigation);
        },

        listenForChange: function() {
            this.sandbox.dom.on(this.$el, 'keyup change', function() {
                this.setHeaderBar(false);
            }.bind(this), '.trigger-save-button');
        },

        setHeaderBar: function(saved) {
            this.sandbox.emit('sulu.content.contents.set-header-bar', saved);
        },

        submit: function() {
            this.sandbox.logger.log('save Model');

            var data = {};

            data.navigation = this.sandbox.dom.prop('#show-in-navigation', 'checked');
            data.nodeType = this.sandbox.dom.val('input[name="nodeType"]:checked');

            this.data = this.sandbox.util.extend(true, {}, this.data, data);
            this.sandbox.emit('sulu.content.contents.save', this.data);
        }
    };
});
