/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery'], function ($) {
    'use strict';

    var baseUrl = '/admin/api/users';

    return {
        find: function(id) {
            var deferred = $.Deferred();

            $.ajax(
                baseUrl + '/' + id,
                {
                    method: 'GET',
                    contentType: 'application/json; charset=utf-8',
                    success: function(response) {
                        deferred.resolve(response);
                    }.bind(this),
                    error: function(xhr) {
                        deferred.reject(xhr);
                    }.bind(this)
                }
            );

            return deferred.promise();
        }
    }
});
