/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore'], function(_) {

    'use strict';

    var defaults = {
        options: {
            url: '/admin/api/webspaces/<%= webspace %>'
        }
    };

    return {

        defaults: defaults,

        header: function() {
            return {
                title: function() {
                    return this.data.name;
                }.bind(this),

                noBack: true,

                tabs: {
                    url: '/admin/content-navigations?alias=webspace&webspace=' + this.options.webspace,
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

            this.sandbox.util.load(
                _.template(this.options.url, {webspace: this.options.webspace})
            ).then(function(data) {
                deferred.resolve(data);
            });

            return deferred.promise();
        }
    };
});
