/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var synonyms = {},
        references = {},

        resolveAlias = function(alias) {
            if (synonyms.hasOwnProperty(alias)) {
                return synonyms[alias];
            }

            return alias;
        };

    return {
        setSynonym: function(synonym, alias) {
            synonyms[synonym] = alias;
        },

        add: function(alias, id) {
            alias = resolveAlias(alias);
            if (!references[alias]) {
                references[alias] = [];
            }

            references[alias].push(id);
        },

        getAll: function(alias) {
            alias = resolveAlias(alias);
            if (!references[alias]) {
                return [];
            }

            return references[alias];
        }
    };
});
