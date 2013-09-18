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
    'sulutranslate/components/translations/models/code',
    'sulutranslate/components/translations/models/translation'
], function(Backbone, Code, Translation) {

    return Backbone.Collection.extend({

        model: Translation,

        url: function() {
            return '/translate/api/catalogues/' + this.catalogueId + '/translations'
        },

        initialize: function(options) {
            this.catalogueId = options.translateCatalogueId;
        },

        parse: function(resp) {
            return resp.items;
        },

        save: function(translations) {

            $.ajax({

                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },

                type: "PATCH",
                url: this.url(),
                data: JSON.stringify(translations),

                success: function() {
                    console.log("patch successful");
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // log the error to the console
                    console.log("error during patch: " + textStatus, errorThrown);
                },
                complete: function() {
                    console.log("completed patch");
                }

            });
        }
    });
});
