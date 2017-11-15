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

    var constants = {
            instanceName: 'author-selection'
        },
        defaults = {
        options: {
            nullableAuthor: false,
            data: {
                authored: null,
                authoredTime: null,
                author: null
            },
            selectCallback: function(data) {
            },
            matchings: JSON.parse(fieldsResponse)
        },
        translations: {
            authored: 'sulu.content.form.settings.authored',
            noAuthor: 'sulu.content.form.settings.no-author'
        },
        templates: {
            skeleton: [
                '   <div class="grid-row">', 
                 '       <label for="authored"><%= translations.authored %></label>',
                '   </div>',
                '   <div class="grid-row form-group">', 
                '       <div class="grid-col-6">',
                 '           <div class="authored-component"',
                 '              data-aura-component="input@husky"', 
                '               data-aura-skin="date"', 
                '               data-value="<%= authored %>" />',
                 '       </div>', 
                '       <div class="grid-col-6">',
                 '           <div class="authored-time-component"', 
                '              data-aura-component="input@husky"',
                 '               data-aura-skin="time"',
                 '               data-value="<%= authoredTime %>" />',
                 '       </div>',
                 '   </div>',
                '   <div class="grid-row search-row">',
                '       <div class="grid-col-8">',
                '<% if (nullableAuthor) { %>',
                '           <label for="no-author" class="m-top-5">',
                '               <div class="custom-radio">',
                '                   <input id="no-author" type="radio" class="form-element">',
                '                   <span class="icon"></span>',
                '               </div>',
                '               <%= translations.noAuthor %>',
                '            </label>',
                '<% } %>',
                '       </div>',
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
                nullableAuthor: this.options.nullableAuthor,
                authored: this.options.data.authored ? this.options.data.authored : null,
                authoredTime: this.options.data.authored
                    ? Globalize.format(new Date(this.options.data.authored), Globalize.culture().calendar.patterns.t)
                    : null
            })));

            this.sandbox.start([
                {
                    name: 'search@husky',
                    options: {
                        el: '.author-selection-search',
                        appearance: 'white small',
                        instanceName: constants.instanceName + '-search'
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: '.author-selection-list',
                        instanceName: constants.instanceName,
                        url: '/admin/api/contacts?flat=true',
                        resultKey: 'contacts',
                        sortable: false,
                        selectedCounter: false,
                        searchInstanceName: constants.instanceName + '-search',
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

            if (this.options.nullableAuthor) {
                this.initializeNullableRadio();
            }
        },

        bindCustomEvents: function() {
            this.sandbox.once('sulu.content.contents.get-author', function() {
                this.data.authored = document.test.$el.find('.authored-component').data('value')+' '+ document.test.$el.find('.authored-time-component').data('value');
                this.sandbox.emit('husky.datagrid.' + constants.instanceName + '.items.get-selected', function(ids, items) {
                    if (items.length > 0) {
                        this.data.author = ids[0];
                        this.data.authorItem = items[0];
                    }

                    this.options.selectCallback(this.data);
                }.bind(this), true);
            }.bind(this));
        },

        initializeNullableRadio: function() {
            var $radio = this.$el.find('#no-author'),
                selectedId = this.data.author;

            if (!this.data.author) {
                $radio.prop('checked', true);
            }

            this.sandbox.on('husky.datagrid.' + constants.instanceName + '.item.select', function(id) {
                selectedId = id;
                $radio.prop('checked', false);
            }.bind(this));

            $radio.on('click', function() {
                if (!$radio.prop('checked')) {
                    return;
                }

                this.sandbox.emit('husky.datagrid.' + constants.instanceName + '.deselect.item', selectedId);
                this.data.author = null;
                this.data.authorItem = null;
                selectedId = null;
            }.bind(this));
        }
    };
});
