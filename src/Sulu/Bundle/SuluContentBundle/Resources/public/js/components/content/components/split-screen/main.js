/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app-config'], function(AppConfig) {

    'use strict';

    var templates = {
            skeleton: function(id) {
                return [
                    '<div id="top-toolbar"></div>',
                    '<div id="form" class="grid" data-aura-component="content@sulucontent" data-aura-id="', id, '" data-aura-display="form"></div>',
                    '<div id="preview"><iframe src="http://sulu.lo/admin/content/preview/07b09093-76e3-4eba-8078-7cae802a80ee" height="100%" width="100%"></iframe></div>'
                ].join('');
            }
        },
        toolbarData = function() {
            return[
                {
                    id: 'exit',
                    icon: 'remove',
                    container: 'left',
                    align: 'left',
                    customClass: 'exit',
                    closeComponent: true,
                    callback: function() {
                        window.close();
                    }.bind(this)
                },
                {
                    id: 'state',
                    icon: 'publish',
                    container: 'left',
                    align: 'right',
                    customClass: 'state',
                    dynamicIcon: true,
                    items: [
                        {
                            title: 'publish',
                            selectedIcon: 'publish',
                            callback: function() {
                                this.sandbox.emit('husky.top-toolbar.state.loading');
                                // FIXME only dummy
                                setTimeout(function() {
                                    this.sandbox.emit('husky.top-toolbar.state.enable', false, false);
                                }.bind(this), 1000);
                            }.bind(this)
                        },
                        {
                            title: 'unpublish',
                            selectedIcon: 'unpublish',
                            callback: function() {
                                this.sandbox.emit('husky.top-toolbar.state.loading');
                                // FIXME only dummy
                                setTimeout(function() {
                                    this.sandbox.emit('husky.top-toolbar.state.enable', false, false);
                                }.bind(this), 1000);
                            }
                        }
                    ]
                },
                {
                    id: 'save',
                    icon: 'floppy-save',
                    disabledIcon: 'remove',
                    container: 'left',
                    align: 'right',
                    customClass: 'save',
                    highlight: true,
                    callback: function() {
                        // TODO save
                    }
                },
                {
                    id: 'resolution',
                    container: 'right',
                    align: 'right',
                    customClass: 'resolution',
                    title: '1024 x 768',
                    dynamicTitle: true,
                    items: [
                        {
                            title: '1024 x 768',
                            callback: function() {
                            }
                        },
                        {
                            title: '1280 x 800',
                            callback: function() {
                            }
                        },
                        {
                            title: '1920 x 1080',
                            callback: function() {
                            }
                        }
                    ]
                }
            ];
        };

    return {

        view: true,

        initialize: function() {
            this.render();
        },

        render: function() {
            this.html(templates.skeleton(this.options.id));

            this.sandbox.start([
                {
                    name: 'top-toolbar@husky',
                    options: {
                        el: '#top-toolbar',
                        data: toolbarData.call(this)
                    }
                }
            ]);
        }
    };
});
