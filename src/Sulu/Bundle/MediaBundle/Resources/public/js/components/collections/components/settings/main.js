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

            // shows a delete success label. If a collection just got deleted
            this.sandbox.sulu.triggerDeleteSuccessLabel('labels.success.collection-deleted-desc');
        },

        /**
         * Binds custom related events
         */
        bindCustomEvents: function() {
            // load collections list if back icon is clicked
            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.media.collections.list');
            }.bind(this));

            // change the editing language
            this.sandbox.on('sulu.header.toolbar.language-changed', this.changeLanguage.bind(this));

            // save button clicked
            this.sandbox.on('sulu.header.toolbar.save', this.save.bind(this));
        },

        /**
         * Changes the editing language
         * @param locale {string} the new locale to edit the collection in
         */
        changeLanguage: function(locale) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'language');
            this.sandbox.emit(
                'sulu.media.collections.reload-collection',
                this.options.data.id, {locale: locale.localization, breadcrumb: 'true'},
                function(collection) {
                    this.options.data = collection;
                    this.sandbox.form.setData('#' + constants.settingsFormId, this.options.data);
                    this.setHeaderInfos();
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'language', false);
                }.bind(this)
            );
            this.sandbox.emit('sulu.media.collections-edit.set-locale', locale.localization);
        },

        /**
         * Renderes the files tab
         */
        render: function() {
            this.setHeaderInfos();
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
                    this.sandbox.emit('sulu.header.toolbar.state.change', 'edit', false);
                    this.saved = false;
                }
            }.bind(this));
        },

        /**
         * Sets all the Info contained in the header
         * like breadcrumb or title
         */
        setHeaderInfos: function() {
            var breadcrumb = [
                {title: 'navigation.media'},
                {
                    title: 'media.collections.title',
                    event: 'sulu.media.collections.breadcrumb-navigate.root'
                }
            ], i, len, data = this.options.data._embedded.breadcrumb || [];

            for (i = 0, len = data.length; i < len; i++) {
                breadcrumb.push({
                    title: data[i].title,
                    event: 'sulu.media.collections.breadcrumb-navigate',
                    eventArgs: data[i]
                });
            }

            breadcrumb.push({title: this.options.data.title});

            this.sandbox.emit('sulu.header.set-title', this.options.data.title);
            this.sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
        },

        /**
         * Saves the settings-tab
         */
        save: function() {
            if (this.sandbox.form.validate('#' + constants.settingsFormId)) {
                var data = this.sandbox.form.getData('#' + constants.settingsFormId);
                this.options.data = this.sandbox.util.extend(true, {}, this.options.data, data);
                this.options.data.parent = this.options.data._embedded.parent ? this.options.data._embedded.parent.id : null;

                this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');
                this.sandbox.once('sulu.media.collections.collection-changed', this.savedCallback.bind(this));
                this.sandbox.emit('sulu.media.collections.save-collection', this.options.data);
            }
        },

        /**
         * Method which gets called after the save-process has finished
         */
        savedCallback: function() {
            this.setHeaderInfos();
            this.sandbox.emit('sulu.header.toolbar.state.change', 'edit', true, true);
            this.saved = true;
            this.sandbox.emit('sulu.labels.success.show', 'labels.success.collection-save-desc', 'labels.success');
            this.sandbox.emit('husky.data-navigation.collections.reload');
        }
    };
});
