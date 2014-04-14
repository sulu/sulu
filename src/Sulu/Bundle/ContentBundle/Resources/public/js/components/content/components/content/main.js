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
                            'group': 'left',
                            'position': 100,
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
                                    this.sandbox.emit('sulu.dropdown.template.item-clicked', item);
                                }.bind(this)
                            }
                        },
                        languageSelector = {
                            id: 'language',
                            iconSize: 'large',
                            group: 'right',
                            position: 10,
                            type: 'select',
                            title: '',
                            hidden: true,
                            class: 'highlight-white',
                            itemsOption: {
                                url: '/admin/content/languages/' + this.options.webspace,
                                titleAttribute: 'name',
                                idAttribute: 'localization',
                                translate: false,
                                callback: function(item) {
                                    this.sandbox.emit('sulu.dropdown.languages.item-clicked', item);
                                }.bind(this)
                            }
                        };
                    if (!this.options.id) {
                        return [template, state, languageSelector];
                    } else {
                        return [template, state, languageSelector];
                    }
                }.bind(this)
            };
        }
    };
});
