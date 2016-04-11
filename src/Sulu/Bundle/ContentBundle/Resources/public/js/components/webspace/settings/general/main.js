/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!./skeleton.html'], function(skeleton) {

    'use strict';

    var defaults = {
        templates: {
            skeleton: skeleton
        },
        translations: {
            nameKey: 'public.name',
            localizationKey: 'content.webspace.settings.localization',
            localizationsKey: 'content.webspace.settings.localizations',
            themeKey: 'content.webspace.settings.theme',
            urlKey: 'content.webspace.settings.url',
            urlsKey: 'content.webspace.settings.urls',
            mainKey: 'content.webspace.settings.main',
            redirectKey: 'content.webspace.settings.redirect'
        }
    };

    return {

        defaults: defaults,

        tabOptions: function() {
            return {
                title: this.data.webspace.title
            };
        },

        layout: {
            content: {
                leftSpace: false,
                rightSpace: false
            }
        },

        initialize: function() {
            this.render();
        },

        render: function() {
            this.html(
                this.templates.skeleton(
                    {
                        webspace: this.data,
                        translations: this.translations
                    }
                )
            );
        },

        loadComponentData: function() {
            return this.options.data();
        }
    };
});
