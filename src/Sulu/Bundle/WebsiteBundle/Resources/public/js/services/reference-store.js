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

    var references = {};

    return {
        add: function(alias, id) {
            if (!references[alias]) {
                references[alias] = [];
            }

            references[alias].push(id);
        },

        getAll: function(alias) {
            if (!references[alias]) {
                return [];
            }

            return references[alias];
        }
    };
});
