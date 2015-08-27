/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
        'services/husky/util',
        'services/husky/mediator',
    ], function(util,
                mediator) {

        'use strict';

        var instance = null,

            /**
             * Delete contact by given id
             * @param contactId contact to delete
             * @returns {*}
             */
            deleteContact = function(contactId) {
                var promise = $.Deferred(),
                    contact = Contact.findOrCreate({id: contactId});

                contact.destroy({
                    success: function() {
                        mediator.emit('sulu.contacts.contact.deleted', contactId);
                        promise.resolve();
                    }.bind(this),
                    error: function() {
                        promise.fail();
                    }.bind(this)
                });

                return promise;
            };


        /** @constructor **/
        function OverlayManager() {
        }

        OverlayManager.prototype = {
            startCreateCollectionOverlay: function(sandbox, parentCollection) {
                var $element = sandbox.dom.createElement('<div id="collection-add"/>'),
                    parentId = (!!parentCollection && !!parentCollection.id) ? parentCollection.id : null;

                sandbox.dom.append('body', $element);

                sandbox.start([{
                    name: 'collections/create-overlay@sulumedia',
                    options: {
                        el: $element,
                        parent: parentId,
                        createdCallback: function(collection) {
                            sandbox.emit(
                                'sulu.labels.success.show',
                                'labels.success.collection-save-desc',
                                'labels.success'
                            );
                            sandbox.emit(
                                'sulu.router.navigate',
                                'media/collections/edit:' + collection.get('id') + '/files'
                            );
                            sandbox.emit(
                                'husky.data-navigation.collections.set-url',
                                '/admin/api/collections/' + collection.get('id') + '?depth=1'
                            );
                        }.bind(this)
                    }
                }]);
            }
        };

        OverlayManager.getInstance = function() {
            if (instance === null) {
                instance = new OverlayManager();
            }
            return instance;
        };

        return OverlayManager.getInstance();
    }
);
