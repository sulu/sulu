/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'backbonerelational',
    'sulucontact/model/emailType'], function (BackboneRelational, EmailType) {
    return Backbone.RelationalModel.extend({
        urlRoot: '/contact/api/emails',
        defaults: {
            id: null,
            email: '',
            emailType: null
        }, relations: [
            {
                type: Backbone.HasOne,
                key: 'emailType',
                relatedModel: 'EmailType'
            }
        ]
    });
});
