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
    var catalogueFormId = '#catalogue-form',
        id = 'new';

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

            RelationalStore.reset();

            this.initializeHeader();
            this.render();
        },

        render: function() {

            var packageModel = null,
                catalogues = [],
                template;

            this.cataloguesToDelete = [];


            if (!!this.options.data) {
                packageModel = this.options.data;
                catalogues = this.options.data.catalogues;
            }

            template = this.sandbox.template.parse(formTemplate, packageModel);
            this.sandbox.dom.html(this.options.el, template);


            this.initDataGrid(catalogues);

            // TODO - does not work for datagrid - is not rendered at this point
            this.sandbox.form.create(catalogueFormId);

            this.initFormEvents();

            if (!!this.options.data && !!this.options.data.id) {
                id = this.options.data.id;
            }

            this.sandbox.emit('navigation.item.column.show', {
                data: this.getTabs(id)
            }, this);
        },

        initDataGrid: function(catalogues) {
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
                            '   <input class="form-element inputLocale" type="text" data-min-length="3" value="<% if (!!locale) { %><%= locale %><% } %>"/>',
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
                    id = this.sandbox.dom.attr($element.parent().parent()[0], 'data-id'); // FIXME

                if (!!id) {
                    this.getCatalogueById(id);
                }
            }, this);
        },

        initFormEvents: function() {

            this.sandbox.dom.on('#catalogue-form', 'click', function() {
                this.sandbox.emit('husky.datagrid.row.add', { id: '', isDefault: false, locale: '', translations: [] });

                // TODO add new fields to validation
                // this.sandbox.form.addField(selectorForm, selectorField);

            }.bind(this), '#add-catalogue-row');

        },

        initializeHeader: function() {

            this.sandbox.emit('husky.header.button-type', 'saveDelete');

            this.sandbox.on('husky.button.save.click', function() {
                this.submit();
            }, this);

            this.sandbox.on('husky.button.delete.click', function() {
                this.sandbox.emit('sulu.translate.packages.delete', [id], true);
            }, this);
        },

        getCatalogueById: function(id) {

            var catalogues = this.options.data.catalogues;

            this.sandbox.util.each(this.options.data.catalogues, function(index) {

                if (parseInt(catalogues[index].id, 10) === parseInt(id, 10)) {

                    this.cataloguesToDelete.push(catalogues[index].id);
                    catalogues.splice(index, 1);
                    return;
                }

            }.bind(this));
        },

        submit: function() {

            // TODO validation
            if (this.sandbox.form.validate(catalogueFormId)) {

                if (!this.options.data) {
                    this.options.data = {};
                    this.options.data.id = null;
                }

                this.options.data.name = this.sandbox.dom.val('#name');
                this.options.data.catalogues = this.getChangedCatalogues();

                this.sandbox.emit('sulu.translate.package.save', this.options.data, this.cataloguesToDelete);
            }
        },

        getChangedCatalogues: function() {

            var rows = this.sandbox.dom.find('tbody > tr', '#catalogues'),
                changedCatalogues = [];

            this.sandbox.util.each(rows, function(index) {

                var id = this.sandbox.dom.attr(rows[index], 'data-id'),
                    checkBox = this.sandbox.dom.find('input.isDefault', rows[index]),
                    isDefault = this.sandbox.dom.is(checkBox, ':checked'),
                    input = this.sandbox.dom.find('input.inputLocale', rows[index]),
                    locale = this.sandbox.dom.val(input),
                    catalogue = null;

                if (!!locale && locale.length > 0) {

                    catalogue = {
                        id: id,
                        isDefault: isDefault,
                        locale: locale
                    };

                    changedCatalogues.push(catalogue);
                }

            }.bind(this));

            return changedCatalogues;
        }

    };
});
