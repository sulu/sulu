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

    return {

        name: 'Sulu Translate Package Form',
        view: true,


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

            if (!!this.options.data) {
                packageModel = this.options.data
                catalogues = this.options.data.catalogues;
                this.cataloguesToDelete = [];
            }

            var template = this.sandbox.template.parse(formTemplate, packageModel);
            this.sandbox.dom.html(this.options.el, template);

            this.sandbox.start([
                {name: 'datagrid@husky', options: {
                    el: this.sandbox.dom.find('#catalogues', '#catalogue-form'),
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
                            '<input id="isDefault<% if (!!id) { %><%= id %><% } %>" type="radio" class="custom-radio <% if (!!isDefault) { %><%= \'is-selected\" checked=\"checked\' %><% } %>" name="catalogue-radio">',
                            '<span class="custom-radio-icon"></span>',
                            '</label>',
                            '</td>',
                            '<td>',
                            '<input class="form-element" id="inputLocale<% if (!!id) { %><%= id %><% } %>" type="text" data-trigger="focusout" data-minlength="3" value="<% if (!!locale) { %><%= locale %><% } %>"/>',
                            '</td>',
                            '<td class="remove-row">',
                            '<span class="icon-remove pointer"></span>',
                            '</td>',
                            '</tr>'
                        ].join('')
                    }

                }}
            ]);

            this.initFormEvents();
        },

        initFormEvents: function() {

            this.$el.on('click', '#add-catalogue-row', function(event) { // FIXME: jquery
                this.sandbox.emit('husky.datagrid.row.add', { id: '', isDefault: false, locale: '', translations: [] });
            }.bind(this));

            this.sandbox.on('husky.datagrid.row.removed', function(event) {
                var $element = this.sandbox.dom.$(event.currentTarget),
                    id = this.sandbox.dom.attr($element.parent().parent()[0],'data-id'); // FIXME

                if(!!id) {
                    this.getCatalogueById(id);
                }
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

        initializeHeader: function() {

            this.sandbox.emit('husky.header.button-type', 'saveDelete');

            this.sandbox.on('husky.button.save.click', function(event) {
                this.submit();
            }, this);

            this.sandbox.on('husky.button.delete.click', function(event) {
                console.log("delete");
                //this.sandbox.emit('sulu.translate.package.delete');
            }, this);
        },

        submit: function() {

            // TODO validation

            var changedCatalogues = this.getChangedCatalogues();

            this.options.data.name = this.sandbox.dom.val('#name');
            this.options.data.catalogues = changedCatalogues;
            console.log(this.options.data, "data to save");

            this.sandbox.emit('sulu.translate.package.save', this.options.data, this.cataloguesToDelete);
        },

        getChangedCatalogues: function() {

            var rows = this.sandbox.dom.find('tbody > tr', '#catalogues'),
                changedCatalogues = new Array();

            this.sandbox.util.each(rows, function(index) {

                // TODO new elements - no id!

                var id = this.sandbox.dom.attr(rows[index], 'data-id'),
                    isDefault = this.sandbox.dom.is('#isDefault' + id, ':checked'),
                    locale = this.sandbox.dom.val('#inputLocale' + id),
                    catalogue = {
                        id: id,
                        isDefault: isDefault,
                        locale: locale
                    };

                changedCatalogues.push(catalogue);

            }.bind(this));

            return changedCatalogues;
        }

    };
});
