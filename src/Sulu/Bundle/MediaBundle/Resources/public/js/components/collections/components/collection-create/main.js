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
            instanceName: ''
        },

        constants = {
            newFormSelector: '#collection-new'
        },

        eventNamespace = 'sulu.collections.add-overlay.',

        /**
         * Creates the eventnames
         * @param postFix {String} event name to append
         */
        createEventName = function(postFix) {
            return eventNamespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        },

        /**
         * @event sulu.collections.add-overlay.created
         */
        CREATED = function() {
            return createEventName.call(this, 'created')
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

                model.set(collection);

                model.save(null, {
                    success: function(collection) {
                        this.sandbox.emit(
                            'sulu.labels.success.show',
                            'labels.success.collection-save-desc',
                            'labels.success'
                        );
                        this.sandbox.emit('sulu.router.navigate', 'media/collections/edit:' + collection.get('id') + '/files');
                        this.sandbox.emit(CREATED.call(this), collection);
                    }.bind(this)
                });

                this.sandbox.stop();
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
