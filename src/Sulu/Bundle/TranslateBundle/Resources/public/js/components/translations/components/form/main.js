/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'text!/translate/template/translation/form',
    'mvc/relationalstore'
], function(formTemplate, RelationalStore) {

    'use strict';
    var codesToDelete = [],
        codesForm = '#codes-form';

    return {

        name: 'Sulu Translate Package Form',
        view: true,

        initialize: function() {
            this.sandbox.off(); // FIXME automate this call
            RelationalStore.reset();

            this.initializeHeader();
            this.render();
        },

        render: function() {


            var template = this.sandbox.template.parse(formTemplate, {
                package: this.options.data.package,
                catalogue: this.options.data.selectedCatalogue,
                translations: this.options.data.translations
            });

            this.sandbox.dom.html(this.options.el, template);

            this.initFormEvents();
            this.initSelectCatalogues();
            this.initVisibilityOptions();

            this.sandbox.form.create(codesForm, {debug: true});
        },

        initSelectCatalogues: function() {

            this.sandbox.start([
                {name: 'select@husky', options: {
                    el: this.sandbox.dom.$('#languageCatalogue'),
                    valueName: 'locale',
                    instanceName: 'catalogues',
                    selected: this.options.data.selectedCatalogue,
                    data: this.options.data.catalogues
                }}
            ]);
        },

        initFormEvents: function() {

            // add row
            this.sandbox.dom.on('.add-code', 'click', function(event) {
                this.addRow(event);
            }.bind(this));

            // remove row
            var $form = this.sandbox.dom.$('#codes-form');
            this.sandbox.dom.on($form, 'click', function(event) {
                this.removeRow(event);
            }.bind(this), '.icon-remove');

            // enable input fields
            this.sandbox.dom.on('.form-element[readonly]', 'click', function(event) {
                this.unlockFormElement(event);
            }.bind(this));

            // automatic resize of textareas
            this.sandbox.dom.on('#codes-form', 'keyup', function(event){

                // TODO test it
                //var TEXTAREA_LINE_HEIGHT = 13,
                var textarea = event.currentTarget,
                    newHeight = textarea.scrollHeight,
                    currentHeight = textarea.clientHeight;

                if (newHeight > currentHeight) {
                    textarea.style.height = newHeight + 5 + 'px';
                }

            }.bind(this), 'textarea');

            // automatic resize of textareas
            this.sandbox.dom.on('#codes-form', 'keyup', function(event){
                this.updateLengthConstraint(event);
            }.bind(this), '.input-length');

            // selected catalogue changed
            this.sandbox.on('select.catalogues.item.changed', function(catalogueId){
                this.sandbox.emit('sulu.translate.catalogue.changed', catalogueId);
            }, this);
        },

        unlockFormElement: function(event) {
            var $element = $(event.currentTarget);
            $($element).prop('readonly', false);

        },

        removeRow: function(event) {
            var $element = this.sandbox.dom.$(event.currentTarget),
                $tr = this.sandbox.dom.parent(this.sandbox.dom.parent($element)),
                $trOptions = this.sandbox.dom.next($tr, '.additional-options'),
                id = this.sandbox.dom.attr($tr, 'data-id');

            this.sandbox.dom.remove($tr);
            this.sandbox.dom.remove($trOptions);

            if(!!id) {

                this.sandbox.util.each(this.options.data.translations, function(key, value) {
                    if(parseInt(value.id,10) === parseInt(id,10)) {
                        codesToDelete.push(value.code.id);
                        return false;
                    }
                });
            }

        },

        updateLengthConstraint: function(event){

            var newMinLength = this.sandbox.dom.val(event.currentTarget),
                $tr = this.sandbox.dom.prev(
                                    this.sandbox.dom.parent(
                                        this.sandbox.dom.parent(
                                            this.sandbox.dom.parent(
                                                this.sandbox.dom.parent(event.currentTarget))))),
                $translationField = this.sandbox.dom.find('.textarea-translation',$tr),
                $translationFieldInfo = this.sandbox.dom.find('.letter-info',$tr);

            this.sandbox.dom.text($translationFieldInfo, "[Max. "+newMinLength+" chars]");
            this.sandbox.form.updateConstraint(codesForm,$translationField, 'maxLength',{maxLength: newMinLength});
        },

        addRow: function(event) {

            var $element = this.sandbox.dom.$(event.currentTarget),
                sectionId = this.sandbox.dom.attr($element, 'data-target-element'),
                $tbody = this.sandbox.dom.find('tbody', '#' + sectionId),
                $lastRow = this.sandbox.dom.find('tr:last', $tbody);

            this.sandbox.dom.append($tbody, this.templates.rowTemplate());

            var $addedRow = this.sandbox.dom.next($lastRow, 'tr'),
                $addedOptionsRow = this.sandbox.dom.next($addedRow, 'tr'),
                $codeField = this.sandbox.dom.find('.input-code',$addedRow),
                $translationField = this.sandbox.dom.find('.textarea-translation',$addedRow),
                $lengthField = this.sandbox.dom.find('.input-length',$addedOptionsRow);

            this.sandbox.form.addField(codesForm,$codeField);
            this.sandbox.form.addField(codesForm,$translationField);
            this.sandbox.form.addField(codesForm,$lengthField);
        },

        initVisibilityOptions: function() {

            this.sandbox.dom.on('.show-options', 'click', function(event) {

                var $element = this.sandbox.dom.$(event.currentTarget),
                    $optionsTr = this.sandbox.dom.next(this.sandbox.dom.parent(this.sandbox.dom.parent($element)), '.additional-options');

                this.sandbox.dom.toggleClass($element, 'icon-arrow-right');
                this.sandbox.dom.toggleClass($element, 'icon-arrow-down');
                this.sandbox.dom.toggleClass($optionsTr, 'hidden');

            }.bind(this));

        },

        templates: {
            rowTemplate: function() {
                return [
                    '<tr>',
                        '<td width="20%">',
                            '<input class="form-element input-code" value="" data-unique="true" data-validation-required="true"/>',
                        '</td>',
                        '<td width="37%">',
                            '<textarea class="form-element vertical textarea-translation" data-validation-max-length="50"></textarea>',
                            '<small class="grey letter-info">[Max. 50 chars]</small>',
                        '</td>',
                        '<td width="37%">',
                            '<p class="grey"></p>',
                        '</td>',
                        '<td width="6%">',
                            '<p class="icon-remove m-left-5 pointer"></p>',
                        '</td>',
                    '</tr>',
                    '<tr class="additional-options">',
                        '<td colspan="4">',
                            '<div class="grid-row">',
                                '<div class="grid-col-3">',
                                    '<span>Length</span>',
                                    '<input class="form-element input-length" value="50"  data-validation-required="true" data-type="decimal" data-validation-min="0" type="number" min="0"/>',
                                '</div>',
                                '<div class="grid-col-2 m-top-35"><input type="checkbox" class="custom-checkbox checkbox-frontend"><span class="custom-checkbox-icon"></span><span class="m-left-5">Frontend</span></div>',
                                '<div class="grid-col-2  m-top-35"><input type="checkbox" class="custom-checkbox checkbox-backend"><span class="custom-checkbox-icon"></span><span class="m-left-5">Backend</span></div>',
                            '</div>',
                        '</td>',
                    '</tr>'].join('');
            }
        },

        initializeHeader: function() {

            this.sandbox.emit('husky.header.button-type', 'saveDelete');

            this.sandbox.on('husky.button.save.click', function() {
                this.submit();
            }, this);

            this.sandbox.on('husky.button.delete.click', function() {
                this.sandbox.emit('sulu.translate.catalogue.delete', [this.options.data.selectedCatalogue.id]);
            }, this);
        },

        submit: function() {

            if(this.sandbox.form.validate(codesForm)) {

            var updatedTranslations = [],
                $rows = this.sandbox.dom.find('table tbody tr', '#codes-form');

            for (var i = 0; i < $rows.length;) {

                var $translation = $rows[i],
                    $options = $rows[i + 1],
                    id = $($rows[i]).data('id'),

                    newCode = this.sandbox.dom.val(this.sandbox.dom.find('.input-code',$translation)),
                    newTranslation = this.sandbox.dom.val(this.sandbox.dom.find('.textarea-translation',$translation)),

                    newLength = this.sandbox.dom.val(this.sandbox.dom.find('.input-length',$options)),
                    newFrontend = this.sandbox.dom.is(this.sandbox.dom.find('.checkbox-frontend',$options),':checked'),
                    newBackend = this.sandbox.dom.is(this.sandbox.dom.find('.checkbox-backend',$options),':checked'),

                    translationModel = null;

                // get data of existing translation and code and compare with version in form
                if (!!id) {

                    this.sandbox.util.each(this.options.data.translations, function(key, value) {
                        if (parseInt(value.id,10) === parseInt(id,10)) {
                            translationModel = value;
                            return false;
                        }
                    });


                    if (!!translationModel) {

                        var currentCode = translationModel.code.code,
                            currentTranslation = translationModel.value,
                            currentLength = translationModel.code.length,
                            currentFrontend = translationModel.code.frontend,
                            currentBackend = translationModel.code.backend;

                        if (newCode !== currentCode ||
                            newTranslation !== currentTranslation ||
                            newLength !== currentLength ||
                            newFrontend !== currentFrontend ||
                            newBackend !== currentBackend) {

                            translationModel.code.code = newCode;
                            translationModel.value = newTranslation;
                            translationModel.code.length = newLength;
                            translationModel.code.frontend = newFrontend;
                            translationModel.code.backend = newBackend;

                            updatedTranslations.push(translationModel);
                        }
                    }

                    // TODO else errormessage

                } else {

                    // new translation and new code
                    if (newCode !== undefined && newCode !== "") {

                        var codeModel = {};
                        codeModel.code = newCode;
                        codeModel.length = newLength;
                        codeModel.frontend = newFrontend;
                        codeModel.backend = newBackend;

                        translationModel = {};
                        translationModel.value = newTranslation;

                        translationModel.code = codeModel;
                        updatedTranslations.push(translationModel);
                    }
                }
                i = i + 2;

            }

            this.sandbox.emit('sulu.translate.translations.save', updatedTranslations, codesToDelete);
            codesToDelete = [];
            }
        }


    };
});
