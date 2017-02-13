/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Overlay for media-link plugin.
 *
 * @class media-selection/overlay
 * @constructor
 */
define([
    'underscore',
    'config',
    'services/sulumedia/collection-manager',
    'services/sulumedia/user-settings-manager',
    'text!./skeleton.html'
], function(_, Config, CollectionManager, UserSettingsManager, skeletonTemplate) {

    'use strict';

    var constants = {
            listContainerSelector: '.list-container',
            overlayBackButtonSelector: '.overlay-container .back',
            dropzoneWrapperContainer: '.dropzone-wrapper-container',
            newFormSelector: '#collection-new'
        },

        emptyCollection = {
            title: '',
            description: ''
        },

        getRootData = function() {
            return {
                title: this.sandbox.translate('sulu.media.all-collections'),
                hasSub: true
            };
        };

    return {
        templates: [
            '/admin/media/template/collection/new'
        ],

        defaults: {
            options: {
                preselected: [],
                url: '/admin/api/media',
                singleSelect: false,
                removeable: true,
                instanceName: null,
                locale: UserSettingsManager.getMediaLocale(),
                types: null,
                removeOnClose: false,
                openOnStart: false,
                saveCallback: function() {
                },
                removeCallback: function() {
                }
            },

            templates: {
                skeleton: skeletonTemplate,
                url: [
                    '<%= url %>?locale=<%= locale %>',
                    '<% if (!!types) {%>&types=<%= types %><% } %>',
                    '<% _.each(params, function(value, key) {%>&<%= key %>=<%= value %><% }) %>'
                ].join('')
            },

            translations: {
                save: 'sulu-media.selection.overlay.save',
                remove: 'public.remove',
                uploadInfo: 'media-selection.list-toolbar.upload-info',
                allMedias: 'media-selection.overlay.all-medias'
            }
        },

        events: {
            names: {
                setItems: {
                    postFix: 'set-items',
                    type: 'on'
                },
                open: {
                    postFix: 'open',
                    type: 'on'
                }
            },
            namespace: 'sulu.media-selection-overlay.'
        },

        loadedItems: {},

        initialize: function() {
            this.data = {};

            this.initializeDialog();

            this.bindCollectionViewEvents();
            this.bindDomEvents();
            this.bindCustomEvents();
        },

        bindCollectionViewEvents: function() {
            this.sandbox.on(
                'sulu.collection-view.' + this.options.instanceName + '.asset.clicked',
                function(id, item) {
                    if (this.options.singleSelect) {
                        this.setItems([item]);
                        this.save();
                        this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.close');
                    }
                },
                this
            );

            this.sandbox.on('sulu.collection-view.' + this.options.instanceName + '.asset.added', function(id, item) {
                this.addItem(item);
            }.bind(this));

            this.sandbox.on('sulu.collection-view.' + this.options.instanceName + '.asset.removed', function(id) {
                this.removeItem(id);
            }.bind(this));

            this.sandbox.on(
                'sulu.collection-view.' + this.options.instanceName + '.folder.clicked',
                this.renderCollectionView,
                this
            );

            this.sandbox.on(
                'sulu.collection-view.' + this.options.instanceName + '.folder.breadcrumb-clicked',
                this.handleBreadcrumbClick,
                this
            );

            this.sandbox.on(
                'sulu.collection-view.' + this.options.instanceName + '.folder.add-clicked',
                this.slideToAddForm,
                this
            );
        },

        bindDomEvents: function() {
            this.$el.on('click', '.back', function() {
                if (!!this.data._embedded && !!this.data._embedded.parent) {
                    this.renderCollectionView(this.data._embedded.parent.id);
                } else {
                    this.renderCollectionView();
                }
            }.bind(this));
        },

        bindCustomEvents: function() {
            if (!!this.options.removeOnClose) {
                this.sandbox.on('husky.overlay.' + this.options.instanceName + '.closed', function() {
                    this.sandbox.stop();
                }.bind(this));
            }

            this.events.setItems(this.setItems.bind(this));

            this.events.open(function() {
                this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.open');
            }.bind(this));

            this.sandbox.on('husky.datagrid.' + this.options.instanceName + '.loaded', function(data) {
                _.each(data._embedded.media, function(item) {
                    this.loadedItems[item.id] = item;
                }.bind(this));
            }.bind(this));
        },

        save: function() {
            this.options.saveCallback(this.getData());
        },

        getData: function() {
            return _.map(this.items, function(item) {
                if (!this.loadedItems || !this.loadedItems[item.id]) {
                    return item;
                }

                return this.loadedItems[item.id];
            }.bind(this));
        },

        setItems: function(items) {
            this.items = items;

            var ids = _.map(this.items, function(item) {
                return parseInt(item.id);
            });

            this.sandbox.emit('husky.datagrid.' + this.options.instanceName + '.selected.update', ids);
        },

        addItem: function(item) {
            if (this.has(item.id)) {
                return false;
            }

            this.items.push(item);

            return true;
        },

        removeItem: function(id) {
            this.items = _.filter(this.items, function(item) {
                return item.id !== id;
            });
        },

        has: function(id) {
            return !!_.filter(this.items, function(item) {
                return item.id === id;
            }).length;
        },

        getUrl: function(params) {
            if (!params) {
                params = {};
            }

            return this.templates.url({
                url: this.options.url,
                locale: this.options.locale,
                types: this.options.types,
                params: params
            });
        },

        startOverlayComponents: function () {
            this.startToolbar();
            this.renderCollectionView();
        },

        initializeDialog: function() {
            var $overlay = this.sandbox.dom.createElement('<div class="overlay-container"/>');
            this.sandbox.dom.append(this.$el, $overlay);

            var buttons = [
                {
                    type: 'cancel',
                    align: 'left'
                }
            ];

            if (!!this.options.removeable) {
                buttons.push({
                    text: this.translations.remove,
                    align: 'center',
                    classes: 'just-text',
                    callback: function() {
                        this.options.removeCallback();
                        this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.close');
                    }.bind(this)
                });
            }

            if (!this.options.singleSelect) {
                buttons.push({
                    type: 'ok',
                    text: this.translations.save,
                    align: 'right'
                });
            }

            // start form when overlay is opened
            this.sandbox.once('husky.overlay.' + this.options.instanceName + '.opened', function() {
                this.sandbox.form.create(constants.newFormSelector);
            }.bind(this));

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        openOnStart: this.options.openOnStart,
                        removeOnClose: this.options.removeOnClose,
                        el: $overlay,
                        container: this.$el,
                        cssClass: 'media-selection-overlay',
                        instanceName: this.options.instanceName,
                        slides: [
                            {
                                displayHeader: false,
                                data: this.templates.skeleton({
                                    title: this.translations.allMedias
                                }),
                                contentSpacing: false,
                                buttons: buttons,
                                okCallback: function() {
                                    this.save();
                                }.bind(this)
                            },
                            {
                                title: this.sandbox.translate('sulu.media.add-collection'),
                                data: this.renderTemplate('/admin/media/template/collection/new'),
                                okCallback: function() {
                                    this.addCollection();

                                    return false;
                                }.bind(this),
                                cancelCallback: function() {
                                    this.slideToCollectionView();

                                    return false;
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]).then(function() {
                this.setItems(this.options.preselected);

                if (!!this.options.openOnStart) {
                    this.startOverlayComponents();

                    return;
                }

                this.sandbox.once('husky.overlay.' + this.options.instanceName + '.opened', function() {
                    this.startOverlayComponents();
                }.bind(this));
            }.bind(this));
        },

        startToolbar: function() {
            this.sandbox.start([{
                name: 'toolbar@husky',
                options: {
                    el: this.$el.find('.toolbar'),
                    instanceName: this.options.instanceName,
                    skin: 'big',
                    buttons: [
                        {
                            id: 'add-folder',
                            icon: 'plus-circle',
                            title: 'sulu.media.add-collection',
                            callback: this.slideToAddForm.bind(this)
                        }
                    ]
                }
            }]);
        },

        startCollectionView: function (data) {
            var $collectionView = $('<div class="collection-view"/>');

            this.$el.find(constants.listContainerSelector).append($collectionView);

            this.sandbox.start([{
                name: 'collection-view@sulumedia',
                options: {
                    el: $collectionView,
                    data: data,
                    locale: this.options.locale,
                    instanceName: this.options.instanceName,
                    assetActions: ['fa-check-circle-o'],
                    assetTypes: this.getTypes(),
                    assetSelectOnClick: true,
                    assetSingleSelect: !!this.options.singleSelect,
                    assetPreselected: _.map(this.items, function (item) {
                        return parseInt(item.id);
                    }),
                    assetShowActionIcon: !!this.options.singleSelect,
                    assetHasEdit: false,
                    assetHasDelete: false,
                    assetHasMove: false,
                    assetHasSelectedCounter: false,
                    dropzoneOverlayContainer: this.$el.find(constants.dropzoneWrapperContainer),
                    parentContainer: constants.listContainerSelector
                }
            }]);
        },

        getTypes: function() {
            if (!this.options.types) {
                return [];
            }

            return this.options.types.split(',');
        },

        handleBackButtonDisplay: function () {
            if (!!this.data.id) {
                this.sandbox.dom.show(constants.overlayBackButtonSelector);
            } else {
                this.sandbox.dom.hide(constants.overlayBackButtonSelector);
            }
        },

        handleBreadcrumbClick: function(breadcrumb) {
            this.renderCollectionView(breadcrumb.data.id);
        },

        renderCollectionView: function(collectionId) {
            var $loader;

            this.sandbox.stop('.collection-view');

            if (!collectionId) {
                this.data = getRootData.call(this);
                this.startCollectionView(this.data);
                this.handleBackButtonDisplay();
            } else {
                $loader = $('<div class="loader"/>');
                this.sandbox.sulu.showLoader.call(this, $loader);

                this.$el.find(constants.listContainerSelector).append($loader);

                CollectionManager
                    .load(collectionId, this.options.locale)
                    .then(function(data) {
                        this.sandbox.stop($loader);
                        this.data = data;
                        this.handleBackButtonDisplay();
                        this.startCollectionView(this.data);
                    }.bind(this));
            }
        },

        slideToAddForm: function() {
            this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.slide-to', 1);
        },

        slideToCollectionView: function() {
            this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.slide-to', 0);
            this.sandbox.form.setData(constants.newFormSelector, emptyCollection);
        },

        addCollection: function() {
            if (this.sandbox.form.validate(constants.newFormSelector)) {
                var collection = this.sandbox.form.getData(constants.newFormSelector);

                this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.show-loader');

                collection.parent = this.data.id;
                collection.locale = UserSettingsManager.getMediaLocale();

                CollectionManager.save(collection).then(function(collection) {
                    this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.hide-loader');
                    this.slideToCollectionView();
                    this.renderCollectionView(collection.id);
                }.bind(this));
            }
        }
    };
});
