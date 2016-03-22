/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'vendor/clipboard/clipboard'], function($, Clipboard) {

    'use strict';

    function ClipboardInstance(selector, options) {
        this.clipboard = new Clipboard(selector, options || {});
    }

    ClipboardInstance.prototype.destroy = function() {
        this.clipboard.destroy();
    };

    return {

        name: 'clipboard',

        initialize: function(app) {

            app.sandbox.clipboard = {
                initialize: function(selector, options) {
                    return new ClipboardInstance(selector, options);
                }
            };

            app.components.before('destroy', function() {
                if (!!this.clipboard) {
                    this.clipboard.destroy();
                }
            });

        }
    };
});
