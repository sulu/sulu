/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'jquery',
    'services/husky/util',
    'services/husky/mediator',
    'services/suluwebsite/reference-store'
], function($, util, mediator, referenceStore) {

    'use strict';

    var start,
        last,

        resolved = false,

        load = function(url, data, alias) {
            if (alias) {
                data.excluded = data.excluded.concat(referenceStore.getAll(alias));
            }

            data.excluded = data.excluded.filter(function(item) {
                return !!item;
            }).join(',');

            return util.load(url, data);
        };

    return {
        initialize: function() {
            start = last = $.Deferred();
            mediator.once('sulu.content.initialized', function() {
                resolved = true;
                start.resolve();
            });
        },

        load: function(url, data, alias) {
            if (resolved) {
                return load(url, data, alias);
            }

            var deferred = $.Deferred();
            last.always(function() {
                load(url, data, alias).done(function(result) {
                    deferred.resolve(result);
                }).fail(function(textStatus) {
                    deferred.reject(textStatus);
                });
            });

            return last = deferred;
        }
    }
});
