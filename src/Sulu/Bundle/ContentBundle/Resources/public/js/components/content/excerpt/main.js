/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    return {

        type: 'excerpt-tab',

        tabInitialize: function() {
            this.sandbox.emit('sulu.app.ui.reset', { navigation: 'small', content: 'auto'});
            this.sandbox.emit('husky.toolbar.header.item.disable', 'template', false);
        },

        loadComponentData: function() {
            var promise = $.Deferred();

            // get content data
            this.sandbox.emit('sulu.content.contents.get-data', function(data) {
                promise.resolve(this.parseData(data));
            }.bind(this));

            return promise;
        },

        parseData: function(data) {
            return data.ext.excerpt;
        },

        listenForChange: function() {
            this.sandbox.dom.on(this.options.formId, 'keyup change', function() {
                this.setHeaderBar(false);
            }.bind(this), '.trigger-save-button');

            this.sandbox.on('sulu.content.changed', function() {
                this.setHeaderBar(false);
            }.bind(this));

            this.sandbox.emit('sulu.content.contents.show-save-items', 'content');
        },

        setHeaderBar: function(saved) {
            this.sandbox.emit('sulu.content.contents.set-header-bar', saved);
        },

        save: function(data, action) {
            var content = this.options.data();
            content.ext.excerpt = data;
            this.sandbox.emit('sulu.content.contents.save', content, action);
        }
    };
});
