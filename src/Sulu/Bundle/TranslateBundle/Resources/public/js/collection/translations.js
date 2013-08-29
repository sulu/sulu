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

        model: Translation,

        url: function() {
            return '/catalogues/'+this.catalogueId+'/translations'
        },

        initialize: function(catalogueId) {
            this.catalogueId = catalogueId;
        },

        parse: function(resp) {
            return resp.items;
        },

        save: function(translations){

            $.ajax({
                type: "PATCH",
                url: this.url(),
                data: translations
            }).done(function( msg ) {
                    alert( "save: " + msg );
                });

        }

    });

});
