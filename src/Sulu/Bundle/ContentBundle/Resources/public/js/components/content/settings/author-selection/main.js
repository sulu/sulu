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
            formId: 'authored-form',
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
                '   <div class="grid-row form-group">',
                '       <label for="authored"><%= translations.authored %></label>',
                '       <form id="<%= formId %>">',
                '           <div class="grid-col-6">',
                 '              <div data-type="husky-input"',
                 '                  data-aura-component="input@husky"',
                '                  data-mapper-property="authored"',
                '                  data-validation-required="true"',
                '                  data-aura-skin="date" />',
                 '          </div>', 
                '           <div class="grid-col-6">',
                 '              <div data-type="husky-input"',
                '                  data-value="<%= authoredTime %>"',
                '                  data-aura-component="input@husky"',
                '                  data-mapper-property="authoredTime"',
                '                  data-validation-required="true"',
                 '                  data-aura-skin="time" />',
                 '          </div>',
                 '       </form>',
                 '   </div>',
                '   <div class="grid-row search-row">',
                '       <div class="grid-col-8">',
                '<% if (nullableAuthor) { %>',
                '           <label for="no-author" class="m-top-5">',
                '               <div class="custom-radio">',
                '                   <input id="no-author" type="radio" name="datagrid-author-selection" class="form-element">',
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

            var formSelector = '#' + this.options.formId;
            var authored = this.options.data.authored ? this.options.data.authored : null;

            var authoredTime = authored
                ? Globalize.format(app.sandbox.date.parse(authored), Globalize.culture().calendar.patterns.t)
                : null;

            this.html($(this.templates.skeleton({
                translations: this.translations,
                nullableAuthor: this.options.nullableAuthor,
                authoredTime: authoredTime,
                formId: this.options.formId
            })));

            this.form = this.sandbox.form.create(formSelector);

            this.initialized.then(function() {
                this.sandbox.form.setData(formSelector, {
                    authored: authored,
                    authoredTime: authoredTime
                }).then(function() {
                    this.sandbox.start(this);
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
                }.bind(this));
            }.bind(this));

            if (this.options.nullableAuthor) {
                this.initializeNullableRadio();
            }
        },

        bindCustomEvents: function() {
            this.sandbox.once('sulu.content.contents.get-author', function() {
                var data = this.form.mapper.getData();

                this.data.authored = Globalize.format(
                    Globalize.parseDate(
                        data.authored + ' ' + (data.authoredTime ? data.authoredTime : '00:00:00'),
                        'yyyy-MM-dd HH:mm:ss'
                    ),
                    "yyyy'-'MM'-'dd'T'HH':'mm':'sszz'00'"
                );

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
