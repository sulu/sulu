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

        content: function() {
            return {
                url: '/admin/content/navigation/content',
                parentTemplate: 'default',
                template: function() {
                    var state = {
                            'id': 'state',
                            'group': 'right',
                            'class': 'highlight-gray',
                            'position': 2,
                            'type': 'select'
                        },
                        template = {
                            id: 'template',
                            icon: 'brush',
                            iconSize: 'large',
                            group: 'left',
                            position: 10,
                            type: 'select',
                            title: '',
                            hidden: true,
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
                        };
                    if (!this.options.id) {
                        return [template, state];
                    } else {
                        return [template, state];
                    }
                }.bind(this)
            };
        }
    };
});
