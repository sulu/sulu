/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require.config({
    paths: {
        sulumedia: '../../sulumedia/js',
        "type/mediaSelection": '../../sulumedia/js/validation/types/mediaSelection'
    }
});

define({

    name: "SuluMediaBundle",

    initialize: function(app) {

        'use strict';
        var sandbox = app.sandbox;

        app.components.addSource('sulumedia', '/bundles/sulumedia/js/components');

        sandbox.urlManager.setUrl('media', 'media/collections/edit:<%= collectionId %>/files/edit:<%= mediaId %>', function(data) {
            return {
                mediaId: data.properties.media_id,
                collectionId: data.properties.collection_id
            };
        });

        // list all collections
        sandbox.mvc.routes.push({
            route: 'media/collections/root',
            callback: function() {
                return '<div data-aura-component="collections/components/root@sulumedia" />';
            }
        });

        // show a single collection with files and upload
        sandbox.mvc.routes.push({
            route: 'media/collections/edit::id/:content',
            callback: function(id, content) {
                return '<div data-aura-component="collections/components/content@sulumedia" data-aura-content="' + content + '" data-aura-id="' + id + '"/>';
            }
        });

        app.components.before('initialize', function() {
            if (this.name !== 'Sulu App') {
                return;
            }

            this.sandbox.on('husky.data-navigation.collections.select', function(item) {
                if (item === null) {
                    this.sandbox.emit('sulu.router.navigate', 'media/collections/root');
                }
            }.bind(this));

            this.sandbox.on('husky.data-navigation.collections.add', function(item) {
                var $element = this.sandbox.dom.createElement('<div id="collection-add"/>'),
                    parentId = !!item && !!item.id ? item.id : null;

                this.sandbox.dom.append('body', $element);

                this.sandbox.start([{
                    name: 'collections/components/collection-create@sulumedia',
                    options: {
                        el: $element,
                        parent: parentId,
                        createdCallback: function(collection) {
                            this.sandbox.emit(
                                'sulu.labels.success.show',
                                'labels.success.collection-save-desc',
                                'labels.success'
                            );
                            this.sandbox.emit(
                                'sulu.router.navigate',
                                'media/collections/edit:' + collection.get('id') + '/files'
                            );
                            this.sandbox.emit(
                                'husky.data-navigation.collections.set-url',
                                '/admin/api/collections/' + collection.get('id') + '?depth=1'
                            );
                        }.bind(this)
                    }
                }]);
            }.bind(this));
        });
    }
});
