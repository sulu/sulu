/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function () {

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
        initialize: function () {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.saved = true;

            this.bindCustomEvents();
            this.render();
        },

        /**
         * Binds custom related events
         */
        bindCustomEvents: function () {
            // load collections list if back icon is clicked
            this.sandbox.on('sulu.header.back', function () {
                this.sandbox.emit('sulu.media.collections.list');
            }.bind(this));

            // change the editing language
            this.sandbox.on('sulu.header.language-changed', this.changeLanguage.bind(this));

            // save button clicked
            this.sandbox.on('sulu.header.toolbar.save', this.save.bind(this));
        },

        /**
         * Deletes the current collection
         */
        deleteCollection: function () {
            this.sandbox.emit('sulu.media.collections.delete-collection', this.options.data.id, function () {
                this.sandbox.sulu.unlockDeleteSuccessLabel();
                this.sandbox.emit('sulu.media.collections.collection-list');
            }.bind(this));
        },

        /**
         * Changes the editing language
         * @param locale {string} the new locale to edit the collection in
         */
        changeLanguage: function(locale) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'language');
            this.sandbox.emit(
                'sulu.media.collections.reload-collection',
                this.options.data.id, {locale: locale},
                function(collection) {
                    this.options.data = collection;
                    this.sandbox.form.setData('#' + constants.settingsFormId, this.options.data);
                    this.setHeaderInfos();
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'language', false);
                }.bind(this)
            );
            this.sandbox.emit('sulu.media.collections-edit.set-locale', locale);
        },

        /**
         * Renderes the files tab
         */
        render: function () {
            this.setHeaderInfos();
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/media/template/collection/settings'));
            this.sandbox.start('#' + constants.settingsFormId);
            this.sandbox.form.create('#' + constants.settingsFormId);
            this.sandbox.form.setData('#' + constants.settingsFormId, this.options.data).then(function () {
                this.startToolbar();
                this.bindDomEvents();
            }.bind(this));
        },

        /**
         * Binds dom events concerning the settings tab
         */
        bindDomEvents: function () {
            // activate save-button on key input
            this.sandbox.dom.on('#' + constants.settingsFormId, 'change keyup', function () {
                if (this.saved === true) {
                    this.sandbox.emit('sulu.header.toolbar.state.change', 'edit', false);
                    this.saved = false;
                }
            }.bind(this));
        },

        /**
         * Starts the Toolbar for the settings-tab
         */
        startToolbar: function () {
            this.sandbox.emit('sulu.header.set-toolbar', {
                    template: 'save',
                    parentTemplate: [{
                        id: 'delete',
                        icon: 'trash-o',
                        title: this.sandbox.translate('sulu.collections.delete-collection'),
                        callback: this.deleteCollection.bind(this)
                    }],
                    languageChanger: {
                        preSelected: this.options.locale
                    }
                }
            );
        },

        /**
         * Sets all the Info contained in the header
         * like breadcrumb or title
         */
        setHeaderInfos: function () {
            this.sandbox.emit('sulu.header.set-title', this.options.data.title);
            this.sandbox.emit('sulu.header.set-breadcrumb', [
                {title: 'navigation.media'},
                {title: 'media.collections.title', event: 'sulu.media.collections.list'},
                {title: this.options.data.title}
            ]);
        },

        /**
         * Saves the settings-tab
         */
        save: function () {
            if (this.sandbox.form.validate('#' + constants.settingsFormId)) {
                var data = this.sandbox.form.getData('#' + constants.settingsFormId);
                this.options.data = this.sandbox.util.extend(true, {}, this.options.data, data);
                this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');
                this.sandbox.once('sulu.media.collections.collection-changed', this.savedCallback.bind(this));
                this.sandbox.emit('sulu.media.collections.save-collection', this.options.data);
            }
        },

        /**
         * Method which gets called after the save-process has finished
         */
        savedCallback: function () {
            this.setHeaderInfos();
            this.sandbox.emit('sulu.header.toolbar.state.change', 'edit', true, true);
            this.saved = true;
            this.sandbox.emit('sulu.labels.success.show', 'labels.success.collection-save-desc', 'labels.success');
        }
    };
});
