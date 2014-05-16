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

    var constants = {
        allCollectionsUrl: '/admin/api/collections'
    };

    return {

        initialize: function() {
            this.bindCustomEvents();
            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'files') {
                this.renderFiles();
            } else {
                throw 'display type wrong';
            }
        },

        /**
         * Bind custom events concerning collections
         */
        bindCustomEvents: function() {
            // navigate to list view
            this.sandbox.on('sulu.media.collections.list', function(noReload) {
                this.sandbox.emit('sulu.router.navigate', 'media/collections', !noReload ? true : false , true);
            }, this);

            // navigate to collection edit
            this.sandbox.on('sulu.media.collections.files', function(collectionId, tab) {
                // default tab is files
                tab = (!!tab) ? tab : 'files';
                this.sandbox.emit('sulu.router.navigate', 'media/collections/edit:'+ collectionId +'/' + tab , true, true);
            }.bind(this));
        },

        /**
         * Inserts a container and starts the collections list in it
         */
        renderList: function() {
            var $list = this.sandbox.dom.createElement('<div id="collections-list-container"/>');
            this.html($list);
            this.sandbox.util.load(constants.allCollectionsUrl).then(function(collections) {
                this.sandbox.start([
                    {
                        name: 'collections/components/list@sulumedia',
                        options: {
                            el: $list,
                            data: collections
                        }
                    }
                ]);
            }.bind(this));
        },

        /**
         * Inserts a container and starts the files-view of a single
         * collection in it
         */
        renderFiles: function() {
            var $files = this.sandbox.dom.createElement('<div id="collection-files-container"/>');
            this.html($files);
            this.sandbox.util.load(constants.allCollectionsUrl + '/' + this.options.id).then(function(collection) {
                this.sandbox.start([
                    {
                        name: 'collections/components/files@sulumedia',
                        options: {
                            el: $files,
                            activeTab: this.options.content,
                            data: collection
                        }
                    }
                ]);
            }.bind(this));
        }
    };
});
