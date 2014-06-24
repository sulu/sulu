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
        templates: ['/admin/content/template/content/seo'],

        initialize: function() {
            this.sandbox.emit('sulu.app.ui.reset', { navigation: 'small', content: 'auto'});

            this.html(this.renderTemplate('/admin/content/template/content/seo'));
        }
    };
});
