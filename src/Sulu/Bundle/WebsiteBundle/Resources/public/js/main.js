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
        suluwebsite: '../../suluwebsite/js',
        suluwebsitecss: '../../suluwebsite/css',

        "type/piwik": '../../suluwebsite/js/validation/piwik'
    }
});

define(['jquery', 'css!suluwebsitecss/main'], function($) {
    return {

        name: "SuluWebsiteBundle",

        initialize: function(app) {

            'use strict';

            var sandbox = app.sandbox;

            app.sandbox.website = {
                /**
                 * Clear the cache for the website.
                 */
                cacheClear: function() {
                    $.ajax('/admin/website/cache', { method: 'DELETE' })
                        .then(function() {
                            app.sandbox.emit(
                                'sulu.labels.success.show',
                                'sulu.website.cache.remove.success.description',
                                'sulu.website.cache.remove.success.title',
                                'cache-success'
                            );
                        }.bind(this))
                        .fail(function(jqXHR) {
                            if (jqXHR.status === 403) {
                                return;
                            }

                            app.sandbox.emit(
                                'sulu.labels.error.show',
                                'sulu.website.cache.remove.error.description',
                                'sulu.website.cache.remove.error.title',
                                'cache-error'
                            );
                        }.bind(this));
                }
            };

            app.components.addSource('suluwebsite', '/bundles/suluwebsite/js/components');

            // cache clear button
            sandbox.mvc.routes.push({
                route: 'settings/cache',
                callback: function() {
                    return '<div data-aura-component="cache@suluwebsite"/>';
                }
            });
        }
    };
});
