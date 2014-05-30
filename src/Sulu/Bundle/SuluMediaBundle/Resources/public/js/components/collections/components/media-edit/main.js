/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * @class MediaEdit
 * Class which shows overlays for editing media models
 * @constructor
 *
 **/
define(function () {

    'use strict';

    var namespace = 'sulu.media-edit.',

        defaults = {
            infoKey: 'public.info'
        },

        constants = {
            infoFormId: 'media-info'
        },

        /**
         * listens on and shows an overlay to edit the media for a given id
         * @event sulu.media-edit.edit
         * @param media {Object} the media model to edit
         */
            EDIT = function() {
            return createEventName.call(this, 'edit');
        },

        /** returns normalized event names */
            createEventName = function(postFix) {
            return namespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        };

    return {
        view: true,

        templates: ['/admin/media/template/media/media-info'],

        /**
         * Initializes the collections list
         */
        initialize: function () {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.bindCustomEvents();
            this.sandbox.dom.width(this.$el, 0);
            this.sandbox.dom.height(this.$el, 0);
            this.media = null;
        },

        /**
         * Bind custom-related events
         */
        bindCustomEvents: function() {
            this.sandbox.on(EDIT.call(this), this.editMedia.bind(this));
        },

        /**
         * Shows an overlay to edit a media
         * @param media {Object} the media model
         */
        editMedia: function(media) {
            this.media = media;
            this.$info = this.renderTemplate('/admin/media/template/media/media-info');
            this.startOverlay();
        },

        /**
         * Starts the actual overlay
         */
        startOverlay: function() {
            var $container = this.sandbox.dom.createElement('<div/>');
            this.sandbox.dom.append(this.$el, $container);
            this.sandbox.once('husky.overlay.media-edit.opened', function() {
                this.sandbox.form.create('#' + constants.infoFormId);
                this.sandbox.form.setData('#' + constants.infoFormId, this.media);
            }.bind(this));
            this.sandbox.start([{
                name: 'overlay@husky',
                options: {
                   el: $container,
                   title: this.media.title,
                   tabs: [
                       {title: this.sandbox.translate(this.options.infoKey), data: this.$info}
                   ],
                   openOnStart: true,
                   removeOnClose: true,
                   instanceName: 'media-edit',
                   okCallback: this.changeModel.bind(this)
                }
            }]);
        },

        /**
         * Maps the overlay inputs back on the model
         */
        changeModel: function() {
            if (this.sandbox.form.validate('#' + constants.infoFormId)) {
                var data = this.sandbox.form.getData('#' + constants.infoFormId);
                this.media = this.sandbox.util.extend(true, {}, this.media, data);
                this.sandbox.emit('sulu.media.collections.save-media', this.media);
                this.media = null;
            } else {
                return false;
            }
        }
    };
});
