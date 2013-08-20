/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require.config({
    paths: {
        sulutranslate: '../../sulutranslate/js'
    }
});

define(['sulutranslate/bundle'], function(Bundle) {

    'use strict';

    Bundle.initialize();

});
