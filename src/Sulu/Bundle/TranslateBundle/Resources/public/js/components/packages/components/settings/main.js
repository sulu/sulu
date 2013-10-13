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

            view: true,

            templates: ['/admin/translate/template/package/form'],

            getTabs: function() {
                // TODO translate
                var navigation = {
                        'title': 'Package',
                        'displayOption': 'content',
                        'header': {
                            'displayOption': 'link',
                            'action': 'settings/translate'
                        },
                        'hasSub': 'true',
                        //TODO id mandatory?
                        'sub': {
                            'items': []
                        }
                    },
                    firstId = this.options.data.catalogues.length > 0 ? this.options.data.catalogues[0].id : null;

                if (!!this.options.data.id) {
                    navigation.sub.items.push({
                        'title': 'Settings',
                        'action': 'settings/translate/edit:' + this.options.data.id + '/settings',
                        'hasSub': false,
                        'type': 'content',
                        'id': 'translate-package-settings-' + this.options.data.id,
                        'selected': true
                    });

                    if (!!firstId) {
                        navigation.sub.items.push({
                            'title': 'Details',
                            'action': 'settings/translate/edit:' + this.options.data.id + '/details',
                            'hasSub': false,
                        'type': 'content',
                        'id': 'translate-package-details-' + this.options.data.id
                    });
                    }
                }

                return navigation;
            },

            initialize: function() {
                currentType = '';
                currentState = '';
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

                this.setTabs();

                this.bindDomEvents();
                this.bindCustomEvents();
            },

            setTabs: function() {
                var tabs = this.getTabs();
                if (!!tabs) {
                    this.sandbox.emit('navigation.item.column.show', {
                        data: tabs
                    });
                }
            },

            bindDomEvents: function() {
                this.sandbox.dom.on('#catalogue-add', 'click', this.addCatalogue.bind(this));
                this.sandbox.dom.on('#catalogues', 'click', this.removeCatalogue.bind(this), '.catalogue-remove');
            },

            bindCustomEvents: function() {
                // delete contact
                this.sandbox.on('husky.button.delete.click', function() {
                    this.sandbox.emit('sulu.translate.package.delete', this.options.data.id);
                }, this);

                // contact saved
                this.sandbox.on('sulu.translate.package.saved', function(id, data) {
                    this.options.data = data;
                    this.setTabs();
                    this.setHeaderBar(true);
                }, this);

                // contact saved
                this.sandbox.on('husky.button.save.click', function() {
                    this.submit();
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
                var changeType, changeState,
                    ending = (!!this.options.data && !!this.options.data.id) ? 'Delete' : '';

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

                if (currentType !== changeType) {
                    this.sandbox.emit('husky.header.button-type', changeType);
                    currentType = changeType;
                }
                if (currentState !== changeState) {
                    this.sandbox.emit('husky.header.button-state', changeState);
                    currentState = changeState;
                }
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
