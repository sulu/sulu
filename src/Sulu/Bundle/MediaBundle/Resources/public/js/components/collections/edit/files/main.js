/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['services/sulumedia/media-manager',
    'services/sulumedia/user-settings-manager',
    'services/sulumedia/media-router'], function(MediaManager, UserSettingsManager, MediaRouter) {

    'use strict';

    return {

        /**
         * Binds custom related events
         */
        bindCustomEvents: function() {
            // open data-source folder-overlay
            this.sandbox.on('sulu.list-toolbar.add', function() {
                this.sandbox.emit('husky.dropzone.' + this.options.instanceName + '.open-data-source');
            }.bind(this));
        },

        /**
         * Takes an array of files and adds them to the datagrid
         * @param files {Array} array of files
         */
        addFilesToDatagrid: function(files) {
            for (var i = -1, length = files.length; ++i < length;) {
                files[i].selected = true;
            }
            this.sandbox.emit('husky.datagrid.records.add', files, this.scrollToBottom.bind(this));
            this.sandbox.emit('husky.data-navigation.collections.reload');
        },

        /**
         * Scrolls the whole form the the bottom
         */
        scrollToBottom: function() {
            this.sandbox.dom.scrollAnimate(this.sandbox.dom.height(this.sandbox.dom.$document), 'body');
        }
    };
});
