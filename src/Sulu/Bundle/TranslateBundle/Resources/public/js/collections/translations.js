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
    'sulutranslate/models/code',
    'sulutranslate/models/translation'
], function(Collection, Code, Translation) {

    'use strict';

    return new Collection({

        model: Translation,

        url: function() {
            return '/admin/api/catalogues/' + this.catalogueId + '/translations';
        },

        initialize: function(options) {
            this.catalogueId = options.translateCatalogueId;
        },

        parse: function(resp) {
            return resp._embedded.translations;
        },

        save: function(sandbox, translations, options) {

            sandbox.util.ajax({

                headers: {
                    'Content-Type': 'application/json'
                },

                type: "PATCH",
                url: this.url(),
                data: JSON.stringify(translations),

                success: function() {
                    if (!!options && !!options.success && typeof options.success === 'function') {
                        options.success();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (!!options && !!options.error && typeof options.error === 'function') {
                        options.error(jqXHR, textStatus, errorThrown);
                    }
                },
                complete: function() {
                    if (!!options && !!options.complete && typeof options.complete === 'function') {
                        options.complete();
                    }
                }

            });
        }
    });
});
