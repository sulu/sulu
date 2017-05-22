/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function(){
    'use strict';

    return {
        type: 'excerpt-tab',

        tabInitialize: function() {
            this.sandbox.emit('husky.toolbar.header.item.disable', 'template', false);
        },

        parseData: function(data) {
            return data.ext.excerpt;
        },

        loadComponentData: function() {
            var promise = $.Deferred();

            // get content data
            this.sandbox.emit('sulu.snippets.snippet.get-data', function(data) {
                promise.resolve(this.parseData(data));
            }.bind(this));

            return promise;
        },

        getTemplate: function() {
            return 'text!/admin/content/template/form/excerpt.html?language=' + this.options.locale
                + '&excludedProperties=title,more,description,icon,images';
        },

        save: function(data, action) {
            this.sandbox.emit('sulu.snippets.snippet.get-data', function(snippet) {
                snippet.ext.excerpt = data;
                this.sandbox.emit('sulu.snippets.snippet.save', snippet, action);
            }.bind(this));
        }
    };
});
