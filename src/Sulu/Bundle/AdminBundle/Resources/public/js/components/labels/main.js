/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * provides:
 *  - sulu.edittoolbar.setState();
 *  - sulu.edittoolbar.setButton(id);
 *
 * triggers:
 *  - sulu.edittoolbar.submit - when most left button was clicked
 *
 * options:
 *  - heading - string
 *  - tabsData - dataArray needed for building tabs
 *  -
 *
 *
 */

define([], function() {

    'use strict';

    var defaults = {

    }


    return {
        view: true,

        initialize: function() {
            // merge defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

        }
    };
});
