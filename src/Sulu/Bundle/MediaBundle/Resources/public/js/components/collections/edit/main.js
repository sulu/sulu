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

    var namespace = 'sulu.media.collections.edit.',

    /**
     * emitted if the collection object has changed
     * @event sulu.media.collections.edit.updated
     * @param data {Object} the new collection object
     */
    UPDATED = function() {
        return createEventName.call(this, 'updated');
    },

    /** returns normalized event names */
    createEventName = function(postFix) {
        return namespace + postFix;
    };

    return {

        header: function() {
            return {
                noBack: true,
                tabs: {
                    url: '/admin/content-navigations?alias=media'
                },
                toolbar: {
                    buttons: [
                        'save',
                        {
                            'settings': {
                                dropdownItems: [
                                    {
                                        id: 'collection-move',
                                        title: this.sandbox.translate('sulu.collection.move'),
                                        callback: this.startMoveCollectionOverlay.bind(this)
                                    },
                                    {
                                        id: 'delete',
                                        title: this.sandbox.translate('sulu.collections.delete-collection'),
                                        callback: this.deleteCollection.bind(this)
                                    }
                                ]
                            }
                        }
                    ],
                    languageChanger: {
                        url: '/admin/api/localizations',
                        resultKey: 'localizations',
                        titleAttribute: 'localization',
                        preSelected: this.options.locale
                    }
                }
            };
        },

        initialize: function() {
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            // move collection overlay
            this.sandbox.on('sulu.media.collection-select.move-collection.selected', this.moveCollection.bind(this));
            // change the editing language
            this.sandbox.on('sulu.header.language-changed', this.changeLanguage.bind(this));
        },

        /**
         * Deletes the current collection
         */
        deleteCollection: function() {
            this.sandbox.emit('sulu.media.collections.delete-collection', this.options.id);
        },

        /**
         * starts overlay for collection media
         */
        startMoveCollectionOverlay: function() {
            this.sandbox.emit('sulu.media.collection-select.move-collection.open');
        },

        /**
         * Changes the editing language
         * @param language {string} the new locale to edit the collection in
         */
        changeLanguage: function(language) {
            this.sandbox.emit(
                'sulu.media.collections.reload-collection',
                this.options.data.id, {locale: language.id, breadcrumb: 'true'},
                function(collection) {
                    this.sandbox.emit(UPDATED.call(this), collection);
                }.bind(this)
            );
            this.sandbox.emit('sulu.media.collections.set-locale', language.id);
        },

        /**
         * emit events to move collection
         * @param collection
         */
        moveCollection: function(collection) {
            this.sandbox.emit('sulu.media.collections.move', this.options.id, collection,
                function() {
                    var url = '/admin/api/collections/' + this.options.id + '?depth=1&sortBy=title';

                    this.sandbox.emit('husky.data-navigation.collections.set-url', url);
                    this.sandbox.emit('sulu.labels.success.show', 'labels.success.collection-move-desc', 'labels.success');
                }.bind(this)
            );

            this.sandbox.emit('sulu.media.collection-select.move-collection.restart');
            this.sandbox.emit('sulu.media.collection-select.move-collection.close');
        }
    };
});
