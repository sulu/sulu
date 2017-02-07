/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

/**
 * @class CSV-Export
 * @constructor
 */
define(['text!/admin/templates/csv-export-form'], function(form) {

    'use strict';

    return {
        type: 'csv-export',

        getFormTemplate: function() {
            return form;
        }
    };
});
