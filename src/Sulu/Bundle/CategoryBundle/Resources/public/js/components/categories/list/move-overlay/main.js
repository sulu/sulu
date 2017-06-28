/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var defaults = {
        options: {
            locale: null,
            selectCallback: function(id) {
            }
        },
        translations: {
            move: 'sulu.category.move-title',
            moveToRoot: 'sulu.category.move-to-root'
        },
        templates: {
            overlayContent: [
                '<div class="move-categories-container">',
                '   <div class="m-bottom-20">',
                '       <label for="category-move-to-root">',
                '           <div class="custom-radio">',
                '                <input id="category-move-to-root" type="radio" class="form-element"/>',
                '                <span class="icon"></span>',
                '            </div>',
                '            <%= translations.moveToRoot %>',
                '        </label>',
                '    </div>',
                '    <div class="datagrid"/>',
                '</div>'
            ].join('')
        }
    };

    var constants = {
        name: 'move-categories',
        resultKey: 'categories',
        lastClickedCategorySettingsKey: 'categoriesLastClicked'
    };

    return {

        defaults: defaults,

        selectedId: null,

        initialize: function() {
            this.render();

            this.bindCustomEvents();
            this.bindDomEvents();

            this.startOverlay();
        },

        render: function() {
            this.$overlayContent = $(this.templates.overlayContent({translations: this.translations}));

            this.$overlayContainer = $('<div/>');
            this.$componentContainer = this.$overlayContent.find('.datagrid');
            this.$rootCheckbox = this.$overlayContent.find('#category-move-to-root');

            this.$el.append(this.$overlayContainer);
        },

        startOverlay: function() {
            this.sandbox.start([{
                name: 'overlay@husky',
                options: {
                    el: this.$overlayContainer,
                    instanceName: constants.name,
                    openOnStart: true,
                    removeOnClose: true,
                    skin: 'medium',
                    cssClass: constants.name,
                    slides: [
                        {
                            title: this.translations.move,
                            buttons: [
                                {
                                    type: 'cancel',
                                    align: 'left'
                                },
                                {
                                    type: 'ok',
                                    align: 'right',
                                    inactive: true
                                }
                            ],
                            data: this.$overlayContent,
                            okCallback: function() {
                                this.options.selectCallback(this.selectedId);

                                this.sandbox.stop();
                            }.bind(this)
                        }
                    ]
                }
            }]);
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.datagrid.' + constants.name + '.item.select', function(id) {
                this.sandbox.emit('husky.overlay.' + constants.name + '.okbutton.activate');

                this.selectedId = id;
                this.$rootCheckbox.prop('checked', false);
            }.bind(this));

            this.sandbox.once('husky.overlay.' + constants.name + '.initialized', function() {
                $.ajax('/admin/api/categories/fields?locale=' + this.options.locale)
                    .done(function(fields) {
                        this.sandbox.start([{
                            name: 'datagrid@husky',
                            options: {
                                el: this.$componentContainer,
                                url: '/admin/api/categories?flat=true&sortBy=name&sortOrder=asc&locale=' + this.options.locale,
                                childrenPropertyName: 'hasChildren',
                                expandIds: [this.sandbox.sulu.getUserSetting(constants.lastClickedCategorySettingsKey)],
                                pagination: false,
                                resultKey: constants.resultKey,
                                instanceName: constants.name,
                                matchings: fields,
                                viewOptions: {
                                    table: {
                                        actionIcon: 'check',
                                        hideChildrenAtBeginning: false,
                                        cropContents: false,
                                        selectItem: {
                                            type: 'radio',
                                            inFirstCell: true
                                        }
                                    }
                                }
                            }
                        }]);
                    }.bind(this));
            }.bind(this));
        },

        bindDomEvents: function() {
            this.$rootCheckbox.change(function() {
                if (!this.$rootCheckbox.prop('checked')) {
                    if (!this.selectedId) {
                        this.sandbox.emit('husky.overlay.' + constants.name + '.okbutton.deactivate');
                    }

                    return;
                }

                this.sandbox.emit('husky.overlay.' + constants.name + '.okbutton.activate');
                this.sandbox.emit('husky.datagrid.' + constants.name + '.deselect.item', this.selectedId);
                this.selectedId = null;
            }.bind(this));
        }
    };
});
