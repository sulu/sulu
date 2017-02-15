/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'underscore',
    'jquery',
    'config',
    'sulusecurity/components/users/models/user',
    'sulucontact/models/contact',
    'services/husky/url-validator',
    'sulucontent/services/content-manager'
], function(_, $, Config, User, Contact, urlValidator, contentManager) {

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
        TYPE_EXTERNAL = 4,

        constants = {
            validateErrorClass: 'husky-validate-error',
            internalLink: {
                titleContainer: '#internal-link-container .title',
                linkContainer: '#internal-link-container .link'
            },
            externalLink: {
                titleContainer: '#external-link-container .title',
                linkContainer: '#external-link-container .link'
            }
        },

        authorFullname = null,

        isShadow = function() {
            return this.sandbox.dom.prop('#shadow_on_checkbox', 'checked');
        },

        setCreationChangelog = function(fullName, time) {
            var creationText, formattedTime = this.sandbox.date.format(time, true);

            if (!!fullName) {
                creationText = this.sandbox.util.sprintf(
                    this.sandbox.translate('sulu.content.form.settings.changelog.created'),
                    {
                        creator: fullName,
                        created: formattedTime
                    }
                );
            } else {
                creationText = this.sandbox.util.sprintf(
                    this.sandbox.translate('sulu.content.form.settings.changelog.created-only'),
                    {
                        created: formattedTime
                    }
                )
            }

            this.sandbox.dom.text('#created', creationText);
        },

        setChangeChangelog = function(fullName, time) {
            var changedText, formattedTime = this.sandbox.date.format(time, true);

            if (!!fullName) {
                changedText = this.sandbox.util.sprintf(
                    this.sandbox.translate('sulu.content.form.settings.changelog.changed'),
                    {
                        changer: fullName,
                        changed: formattedTime
                    }
                );
            } else {
                changedText = this.sandbox.util.sprintf(
                    this.sandbox.translate('sulu.content.form.settings.changelog.changed-only'),
                    {
                        changed: formattedTime
                    }
                )
            }

            this.sandbox.dom.text('#changed', changedText);
        },

        setAuthorChangelog = function(fullName, time) {
            var authoredText, formattedTime = this.sandbox.date.format(time);

            fullName = fullName || authorFullname;

            if (!!fullName) {
                authorFullname = fullName;
                authoredText = this.sandbox.util.sprintf(
                    this.sandbox.translate('sulu.content.form.settings.changelog.authored'),
                    {
                        author: fullName,
                        authored: formattedTime
                    }
                );
            } else {
                authoredText = this.sandbox.util.sprintf(
                    this.sandbox.translate('sulu.content.form.settings.changelog.authored-only'),
                    {
                        authored: formattedTime
                    }
                )
            }

            this.sandbox.dom.text('#author', authoredText);
        },

        showChangelogContainer = function() {
            this.sandbox.dom.show('#changelog-container');
        };

    return {

        layout: function() {
            return {
                extendExisting: true,
                content: {
                    width: 'fixed',
                    leftSpace: true,
                    rightSpace: true
                }
            };
        },

        initialize: function() {
            this.sandbox.emit('husky.toolbar.header.item.disable', 'template', false);
            this.load();

            this.bindCustomEvents();
        },

        startComponents: function() {
            var languages = this.sandbox.dom.data('#shadow_base_language_select', 'languages'),
                shadowsForSelect = [],
                existingShadowForCurrentLanguage = null,
                selectedLanguage;

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
                this.sandbox.util.each(this.data.concreteLanguages, function(i, language) {
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

            selectedLanguage = this.data.shadowBaseLanguage;

            // show at least a message
            if (shadowsForSelect.length === 0) {
                shadowsForSelect = [
                    {
                        id: -1,
                        name: this.sandbox.translate('sulu.content.form.settings.shadow.no_base_language'),
                        disabled: true
                    }
                ];
            }

            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: '#shadow_base_language_select',
                        instanceName: 'settings',
                        multipleSelect: false,
                        defaultLabel: this.sandbox.translate('sulu.content.form.settings.shadow.select_base_language'),
                        data: shadowsForSelect,
                        preSelectedElements: [selectedLanguage]
                    }
                }
            ]);
        },

        bindCustomEvents: function() {
            // content save
            this.sandbox.on('sulu.toolbar.save', function(action) {
                this.submit(action);
            }, this);

            // set header bar unsaved
            var changedEvent = function() {
                this.sandbox.emit('sulu.content.contents.set-header-bar', false);
            }.bind(this);

            // hear for changing navigation contexts
            this.sandbox.on('husky.select.nav-contexts.selected.item', changedEvent.bind(this));
            this.sandbox.on('husky.select.nav-contexts.deselected.item', changedEvent.bind(this));

            this.sandbox.on('husky.select.settings.selected.item', function() {
                this.sandbox.emit('sulu.content.changed');
                this.sandbox.emit('sulu.content.contents.set-header-bar', false);
            }.bind(this));

            this.sandbox.on('sulu.header.state.changed', function(state) {
                this.state = state;
            }.bind(this));
        },

        bindDomEvents: function() {
            this.sandbox.dom.on('#content-type-container', 'change', function(e) {
                var $checkbox = this.sandbox.dom.$(e.currentTarget),
                    $form = this.sandbox.dom.find('.sub-form', $checkbox.parent().parent().parent()),
                    type = this.sandbox.dom.val($checkbox);

                this.sandbox.dom.hide('#content-type-container .sub-form');
                this.sandbox.dom.show($form);

                if (parseInt(type) === TYPE_CONTENT || !!this.data.shadowOn) {
                    this.sandbox.dom.show('#shadow-container');
                } else {
                    this.sandbox.dom.hide('#shadow-container');
                }
            }.bind(this), '.content-type');

            this.sandbox.dom.on('#shadow_on_checkbox', 'click', function() {
                this.updateVisibilityForShadowCheckbox(false);
            }.bind(this));

            this.sandbox.dom.on('#change-author', 'click', function() {
                this.openAuthorSelection();
            }.bind(this));
        },

        updateVisibilityForShadowCheckbox: function(isInitial) {
            var shadow = isShadow.call(this),
                tabAction,
                $shadowDescription = this.sandbox.dom.find('#shadow-container .input-description');

            if (false === isInitial) {
                tabAction = 'hide';
            }

            if (tabAction === 'hide') {
                this.sandbox.emit('husky.toolbar.header.item.disable', 'state', false);
            } else {
                this.sandbox.emit('husky.toolbar.header.item.enable', 'state', false);
            }

            if (!!shadow) {
                this.sandbox.emit('sulu.content.contents.show-save-items', 'shadow');
                $shadowDescription.show();
            } else {
                this.sandbox.emit('sulu.content.contents.show-save-items', 'content');
                $shadowDescription.hide();
            }

            this.sandbox.util.each(['show-in-navigation-container', 'settings-content-form-container'], function(i, formGroupId) {
                if (!!shadow) {
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

            require(['text!/admin/content/template/content/settings.html?webspaceKey=' + this.options.webspace + '&languageCode=' + this.options.language], function(template) {
                this.sandbox.dom.html(this.$el, this.sandbox.util.template(template, {
                    translate: this.sandbox.translate
                }));

                this.buildAllNavContexts(this.sandbox.dom.data('#nav-contexts', 'auraData'));

                this.bindDomEvents();
                this.setData(this.data);
                this.listenForChange();
                this.startComponents();
                this.sandbox.start(this.$el, {reset: true});

                this.sandbox.start([
                    {
                        name: 'single-internal-link@sulucontent',
                        options: {
                            el: '#internal-link',
                            instanceName: 'internal-link',
                            resultKey: 'nodes',
                            url: [
                                '/admin/api/nodes{/uuid}?depth=1&webspace=', this.options.webspace,
                                '&language=', this.options.language,
                                '&webspace-node=true'
                            ].join(''),
                            columnNavigationUrl: [
                                '/admin/api/nodes?{id=uuid&}tree=true&webspace=', this.options.webspace,
                                '&language=', this.options.language,
                                '&webspace-node=true'
                            ].join(''),
                            disabledIds: [this.data.id]
                        }
                    }
                ]);

                if (Config.get('sulu-content')['versioning']['enabled']) {
                    this.sandbox.start([
                        {
                            name: 'datagrid@husky',
                            options: {
                                el: '#versions',
                                instanceName: 'versions',
                                url: [
                                    '/admin/api/nodes/',
                                    this.options.id,
                                    '/versions?language=', this.options.language,
                                    '&webspace=', this.options.webspace
                                ].join(''),
                                resultKey: 'versions',
                                actionCallback: this.restoreVersion.bind(this),
                                viewOptions: {
                                    table: {
                                        actionIcon: 'history',
                                        actionColumn: 'authored',
                                        selectItem: false
                                    }
                                },
                                matchings: [
                                    {
                                        name: 'authored',
                                        attribute: 'authored',
                                        content: this.sandbox.translate('sulu-document-manager.version.authored'),
                                        type: 'datetime'
                                    },
                                    {
                                        name: 'author',
                                        attribute: 'author',
                                        content: this.sandbox.translate('sulu-document-manager.version.author')
                                    }
                                ]
                            }
                        }
                    ]);
                }

                this.updateVisibilityForShadowCheckbox(true);

                this.updateChangelog(data);
            }.bind(this));
        },

        buildAllNavContexts: function(navContexts) {
            this.allNavContexts = {};

            for (var i = 0, len = navContexts.length; i < len; i++) {
                this.allNavContexts[navContexts[i].id] = navContexts[i].name;
            }
        },

        updateChangelog: function(data) {
            var creator,
                changer,
                author,
                creatorDef = this.sandbox.data.deferred(),
                changerDef = this.sandbox.data.deferred(),
                authorDef = this.sandbox.data.deferred();

            if (data.creator === data.changer) {
                creator = new User({id: data.creator});

                creator.fetch({
                    global: false,
                    success: function(model) {
                        creatorDef.resolve(model.get('fullName'), data.created);
                        changerDef.resolve(model.get('fullName'), data.changed);
                    },
                    error: function() {
                        creatorDef.resolve(null, data.created);
                        changerDef.resolve(null, data.changed);
                    }.bind(this)
                });
            } else {
                creator = new User({id: data.creator});
                changer = new User({id: data.changer});

                creator.fetch({
                    global: false,
                    success: function(model) {
                        creatorDef.resolve(model.get('fullName'), data.created);
                    },
                    error: function() {
                        creatorDef.resolve(null, data.created);
                    }.bind(this)
                });

                changer.fetch({
                    global: false,
                    success: function(model) {
                        changerDef.resolve(model.get('fullName'), data.changed);
                    },
                    error: function() {
                        changerDef.resolve(null, data.changed);
                    }.bind(this)
                })
            }

            if (!!data.author) {
                author = new Contact({id: data.author});
                author.fetch({
                    global: false,

                    success: function(model) {
                        authorDef.resolve(model.get('fullName'), new Date(data.authored));
                    }.bind(this),

                    error: function() {
                        authorDef.resolve(null, new Date(data.authored));
                    }.bind(this)
                });
            } else {
                authorDef.resolve(null, new Date(data.authored));
            }

            this.sandbox.data.when(creatorDef, changerDef, authorDef).then(function(creation, change, author) {
                setCreationChangelog.call(this, creation[0], creation[1]);
                setChangeChangelog.call(this, change[0], change[1]);
                setAuthorChangelog.call(this, author[0], author[1]);
                showChangelogContainer.call(this);
            }.bind(this));
        },

        setData: function(data) {
            var type = parseInt(data.nodeType);

            if (type === TYPE_CONTENT) {
                this.sandbox.dom.attr('#content-node-type', 'checked', true).trigger('change');
            } else if (type === TYPE_INTERNAL) {
                this.sandbox.dom.attr('#internal-link-node-type', 'checked', true).trigger('change');
            } else if (type === TYPE_EXTERNAL) {
                this.sandbox.dom.attr('#external-link-node-type', 'checked', true).trigger('change');
            }

            if (!!data.title) {
                this.sandbox.dom.val('#internal-title', data.title);
                this.sandbox.dom.val('#external-title', data.title);
            }
            if (!!data.internal_link) {
                this.sandbox.dom.data('#internal-link', 'singleInternalLink', data.internal_link);
            }
            if (!!data.external) {
                this.sandbox.dom.data('#external', 'url-data', urlValidator.match(data.external));
            }

            // updated after init
            this.sandbox.on('husky.select.nav-contexts.initialize', function() {
                var navContextsValues = [], i, len;
                for (i = 0, len = data.navContexts.length; i < len; i++) {
                    navContextsValues.push(this.allNavContexts[data.navContexts[i]]);
                }

                this.sandbox.dom.data('#nav-contexts', 'selection', data.navContexts);
                this.sandbox.dom.data('#nav-contexts', 'selectionValues', navContextsValues);

                $('#nav-contexts').trigger('data-changed');
            }.bind(this));

            if (data.shadowOn) {
                this.sandbox.dom.attr('#shadow_on_checkbox', 'checked', true);
                this.sandbox.emit('husky.toolbar.header.item.disable', 'state', false);
            }
        },

        listenForChange: function() {
            this.sandbox.dom.on(this.$el, 'keyup change', function() {
                this.setHeaderBar(false);
            }.bind(this), '.trigger-save-button');

            this.sandbox.on('sulu.single-internal-link.internal-link.data-changed', function() {
                this.setHeaderBar(false);
            }.bind(this));
        },

        setHeaderBar: function(saved) {
            this.sandbox.emit('sulu.content.contents.set-header-bar', saved);
        },

        submit: function(action) {
            this.sandbox.logger.log('save Model');

            var data = {},
                baseLanguages = this.sandbox.dom.data('#shadow_base_language_select', 'selectionValues');

            data.id = this.data.id;
            data.navContexts = this.sandbox.dom.data('#nav-contexts', 'selection');
            data.nodeType = parseInt(this.sandbox.dom.val('input[name="nodeType"]:checked'));
            data.shadowOn = isShadow.call(this);
            data.shadowBaseLanguage = null;

            if (!!this.state) {
                data.state = this.state;
            }

            if (data.nodeType === TYPE_INTERNAL) {
                data.title = this.sandbox.dom.val('#internal-title');
                data.internal_link = this.sandbox.dom.data('#internal-link', 'singleInternalLink');
            } else if (data.nodeType === TYPE_EXTERNAL) {
                var urlData = this.sandbox.dom.data('#external', 'url-data');
                data.title = this.sandbox.dom.val('#external-title');
                data.external = urlData.scheme + urlData.specificPart;
                data.urlParts = urlData;
            }

            if (!!data.shadowOn && !!baseLanguages && baseLanguages.length > 0) {
                data.shadowBaseLanguage = baseLanguages[0];
            }

            data.author = this.data.author;
            data.authored = this.data.authored;

            if (!this.validate(data)) {
                this.sandbox.emit('sulu.labels.warning.show', 'form.validation-warning', 'labels.warning');

                return;
            }

            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');

            contentManager.save(
                data,
                this.options.language,
                this.options.webspace,
                {
                    action: action
                },
                function(response) {
                    this.sandbox.emit('sulu.content.contents.saved', response.id, response, action);
                    this.sandbox.emit('husky.datagrid.versions.update');
                }.bind(this),
                function(xhr) {
                    this.sandbox.emit('sulu.content.contents.error', xhr.status, data);
                }.bind(this)
            );
        },

        restoreVersion: function(versionId, version) {
            this.sandbox.sulu.showConfirmationDialog({
                callback: function(wasConfirmed) {
                    if (!wasConfirmed) {
                        return;
                    }

                    this.sandbox.emit('husky.overlay.alert.show-loader');
                    contentManager.restoreVersion(this.options.id, versionId, version.locale, this.options.webspace)
                        .always(function() {
                            this.sandbox.emit('husky.overlay.alert.hide-loader');
                        }.bind(this))
                        .then(function() {
                            this.sandbox.emit('husky.overlay.alert.close');
                            this.sandbox.emit(
                                'sulu.router.navigate',
                                [
                                    'content/contents/',
                                    this.options.webspace,
                                    '/',
                                    this.options.language,
                                    '/edit:',
                                    this.options.id,
                                    '/content'
                                ].join(''),
                                true,
                                true
                            );
                        }.bind(this))
                        .fail(function() {
                            this.sandbox.emit(
                                'sulu.labels.error.show',
                                'sulu.content.restore-error-description',
                                'sulu.content.restore-error-title'
                            );
                        }.bind(this));

                    return false;
                }.bind(this),
                title: this.sandbox.translate('sulu-document-manager.restore-confirmation-title'),
                description: this.sandbox.translate('sulu-document-manager.restore-confirmation-description')
            });
        },

        validate: function(data) {
            // remove validation classes
            this.sandbox.dom.removeClass(constants.internalLink.titleContainer, constants.validateErrorClass);
            this.sandbox.dom.removeClass(constants.internalLink.linkContainer, constants.validateErrorClass);
            this.sandbox.dom.removeClass(constants.externalLink.titleContainer, constants.validateErrorClass);
            this.sandbox.dom.removeClass(constants.externalLink.linkContainer, constants.validateErrorClass);

            if (data.nodeType === TYPE_INTERNAL) {
                return this.validateInternal(data);
            } else if (data.nodeType === TYPE_EXTERNAL) {
                return this.validateExternal(data);
            }

            return true;
        },

        validateInternal: function(data) {
            var result = true;

            if (!data.title) {
                result = false;
                this.sandbox.dom.addClass(constants.internalLink.titleContainer, constants.validateErrorClass);
            }

            if (!data.internal_link) {
                result = false;
                this.sandbox.dom.addClass(constants.internalLink.linkContainer, constants.validateErrorClass);
            }

            return result;
        },

        validateExternal: function(data) {
            var result = true;

            if (!data.title) {
                result = false;
                this.sandbox.dom.addClass(constants.externalLink.titleContainer, constants.validateErrorClass);
            }

            if (!data.urlParts.scheme || !data.urlParts.specificPart) {
                result = false;
                this.sandbox.dom.addClass(constants.externalLink.linkContainer, constants.validateErrorClass);
            }

            return result;
        },

        openAuthorSelection: function() {
            var $overlayContainer = $('<div/>'),
                $componentContainer = $('<div/>');

            this.$el.append($overlayContainer);

            this.sandbox.start([{
                name: 'overlay@husky',
                options: {
                    el: $overlayContainer,
                    instanceName: 'author-selection',
                    openOnStart: true,
                    removeOnClose: true,
                    skin: 'medium',
                    slides: [
                        {
                            title: this.sandbox.translate('sulu.content.form.settings.author'),
                            okCallback: function() {
                                this.sandbox.emit('sulu.content.contents.get-author');
                            }.bind(this),
                            data: $componentContainer
                        }
                    ]
                }
            }]);

            this.sandbox.once('husky.overlay.author-selection.initialized', function() {
                this.sandbox.start([
                    {
                        name: 'content/settings/author-selection@sulucontent',
                        options: {
                            el: $componentContainer,
                            locale: this.options.locale,
                            data: {author: this.data.author, authored: this.data.authored},
                            selectCallback: function(data) {
                                this.setAuthor(data);

                                this.sandbox.emit('husky.overlay.author-selection.close');
                            }.bind(this)
                        }
                    }
                ]);
            }.bind(this));
        },

        setAuthor: function(data) {
            this.setHeaderBar(false);

            this.data.authored = data.authored;
            if (!data.authorItem) {
                setAuthorChangelog.call(this, null, new Date(data.authored));

                return;
            }

            setAuthorChangelog.call(this, data.authorItem.firstName + ' ' + data.authorItem.lastName, new Date(data.authored));
            this.data.author = data.author;
        }
    };
});
