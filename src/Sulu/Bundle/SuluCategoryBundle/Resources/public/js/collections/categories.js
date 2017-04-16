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
    'sulucategory/model/category'
], function(Collection, CategoryModel) {

    return Collection({

        model: CategoryModel,

        url: function() {
            return '/admin/api/categories';
        },

        parse: function(resp) {
            return resp._embedded.categories;
        }

    });

});
