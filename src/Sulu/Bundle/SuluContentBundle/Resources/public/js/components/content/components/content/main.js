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

        header: function() {
            var noBack = (this.options.id === 'index') ? true : false;

            return {
                noBack: noBack,
                tabs: {
                    url: '/admin/content/navigation/content'
                },

                toolbar: {
                    parentTemplate: 'default',

                    languageChanger: {
                        url: '/admin/content/languages/' + this.options.webspace,
                        preSelected: this.options.language
                    },

                    template: [
                        {
                            'id': 'state',
                            'group': 'left',
                            'position': 100,
                            'type': 'select'
                        },
                        {
                            id: 'template',
                            icon: 'pencil',
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
                        }
                    ]
                }
            };
        }
    };
});
