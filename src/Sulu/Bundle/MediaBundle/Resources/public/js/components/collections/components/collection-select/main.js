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
            instanceName: 'collection-select'
        },

        templates = {
            toggler: [
                '<div id="show-ghost-pages"></div>',
                '<label class="inline spacing-left" for="show-ghost-pages"><%= label %></label>'
            ].join(''),

            columnNavigation: function() {
                return [
                    '<div id="child-column-navigation"/>',
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
         * Selected
         * @event sulu.media.collection-select.selected
         */
        SELECTED = function() {
            return createEventName.call(this, 'selected');
        },

        /** returns normalized event names */
        createEventName = function(postFix) {
            return namespace + postFix;
        };

    return {

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
                this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.open');
            }.bind(this));

            this.sandbox.on(CLOSE.call(this), function() {
                // FIXME bug in overlay
                this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.close');
            }.bind(this));

            // wait for overlay initialized to initialize overlay
            this.sandbox.once('husky.overlay.' + this.options.instanceName + '.initialized', function() {
                this.startOverlayColumnNavigation();
            }.bind(this));

            // wait for column navigation edit click
            this.sandbox.on('husky.column-navigation.' + this.options.instanceName + '.edit', function(item) {
                this.sandbox.emit(SELECTED.call(this), item);
            }.bind(this));

            // adjust position of overlay after column-navigation has initialized
            this.sandbox.once('husky.column-navigation.' + this.options.instanceName + '.initialized', function() {
                this.sandbox.emit('husky.overlay.' + this.options.instanceName + '.set-position');
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
            this.sandbox.start(
                [
                    {
                        name: 'column-navigation@husky',
                        options: {
                            el: '#child-column-navigation',
                            url: '/admin/api/collections',
                            instanceName: this.options.instanceName,
                            editIcon: 'fa-check-circle',
                            resultKey: 'collections',
                            showEdit: false,
                            showStatus: false,
                            responsive: false,
                            skin: 'fixed-height-small'
                        }
                    }
                ]
            );
        }
    };
});
