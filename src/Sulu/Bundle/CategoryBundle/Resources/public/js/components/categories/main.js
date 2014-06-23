/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function () {

    'use strict';

    return {

        initialize: function () {
            this.bindCustomEvents();

            if (this.options.display === 'list') {
                this.renderList();
            } else {
                throw 'display type wrong';
            }
        },

        bindCustomEvents: function () {

        },

        renderList: function () {
            var $list = this.sandbox.dom.createElement('<div id="categories-list-container"/>');
            this.html($list);
            this.sandbox.start([{
                name: 'categories/list@sulucategory',
                options: {
                    el: $list
                }
            }]);
        }
    };
});
