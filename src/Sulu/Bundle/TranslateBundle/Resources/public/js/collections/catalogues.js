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
    'sulutranslate/models/catalogue'
], function(Collection, Catalogue) {

    return Collection({

        model: Catalogue,

        url: function() {
            return '/admin/api/catalogues?flat=true&fields=' + this.fields + '&packageId=' + this.packageId;
        },

        initialize: function(options) {
            this.packageId = options.packageId;
            this.fields = options.fields;
        },

        parse: function(resp) {
            return resp._embedded.catalogues;
        }

    });

});
