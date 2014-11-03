/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'mvc/relationalmodel',
    'sulucontact/model/account',
    'mvc/hasone',
    'sulucontact/model/contact',
    'sulucontact/model/activityPriority',
    'sulucontact/model/activityType',
    'sulucontact/model/activityStatus'
], function(RelationalModel, Account, HasOne, Contact, ActivityPriority, ActivityStatus, ActivityType) {

    'use strict';

    return RelationalModel({
        urlRoot: '/admin/api/activities',
        defaults: function() {
            return {
                id: null,
                subject: '',
                note: '',
                dueDate: '',
                startDate: '',
                activityStatus: null,
                activityType: null,
                activityPriority: null,
                account: null,
                contact: null,
                assignedContact: null
            };
        }, relations: [
            {
                type: HasOne,
                key: 'account',
                relatedModel: Account
            },
            {
                type: HasOne,
                key: 'contact',
                relatedModel: Contact
            },
            {
                type: HasOne,
                key: 'assignedContact',
                relatedModel: Contact
            },
            {
                type: HasOne,
                key: 'activityType',
                relatedModel: ActivityType
            },
            {
                type: HasOne,
                key: 'activityPriority',
                relatedModel: ActivityPriority
            },
            {
                type: HasOne,
                key: 'activityStatus',
                relatedModel: ActivityStatus
            }
        ]
    });
});
