/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['mvc/relationalmodel'], function (RelationalModel) {

    'use strict';

    return RelationalModel({
        urlRoot: '/admin/api/media',

        defaults: function () {
            return {
                id: null,
                locale: '',
                title: '',
                name: '',
                description: '',
                tags: [],
                categories: [],
                copyright: '',
                credits: '',
                versions: {}
            };
        }
    });
});
