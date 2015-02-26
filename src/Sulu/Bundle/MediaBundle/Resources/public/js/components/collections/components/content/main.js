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

    var namespace = 'sulu.media.collections-edit.',

        constants = {
            moveSelector: '.move-container'
        },

        /**
         * sets the locale
         * @event sulu.media.collections.collection-list
         * @param {String} the new locale
         */
        SET_LOCALE = function() {
            return createEventName.call(this, 'set-locale');
        },

        /**
         * sets the locale
         * @event sulu.media.collections.collection-list
         * @param {Function} the callback to pass the locale to
         */
        GET_LOCALE = function() {
            return createEventName.call(this, 'get-locale');
        },

        /** returns normalized event names */
        createEventName = function(postFix) {
            return namespace + postFix;
        };

    return {
        header: function() {
            return {
                tabs: {
                    url: '/admin/media/navigation/collection'
                },
                toolbar: {
                    template: [
                        {
                            id: 'delete',
                            icon: 'trash-o',
                            position: 20,
                            callback: this.deleteCollection.bind(this)
                        },
                        {
                            id: 'settings',
                            icon: 'gear',
                            position: 30,
                            items: [
                                {
                                    id: 'collection-move',
                                    title: this.sandbox.translate('sulu.collection.move'),
                                    callback: this.startMoveCollectionOverlay.bind(this)
                                }
                            ]
                        }
                    ],
                    parentTemplate: 'save',
                    languageChanger: {
                        preSelected: this.options.locale
                    }
                },
                noBack: true
            };
        },

        /**
         * Deletes the current collection
         */
        deleteCollection: function() {
            this.sandbox.emit('sulu.media.collections.delete-collection', this.options.id, function() {
                this.sandbox.sulu.unlockDeleteSuccessLabel();
                var url = '/admin/api/collections';
                this.sandbox.emit('husky.data-navigation.collections.set-url', url);

                // TODO goto?
                // this.sandbox.emit('sulu.media.collections.collection-list');
            }.bind(this));
        },

        /**
         * starts overlay for collection media
         */
        startMoveCollectionOverlay: function() {
            this.sandbox.emit('sulu.media.collection-select.move-collection.open');
        },

        /**
         * emit events to move collection
         * @param collection
         */
        moveCollection: function(collection) {
            this.sandbox.emit('sulu.media.collections.move', this.options.id, collection,
                function() {
                    var url = '/admin/api/collections/' + this.options.id + '?depth=1';

                    this.sandbox.emit('husky.data-navigation.collections.set-url', url);
                    this.sandbox.emit('sulu.labels.success.show', 'labels.success.collection-move-desc', 'labels.success');
                }.bind(this)
            );

            this.sandbox.emit('sulu.media.collection-select.move-collection.restart');
            this.sandbox.emit('sulu.media.collection-select.move-collection.close');
        },

        initialize: function() {
            this.locale = this.sandbox.sulu.user.locale;
            this.bindCustomEvents();

            this.renderMoveOverlay();
        },

        renderMoveOverlay: function() {
            var $element = this.sandbox.dom.createElement('<div/>');

            this.sandbox.dom.append(this.$el, $element);

            this.sandbox.start([{
                name: 'collections/components/collection-select@sulumedia',
                options: {
                    el: $element,
                    instanceName: 'move-collection',
                    title: this.sandbox.translate('sulu.collection.move.overlay-title'),
                    rootCollection: true,
                    disableIds: [this.options.id],
                    disabledChildren: true
                }
            }]);
        },

        bindCustomEvents: function() {
            this.sandbox.on(SET_LOCALE.call(this), this.setLocale.bind(this));
            this.sandbox.on(GET_LOCALE.call(this), this.getLocale.bind(this));

            // move collection overlay
            this.sandbox.on('sulu.media.collection-select.move-collection.selected', this.moveCollection.bind(this));
        },

        setLocale: function(locale) {
            this.locale = locale;
        },

        getLocale: function(callback) {
            callback(this.locale);
        }
    };
});
