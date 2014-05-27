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
 * Class which takes ids of medias and shows overlays to edit them
 * @constructor
 *
 **/
define(function () {

    'use strict';

    var defaults = {

        },
        constants = {

        },

        namespace = 'sulu.media-edit.',

        templates = {

        },

        /**
         * listens on and shows an overlay to edit the media for a given id
         * @event sulu.media-edit.edit
         * @param id {Number|String} id of the media to edit
         */
            EDIT = function() {
            return createEventName.call(this, 'edit');
        },

        /** returns normalized event names */
            createEventName = function(postFix) {
            return namespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        };

    return {

        /**
         * Initializes the collections list
         */
        initialize: function () {
            // extend defaults with options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.bindCustomEvents();
            this.sandbox.dom.hide(this.$el);
        },

        /**
         * Bind custom-related events
         */
        bindCustomEvents: function() {
            this.sandbox.on(EDIT.call(this), this.editMedia.bind(this));
        },

        /**
         * Shows an overlay to edit a media
         * @param id {Number|String} id of the media to edit
         */
        editMedia: function(id) {
            console.log(id);
        }
    };
});
