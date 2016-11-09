/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function () {
    'use strict';

    return {
        setLastSelectedPage: function(webspace, uuid) {
            App.sulu.saveUserSetting(webspace + 'ColumnNavigationSelected', uuid);
        }
    }
});
