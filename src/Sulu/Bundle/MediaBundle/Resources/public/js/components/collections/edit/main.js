/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['services/sulumedia/collection-manager',
    'services/sulumedia/media-manager',
    'services/sulumedia/user-settings-manager'], function(CollectionManager, MediaManager, UserSettingsManager) {

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
        },

        defaults = {
            data: {},
            instanceName: 'collection'
        },

        constants = {
            dropzoneSelector: '.dropzone-container',
            toolbarSelector: '.list-toolbar-container',
            datagridSelector: '.datagrid-container',
            moveSelector: '.move-container',
        };

    return {
        view: true,

        header: function () {
            return {
                noBack: true,
                toolbar: {
                    buttons: {
                        settings: {
                            options: {
                                dropdownItems: [
                                    {
                                        id: 'collection-edit',
                                        title: this.sandbox.translate('sulu.collection.edit'), //todo: add translations
                                        //callback: this.startMoveCollectionOverlay.bind(this) // todo: implement edit collection
                                    },
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
                    },
                    languageChanger: {
                        url: '/admin/api/localizations',
                        resultKey: 'localizations',
                        titleAttribute: 'localization',
                        preSelected: UserSettingsManager.getMediaLocale()
                    }
                }
            };
        },

        layout: {
            navigation: {
                collapsed: true
            },
            content: {
                width: 'max'
            }
        },

        templates: [
            '/admin/media/template/collection/files'
        ],

        loadComponentData: function() {
            var promise = this.sandbox.data.deferred();
            CollectionManager.loadOrNew(this.options.id).then(function(data) {
                promise.resolve(data);
            });
            return promise;
        },

        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.sandbox.sulu.saveUserSetting(constants.lastVisitedCollectionKey, this.options.id);

            // handle data-navigation
            var url = '/admin/api/collections/' + this.data.id + '?depth=1&sortBy=title';
            this.sandbox.emit('husky.data-navigation.collections.set-url', url);
            this.sandbox.emit('husky.navigation.select-id', 'collections-edit', {dataNavigation: {url: url}});

            this.bindDatagridEvents();
            this.bindDropzoneEvents();
            this.bindOverlayEvents();
            this.bindManagerEvents();
            this.bindCustomEvents();

            this.render();
        },

        /**
         * Deconstructor
         */
        destroy: function() {
            this.sandbox.stop(constants.dropzoneSelector);
        },

        bindDatagridEvents: function() {
            // change datagrid to table
            this.sandbox.on('sulu.toolbar.change.table', function() {
                UserSettingsManager.setMediaListView('table');
                this.sandbox.emit('husky.datagrid.view.change', 'table');
            }.bind(this));

            // change datagrid to masonry
            this.sandbox.on('sulu.toolbar.change.masonry', function() {
                UserSettingsManager.setMediaListView('decorators/masonry');
                this.sandbox.emit('husky.datagrid.view.change', 'decorators/masonry');
            }.bind(this));

            // download media
            this.sandbox.on('husky.datagrid.download-clicked', function(id) {
                MediaManager.loadOrNew(id).then(function(media) {
                    this.sandbox.dom.window.location.href = media.versions[media.version].url;
                }.bind(this));
            }.bind(this));

            // toggle edit button
            this.sandbox.on('husky.datagrid.number.selections', this.toggleEditButton.bind(this));
        },

        bindDropzoneEvents: function() {
            this.sandbox.on('husky.dropzone.account-logo.success', function(file) {
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-upload-desc', 'labels.success');
                this.sandbox.emit('husky.datagrid.records.add', [file]);
                this.sandbox.emit('husky.data-navigation.collections.reload');
            }, this);
        },

        bindManagerEvents: function() {
            //remove from datagrid
            this.sandbox.on('sulu.medias.media.deleted', function(id) {
                this.sandbox.emit('husky.datagrid.record.remove', id);
                this.sandbox.emit('husky.data-navigation.collections.reload');
            }.bind(this));
        },

        bindOverlayEvents: function() {
            // unlock the dropzone pop-up if the media-edit overlay was closed
            this.sandbox.on('sulu.media-edit.closed', function() {
                this.sandbox.emit('husky.datagrid.items.deselect');
                this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.unlock-popup');
            }.bind(this));

            // move
            this.sandbox.on('sulu.media.collection-select.move-media.selected', this.moveMedia.bind(this));
        },

        bindCustomEvents: function() {
            // move collection overlay
            this.sandbox.on('sulu.media.collection-select.move-collection.selected', this.moveCollection.bind(this));
            // change the editing language
            this.sandbox.on('sulu.header.language-changed', this.changeLanguage.bind(this));
            // delete a media
            this.sandbox.on('sulu.list-toolbar.delete', this.deleteMedia.bind(this));
            // edit media
            this.sandbox.on('sulu.list-toolbar.edit', this.editMedia.bind(this));
            // update datagrid if media-edit is finished
            this.sandbox.on('sulu.media.collections.save-media', this.updateGrid.bind(this));

        },

        /**
         * Renders the files tab
         */
        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/media/template/collection/files'));
            this.startDropzone();
            this.startDatagrid();
            this.renderSelectCollection();
        },

        /**
         * Starts the dropzone component
         */
        startDropzone: function() {
            this.sandbox.start([
                {
                    name: 'dropzone@husky',
                    options: {
                        el: this.$find(constants.dropzoneSelector),
                        url: '/admin/api/media?collection=' + this.data.id,
                        method: 'POST',
                        paramName: 'fileVersion',
                        instanceName: this.options.instanceName
                    }
                }
            ]);
        },

        /**
         * starts overlay for move media
         */
        startMoveMediaOverlay: function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.selectedIds = ids;
                this.sandbox.emit('sulu.media.collection-select.move-media.open');
            }.bind(this));
        },

        /**
         * Starts the list-toolbar in the header
         */
        startDatagrid: function() {
            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this, 'media', '/admin/api/media/fields',
                {
                    el: this.$find(constants.toolbarSelector),
                    instanceName: this.options.instanceName,
                    template: this.sandbox.sulu.buttons.get({
                        edit: {
                            options: {
                                callback: function() {
                                    this.sandbox.emit('sulu.list-toolbar.edit');
                                }.bind(this)
                            }
                        },
                        delete: {
                            options: {
                                callback: function() {
                                    this.sandbox.emit('sulu.list-toolbar.delete');
                                }.bind(this)
                            }
                        },
                        settings: {
                            options: {
                                dropdownItems: [
                                    {
                                        id: 'media-move',
                                        title: this.sandbox.translate('sulu.media.move'),
                                        callback: function() {
                                            this.startMoveMediaOverlay();
                                        }.bind(this)
                                    },
                                    {
                                        type: 'columnOptions'
                                    }
                                ]
                            }
                        },
                        mediaDecoratorDropdown: {}
                    })
                },
                {
                    el: this.$find(constants.datagridSelector),
                    url: '/admin/api/media?orderBy=media.changed&orderSort=DESC&locale=' + UserSettingsManager.getMediaLocale() + '&collection=' + this.data.id,
                    view: UserSettingsManager.getMediaListView(),
                    resultKey: 'media',
                    sortable: false,
                    actionCallback: this.editMedia.bind(this),
                    viewOptions: {
                        table: {
                            actionIconColumn: 'name'
                        }
                    }
                });
        },

        /**
         * Enables or dsiables the edit button
         * @param selectedElements {Number} number of selected elements
         */
        toggleEditButton: function(selectedElements) {
            var enable = selectedElements > 0;
            this.sandbox.emit('sulu.list-toolbar.' + this.options.instanceName + '.edit.state-change', enable);
            this.sandbox.emit(
                'husky.toolbar.' + this.options.instanceName + '.item.' + (!!enable ? 'enable' : 'disable'),
                'media-move',
                false
            );
        },

        /**
         * emit events to move selected media's
         * @param collection
         */
        moveMedia: function(collection) {
            var left = this.selectedIds.length;

            this.sandbox.emit('sulu.media.collections.move-media', this.selectedIds, collection,
                function(mediaId) {
                    this.sandbox.emit('husky.datagrid.record.remove', mediaId);

                    left--;
                    if (left === 0) {
                        this.sandbox.emit('sulu.labels.success.show', 'labels.success.media-move-desc', 'labels.success');
                    }
                }.bind(this)
            );

            this.sandbox.emit('sulu.media.collection-select.move-media.close');
        },

        /**
         * render move media overlay
         */
        renderSelectCollection: function() {
            this.sandbox.start([{
                name: 'collections/select-overlay@sulumedia',
                options: {
                    el: this.$find(constants.moveSelector),
                    instanceName: 'move-media',
                    title: this.sandbox.translate('sulu.media.move.overlay-title'),
                    disableIds: [this.data.id]
                }
            }]);
        },

        /**
         * Edits all selected medias
         * @param additionalMedia {Number|String} id of a media which should, besides the selected ones, also be edited (e.g. if it was clicked)
         */
        editMedia: function(additionalMedia) {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(selected) {
                this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.lock-popup');
                // add additional media to the edit-list, but only if its not already contained
                if (!!additionalMedia && selected.indexOf(additionalMedia) === -1) {
                    selected.push(additionalMedia);
                }
                this.sandbox.emit('sulu.media.collections.edit-media', selected);
            }.bind(this));
        },

        /**
         * Updates the grid
         * @param media {Object|Array} a media object or an array of media objects
         */
        updateGrid: function(media) {
            if (!!media.locale && media.locale === this.options.locale) {
                this.sandbox.emit('husky.datagrid.records.change', media);
            } else if (media.length > 0 && media[0].locale === this.options.locale) {
                this.sandbox.emit('husky.datagrid.records.change', media);
            }
        },

        /**
         * Deletes all selected medias
         */
        deleteMedia: function() {
            this.sandbox.emit('husky.datagrid.items.get-selected', function(ids) {
                this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                    if (!!confirmed) {
                        //this.sandbox.emit('husky.datagrid.medium-loader.show');
                        MediaManager.delete(ids);
                    }
                }.bind(this));
            }.bind(this));
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
                this.data.id, {locale: language.id, breadcrumb: 'true'},
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
