/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'text!/translate/template/package/form'
], function(formTemplate) {

    'use strict';

    return {

        name: 'Sulu Translate Package Form',
        view: true,
        catalogues: [],

        initialize: function() {
            this.sandbox.off(); // FIXME automate this call
            this.initializeHeader();
            this.render();
        },

        render: function() {
//            Backbone.Relational.store.reset(); //FIXME really necessary?

            var template = this.sandbox.template.parse(formTemplate);
            this.$el.html(template); // FIXME: jquery

            if(!!this.options.data){
                this.mapData();
                this.catalogues = data.catalogues;
            }

            this.catalogues.push({id: 1, isDefault: false, locale: 'Deutsch'});
            this.catalogues.push({id: 2, isDefault: true, locale: 'Englisch'});

            this.sandbox.start([
                {name: 'datagrid@husky', options: {
                    el: this.$el.find('#catalogues'), // FIXME: jquery
                    data: {
                        items: this.catalogues
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
                            '<input type="radio" class="custom-radio <% if (!!isDefault) { %><%= \'is-selected\" checked=\"checked\' %><% } %>" name="catalogue-radio">',
                            '<span class="custom-radio-icon"></span>',
                                '</label>',
                                '</td>',
                                '<td>',
                                '<input class="form-element input-locale" type="text" data-trigger="focusout" data-minlength="3" value="<% if (!!locale) { %><%= locale %><% } %>"/>',
                                '</td>',
                                '<td class="remove-row">',
                                '<span class="icon-remove"></span>',
                                '</td>',
                                '</tr>'
                        ].join('')
                    }

                }}
            ]);


            this.initFormEvents();
        },

        mapData: function() {
            // TODO map data to form
        },

        initFormEvents: function(){

            this.$el.on('click','#add-catalogue-row',function(){ // FIXME: jquery
                console.log("Add Row");
            });

        },

        initializeHeader: function() {

            this.sandbox.emit('husky.header.button-type', 'saveDelete');

            this.sandbox.on('husky.button.save.click', function(event){
                console.log("save");
//                this.sandbox.emit('sulu.translate.package.save');

            }, this);

            this.sandbox.on('husky.button.delete.click', function(event){
                console.log("delete");
                //this.sandbox.emit('sulu.translate.package.delete');

            }, this);
        }


    };
});
