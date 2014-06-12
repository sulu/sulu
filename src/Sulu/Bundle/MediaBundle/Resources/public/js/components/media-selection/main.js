/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * handles media selection
 *
 * @class MediaSelection
 * @constructor
 */
define(['sulumedia/collection/collections'], function(Collections) {

    'use strict';

    var defaults = {
            instanceName: null,
            url: null,
            preselected: [1],
            translations: {
                noMediaSelected: 'media-selection.nomedia-selected',
                addImages: 'media-selection.add-images',
                choose: 'media-selection.choose'
            }
        },

        /**
         * namespace for events
         * @type {string}
         */
        eventNamespace = 'sulu.media-selection.',

        /**
         * raised when all overlay components returned their value
         * @event sulu.media-selection.input-retrieved
         */
        INPUT_RETRIEVED = function() {
            return createEventName.call(this, 'input-retrieved');
        },

        /** returns normalized event names */
        createEventName = function(postFix) {
            return eventNamespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        },

        templates = {
            skeleton: function(options) {
                return [
                    '<div class="media-selection-container form-element">',
                    '   <div class="media-selection-header">',
                    '       <a href="#" class="fa-plus-circle add" id="', options.ids.addButton, '"></a>',
                    '       <div class="position-outer">',
                    '           <select class="position" id="', options.ids.position, '">',
                    '               <option value="0">left</option>',
                    '               <option value="1">right</option>',
                    '           </select>',
                    '       </div>',
                    '       <a href="#" class="fa-cog config" id="', options.ids.configButton, '"></a>',
                    '   </div>',
                    '   <div class="media-selection" id="', options.ids.content, '"></div>',
                    '</div>'
                ].join('');
            },

            noContent: function(noContentString) {
                return [
                    '<div class="no-content">',
                    '   <span class="fa-image icon"></span>',
                    '   <div class="text">', noContentString, '</div>',
                    '</div>'
                ].join('');
            }
        },

        getId = function(type) {
            return '#' + this.options.ids[type];
        },

        render = function() {
            // init collection
            this.collections = new Collections();

            this.options.ids = {
                addButton: 'resource-locator-' + this.options.instanceName + '-add',
                configButton: 'resource-locator-' + this.options.instanceName + '-config',
                position: 'resource-locator-' + this.options.instanceName + '-position',
                content: 'resource-locator-' + this.options.instanceName + '-content',
                chooseTab: 'resource-locator-' + this.options.instanceName + '-choose-tab'
            };
            this.sandbox.dom.html(this.$el, templates.skeleton(this.options));

            // init container
            this.$content = this.sandbox.dom.find(getId.call(this, 'content'), this.$el);
            this.$addButton = this.sandbox.dom.find(getId.call(this, 'addButton'), this.$el);
            this.$configButton = this.sandbox.dom.find(getId.call(this, 'configButton'), this.$el);

            // render no images selected
            renderStartContent.call(this);

            // sandbox event handling
            bindCustomEvents.call(this);

            // init overlays
            // TODO config overlay
            startAddOverlay.call(this);

            // dom event handling
            bindDomEvents.call(this);
        },

        /**
         * Renders the content at the beginning
         * (with no items and before any request)
         */
        renderStartContent = function() {
            var noMedia = this.sandbox.translate(this.options.translations.noMediaSelected);
            this.sandbox.dom.html(this.$content, templates.noContent(noMedia));
        },

        bindDomEvents = function() {
        },

        bindCustomEvents = function() {
            this.sandbox.on('husky.tabs.overlaymedia-selection.' + this.options.instanceName + '.add.initialized', function() {
                this.collections.fetch({
                    success: function(collections) {
                        this.sandbox.start([
                            {
                                name: 'grid-group@suluadmin',
                                options: {
                                    data: collections.toJSON(),
                                    el: this.sandbox.dom.find(getId.call(this, 'chooseTab')),
                                    instanceName: 'collections',
                                    gridUrl: '/admin/api/media?collection=',
                                    preselected: this.options.preselected,
                                    dataGridOptions: {
                                        view: 'table',
                                        pagination: false,
                                        matchings: [
                                            {
                                                id: 'id'
                                            },
                                            {
                                                id: 'thumbnails',
                                                translation: 'thumbnails',
                                                type: 'thumbnails'
                                            },
                                            {
                                                id: 'title',
                                                translation: 'title'
                                            },
                                            {
                                                id: 'description',
                                                translation: 'description'
                                            }
                                        ]
                                    }
                                }
                            }
                        ]);
                    }.bind(this)
                });
            }.bind(this));

            // data from overlay retrieved
            this.sandbox.on(INPUT_RETRIEVED.call(this), function() {
                // TODO if data changed load
                this.sandbox.logger.log(this.ids);

                this.sandbox.util.load()
            }.bind(this));
        },

        /**
         * Starts the overlay component
         */
        startAddOverlay = function() {
            var chooseTabData = getChooseTabData.call(this);

            var $element = this.sandbox.dom.createElement('<div/>');
            this.sandbox.dom.append(this.$el, $element);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        triggerEl: this.$addButton,
                        el: $element,
                        container: this.$el,
                        instanceName: 'media-selection.' + this.options.instanceName + '.add',
                        skin: 'wide',
                        slides: [
                            {
                                title: this.sandbox.translate(this.options.translations.addImages),
                                okCallback: getAddOverlayData.bind(this),
                                tabs: [
                                    {
                                        title: this.sandbox.translate(this.options.translations.choose),
                                        data: chooseTabData
                                    }
                                ]
                            }
                        ]
                    }
                }
            ]);
        },

        getChooseTabData = function() {
            return this.sandbox.dom.createElement('<div id="' + this.options.ids.chooseTab + '" style="max-height: 500px;"/>');
        },

        getAddOverlayData = function() {
            var idsDef = this.sandbox.data.deferred();

            this.sandbox.emit('sulu.grid-group.collections.get-selected-ids', function(ids) {
                this.ids = ids;
                idsDef.resolve();
            }.bind(this));

            idsDef.then(function() {
                this.sandbox.emit(INPUT_RETRIEVED.call(this));
            }.bind(this));
        };

    return {
        historyClosed: true,

        initialize: function() {
            // extend default options
            this.options = this.sandbox.util.extend({}, defaults, this.options);

            render.call(this);
        }
    };
});
