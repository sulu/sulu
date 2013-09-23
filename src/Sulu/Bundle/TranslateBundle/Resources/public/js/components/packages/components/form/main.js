/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'text!/translate/template/package/form',
    'mvc/relationalstore'
], function(formTemplate, RelationalStore) {

    'use strict';
    var catalogueFormId = '#catalogue-form';

    return {

        name: 'Sulu Translate Package Form',
        view: true,

        // Navigation
        getTabs: function(id) {
            //TODO Simplify this task for bundle developer?

            // TODO translate
            var navigation = {
                'title': 'Package',
                'header': {
                    'title': 'Package'
                },
                'hasSub': 'true',
                //TODO id mandatory?
                'sub': {
                    'items': []
                }
            };

            if (!!id) {
                navigation.sub.items.push({
                    'title': 'Details',
                    'action': 'settings/translate/edit:' + id + '/details',
                    'hasSub': false,
                    'type': 'content',
                    'id': 'translate-package-details-' + id
                });
            }

            navigation.sub.items.push({
                'title': 'Settings',
                'action': 'settings/translate/edit:' + id + '/settings',
                'hasSub': false,
                'type': 'content',
                'id': 'translate-package-settings-' + id
            });

            return navigation;
        },


        initialize: function() {
            this.sandbox.off(); // FIXME automate this call
            this.initializeHeader();
            this.render();
        },

        render: function() {
            RelationalStore.reset();
//            this.$el.removeData('Husky.Ui.DataGrid'); // FIXME: jquery

            var packageModel = null,
                catalogues = new Array();
                this.cataloguesToDelete = new Array();

            if (!!this.options.data) {
                packageModel = this.options.data
                catalogues = this.options.data.catalogues;
            }

            var template = this.sandbox.template.parse(formTemplate, packageModel);
            this.sandbox.dom.html(this.options.el, template);


            this.initDataGrid(catalogues);

            // TODO - does not work for datagrid - is not rendered at this point
            this.sandbox.validation.create(catalogueFormId);
            this.initFormEvents();

            this.sandbox.emit('navigation.item.column.show', {
                data: this.getTabs(this.options.data.id)
            },this);
        },

        initDataGrid: function(catalogues){
            this.sandbox.start([
                {name: 'datagrid@husky', options: {
                    el: this.sandbox.dom.find('#catalogues', catalogueFormId),
                    data: {
                        items: catalogues
                    },
                    pagination: false,
                    selectItem: {
                        type: 'radio'
                    },
                    removeRow: true,
                    tableHead: [
                        {content: 'Language'},
                        {content: ''}
                    ],
                    excludeFields: [
                        'id',
                        'isDefault'
                    ],
                    template: {
                        row: [
                            '<tr <% if (!!id) { %> data-id="<%= id %>"<% } %> >',
                                '<td>',
                                    '<label>',
                                        '<input type="radio" class="custom-radio isDefault <% if (!!isDefault) { %><%= \'is-selected\" checked=\"checked\' %><% } %>" name="catalogue-radio">',
                                        '<span class="custom-radio-icon"></span>',
                                    '</label>',
                                '</td>',
                                '<td>',
                                '   <input class="form-element inputLocale" type="text" data-validate="true" data-minlength="3" value="<% if (!!locale) { %><%= locale %><% } %>"/>',
                                '</td>',
                                '<td class="remove-row">',
                                    '<span class="icon-remove pointer"></span>',
                                '</td>',
                            '</tr>'
                        ].join('')
                    }

                }}
            ]);

            this.sandbox.on('husky.datagrid.row.removed', function(event) {
                var $element = this.sandbox.dom.$(event.currentTarget),
                    id = this.sandbox.dom.attr($element.parent().parent()[0],'data-id'); // FIXME

                if(!!id) {
                    this.getCatalogueById(id);
                }
            }, this);
        },

        initFormEvents: function() {

            this.$el.on('click', '#add-catalogue-row', function(event) { // FIXME: jquery
                this.sandbox.emit('husky.datagrid.row.add', { id: '', isDefault: false, locale: '', translations: [] });
            }.bind(this));

        },

        initializeHeader: function() {

            this.sandbox.emit('husky.header.button-type', 'saveDelete');

            this.sandbox.on('husky.button.save.click', function(event) {
                this.submit();
            }, this);

            this.sandbox.on('husky.button.delete.click', function(event) {
               this.sandbox.emit('sulu.translate.packages.delete',[this.options.data.id], true);
            }, this);
        },

        getCatalogueById: function(id) {

            var catalogues = this.options.data.catalogues;

            this.sandbox.util.each(this.options.data.catalogues, function(index) {

                if (parseInt(catalogues[index].id) === parseInt(id)) {

                    this.cataloguesToDelete.push(catalogues[index].id);
                    catalogues.splice(index,1);
                    return;
                }

            }.bind(this));
        },

        submit: function() {

            // TODO validation
            if(this.sandbox.validation.validate(catalogueFormId)) {

                if(!this.options.data) {
                    this.options.data = {};
                    this.options.data.id;
                }

                this.options.data.name = this.sandbox.dom.val('#name');
                this.options.data.catalogues = this.getChangedCatalogues();

                this.sandbox.emit('sulu.translate.package.save', this.options.data, this.cataloguesToDelete);
            }
        },

        getChangedCatalogues: function() {

            var rows = this.sandbox.dom.find('tbody > tr', '#catalogues'),
                changedCatalogues = new Array();

            this.sandbox.util.each(rows, function(index) {

                var id = this.sandbox.dom.attr(rows[index], 'data-id');
                var checkBox = this.sandbox.dom.find('input.isDefault', rows[index]),
                    isDefault = this.sandbox.dom.is(checkBox, ':checked'),
                    input = this.sandbox.dom.find('input.inputLocale', rows[index]),
                    locale = this.sandbox.dom.val(input);

                if(!!locale && locale.length > 0) {

                    var catalogue = {
                            id: id,
                            isDefault: isDefault,
                            locale: locale
                        };

                    console.log(catalogue, "pushed catalogue");
                    changedCatalogues.push(catalogue);
                }

            }.bind(this));

            return changedCatalogues;
        }

    };
});
