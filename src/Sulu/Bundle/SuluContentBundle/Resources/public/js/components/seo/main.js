/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontent/model/seo'
], function(Seo) {

    'use strict';

    return {
        templates: ['/admin/content/template/content/seo'],

        initialize: function() {
            this.sandbox.emit('sulu.app.ui.reset', { navigation: 'small', content: 'auto'});

            this.html(this.renderTemplate('/admin/content/template/content/seo'));

            this.loadData();
        },

        loadData: function() {
            this.model = new Seo({webspaceKey: this.options.webspace, languageCode: this.options.language});
            this.model.set({id: this.options.id});
            this.model.fetch({
                success: function(model) {
                    this.data = model.toJSON();

                    this.setTitle()
                }.bind(this)
            });
        },

        /**
         * hack to simulate form component
         * copied from form component
         */

        /**
         * Sets the title of the page and if in edit mode calls a method to set the breadcrumb
         */
        setTitle: function() {
            var value = this.data['sulu.node.name'];

            if (!!this.options.id && value !== '') {
                this.sandbox.emit('sulu.header.set-title', value);
                this.setBreadcrumb();
            } else {
                this.sandbox.emit('sulu.header.set-title', this.sandbox.translate('content.contents.title'));
            }
        }
    };
});
