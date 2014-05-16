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
            //Todo: remove hardcoded data
            var $list = this.sandbox.dom.createElement('<div id="collections-list-container"/>');
            this.html($list);
            this.sandbox.start([
                {
                    name: 'collections/components/list@sulumedia',
                    options: {
                        el: $list,
                        data: {
                            _embedded: [
                                {
                                    id: 1,
                                    title: 'Collection 1',
                                    color: '9854fa',
                                    children: 320
                                },
                                {
                                    id: 2,
                                    title: 'Collection 2',
                                    color: 'ff54fa',
                                    children: 10
                                },
                                {
                                    id: 3,
                                    title: 'Collection 3',
                                    color: '7432cf',
                                    children: 57
                                },
                                {
                                    id: 4,
                                    title: 'Collection 4',
                                    color: '96341a',
                                    children: 23
                                },
                                {
                                    id: 5,
                                    title: 'Collection 5',
                                    color: 'dd94fc',
                                    children: 19
                                }
                            ]
                        }
                    }
                }
            ]);
        },

        /**
         * Inserts a container and starts the files-view of a single
         * collection in it
         */
        renderFiles: function() {
            //Todo: remove hardcoded data
            var $files = this.sandbox.dom.createElement('<div id="collection-files-container"/>');
            this.html($files);
            this.sandbox.start([
                {
                    name: 'collections/components/files@sulumedia',
                    options: {
                        el: $files,
                        activeTab: this.options.content,
                        data: {
                            id: 1,
                            title: 'Collection 1',
                            color: '9854fa',
                            files: 'admin/api/collections/1'
                        }
                    }
                }
            ]);
        }
    };
});
