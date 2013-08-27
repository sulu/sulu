/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'backbone',
    'sulutranslate/model/code',
    'sulutranslate/model/translation'
    ], function(Backbone, Code, Translation) {

    return Backbone.Collection.extend({

        model: Code,

        url: function () {
            var url = '/translate/api/codes?packageId='+this.translatePackageId+'&catalogueId='+this.translateCatalogueId;
            console.log(url, 'url');
            return url;
        },

        parse: function(resp) {
            return resp.items;
        },

        initialize: function(models, options) {
            this.translatePackageId = options.translatePackageId;
            this.translateCatalogueId = options.translateCatalogueId;
        }


    });

});
