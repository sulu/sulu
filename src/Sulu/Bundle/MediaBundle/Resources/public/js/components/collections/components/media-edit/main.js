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
            titleKey: 'public.title',
            descriptionKey: 'public.description',
            infoKey: 'public.info'
        },

        constants = {
            titleId: 'media-edit-title',
            descriptionId: 'media-edit-description'
        },

        templates = {
            info: [
                '<div class="grid">',
                '   <div class="grid-row">',
                '       <label for="', constants.titleId ,'"><%= titleDesc %></label>',
                '       <input class="form-element" type="text" id="', constants.titleId ,'" value="<%= title %>"/>',
                '   </div>',
                '   <div class="grid-row">',
                '       <label for="', constants.descriptionId ,'"><%= descriptionDesc %></label>',
                '       <textarea class="small noResize form-element" id="', constants.descriptionId ,'"><%= description %></textarea>',
                '   </div>',
                '</div>'
            ].join('')
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
            this.$info = this.sandbox.dom.createElement(this.sandbox.util.template(templates.info)({
                titleDesc: this.sandbox.translate(this.options.titleKey),
                descriptionDesc: this.sandbox.translate(this.options.descriptionKey),
                title: (!!media.title) ? media.title : '',
                description: (!!media.description) ? media.description : ''
            }));
            this.startOverlay();
        },

        /**
         * Starts the actual overlay
         */
        startOverlay: function() {
            var $container = this.sandbox.dom.createElement('<div/>');
            this.sandbox.dom.append(this.$el, $container);
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
                   okCallback: this.changeModel.bind(this)
                }
            }]);
        },

        /**
         * Maps the overlay inputs back on the model
         */
        changeModel: function() {
            var title = this.sandbox.dom.val(this.sandbox.dom.find('#' + constants.titleId, this.$info)),
                description = this.sandbox.dom.html(this.sandbox.dom.find('#' + constants.descriptionId, this.$info));
            this.media.title = title;
            this.media.description = description;
            this.sandbox.emit('sulu.media.collections.save-media', this.media);
            this.media = null;
        }
    };
});
