/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['sulumedia/model/collection'], function(Collection) {

    'use strict';

    var defaults = {
            parent: null,
            instanceName: '',
            createdCallback: function() {
            }
        },

        constants = {
            newFormSelector: '#collection-new'
        };

    return {

        templates: [
            '/admin/media/template/collection/new'
        ],

        /**
         * Initializes the collections list
         */
        initialize: function () {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            this.openOverlay();
        },

        /**
         * Adds a new collection the the list
         * @returns {Boolean} returns false if a new and unsafed collection exists
         */
        addCollection: function () {
            if (this.sandbox.form.validate(constants.newFormSelector)) {
                var collection = this.sandbox.form.getData(constants.newFormSelector),
                    model = new Collection();

                collection.parent = this.options.parent;
                collection.locale = this.sandbox.sulu.user.locale;

                model.set(collection);

                model.save(null, {
                    success: function(collection) {
                        this.options.createdCallback(collection);
                        this.sandbox.stop();
                    }.bind(this),
                    error: function() {
                        this.sandbox.stop();
                    }.bind(this)
                });
            } else {
                return false;
            }
        },

        /**
         * Opens a overlay for a new collection
         */
        openOverlay: function () {
            var $container = this.sandbox.dom.createElement('<div class="overlay-element"/>');
            this.sandbox.dom.append(this.$el, $container);

            this.$overlayContent = this.renderTemplate('/admin/media/template/collection/new');

            this.sandbox.once('husky.overlay.add-collection.opened', function () {
                this.sandbox.start(constants.newFormSelector);
                this.sandbox.form.create(constants.newFormSelector);
            }.bind(this));

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $container,
                        title: this.sandbox.translate('sulu.media.add-collection'),
                        instanceName: 'add-collection',
                        data: this.$overlayContent,
                        okCallback: this.addCollection.bind(this),
                        cancelCallBack: function() {
                            this.sandbox.stop();
                        }.bind(this),
                        openOnStart: true,
                        removeOnClose: true
                    }
                }
            ]);
        }
    };
});
