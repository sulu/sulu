/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function(Config) {

    'use strict';

    var constants = {
            filterUrl: 'api/filters?flat=true&context='
        },

        bindCustomEvents = function() {
            this.sandbox.on('sulu.header.toolbar.extend', extendToolbar.bind(this));
        },

        extendToolbar = function(context, toolbar) {
            if(!!context){
                var url = constants.filterUrl+context,
                    filterDropDown = {
                        id: 'filters',
                        icon: 'filter',
                        title: this.sandbox.translate('resource.filter'),
                        group: 2,
                        position: 1,
                        class: 'highlight-white',
                        type: 'select',
                        itemsOption: {
                            url: url,
                            resultKey: 'filters',
                            titleAttribute: 'name',
                            idAttribute: 'id',
                            translate: false,
                            languageNamespace: 'toolbar.',
                            markable: true,
                            callback: function(item) {
                                console.log(item, 'This is a test callback');
                            }
                        }
                    };

                toolbar.push(filterDropDown);
            }
        };

    return {

        initialize: function(sandbox) {
            this.sandbox = sandbox;
            bindCustomEvents.call(this);
        }
    };
});
