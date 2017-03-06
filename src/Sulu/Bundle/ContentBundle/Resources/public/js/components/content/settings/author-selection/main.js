/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'text!/admin/api/contacts/fields'], function($, fieldsResponse) {

    'use strict';

    var defaults = {
        options: {
            data: {
                authored: null,
                author: null
            },
            selectCallback: function(data) {
            },
            matchings: JSON.parse(fieldsResponse)
        },
        translations: {
            authored: 'sulu.content.form.settings.authored'
        },
        templates: {
            skeleton: [
                '<div class="grid">',
                '   <div class="grid-row form-group">',
                '       <label for="authored"><%= translations.authored %></label>',
                '       <div class="authored-component"',
                '            data-aura-component="input@husky"',
                '            data-aura-skin="date"',
                '            data-value="<%= authored %>" />',
                '       </div>',
                '   </div>',
                '   <div class="grid-row search-row">',
                '       <div class="grid-col-8"/>',
                '       <div class="grid-col-4 author-selection-search"/>',
                '   </div>',
                '   <div class="grid-row">',
                '       <div class="grid-col-12 author-selection-list" style="max-height: 500px; overflow: scroll;"/>',
                '   </div>',
                '</div>'
            ].join('')
        }
    };

    return {

        defaults: defaults,

        initialize: function() {
            this.data = this.options.data;

            this.bindCustomEvents();
            this.html($(this.templates.skeleton({
                translations: this.translations,
                authored: this.options.data.authored ? this.options.data.authored : null
            })));

            this.sandbox.start([
                {
                    name: 'search@husky',
                    options: {
                        el: '.author-selection-search',
                        appearance: 'white small',
                        instanceName: 'author-selection-search'
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: '.author-selection-list',
                        instanceName: 'author-selection',
                        url: '/admin/api/contacts?flat=true',
                        resultKey: 'contacts',
                        sortable: false,
                        selectedCounter: false,
                        searchInstanceName: 'author-selection-search',
                        searchFields: ['fullName', 'mainEmail'],
                        preselected: !!this.options.data.author ? [this.options.data.author] : [],
                        paginationOptions: {
                            dropdown: {
                                limit: 20
                            }
                        },
                        viewOptions: {
                            table: {
                                selectItem: {
                                    type: 'radio'
                                }
                            }
                        },
                        matchings: this.options.matchings
                    }
                }
            ]);
        },

        bindCustomEvents: function() {
            this.sandbox.once('sulu.content.contents.get-author', function() {
                this.data.authored = this.$el.find('.authored-component').data('value');

                this.sandbox.emit('husky.datagrid.author-selection.items.get-selected', function(ids, items) {
                    if (items.length > 0) {
                        this.data.author = ids[0];
                        this.data.authorItem = items[0];
                    }

                    this.options.selectCallback(this.data);
                }.bind(this), true);
            }.bind(this));
        }
    };
});
