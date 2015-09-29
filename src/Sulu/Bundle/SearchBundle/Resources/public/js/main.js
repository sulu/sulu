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
        sulusearch: '../../sulusearch/js',
        sulusearchcss: '../../sulusearch/css'
    }
});

define(['css!sulusearchcss/main'], function() {
    return {

        name: 'Sulu Search Bundle',

        initialize: function(app) {

            'use strict';

            var sandbox = app.sandbox,
                dataOverlayStarted = false,
                $element;

            app.components.addSource('sulusearch', '/bundles/sulusearch/js/components');

            app.components.before('initialize', function() {
                if (this.name !== 'Sulu App') {
                    return;
                }

                this.sandbox.on('husky.navigation.item.select', function() {
                    this.sandbox.emit('sulu.data-overlay.hide');
                }.bind(this));

                this.sandbox.on('husky.navigation.item.event.search', function() {
                    if (!dataOverlayStarted) {
                        $element = this.sandbox.dom.createElement('<div class="data-overlay-container"/>');

                        App.dom.append('.content-container', $element);
                        App.start([
                            {
                                name: 'data-overlay@suluadmin',
                                options: {
                                    el: '.data-overlay-container',
                                    component: 'search-results@sulusearch'
                                }
                            }
                        ]).then(function() {
                            dataOverlayStarted = true;
                            sandbox.emit('sulu.data-overlay.show');
                        });
                    } else {
                        this.sandbox.emit('sulu.data-overlay.show');
                    }
                }.bind(this));
            });
        }
    };
});
