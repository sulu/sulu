/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'mvc/collection',
    'sulumedia/model/collection'
], function(Collection, CollectionModel) {

    return Collection({

        model: CollectionModel,

        url: function() {
            return '/admin/api/collections?depth=0';
        },

        parse: function(resp) {
            return resp._embedded.collections;
        }

    });

});
