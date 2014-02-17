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
        content: {
            url: '/admin/content/navigation/content',
            title: 'content.contents.title',
            parentTemplate: 'default',
            template: [{
                icon: 'eye-open',
                iconSize: 'large',
                group: 'left',
                position: 20,
                items: [
                    {
                        title: App.translate('sulu.edit-toolbar.new-window'),
                        callback: function () {
                            App.emit('sulu.edit-toolbar.preview.new-window');
                        }
                    },
                    {
                        title: App.translate('sulu.edit-toolbar.split-screen'),
                        callback: function () {
                            App.emit('sulu.edit-toolbar.preview.split-screen');
                        }
                    }
                ]
            },
            {
                'id': 'state',
                'group': 'right',
                'class': 'highlight-gray',
                'position': 2,
                'type': 'select'
            }]
        }
    };
});
