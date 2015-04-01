/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

/**
 * @class WidgetGroups
 * @constructor
 */
define(['config'], function(Config) {

    'use strict';

    var widgetGroups = Config.get('sulu_admin.widget_groups');

    return {
        exists: function(alias) {
            alias = alias.replace('-', '_');

            return (!!widgetGroups[alias] && !!widgetGroups[alias]['mappings'] && widgetGroups[alias]['mappings'].length > 0);
        }
    };
});
