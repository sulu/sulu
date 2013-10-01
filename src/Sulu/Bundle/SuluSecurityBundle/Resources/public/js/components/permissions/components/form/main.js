/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!/security/template/permission/form'], function(Template) {

    'use strict';

    return {

        name: 'Sulu Security Permissions Form',

        view: true,

        initialize: function() {

            this.formId = '#permissions-form';

            if(!!this.options.data) {
                this.user = this.options.data.user;
                this.contact = this.options.data.contact;
                this.roles = this.options.data.roles;
            } else {
                // TODO error message
                this.sandbox.logger.log("no data given");
            }

            this.render();
            this.initializePasswordFields();
            this.initializeRoles();

            this.bindDOMEvents();
            this.bindCustomEvents();

            this.initializeHeaderbar();
        },

        // Headerbar

        initializeHeaderbar: function(){
            this.currentType = '';
            this.currentState = '';

            this.setHeaderBar(true);
            this.listenForChange();
        },

        setHeaderBar: function(saved) {

            var changeType,
                changeState,
                ending = (!!this.contact.id) ? 'Delete' : '';

            changeType = 'save' + ending;

            if (saved) {
                if (ending === '') {
                    changeState = 'hide';
                } else {
                    changeState = 'standard';
                }
            } else {
                changeState = 'dirty';
            }

            if (this.currentType !== changeType) {
                this.sandbox.emit('husky.header.button-type', changeType);
                this.currentType = changeType;
            }
            if (this.currentState !== changeState) {
                this.sandbox.emit('husky.header.button-state', changeState);
                this.currentState = changeState;
            }

        },

        listenForChange: function() {

            this.sandbox.dom.on(this.formId, 'change', function() {
                this.setHeaderBar(false);
            }.bind(this), "select, input");
            this.sandbox.dom.on(this.formId, 'keyup', function() {
                this.setHeaderBar(false);
            }.bind(this), "input");

            //TODO ddms
        },



        

        render: function() {
            this.sandbox.dom.html(this.$el, this.sandbox.template.parse(Template, {data: this.options.data}));
        },

        initializePasswordFields: function() {
            this.sandbox.start([
                {
                    name: 'password-fields@husky',
                    options: {
                        instanceName: "instance1",
                        el: '#password-component'
                    }
                }
            ]);
        },

        initializeRoles: function() {

            var $permissionsContainer = this.sandbox.dom.$('#permissions-grid'),
                $table = this.sandbox.dom.createElement('<table/>', {class: 'table'}),
                $tableHeader = this.prepareTableHeader(),
                //$tableRows = this.prepareTableContent(),
                $tmp;

            //this.sandbox.dom.empty($permissionsContainer);
            // TODO clear div before - add empty() to jquery extension when everything merged


            $tmp = this.sandbox.dom.append($table, $tableHeader);

            // TODO test - multiple permissions?
            //$tmp = this.sandbox.dom.append($tmp, $tableRows);
            this.sandbox.dom.html($permissionsContainer,$tmp);

            // TODO start ddms
        },

        prepareTableHeader: function() {
            return this.template.tableHead('Title', 'Languages', 'Permissions');
        },

        prepareTableContent: function() {
            var $tableBody = this.sandbox.dom.createElement('<tbody/>'),
                tableContent = [];

            this.sandbox.util.each(this.roles, function(index, value){
                tableContent.push(this.template.tableRow(value.id, value.name, value.permissions));
            }.bind(this));

            return this.sandbox.dom.append($tableBody, tableContent.join(''));

        },

        bindDOMEvents: function() {

        },

        bindCustomEvents: function() {
            // delete contact
            this.sandbox.on('husky.button.delete.click', function() {
                //this.sandbox.emit('sulu.user.permissions.delete', this.contact.id);
            }, this);

            // contact saved
            this.sandbox.on('sulu.user.permissions.save', function(id, data) {
                if (!this.options.data.id) {
                    this.options.data = data;
                }
                this.setHeaderBar(true);
            }, this);

            // contact saved
            this.sandbox.on('husky.button.save.click', function() {
                this.save();
            }, this);
        },

        save: function() {
            // FIXME  Use datamapper instead
            var data = {
//                id: this.sandbox.dom.val('#id'),
//                name: this.sandbox.dom.val('#name'),
//                system: this.sandbox.dom.val('#system'),
//                permissions: permissionData
            };

            this.sandbox.emit('sulu.user.permissions.save', data);
        },

        template: {
            tableHead: function(thLabel1, thLabel2, thLabel3) {
                return [
                    '<thead>',
                        '<tr>' +
                            '<th><input id=\'selectAll\' type=\'checkbox\'/></th>',
                            '<th>',thLabel1,'</th>',
                            '<th>',thLabel2,'</th>',
                            '<th colspan=\'7\'>',thLabel3,'</th>',
                        '</tr>',
                    '</thead>'
                ].join('');

            },
            tableRow: function(id, title, permissions) {

                this.sandbox.util.each(permissions, function(index,value){
                    // TODO
                });

                var $row = [
                    '<tr data-id=\"',id,'\">',
                        '<td><input type="checkbox" /></td>',
                        '<td>',title,'</td>',
                        '<td class=\"languageSelector\"></td>',
                        '<td><span class="icon-add"></span></td>',
                        '<td><span class="icon-edit"></span></td>',
                        '<td><span class="icon-search is-active"></span></td>',
                        '<td><span class="icon-remove"></span></td>',
                        '<td><span class="icon-settings"></span></td>',
                        '<td><span class="icon-circle-ok is-active"></span></td>',
                        '<td><span class="icon-building"></span></td>',
                    '</tr>'
                ].join('');


                return $row;
            }
        }


    };
});



