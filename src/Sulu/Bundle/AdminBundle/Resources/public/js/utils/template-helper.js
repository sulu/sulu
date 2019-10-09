/*
 * This file is part of the Sulu CMS.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {
    'use strict';

    return {
        transformTemplateData: function(func) {
            return function(data) {
                return func({data: data});
            };
        }
    };
});
