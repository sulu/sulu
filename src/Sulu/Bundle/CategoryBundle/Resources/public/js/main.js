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
        sulucategory: '../../sulucategory/js',
        sulucategorycss: '../../sulucategory/css',

        "type/categoryList": '../../sulucategory/js/validation/types/categoryList'
    }
});

define(['config', 'css!sulucategorycss/main'], function(Config) {
    return {

        name: "SuluCategoryBundle",

        initialize: function(app) {

            'use strict';

            var CATEGORIES_LOCALE = 'categoryLocale',
                getLocale = function() {
                    return app.sandbox.sulu.getUserSetting(CATEGORIES_LOCALE)
                        || app.sandbox.sulu.getDefaultContentLocale();
                },
                toList = function(locale) {
                    app.sandbox.emit('sulu.router.navigate', 'settings/categories/' + locale, false, false);
                },
                toEdit = function(locale, id, content) {
                    app.sandbox.emit('sulu.router.navigate', 'settings/categories/' + locale + '/edit:' + id + '/' + content, false, false);
                },
                toNew = function(locale, content, parent) {
                    app.sandbox.emit('sulu.router.navigate', 'settings/categories/' + locale + '/new/' + (!!parent ? parent + '/' : '') + content, false, false);
                };

            Config.set('sulu_category.user_settings.category_locale', CATEGORIES_LOCALE);

            app.sandbox.urlManager.setUrl('category', 'settings/categories/<%= locale %>/edit:<%= id %>/details');

            app.components.addSource('sulucategory', '/bundles/sulucategory/js/components');

            app.sandbox.mvc.routes.push({
                route: 'settings/categories',
                callback: function() {
                    var locale = getLocale();
                    toList(locale);

                    return '<div data-aura-component="categories@sulucategory" data-aura-display="list" data-aura-locale="' + locale + '"/>';
                }
            });

            app.sandbox.mvc.routes.push({
                route: 'settings/categories/:locale',
                callback: function(locale) {
                    return '<div data-aura-component="categories@sulucategory" data-aura-display="list" data-aura-locale="' + locale + '"/>';
                }
            });

            app.sandbox.mvc.routes.push({
                route: 'settings/categories/new/:parent/:content',
                callback: function(parent, content) {
                    var locale = getLocale();
                    toNew(locale, content, parent);

                    return '<div data-aura-component="categories@sulucategory" data-aura-display="edit" data-aura-parent="' + parent + '" data-aura-locale="' + locale + '"/>';
                }
            });

            app.sandbox.mvc.routes.push({
                route: 'settings/categories/:locale/new/:parent/:content',
                callback: function(locale, parent) {
                    return '<div data-aura-component="categories@sulucategory" data-aura-display="edit" data-aura-parent="' + parent + '" data-aura-locale="' + locale + '"/>';
                }
            });

            app.sandbox.mvc.routes.push({
                route: 'settings/categories/new/:content',
                callback: function(content) {
                    var locale = getLocale();
                    toNew(locale, content);

                    return '<div data-aura-component="categories@sulucategory" data-aura-display="edit" data-aura-locale="' + locale + '"/>';
                }
            });

            app.sandbox.mvc.routes.push({
                route: 'settings/categories/:locale/new/:content',
                callback: function(locale) {
                    return '<div data-aura-component="categories@sulucategory" data-aura-display="edit" data-aura-locale="' + locale + '"/>';
                }
            });

            app.sandbox.mvc.routes.push({
                route: 'settings/categories/edit::id/:content',
                callback: function(id, content) {
                    var locale = getLocale();
                    toEdit(locale, id, content);

                    return '<div data-aura-component="categories@sulucategory" data-aura-display="edit" data-aura-id="' + id + '" data-aura-locale="' + locale + '"/>';
                }
            });

            app.sandbox.mvc.routes.push({
                route: 'settings/categories/:locale/edit::id/:content',
                callback: function(locale, id) {
                    return '<div data-aura-component="categories@sulucategory" data-aura-display="edit" data-aura-id="' + id + '" data-aura-locale="' + locale + '"/>';
                }
            });
        }
    };
});
