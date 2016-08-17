/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['services/husky/util', 'services/husky/mediator'], function(Util, Mediator) {

    'use strict';

    var instance = null,

        navigate = function(route) {
            Mediator.emit('sulu.router.navigate', route, true, true);
        };

    /** @constructor **/
    function CategoryManager() {
    }

    CategoryManager.prototype = {
        toList: function(locale) {
            navigate('settings/categories/' + locale);
        },
        toEdit: function(locale, id, content) {
            navigate('settings/categories/' + locale + '/edit:' + id + '/' + content);
        },
        toNew: function(locale, content, parent) {
            navigate('settings/categories/' + locale + '/new/' + (!!parent ? parent + '/' : '') + content);
        }
    };

    CategoryManager.getInstance = function() {
        if (instance === null) {
            instance = new CategoryManager();
        }
        return instance;
    };

    return CategoryManager.getInstance();
});
