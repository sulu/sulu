/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    return {
        content: {
            url: '/admin/content/navigation/content',
            title: 'content.contents.title',
            extendTemplate: 'default',
            template: function() {
                return [
                    {
                        icon: 'eye-open',
                        iconSize: 'large',
                        group: 'right',
                        position: 1,
                        items: [
                            {
                                title: this.sandbox.translate('sulu.edit-toolbar.new-window'),
                                callback: function() {
                                    this.sandbox.emit('sulu.edit-toolbar.preview.new-window');
                                }.bind(this)
                            },
                            {
                                title: this.sandbox.translate('sulu.edit-toolbar.split-screen'),
                                callback: function() {
                                    this.sandbox.emit('sulu.edit-toolbar.preview.split-screen');
                                }.bind(this)
                            }
                        ]
                    },
                    {
                        id: 'template',
                        icon: 'tag',
                        iconSize: 'large',
                        group: 'right',
                        position: 1,
                        type: 'select',
                        title: '',
                        itemsOption: {
                            url: '/admin/content/template',
                            titleAttribute: 'template',
                            idAttribute: 'template',
                            translate: true,
                            languageNamespace: 'template.',
                            callback: function(item) {
                                this.sandbox.emit('sulu.edit-toolbar.dropdown.template.item-clicked', item);
                            }.bind(this)
                        }
                    }
                ];
            }
        }
    };
});
