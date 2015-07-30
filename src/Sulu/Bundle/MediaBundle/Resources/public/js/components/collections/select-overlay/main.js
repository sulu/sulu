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
            instanceName: 'collection-select',
            title: '',
            rootCollection: false,
            disableIds: [],
            disabledChildren: false
        },

        templates = {
            toggler: [
                '<div id="show-ghost-pages"></div>',
                '<label class="inline spacing-left" for="show-ghost-pages"><%= label %></label>'
            ].join(''),

            columnNavigation: function() {
                return [
                    '<div id="child-column-navigation"></div>',
                    '<div id="wait-container" style="margin-top: 50px; margin-bottom: 200px; display: none;"></div>'
                ].join('');
            }
        },

        namespace = 'sulu.media.collection-select.',

        /**
         * Open overlay
         * @event sulu.media.collection-select.open
         */
        OPEN = function() {
            return createEventName.call(this, 'open');
        },

        /**
         * Close overlay
         * @event sulu.media.collection-select.close
         */
        CLOSE = function() {
            return createEventName.call(this, 'close');
        },

        /**
         * Restart overlay content
         * @event sulu.media.collection-select.close
         */
        RESTART = function() {
            return createEventName.call(this, 'restart');
        },

        /**
         * Selected
         * @event sulu.media.collection-select.selected
         */
        SELECTED = function() {
            return createEventName.call(this, 'selected');
        },

        /** returns normalized event names */
        createEventName = function(postFix, eventNamespace) {
            if(!eventNamespace){
                eventNamespace = namespace;
            }

            return eventNamespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        };

    return {
        $columnNavigationContainer: '#child-column-navigation',
        $columnNavigation: '#child-column-navigation .container',

        /**
         * Initializes the collections list
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.bindCustomEvents();
            this.render();
        },

        /**
         * Binds custom related events
         */
        bindCustomEvents: function() {
            this.sandbox.on(OPEN.call(this), function() {
                this.sandbox.emit(createEventName.call(this, 'open', 'husky.overlay.'));
            }.bind(this));

            this.sandbox.on(CLOSE.call(this), function() {
                this.sandbox.emit(createEventName.call(this, 'close', 'husky.overlay.'));
            }.bind(this));

            this.sandbox.on(RESTART.call(this), function() {
                this.sandbox.stop(this.$columnNavigation);

                this.sandbox.once(createEventName.call(this, 'opened', 'husky.overlay.'), this.startOverlayColumnNavigation.bind(this));
            }.bind(this));

            // wait for overlay initialized to initialize overlay
            this.sandbox.once(createEventName.call(this, 'opened', 'husky.overlay.'), this.startOverlayColumnNavigation.bind(this));

            // wait for column navigation edit click
            this.sandbox.on(createEventName.call(this, 'action', 'husky.column-navigation.'), function(item) {
                this.sandbox.emit(SELECTED.call(this), item);
            }.bind(this));

            // adjust position of overlay after column-navigation has initialized
            this.sandbox.on(createEventName.call(this, 'initialized', 'husky.column-navigation.'), function() {
                this.sandbox.emit(createEventName.call(this, 'set-position', 'husky.overlay.'));
            }.bind(this));
        },

        /**
         * Render component
         */
        render: function() {
            this.renderOverlay();
        },

        renderOverlay: function() {
            var $element = this.sandbox.dom.createElement('<div class="overlay-container"/>'),
                buttons = [
                    {
                        type: 'cancel',
                        align: 'center'
                    }
                ];
            this.sandbox.dom.append(this.$el, $element);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        cssClass: 'collection-select',
                        el: $element,
                        removeOnClose: false,
                        container: this.$el,
                        instanceName: this.options.instanceName,
                        skin: 'wide',
                        slides: [
                            {
                                title: this.sandbox.translate('sulu.media.move.overlay-title'),
                                data: templates.columnNavigation(),
                                buttons: buttons
                            }
                        ]
                    }
                }
            ]);
        },

        /**
         * initialize column navigation
         */
        startOverlayColumnNavigation: function() {
            this.sandbox.dom.append(this.$columnNavigationContainer, '<div class="container"/>');

            var options = {
                el: this.$columnNavigation,
                instanceName: this.options.instanceName,
                actionIcon: 'fa-check-circle',
                resultKey: 'collections',
                showOptions: false,
                showStatus: false,
                responsive: false,
                sortable: false,
                skin: 'fixed-height-small',
                disableIds: this.options.disableIds,
                disabledChildren: this.options.disabledChildren
            };

            if (!!this.options.rootCollection) {
                options.prefilledData = {
                    '_embedded': {
                        'collections': [
                            {
                                'id': 'root',
                                'title': this.sandbox.translate('navigation.media.collections'),
                                'hasSub': true,
                                '_links': {
                                    'children': {'href': '/admin/api/collections?sortBy=title&limit=9999'}
                                },
                                '_embedded': {'collections': []}
                            }
                        ]
                    }
                };
            } else {
                options.url = '/admin/api/collections?sortBy=title&limit=9999';
            }

            this.sandbox.start(
                [
                    {
                        name: 'column-navigation@husky',
                        options: options
                    }
                ]
            );
        }
    };
});
