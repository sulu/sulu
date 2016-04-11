/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery'], function ($) {
    'use strict';

    var baseUrl = '/admin/api/nodes';

    return {
        save: function(data, locale, webspace, successCallback, errorCallback) {
            var method = 'POST', url = baseUrl, requestParameters = [];

            if (!!data.id) {
                method = 'PUT';
                url += '/' + data.id;
            }

            if (!!locale) {
                requestParameters.push('language=' + locale);
            }

            if (!!webspace) {
                requestParameters.push('webspace=' + webspace);
            }

            $.ajax(
                url + '?' + requestParameters.join('&'),
                {
                    method: method,
                    data: JSON.stringify(data),
                    contentType: 'application/json; charset=utf-8',
                    success: successCallback,
                    error: errorCallback
                }
            );
        }
    }
});
