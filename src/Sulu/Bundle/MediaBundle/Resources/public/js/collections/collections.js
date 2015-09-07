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
    'sulumedia/models/collection'
], function(Collection, CollectionModel) {

    return Collection({

        model: CollectionModel,

        url: function() {
            // TODO remove high limit and paginate correctly
            return '/admin/api/collections?limit=99999&depth=99999&flat=true';
        },

        fetchSorted: function(sortBy, options) {
            options = _.defaults((options || {}), {url: this.url() + '&sortBy=' + sortBy});

            return this.fetch.call(this, options);
        },

        parse: function(resp) {
            return resp._embedded.collections;
        }

    });

});
