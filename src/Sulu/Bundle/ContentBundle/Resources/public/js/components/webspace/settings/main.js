/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    return {
        header: function() {
            return {
                title: function() {
                    return this.data.title;
                }.bind(this),

                noBack: true,
                tabs: {
                    url: '/admin/content-navigations?alias=webspace',
                    options: {
                        data: function() {
                            // this.data is set by sulu-content.js with data from loadComponentData()
                            return this.sandbox.util.extend(false, {}, this.data);
                        }.bind(this)
                    }
                }
            };
        },

        layout: function() {
            return {
                extendExisting: true,
                content: {
                    width: 'fixed'
                }
            };
        },

        loadComponentData: function() {
            var deferred = this.sandbox.data.deferred();
            deferred.resolve({title: 'TEST'});

            // TODO load webspace data

            return deferred.promise();
        },

        initialize: function() {
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
        }
    };
});
