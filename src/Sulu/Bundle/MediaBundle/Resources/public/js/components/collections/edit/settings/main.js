/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var defaults = {
            data: {},
            instanceName: 'collection'
        },

        constants = {
            settingsFormId: 'collection-settings'
        };

    return {

        view: true,

        layout: {
            navigation: {
                collapsed: true
            },
            content: {
                width: 'fixed'
            }
        },

        templates: [
            '/admin/media/template/collection/settings'
        ],

        /**
         * Initializes the collections list
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.saved = true;

            var url = '/admin/api/collections/' + this.options.data.id + '?depth=1&sortBy=title';
            this.sandbox.emit('husky.navigation.select-id', 'collections-edit', {dataNavigation: {url: url}});

            this.bindCustomEvents();
            this.render();
        },

        /**
         * Binds custom related events
         */
        bindCustomEvents: function() {
            // update the data
            this.sandbox.on('sulu.media.collections.edit.updated', this.updateData.bind(this));

            // save button clicked
            this.sandbox.on('sulu.toolbar.save', this.save.bind(this));
        },

        /**
         * Updates the data and reloads the grid
         * @param data {Object} the new collection object
         */
        updateData: function(data) {
            this.options.data = data;
            this.sandbox.form.setData('#' + constants.settingsFormId, this.options.data);
        },

        /**
         * Renderes the files tab
         */
        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/media/template/collection/settings'));
            this.sandbox.start('#' + constants.settingsFormId);
            this.sandbox.form.create('#' + constants.settingsFormId);
            this.sandbox.form.setData('#' + constants.settingsFormId, this.options.data).then(function() {
                this.bindDomEvents();
            }.bind(this));
        },

        /**
         * Binds dom events concerning the settings tab
         */
        bindDomEvents: function() {
            // activate save-button on key input
            this.sandbox.dom.on('#' + constants.settingsFormId, 'change keyup', function() {
                if (this.saved === true) {
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
                    this.saved = false;
                }
            }.bind(this));
        },

        /**
         * Saves the settings-tab
         */
        save: function() {
            if (this.sandbox.form.validate('#' + constants.settingsFormId)) {
                var data = this.sandbox.form.getData('#' + constants.settingsFormId);
                this.options.data = this.sandbox.util.extend(true, {}, this.options.data, data);
                this.options.data.parent = this.options.data._embedded.parent ? this.options.data._embedded.parent.id : null;

                this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
                this.sandbox.once('sulu.media.collections.collection-changed', this.savedCallback.bind(this));
                this.sandbox.emit('sulu.media.collections.save-collection', this.options.data);
            }
        },

        /**
         * Method which gets called after the save-process has finished
         */
        savedCallback: function() {
            this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
            this.saved = true;
            this.sandbox.emit('sulu.labels.success.show', 'labels.success.collection-save-desc', 'labels.success');
            this.sandbox.emit('husky.data-navigation.collections.reload');
        }
    };
});
