/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    return {

        type: 'seo-tab',

        tabInitialize: function() {
            this.sandbox.emit('sulu.app.ui.reset', {navigation: 'small', content: 'auto'});
            this.sandbox.emit('husky.toolbar.header.item.disable', 'template', false);
            this.sandbox.emit('sulu.content.contents.show-save-items', 'content');
        },

        getUrl: function() {
            var content = this.options.data();

            return this.options.excerptUrlPrefix + '/' + this.options.language + content.url;
        },

        setHeaderBar: function() {
            this.sandbox.emit('sulu.content.contents.set-header-bar', false);
        },

        parseData: function(data) {
            return data.ext.seo;
        },

        save: function(data, action) {
            var content = this.options.data();
            content.ext.seo = data;

            this.sandbox.emit('sulu.content.contents.save', content, action);
        }
    };
});
