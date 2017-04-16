/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'mvc/relationalmodel'
], function(RelationalModel) {

    'use strict';

    return RelationalModel({

        contactId: null,
        urlRoot: function(){
            return '/admin/api/contact/'+ this.contactId +'/medias';
        },
        defaults: {
            id: null
        }
    });
});
