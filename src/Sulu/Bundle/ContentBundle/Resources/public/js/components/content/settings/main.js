/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app-config'], function(AppConfig) {

    'use strict';

    /**
     * node type constant for content
     * @type {number}
     */
    var TYPE_CONTENT = 1,

        /**
         * node type constant for internal
         * @type {number}
         */
        TYPE_INTERNAL = 2,

        /**
         * node type constant for external
         * @type {number}
         */
        TYPE_EXTERNAL = 4;

    return {

        view: true,

        layout: {
            changeNothing: true
        },

        initialize: function() {
            this.sandbox.emit('husky.toolbar.header.item.disable', 'template', false);
            this.load();

            this.bindCustomEvents();
        },

        startComponents: function () {
            var languages = this.sandbox.dom.data('#shadow_base_language_select', 'languages');
            var shadowsForSelect = [];
            var existingShadowForCurrentLanguage = null;

            if (this.data.enabledShadowLanguages[this.options.language] !== undefined) {
                existingShadowForCurrentLanguage = this.data.enabledShadowLanguages[this.options.language];
            }


            // if there are no languages for whatever reason, show
            // an empty, disabled selection (otherwise the select is broken)
            // note that this is an edge case
            if (languages.length === 0) {
                shadowsForSelect.push({
                    id: '',
                    name: 'no languages',
                    disabled: true
                });
            } else {
                this.sandbox.util.each(this.data.concreteLanguages, function (i, language) {
                    if (this.options.language === language) {
                        return;
                    }

                    var disabled = false;
                    if (existingShadowForCurrentLanguage === language) {
                        disabled = true;
                    }
                    shadowsForSelect.push({
                        id: language,
                        name: language,
                        disabled: disabled
                    });
                }.bind(this));
            }

            var selectedLanguage = this.data.shadowBaseLanguage;

            if (shadowsForSelect[selectedLanguage] === undefined) {
                if (shadowsForSelect.length > 0) {
                    selectedLanguage = shadowsForSelect[0].id;
                }
            }

            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: '#shadow_base_language_select',
                        instanceName: 'settings',
                        multipleSelect: false,
                        defaultLabel: '',
                        data: shadowsForSelect,
                        preSelectedElements: [ selectedLanguage ]
                    }
                }
            ]);
        },

        bindCustomEvents: function() {
            // content save
            this.sandbox.on('sulu.header.toolbar.save', function() {
                this.submit();
            }, this);

            // content saved
            this.sandbox.on('sulu.content.contents.saved', function() {
                // FIXME better solution?
                window.location.reload();
            }, this);

            // set header bar unsaved
            var changedEvent = function() {
                 this.sandbox.emit('sulu.content.contents.set-header-bar', false);
            }.bind(this);

            // hear for changing navigation contexts
            this.sandbox.on('husky.select.nav-contexts.selected.item', changedEvent.bind(this));
            this.sandbox.on('husky.select.nav-contexts.deselected.item', changedEvent.bind(this));

            this.sandbox.on('husky.select.settings.selected.item', function () {
                this.sandbox.emit('sulu.content.changed');
                this.sandbox.emit('sulu.content.contents.set-header-bar', false);
            }.bind(this));
        },

        _updateTabVisibilityForShadowCheckbox: function () {
            var checkboxEl = this.sandbox.dom.find('#shadow_on_checkbox')[0];
            var action = checkboxEl.checked ? 'hide' : 'show';

            this.sandbox.util.each(['content', 'excerpt', 'seo'], function (i, tabName) {
                this.sandbox.emit('husky.tabs.header.item.' + action, 'tab-' + tabName);
            }.bind(this));

            this.sandbox.util.each(['show-in-navigation-container', 'settings-content-form-container'], function (i, formGroupId) {
                if (action === 'hide') {
                    this.sandbox.dom.find('#' + formGroupId).hide();
                } else {
                    this.sandbox.dom.find('#' + formGroupId).show();
                }
            }.bind(this));
        },

        load: function() {
            // get content data
            this.sandbox.emit('sulu.content.contents.get-data', function(data) {
                this.render(data);
            }.bind(this));
        },

        render: function(data) {
            this.data = data;

            require(['text!/admin/content/template/content/settings.html?webspaceKey=' + this.options.webspace], function (template) {
                this.sandbox.dom.html(this.$el, this.sandbox.util.template(template, {
                    translate: this.sandbox.translate
                }));
                this.setData(this.data);
                this.listenForChange();
                this.startComponents();

                this.sandbox.dom.on('#shadow_on_checkbox', 'click', function () {
                    this._updateTabVisibilityForShadowCheckbox();
                }.bind(this));

                this._updateTabVisibilityForShadowCheckbox();
            }.bind(this));
        },

        setData: function(data) {
            var type = parseInt(data.nodeType);

            if (type === TYPE_CONTENT) {
                this.sandbox.dom.attr('#content-node-type', 'checked', true);
            } else if (type === TYPE_EXTERNAL) {
                this.sandbox.dom.attr('#internal-link-node-type', 'checked', true);
            } else if (type === TYPE_EXTERNAL) {
                this.sandbox.dom.attr('#external-link-node-type', 'checked', true);
            }

            // updated after init
            this.sandbox.on('husky.select.nav-contexts.initialize', function() {
                this.sandbox.dom.data('#nav-contexts', 'selection', data.navContexts);
                this.sandbox.dom.data('#nav-contexts', 'selectionValues', data.navContexts);

                $('#nav-contexts').trigger('data-changed');
            }.bind(this));

            if (data.shadowOn) {
                this.sandbox.dom.attr('#shadow_on_checkbox', 'checked', true);
            }
        },

        listenForChange: function() {
            this.sandbox.dom.on(this.$el, 'keyup change', function() {
                this.setHeaderBar(false);
            }.bind(this), '.trigger-save-button');
        },

        setHeaderBar: function(saved) {
            this.sandbox.emit('sulu.content.contents.set-header-bar', saved);
        },

        submit: function() {
            this.sandbox.logger.log('save Model');

            var data = {};

            data.navContexts = this.sandbox.dom.data('#nav-contexts', 'selection');
            data.nodeType = parseInt(this.sandbox.dom.val('input[name="nodeType"]:checked'));
            data.shadowOn = this.sandbox.dom.prop('#shadow_on_checkbox', 'checked');
            data.shadowBaseLanguage = this.sandbox.dom.data('#shadow_base_language_select', 'selectionValues')[0];

            this.data = this.sandbox.util.extend(true, {}, this.data, data);
            this.sandbox.emit('sulu.content.contents.save', this.data, (data.nodeType === TYPE_INTERNAL ? 'internal-link' : data.nodeType === TYPE_EXTERNAL ? 'external-link' : AppConfig.getSection('sulu-content').defaultTemplate));
        }
    };
});
