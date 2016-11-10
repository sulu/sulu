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

        'services/sulucategory/category-manager': '../../sulucategory/js/services/category-manager',
        'services/sulucategory/category-router': '../../sulucategory/js/services/category-router',

        'type/categoryList': '../../sulucategory/js/validation/types/categoryList'
    }
});

define([
    'config',
    'services/sulucategory/category-router',
    'css!sulucategorycss/main'
], function(Config, CategoryRouter) {
    return {

        name: "SuluCategoryBundle",

        initialize: function(app) {

            'use strict';

            var CATEGORIES_LOCALE = 'categoryLocale',
                getLocale = function() {
                    return app.sandbox.sulu.getUserSetting(CATEGORIES_LOCALE)
                        || app.sandbox.sulu.getDefaultContentLocale();
                };

            Config.set('sulu_category.user_settings.category_locale', CATEGORIES_LOCALE);

            app.sandbox.urlManager.setUrl('category', 'settings/categories/<%= locale %>/edit:<%= id %>/details');

            app.components.addSource('sulucategory', '/bundles/sulucategory/js/components');

            app.sandbox.mvc.routes.push({
                route: 'settings/categories',
                callback: function() {
                    return CategoryRouter.toList(getLocale());
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
                    return CategoryRouter.toNew(getLocale(), content, parent);
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
                    return CategoryRouter.toNew(getLocale(), content);
                }
            });

            app.sandbox.mvc.routes.push({
                route: 'settings/categories/:locale/new/:content',
                callback: function(locale, content) {
                    return '<div data-aura-component="categories/edit@sulucategory" data-aura-content="' + content + '" data-aura-locale="' + locale + '"/>';
                }
            });

            app.sandbox.mvc.routes.push({
                route: 'settings/categories/edit::id/:content',
                callback: function(id, content) {
                    return CategoryRouter.toEdit(getLocale(), id, content);
                }
            });

            app.sandbox.mvc.routes.push({
                route: 'settings/categories/:locale/edit::id/:content',
                callback: function(locale, id, content) {
                    return '<div data-aura-component="categories/edit@sulucategory" data-aura-id="' + id + '" data-aura-content="' + content + '" data-aura-locale="' + locale + '"/>';
                }
            });
        }
    };
});
