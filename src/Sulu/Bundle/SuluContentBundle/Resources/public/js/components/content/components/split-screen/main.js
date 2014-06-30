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
            skeleton: function(id, url, webspace, language) {
                return [
                    '<div id="top-toolbar"></div>',
                    '<div id="split-screen">',
                    '<div id="form" class="grid" data-aura-component="content@sulucontent" data-aura-id="', id, '" data-aura-webspace="', webspace, '" data-aura-language="', language, '" data-aura-display="form" data-aura-preview="true"></div>',
                    '<div id="preview"><iframe src="', url, '/admin/content/preview/', id, '" width="1024" height="768"></iframe></div>',
                    '</div>'
                ].join('');
            }
        },
        toolbarData = function() {
            return[
                {
                    id: 'exit',
                    icon: 'times',
                    container: 'left',
                    align: 'left',
                    customClass: 'exit',
                    closeComponent: true,
                    callback: function() {
                        window.close();
                    }.bind(this)
                },
                {
                    id: 3,
                    icon: 'trash-o',
                    container: 'left',
                    align: 'right',
                    customClass: 'delete',
                    callback: function() {
                        this.sandbox.emit('husky.top-toolbar.delete.loading');
                        this.sandbox.emit('sulu.preview.delete');
                    }.bind(this)
                },
                {
                    id: 'save',
                    icon: 'floppy-o',
                    container: 'left',
                    align: 'right',
                    customClass: 'save',
                    highlight: true,
                    disabled: true,
                    callback: function() {
                        this.sandbox.emit('husky.top-toolbar.save.loading');
                        this.sandbox.emit('sulu.preview.save');
                    }.bind(this)
                }
            ];
        };

    return {

        view: true,

        initialize: function() {
            this.render();

            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.preview.state.change', function(saved) {
                if (!!saved) {
                    this.sandbox.emit('husky.top-toolbar.save.disable', true);
                } else {
                    this.sandbox.emit('husky.top-toolbar.save.enable');
                }
            }, this);

            this.sandbox.on('sulu.preview.deleted', function() {
                window.close();
            }, this);
        },

        render: function() {
            this.html(templates.skeleton(this.options.id, this.options.url, this.options.webspace, this.options.language));

            var formWidth = this.sandbox.dom.find('#form').outerWidth() + 10,
                mainWidth = this.sandbox.dom.find('#split-screen').outerWidth();

            this.sandbox.dom.find('#preview').css('width', mainWidth - formWidth);

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
