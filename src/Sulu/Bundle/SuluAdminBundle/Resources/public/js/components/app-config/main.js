/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var config = JSON.parse(JSON.stringify(SULU));

    return {
        getUser: function() {
            return config.user;
        },

        getSection: function(name) {
            return (!!config.sections[name]) ? config.sections[name] : null;
        },

        getDebug: function() {
            return config.debug;
        }
    };
});
