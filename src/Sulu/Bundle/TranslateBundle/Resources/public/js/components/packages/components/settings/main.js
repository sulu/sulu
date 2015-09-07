/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    // FIXME: anonymous function for private vars
    return (function() {
        var form = '#package-form',
            catalogueItem, currentType = '', currentState = '';

        return {

            layout: {},

            templates: ['/admin/translate/template/package/form'],

            initialize: function() {
                this.saved = true;
                currentType = '';
                currentState = '';
                this.formId='#package-form';
                this.form = false;

                this.render();
                this.setHeaderBar(true);
                this.listenForChange();
            },

            render: function() {

                this.$el.html(this.renderTemplate('/admin/translate/template/package/form'));

                catalogueItem = this.$el.find('#catalogues .catalogue-item:first');
                catalogueItem.remove();

                this.initData();

                this.sandbox.form.create(form);
                this.form = true;

                this.bindDomEvents();
                this.bindCustomEvents();
            },

            bindDomEvents: function() {
                this.sandbox.dom.on('#catalogue-add', 'click', this.addCatalogue.bind(this));
                this.sandbox.dom.on('#catalogues', 'click', this.removeCatalogue.bind(this), '.catalogue-remove');

                this.sandbox.dom.keypress(this.formId, function(event) {
                    if (event.which === 13) {
                        event.preventDefault();
                        this.submit();
                    }
                }.bind(this));
            },

            bindCustomEvents: function() {
                // delete contact
                this.sandbox.on('sulu.header.toolbar.delete', function() {
                    this.sandbox.emit('sulu.translate.package.delete', this.options.data.id);
                }, this);

                // contact saved
                this.sandbox.on('sulu.translate.package.saved', function(id, data) {
                    this.options.data = data;
                    this.setHeaderBar(true);
                }, this);

                // contact saved
                this.sandbox.on('sulu.header.toolbar.save', function() {
                    this.submit();
                }, this);

                this.sandbox.on('sulu.header.back', function() {
                    this.sandbox.emit('sulu.translate.package.list');
                }, this);
            },

            initData: function() {
                this.fillFields(this.options.data.catalogues, 1, {
                    id: null,
                    isDefault: true,
                    locale: ''
                });

                this.initCatalogues();

                this.sandbox.dom.val('.name-value', !!this.options.data.name ? this.options.data.name : '');
            },

            initCatalogues: function() {
                this.sandbox.dom.remove('#catalogues .catalogue-item');
                this.sandbox.dom.each(this.options.data.catalogues, function(key, value) {
                    var $item = catalogueItem.clone(),
                        $isDefault = this.sandbox.dom.find('.is-default-value', $item),
                        $locale = this.sandbox.dom.find('.locale-value', $item);

                    this.sandbox.dom.data($item, 'id', value.id);
                    this.sandbox.dom.prop($isDefault, 'checked', !!value.isDefault ? value.isDefault : false);
                    this.sandbox.dom.val($locale, !!value.locale ? value.locale : '');

                    this.sandbox.dom.append('#catalogues', $item);

                    if (!!this.form) {
                        this.sandbox.form.addField(form, $isDefault);
                        this.sandbox.form.addField(form, $locale);
                    }
                }.bind(this));
            },

            fillFields: function(field, minAmount, value) {
                while (field.length < minAmount) {
                    field.push(value);
                }
            },

            submit: function() {
                if (this.sandbox.form.validate(form)) {
                    var data = {},
                        $items = this.sandbox.dom.find('#catalogues .catalogue-item'),
                        item, id,
                        $item, $locale, $isDefault;


                    if (!!this.options.data.id) {
                        data.id = this.options.data.id;
                    }

                    data.name = this.sandbox.dom.val('.name-value');
                    data.catalogues = [];

                    this.sandbox.dom.each($items, function(key, value) {
                        id = this.sandbox.dom.data(value, 'id');
                        $item = this.sandbox.dom.$(value);
                        $locale = this.sandbox.dom.find('.locale-value', $item);
                        $isDefault = this.sandbox.dom.find('.is-default-value', $item);

                        item = {
                            id: id,
                            isDefault: this.sandbox.dom.prop($isDefault, 'checked'),
                            locale: this.sandbox.dom.val($locale)
                        };

                        data.catalogues.push(item);
                    }.bind(this));

                    this.sandbox.emit('sulu.translate.package.save', data);
                }
            },

            addCatalogue: function() {
                var $item = catalogueItem.clone();
                this.sandbox.dom.append('#catalogues', $item);

                this.sandbox.form.addField(form, $item.find('.is-default-value'));
                this.sandbox.form.addField(form, $item.find('.locale-value'));
            },

            removeCatalogue: function(event) {
                var $item = $(event.target).parent().parent().parent();

                this.sandbox.form.removeField(form, $item.find('.is-default-value'));
                this.sandbox.form.removeField(form, $item.find('.locale-value'));

                this.setHeaderBar(false);

                $item.remove();
            },

            // @var Bool saved - defines if saved state should be shown
            setHeaderBar: function(saved) {
                if (saved !== this.saved) {
                    var type = (!!this.options.data && !!this.options.data.id) ? 'edit' : 'add';
                    this.sandbox.emit('sulu.header.toolbar.state.change', type, saved);
                }
                this.saved = saved;
            },

            listenForChange: function() {
                this.sandbox.dom.on(form, 'change', function() {
                    this.setHeaderBar(false);
                }.bind(this), "select, input");

                this.sandbox.dom.on(form, 'keyup', function() {
                    this.setHeaderBar(false);
                }.bind(this), "input");
            }

        };
    })();
});
