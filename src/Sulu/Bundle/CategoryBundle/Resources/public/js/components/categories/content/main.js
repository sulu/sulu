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

    var LOCALE_CHANGED = 'sulu.category.locale-changed';

    return {

        header: function () {
            return {
                tabs: {
                    url: '/admin/category/navigation/category'
                },
                toolbar: {
                    template: 'default',
                    parentTemplate: [
                        {
                            id: 'locale',
                            iconSize: 'large',
                            group: 'right',
                            position: 10,
                            type: 'select',
                            title: '',
                            class: 'highlight-white',
                            hidden: true,
                            itemsOption: {
                                callback: function (locale) {
                                    this.sandbox.emit(LOCALE_CHANGED, locale.id);
                                }.bind(this)
                            },
                            items: [
                                {id: 'en', title: 'en'},
                                {id: 'de', title: 'de'}
                            ]
                        }
                    ],
                    languageChanger: {}
                }
            };
        }

    };
});
