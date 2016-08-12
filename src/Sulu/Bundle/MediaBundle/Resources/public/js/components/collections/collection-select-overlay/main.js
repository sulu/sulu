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

    var namespace = 'sulu.collection-select.',

        defaults = {
            instanceName: '',
            title: '',
            rootCollection: false,
            disableIds: [],
            disabledChildren: false
        },

        templates = {
            columnNavigation: function() {
                return [
                    '<div id="child-column-navigation"></div>',
                    '<div id="wait-container" style="margin-top: 50px; margin-bottom: 200px; display: none;"></div>'
                ].join('');
            }
        },

        constants = {
            columnNavigationSelector: '#child-column-navigation',
            columnNavigationContainerSelector: '#child-column-navigation .container',
        },

        /**
         * raised when an collection is selected
         * @event sulu.collection-select.selected
         */
        SELECTED = function() {
            return createEventName.call(this, 'selected');
        },

        /**
         * raised when the overlay get closed
         * @event sulu.collection-select.closed
         */
        CLOSED = function() {
            return createEventName.call(this, 'closed');
        },

        /**
         * raised when component is initialized
         * @event sulu.collection-select.closed
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        },

        /** returns normalized event names */
        createEventName = function(postFix, eventNamespace) {
            if (!eventNamespace) {
                eventNamespace = namespace;
            }

            return eventNamespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        };

    return {
        /**
         * Initializes the overlay component
         */
        initialize: function() {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {
                locale: this.sandbox.sulu.getDefaultContentLocale()
            }, defaults, this.options);

            this.bindCustomEvents();
            this.openOverlay();

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Binds custom related events
         */
        bindCustomEvents: function() {
            // start column navigation when overlay is openend
            this.sandbox.once(createEventName.call(this, 'opened', 'husky.overlay.'), this.startOverlayColumnNavigation.bind(this));

            // wait for item select in column-navigation
            this.sandbox.on(createEventName.call(this, 'action', 'husky.column-navigation.'), function(item) {
                this.sandbox.emit(SELECTED.call(this), item);
                this.sandbox.stop();
            }.bind(this));
        },

        /**
         * Start the overlay to select a collection
         */
        openOverlay: function() {
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
                        openOnStart: true,
                        removeOnClose: true,
                        instanceName: this.options.instanceName,
                        skin: 'wide',
                        propagateEvents: false,
                        title: this.sandbox.translate('sulu.media.move.overlay-title'),
                        data: templates.columnNavigation(),
                        buttons: buttons,
                        cancelCallback: function() {
                            this.sandbox.stop();
                        }.bind(this),


                    }
                }
            ]);
        },

        /**
         * Start column navigation which displays the collections
         */
        startOverlayColumnNavigation: function() {
            this.$find(constants.columnNavigationSelector).append($('<div class="container"/>'));

            var options = {
                el: constants.columnNavigationContainerSelector,
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
                                    'children': {'href': '/admin/api/collections?sortBy=title&limit=9999&locale=' + this.options.locale}
                                },
                                '_embedded': {'collections': []}
                            }
                        ]
                    }
                };
            } else {
                options.url = '/admin/api/collections?sortBy=title&limit=9999&locale=' + this.options.locale;
            }

            this.sandbox.start(
                [
                    {
                        name: 'column-navigation@husky',
                        options: options
                    }
                ]
            );
        },

        /**
         * Called when component gets destroyed
         */
        destroy: function() {
            this.sandbox.emit(CLOSED.call(this));
        }
    };
});
