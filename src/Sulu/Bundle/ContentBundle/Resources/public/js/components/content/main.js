/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'config',
    'services/sulupreview/preview',
    'sulucontent/model/content',
    'sulucontent/services/content-manager',
    'sulucontent/services/user-settings',
    'sulucontent/components/copy-locale-overlay/main',
    'sulucontent/components/open-ghost-overlay/main',
    'sulusecurity/services/user-manager',
    'sulusecurity/services/security-checker',
    'services/sulucontent/smart-content-manager'
], function(config, Preview, Content, ContentManager, UserSettings, CopyLocale, OpenGhost, UserManager, SecurityChecker, SmartContentManager) {

    'use strict';

    var CONTENT_LANGUAGE = 'contentLanguage',

        SHOW_GHOST_PAGES_KEY = 'column-navigation-show-ghost-pages',

        constants = {
            localizationUrl: '/admin/api/webspace/localizations',
            tabPrefix: 'tab-',
            contentNodeType: 1,
            internalLinkNodeType: 2,
            externalLinkNodeType: 4
        },

        errorCodes = {
            contentChanged: 1102,
            resourceLocatorAlreadyExists: 1103
        },

        translationKeys = {
            deleteReferencedByFollowing: 'content.delete-referenced-by-following',
            deleteConfirmText: 'content.delete-confirm-text',
            deleteConfirmTitle: 'content.delete-confirm-title',
            deleteDoIt: 'content.delete-do-it',
            draftLabel: 'sulu-document-manager.draft-label',
            unpublishConfirmTextNoDraft: 'sulu-content.unpublish-confirm-text-no-draft',
            unpublishConfirmTextWithDraft: 'sulu-content.unpublish-confirm-text-with-draft',
            unpublishConfirmTitle: 'sulu-content.unpublish-confirm-title',
            removeDraft: 'sulu-content.delete-draft',
            deleteDraftConfirmTitle: 'sulu-content.delete-draft-confirm-title',
            deleteDraftConfirmText: 'sulu-content.delete-draft-confirm-text'
        },

        templates = {
            referentialIntegrityMessage: function(pageTitles) {
                var message = [];

                message.push('<p>', this.sandbox.translate(translationKeys.deleteReferencedByFollowing), '</p>');
                message.push('<ul>');

                this.sandbox.util.foreach(pageTitles, function(pageTitle) {
                    message.push('<li>', pageTitle, '</li>');
                });

                message.push('</ul>');
                message.push('<p>', this.sandbox.translate(translationKeys.deleteConfirmText), '</p>');

                return message.join('');
            }
        },

        isHomeDocument = function(data) {
            return data.url === '/';
        },

        hasDraft = function(data) {
            return !data.id || !!data.publishedState || !data.published;
        },

        setToolbarItems = function(item, value) {
            this.sandbox.emit('sulu.header.toolbar.item.' + (!!value ? 'enable' : 'disable'), item, false);
        },

        saveUserLocaleForWebspace = function(locale) {
            this.sandbox.sulu.saveUserSetting(this.options.webspace + '.' + CONTENT_LANGUAGE, locale);
            this.sandbox.sulu.saveUserSetting(CONTENT_LANGUAGE, locale);
        };

    return {

        initialize: function() {
            SmartContentManager.initialize();

            this.saved = true;

            if (this.options.display === 'column') {
                this.renderColumn();
            } else {
                this.render();
            }
            this.bindCustomEvents();
        },

        loadComponentData: function() {
            var localizationDeferred = $.Deferred();
            var dataDeferred = $.Deferred();

            this.loadLocalizations().then(function() {
                localizationDeferred.resolve();
            }.bind(this));

            if (this.options.display !== 'column') {
                this.loadData().then(function() {
                    if (!!this.options.preview) {
                        this.preview = Preview.initialize(this.data._permissions, this.options.webspace);
                    }

                    dataDeferred.resolve();
                }.bind(this));
            } else {
                dataDeferred.resolve();
            }

            return $.when(localizationDeferred, dataDeferred);
        },

        renderColumn: function() {
            var $column = this.sandbox.dom.createElement('<div id="content-column-container"/>');
            this.sandbox.dom.append(this.$el, $column);
            this.sandbox.start([
                {
                    name: 'content/column@sulucontent',
                    options: {
                        el: $column,
                        webspace: this.options.webspace,
                        language: this.options.language
                    }
                }
            ]);
        },

        loadLocalizations: function() {
            return this.sandbox.util.load(constants.localizationUrl + '?webspace=' + this.options.webspace)
                .then(function(data) {
                    this.localizations = data._embedded.localizations.map(function(localization) {
                        return {
                            id: localization.localization,
                            title: localization.localization
                        };
                    });
                }.bind(this));
        },

        loadData: function() {
            var promise = $.Deferred();
            if (!this.content) {
                this.content = new Content({id: this.options.id});
            }

            if (this.options.id !== undefined) {
                this.content.fullFetch(
                    this.options.webspace,
                    this.options.language,
                    true,
                    {
                        success: function(content) {
                            this.data = content.toJSON();
                            promise.resolve();
                        }.bind(this)
                    }
                );
            } else {
                this.data = this.content.toJSON();
                promise.resolve();
            }

            return promise;
        },

        bindCustomEvents: function() {
            // back button
            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.content.contents.list');
            }.bind(this));

            // load column view
            this.sandbox.on('sulu.content.contents.list', function(webspace, language) {
                var route = 'content/contents/' +
                    (!webspace ? this.options.webspace : webspace) + '/' +
                    (!language ? this.options.language : language);
                this.sandbox.emit('sulu.app.ui.reset', {navigation: 'auto', content: 'auto'});
                this.sandbox.emit('sulu.router.navigate', route);
            }, this);

            // getter for content data
            this.sandbox.on('sulu.content.contents.get-data', function(callback) {
                // deep copy of object
                callback(this.sandbox.util.deepCopy(this.data), this.preview);
            }.bind(this));

            // setter for header bar buttons
            this.sandbox.on('sulu.content.contents.set-header-bar', function(saved) {
                this.setHeaderBar(saved);
            }.bind(this));

            // delegate save to tab
            this.sandbox.on('sulu.toolbar.save', function(action) {
                this.sandbox.emit('sulu.tab.save', action);
            }, this);

            // change language
            this.sandbox.on('sulu.header.language-changed', function(item) {
                saveUserLocaleForWebspace.call(this, item.id);
                if (this.options.display !== 'column') {
                    var data = this.content.toJSON();

                    if (!!data.id) {
                        if (-1 === _(data.concreteLanguages).indexOf(item.id)
                            && -1 === _(data.enabledShadowLanguages).values().indexOf(item.id)
                        ) {
                            OpenGhost.openGhost.call(this, data).then(function(copy, src) {
                                if (!!copy) {
                                    CopyLocale.copyLocale.call(
                                        this,
                                        data.id,
                                        src,
                                        [item.id],
                                        function() {
                                            this.load(data, this.options.webspace, item.id, true);
                                        }.bind(this)
                                    );
                                } else {
                                    // new page will be created
                                    this.load({id: data.id}, this.options.webspace, item.id, true);
                                }
                            }.bind(this)).fail(function() {
                                // the open-ghost page got canceled, so reset the language changer
                                this.sandbox.emit('sulu.header.change-language', this.options.language);
                            }.bind(this));
                        } else {
                            this.load(data, this.options.webspace, item.id, true);
                        }
                    } else {
                        this.add(
                            !!this.options.parent ? {id: this.options.parent} : null,
                            this.options.webspace,
                            item.id
                        );
                    }
                } else {
                    this.sandbox.emit('sulu.content.contents.list', this.options.webspace, item.id);
                }
            }, this);

            this.sandbox.on('husky.tabs.header.item.select', function(item) {
                if (item.id === 'tab-excerpt') {
                    this.template = this.data.originTemplate;
                }

                if (item.id === 'tab-permissions') {
                    this.showSaveItems('permissions');
                } else {
                    this.showSaveItems('content');
                }
            }.bind(this));

            // change template
            this.sandbox.on('sulu.dropdown.template.item-clicked', function() {
                this.setHeaderBar(false);
            }.bind(this));

            // content saved
            this.sandbox.on('sulu.content.contents.saved', function(id, data, action) {
                if (!this.options.id) {
                    this.sandbox.sulu.viewStates.justSaved = true;
                } else {
                    this.data = data;
                    this.content.set(data);
                    this.setHeaderBar(true);
                    this.showDraftLabel();

                    // FIXME select should be able to override text in a item
                    this.sandbox.dom.html('li[data-id="' + this.options.language + '"] a', this.options.language);

                    this.sandbox.emit('sulu.header.saved', data);
                    this.showState(!!this.data.published);
                    this.sandbox.emit('sulu.labels.success.show', 'labels.success.content-save-desc', 'labels.success');
                }

                this.afterSaveAction(action, !this.options.id);
            }, this);

            this.sandbox.on('sulu.content.contents.error', function(code, data, action) {
                this.handleError(code, data, action);
            }, this);

            // set default template
            this.sandbox.on('sulu.content.contents.default-template', function(name) {
                this.template = name;
                if (this.data.nodeType !== constants.contentNodeType) {
                    this.sandbox.emit('sulu.header.toolbar.item.change', 'template', name);
                    if (this.hiddenTemplate) {
                        this.hiddenTemplate = false;
                        this.sandbox.emit('sulu.header.toolbar.item.show', 'template', name);
                    }
                }
            }, this);

            this.sandbox.on('sulu.content.contents.show-save-items', function(state) {
                this.showSaveItems(state);
            }.bind(this));

            // expand navigation if navigation item is clicked
            this.sandbox.on('husky.navigation.item.select', function(event) {
                // when navigation item is already opended do nothing - relevant for homepage
                if (event.id !== this.options.id) {
                    this.sandbox.emit('sulu.app.ui.reset', {navigation: 'auto', content: 'auto'});
                }
            }.bind(this));

            this.sandbox.on('sulu.permission-tab.saved', function(data, action) {
                this.afterSaveAction(action, false);
            }.bind(this));

            // bind model data events
            this.bindModelEvents();
        },

        bindModelEvents: function() {
            // delete content
            this.sandbox.on('sulu.content.content.delete', function(id) {
                this.del(id);
            }, this);

            // save the current package
            this.sandbox.on('sulu.content.contents.save', function(data, action) {
                this.save(data, action).then(function() {
                    this.loadData();
                }.bind(this));
            }, this);

            // wait for navigation events
            this.sandbox.on('sulu.content.contents.load', function(item, webspace, language) {
                this.load(item, webspace, language);
            }, this);

            // add new content
            this.sandbox.on('sulu.content.contents.new', function(parent) {
                this.add(parent);
            }, this);

            // delete selected content
            this.sandbox.on('sulu.content.contents.delete', function(ids) {
                this.delContents(ids);
            }, this);

            // move selected content
            this.sandbox.on('sulu.content.contents.move', this.move, this);

            // copy selected content
            this.sandbox.on('sulu.content.contents.copy', this.copy, this);

            // copy-locale selected content
            this.sandbox.on('sulu.content.contents.copy-locale', CopyLocale.copyLocale, this);

            // order selected content
            this.sandbox.on('sulu.content.contents.order', this.order, this);

            // get resource locator
            this.sandbox.on('sulu.content.contents.get-rl', function(title, callback) {
                this.getResourceLocator(title, this.template, callback);
            }, this);

            // load list view
            this.sandbox.on('sulu.content.contents.list', function(webspace, language) {
                var route = 'content/contents/' +
                    (!webspace ? this.options.webspace : webspace) + '/' +
                    (!language ? this.options.language : language);
                this.sandbox.emit('sulu.app.ui.reset', {navigation: 'auto', content: 'auto'});
                this.sandbox.emit('sulu.router.navigate', route);
            }, this);
        },

        getResourceLocator: function(parts, template, callback) {
            var parentUuid = (!!this.options.parent) ? this.options.parent : this.data.parentUuid,
                url = '/admin/api/nodes/resourcelocators/generates?' +
                    (!!parentUuid ? 'parent=' + parentUuid + '&' : '') +
                    'webspace=' + this.options.webspace +
                    '&language=' + this.options.language +
                    '&template=' + template;

            this.sandbox.util.save(url, 'POST', {parts: parts})
                .then(function(data) {
                    callback(data.resourceLocator);
                });
        },

        move: function(id, parentId, successCallback, errorCallback) {
            var url = [
                '/admin/api/nodes/', id, '?webspace=', this.options.webspace,
                '&language=', this.options.language, '&action=move&destination=', parentId
            ].join('');

            this.sandbox.util.save(url, 'POST', {})
                .then(function(data) {
                    if (!!successCallback && typeof successCallback === 'function') {
                        successCallback(data);
                    }
                }.bind(this))
                .fail(function(jqXHR, textStatus, error) {
                    if (!!errorCallback && typeof errorCallback === 'function') {
                        errorCallback(error);
                    }
                }.bind(this));
        },

        copy: function(id, parentId, successCallback, errorCallback) {
            var url = [
                '/admin/api/nodes/', id, '?webspace=', this.options.webspace,
                '&language=', this.options.language, '&action=copy&destination=', parentId
            ].join('');

            this.sandbox.util.save(url, 'POST', {})
                .then(function(data) {
                    if (!!successCallback && typeof successCallback === 'function') {
                        successCallback(data);
                    }
                }.bind(this))
                .fail(function(jqXHR, textStatus, error) {
                    if (!!errorCallback && typeof errorCallback === 'function') {
                        errorCallback(error);
                    }
                }.bind(this));
        },

        order: function(uuid, position, successCallback, errorCallback) {
            var url = [
                '/admin/api/nodes/', uuid, '?webspace=', this.options.webspace,
                '&language=', this.options.language, '&action=order'
            ].join('');

            this.sandbox.util.save(url, 'POST', {
                position: position
            })
                .then(function(data) {
                    if (!!successCallback && typeof successCallback === 'function') {
                        successCallback(data);
                    }
                }.bind(this))
                .fail(function(jqXHR, textStatus, error) {
                    if (!!errorCallback && typeof errorCallback === 'function') {
                        errorCallback(error);
                    }
                }.bind(this));
        },

        del: function(id) {
            this.sandbox.sulu.showDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'edit');
                    if (!this.content || id !== this.content.get('id')) {
                        var content = new Content({id: id});
                        content.fullDestroy(this.options.webspace, this.options.language, false, {
                            processData: true,
                            success: this.deleteSuccessCallback.bind(this),
                            error: function(model, response) {
                                this.displayReferentialIntegrityDialog(model, response.responseJSON);
                            }.bind(this)
                        });
                    } else {
                        this.content.fullDestroy(this.options.webspace, this.options.language, false, {
                            processData: true,
                            success: function() {
                                this.sandbox.emit('sulu.app.ui.reset', {navigation: 'auto', content: 'auto'});
                                this.sandbox.sulu.unlockDeleteSuccessLabel();
                                this.deleteSuccessCallback();
                            }.bind(this),
                            error: function(model, response) {
                                this.displayReferentialIntegrityDialog(model, response.responseJSON);
                            }.bind(this)
                        });
                    }
                }
            }.bind(this));
        },

        delContents: function(ids) {
            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    // TODO: show loading icon
                    ids.forEach(function(id) {
                        var content = new Content({id: id});
                        content.fullDestroy(this.options.webspace, this.options.language, false, {
                            success: function() {
                                this.sandbox.emit('husky.datagrid.record.remove', id);
                            }.bind(this),
                            error: function() {
                                // TODO error message
                            }
                        });
                    }.bind(this));
                }
            }.bind(this));
        },

        displayReferentialIntegrityDialog: function(content, data) {
            var pageTitles = [];

            this.sandbox.util.foreach(data.structures, function(structure) {
                pageTitles.push(structure.title);
            });

            var $element = $('<div/>');
            $('body').append($element);

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: $element,
                        openOnStart: true,
                        title: this.sandbox.translate(translationKeys.deleteConfirmTitle),
                        message: templates.referentialIntegrityMessage.call(this, pageTitles),
                        okDefaultText: this.sandbox.translate(translationKeys.deleteDoIt),
                        type: 'alert',
                        closeCallback: function() {
                        },
                        okCallback: function() {
                            content.fullDestroy(this.options.webspace, this.options.language, true, {
                                processData: true,
                                success: this.deleteSuccessCallback.bind(this)
                            });
                        }.bind(this)
                    }
                }
            ]);
        },

        deleteSuccessCallback: function() {
            var route = 'content/contents/' + this.options.webspace + '/' + this.options.language;
            this.sandbox.emit('sulu.router.navigate', route);
            this.sandbox.emit('sulu.content.content.deleted');
        },

        /**
         * Asks if the content should be overriden, if the content has been changed on the server.
         * @param {Object} data
         * @param {string} action
         */
        handleErrorContentChanged: function(data, action) {
            this.sandbox.emit(
                'sulu.overlay.show-warning',
                'content.changed-warning.title',
                'content.changed-warning.description',
                function() {
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'save');
                }.bind(this),
                function() {
                    this.saveContent(
                        data,
                        {
                            // on success save contents id
                            success: function(response) {
                                var model = response.toJSON();
                                this.sandbox.emit('sulu.content.contents.saved', model.id, model, action);
                                this.sandbox.emit('sulu.header.toolbar.item.enable', 'save');
                            }.bind(this),
                            error: function(model, response) {
                                this.sandbox.emit(
                                    'sulu.content.contents.error',
                                    response.responseJSON.code,
                                    data,
                                    action
                                );
                            }.bind(this)
                        },
                        true,
                        action
                    );
                }.bind(this),
                {
                    okDefaultText: 'content.changed-warning.ok-button'
                }
            );
        },

        /**
         * Handles the error based on its error code.
         * @param {number} errorCode
         * @param {Object} data
         * @param {string} action
         */
        handleError: function(errorCode, data, action) {
            switch (errorCode) {
                case errorCodes.contentChanged:
                    this.handleErrorContentChanged(data, action);
                    break;
                case errorCodes.resourceLocatorAlreadyExists:
                    this.sandbox.emit(
                        'sulu.labels.error.show',
                        'labels.error.content-save-resource-locator',
                        'labels.error'
                    );
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'save');
                    break;
                default:
                    this.sandbox.emit('sulu.labels.error.show', 'labels.error.content-save-desc', 'labels.error');
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'save');
            }
        },

        save: function(data, action) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');

            var def = this.sandbox.data.deferred();

            if (!!this.template) {
                data.template = this.template;
            }

            if (!!this.content) {
                this.content.set(data);
            } else {
                this.content = new Content(data);
            }

            if (!!this.options.id) {
                this.content.set({id: this.options.id});
            }

            this.saveContent(
                data,
                {
                    // on success save contents id
                    success: function(response) {
                        var model = response.toJSON();
                        this.sandbox.emit('sulu.content.contents.saved', model.id, model, action);
                        def.resolve();
                    }.bind(this),
                    error: function(model, response) {
                        this.sandbox.emit('sulu.content.contents.error', response.responseJSON.code, data, action);
                    }.bind(this)
                },
                false,
                action
            );

            return def;
        },

        saveContent: function(data, options, force, action) {
            if (typeof force === 'undefined') {
                force = false;
            }

            this.content.fullSave(
                this.options.webspace,
                this.options.language,
                this.options.parent,
                (isHomeDocument(data) ? 'home' : null),
                null,
                options,
                force,
                action
            );
        },

        /**
         * Routes either to the list, content-add or content-edit, depending on the passed parameter
         * @param action {String} 'new', 'add' or 'edit'
         * @param toEdit {Boolean} iff true and no action has been passed the method routes to 'edit'
         */
        afterSaveAction: function(action, toEdit) {
            if (action === 'back') {
                this.sandbox.emit('sulu.content.contents.list');
            } else if (action === 'new') {
                var parent, breadcrumb = this.content.get('breadcrumb');
                parent = ((!!this.options.id && breadcrumb.length > 1) ?
                        breadcrumb[breadcrumb.length - 1].uuid : null) || this.options.parent;
                this.sandbox.emit('sulu.router.navigate',
                    'content/contents/' + this.options.webspace + '/' +
                    this.options.language + '/add' + ((!!parent) ? ':' + parent : '') + '/content',
                    true, true
                );
            } else if (toEdit) {
                this.sandbox.emit('sulu.router.navigate',
                    'content/contents/' + this.options.webspace + '/' +
                    this.options.language + '/edit:' + this.content.get('id') + '/content'
                );
            }
        },

        load: function(item, webspace, language, forceReload) {
            var action = 'content';
            if (
                (!!item.nodeType && item.nodeType !== constants.contentNodeType) ||
                (!!item.type && !!item.type.name && item.type.name === 'shadow')
            ) {
                action = 'settings';
            }

            this.sandbox.emit(
                'sulu.router.navigate',
                'content/contents/' + (!webspace ? this.options.webspace : webspace) +
                '/' + (!language ? this.options.language : language) + '/edit:' + item.id + '/' + action,
                true, forceReload
            );
        },

        add: function(parent, webspace, language) {
            if (!!parent) {
                this.sandbox.emit(
                    'sulu.router.navigate',
                    'content/contents/' + (!webspace ? this.options.webspace : webspace) +
                    '/' + (!language ? this.options.language : language) + '/add:' + parent.id + '/content'
                );
            } else {
                this.sandbox.emit(
                    'sulu.router.navigate',
                    'content/contents/' +
                    (!webspace ? this.options.webspace : webspace) + '/' +
                    (!language ? this.options.language : language) + '/add/content'
                );
            }
        },

        render: function() {
            this.setTemplate(this.data);
            this.showDraftLabel();

            this.showState(!!this.data.published);

            if (this.options.content === 'permissions') {
                this.showSaveItems('permissions');
            }

            if (!!this.options.preview && this.data.nodeType === constants.contentNodeType && !this.data.shadowOn) {
                var objectClass = 'Sulu\\Bundle\\ContentBundle\\Document\\' + (isHomeDocument(this.data) ? 'Home' : 'Page') + 'Document';

                this.preview.start(
                    objectClass,
                    this.options.id,
                    this.options.language,
                    this.data
                );
            } else {
                this.sandbox.emit('sulu.sidebar.hide');
            }

            if (!!this.options.id) {
                // route to settings
                if (
                    (this.options.content !== 'settings' && this.data.shadowOn === true) ||
                    (this.options.content === 'content' && this.data.nodeType !== constants.contentNodeType)
                ) {
                    this.sandbox.emit(
                        'sulu.router.navigate',
                        'content/contents/' + this.options.webspace +
                        '/' + this.options.language + '/edit:' + this.data.id + '/settings'
                    );
                }

                // set current page as last selected one
                UserSettings.setLastSelectedPage(this.options.webspace, this.options.id);
            }

            this.setHeaderBar(true);
        },

        /**
         * Clear website cache.
         */
        cacheClear: function() {
            this.sandbox.website.cacheClear();
        },

        /**
         * Sets template to header
         * @param {Object} data
         */
        setTemplate: function(data) {
            this.template = data.originTemplate;

            if (
                this.data.nodeType === constants.contentNodeType &&
                this.template !== '' &&
                this.template !== undefined &&
                this.template !== null
            ) {
                this.sandbox.emit('sulu.header.toolbar.item.change', 'template', this.template);
                this.sandbox.emit('sulu.header.toolbar.item.show', 'template');
            }
        },

        /**
         * Sets header bar
         * @param {Boolean} saved
         */
        setHeaderBar: function(saved) {
            var saveDraft = !saved,
                savePublish = !saved,
                publish = !!saved && !this.data.publishedState;

            setToolbarItems.call(this, 'saveDraft', saveDraft);
            setToolbarItems.call(this, 'savePublish', savePublish);
            setToolbarItems.call(this, 'publish', publish);

            setToolbarItems.call(this, 'unpublish', !!this.data.published);

            if (!!saveDraft || !!savePublish || !!publish) {
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
            } else {
                this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', false);
            }

            this.saved = saved;
        },

        getCopyLocaleUrl: function(id, src, dest) {
            return [
                '/admin/api/nodes/', id, '?webspace=', this.options.webspace,
                '&language=', src, '&dest=', dest, '&action=copy-locale'
            ].join('');
        },

        header: function() {
            var length, concreteLanguages = [],
                header, dropdownLocalizations = [], navigationUrl, navigationUrlParams = [];

            if (this.options.display === 'column') {
                var showGhostPages = this.sandbox.sulu.getUserSetting(SHOW_GHOST_PAGES_KEY),
                    toggler = 'toggler-on',
                    columnButtons = {};

                if (showGhostPages !== null) {
                    toggler = (!!JSON.parse(showGhostPages) ? 'toggler-on' : 'toggler');
                }

                columnButtons[toggler] = {
                    options: {
                        title: 'content.contents.show-ghost-pages'
                    }
                };

                header = {
                    noBack: true,
                    toolbar: {
                        buttons: columnButtons,
                        languageChanger: {
                            data: this.localizations,
                            preSelected: this.options.language
                        }
                    }
                };
            } else {
                // object to array
                for (var i in this.data.concreteLanguages) {
                    if (this.data.concreteLanguages.hasOwnProperty(i)) {
                        concreteLanguages.push(this.data.concreteLanguages[i]);
                    }
                }

                // add create new page flag for not existing pages
                for (i = 0, length = this.localizations.length; i < length; i++) {
                    dropdownLocalizations[i] = {
                        id: this.localizations[i].id,
                        title: this.localizations[i].title
                    };
                }

                navigationUrl = '/admin/content-navigations';
                navigationUrlParams.push('alias=content');

                if (!!this.data.id) {
                    navigationUrlParams.push('id=' + this.data.id);
                }

                if (!!this.options.webspace) {
                    navigationUrlParams.push('webspace=' + this.options.webspace);
                }

                if (!!this.options.language) {
                    navigationUrlParams.push('locale=' + this.options.language);
                }

                if (!!navigationUrlParams.length) {
                    navigationUrl += '?' + navigationUrlParams.join('&');
                }

                var buttons = {}, editDropdown = {}, saveDropdown = {};

                if (SecurityChecker.hasPermission(this.data, 'edit')) {
                    saveDropdown.saveDraft = {};

                    if (SecurityChecker.hasPermission(this.data, 'live')) {
                        saveDropdown.savePublish = {};
                        saveDropdown.publish = {};
                    }

                    if (!!config.has('sulu_automation.enabled')) {
                        saveDropdown.automationInfo = {
                            options: {
                                entityId: this.options.id,
                                entityClass: 'Sulu\\Bundle\\ContentBundle\\Document\\BasePageDocument',
                                handlerClass: [
                                    'Sulu\\Bundle\\AutomationBundle\\Handler\\DocumentPublishHandler',
                                    'Sulu\\Bundle\\AutomationBundle\\Handler\\DocumentUnpublishHandler'
                                ]
                            }
                        };
                    }

                    buttons.save = {
                        options: {
                            icon: 'floppy-o',
                            title: 'public.save',
                            disabled: true,
                            callback: function() {
                                this.sandbox.emit('sulu.toolbar.save', 'publish');
                            }.bind(this),
                            dropdownItems: saveDropdown
                        }
                    };

                    buttons.template = {
                        options: {
                            dropdownOptions: {
                                url: '/admin/content/template?webspace=' + this.options.webspace,
                                callback: function(item) {
                                    this.template = item.template;
                                    this.sandbox.emit('sulu.dropdown.template.item-clicked', item);
                                }.bind(this)
                            }
                        }
                    };
                }

                if (SecurityChecker.hasPermission(this.data, 'live') && !isHomeDocument(this.data)) {
                    editDropdown.unpublish = {
                        options: {
                            title: this.sandbox.translate('sulu-document-manager.unpublish'),
                            disabled: !this.data.published,
                            callback: this.unpublish.bind(this)
                        }
                    };

                    editDropdown.divider = {
                        options: {
                            divider: true
                        }
                    };
                }

                if (SecurityChecker.hasPermission(this.data, 'delete') && !isHomeDocument(this.data)) {
                    editDropdown.delete = {
                        options: {
                            disabled: !this.data.id,
                            callback: function() {
                                this.sandbox.emit('sulu.content.content.delete', this.data.id);
                            }.bind(this)
                        }
                    };
                }

                if (SecurityChecker.hasPermission(this.data, 'edit')) {
                    editDropdown.copyLocale = {
                        options: {
                            title: this.sandbox.translate('toolbar.copy-locale'),
                            disabled: !this.data.id,
                            callback: function() {
                                CopyLocale.startCopyLocalesOverlay.call(this).then(function(newLocales) {
                                    this.content.attributes.concreteLanguages = _.uniq(this.data.concreteLanguages.concat(newLocales));
                                    this.data = this.content.toJSON();
                                    this.sandbox.emit('sulu.labels.success.show', 'labels.success.copy-locale-desc', 'labels.success');
                                }.bind(this));
                            }.bind(this)
                        }
                    };
                }

                if (!this.sandbox.util.isEmpty(editDropdown)) {
                    buttons.edit = {
                        options: {
                            dropdownItems: editDropdown
                        }
                    };
                }

                buttons.statePublished = {};
                buttons.stateTest = {};

                header = {
                    noBack: isHomeDocument(this.data),

                    tabs: {
                        url: navigationUrl,
                        options: {
                            locale: this.options.language,
                            data: function() {
                                return this.sandbox.util.deepCopy(this.content.toJSON());
                            }.bind(this)
                        },
                        componentOptions: {
                            values: this.content.toJSON(),
                            previewService: this.preview
                        }
                    },

                    title: function() {
                        return this.data.title;
                    }.bind(this),

                    toolbar: {
                        languageChanger: {
                            data: dropdownLocalizations,
                            preSelected: this.options.language
                        },

                        buttons: buttons
                    }
                };
            }

            return header;
        },

        unpublish: function() {
            this.sandbox.sulu.showConfirmationDialog({
                callback: function(wasConfirmed) {
                    if (!wasConfirmed) {
                        return;
                    }

                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'edit');
                    ContentManager.unpublish(this.data.id, this.options.language)
                        .always(function() {
                            this.sandbox.emit('sulu.header.toolbar.item.enable', 'edit');
                        }.bind(this))
                        .then(function(response) {
                            this.sandbox.emit(
                                'sulu.labels.success.show',
                                'labels.success.content-unpublish-desc',
                                'labels.success'
                            );
                            this.sandbox.emit('sulu.content.contents.saved', response.id, response);
                        }.bind(this))
                        .fail(function() {
                            this.sandbox.emit(
                                'sulu.labels.error.show',
                                'labels.error.content-unpublish-desc',
                                'labels.error'
                            );
                        }.bind(this));
                }.bind(this),
                title: translationKeys.unpublishConfirmTitle,
                description: !!hasDraft(this.data) ?
                    translationKeys.unpublishConfirmTextNoDraft :
                    translationKeys.unpublishConfirmTextWithDraft
            });
        },

        showSaveItems: function(state) {
            var allItems = ['saveDraft', 'savePublish', 'publish', 'saveOnly'], hiddenItems, shownItems;

            if (!state) {
                state = 'content';
            }

            switch (state) {
                case 'content':
                    hiddenItems = [];
                    break;
                case 'shadow':
                case 'permissions':
                    hiddenItems = ['saveDraft', 'savePublish', 'publish'];
                    break;
            }

            shownItems = _.difference(allItems, hiddenItems);

            this.sandbox.util.each(shownItems, function(index, shownItem) {
                this.sandbox.emit('sulu.header.toolbar.item.show', shownItem);
            }.bind(this));

            this.sandbox.util.each(hiddenItems, function(index, hiddenItem) {
                this.sandbox.emit('sulu.header.toolbar.item.hide', hiddenItem);
            }.bind(this));
        },

        showState: function(published) {
            if (!!published) {
                this.sandbox.emit('sulu.header.toolbar.item.hide', 'stateTest');
                this.sandbox.emit('sulu.header.toolbar.item.show', 'statePublished');
            } else {
                this.sandbox.emit('sulu.header.toolbar.item.hide', 'statePublished');
                this.sandbox.emit('sulu.header.toolbar.item.show', 'stateTest');
            }
        },

        showDraftLabel: function() {
            this.sandbox.emit('sulu.header.tabs.label.hide');

            if (hasDraft(this.data)) {
                return;
            }

            UserManager.find(this.data.changer).then(function(response) {
                this.sandbox.emit(
                    'sulu.header.tabs.label.show',
                    this.sandbox.util.sprintf(
                        this.sandbox.translate(translationKeys.draftLabel),
                        {
                            changed: this.sandbox.date.format(this.data.changed, true),
                            user: response.username
                        }
                    ),
                    [
                        {
                            id: 'delete-draft',
                            title: this.sandbox.translate(translationKeys.removeDraft),
                            skin: 'critical',
                            onClick: this.deleteDraft.bind(this)
                        }
                    ]
                );
            }.bind(this));
        },

        deleteDraft: function() {
            this.sandbox.sulu.showDeleteDialog(
                function(wasConfirmed) {
                    if (!wasConfirmed) {
                        return;
                    }

                    this.sandbox.emit('husky.label.header.loading');

                    ContentManager.removeDraft(this.data.id, this.options.language)
                        .then(function(response) {
                            this.sandbox.emit(
                                'sulu.router.navigate',
                                this.sandbox.mvc.history.fragment,
                                true,
                                true
                            );
                            this.sandbox.emit('sulu.content.contents.saved', response.id, response);
                        }.bind(this))
                        .fail(function() {
                            this.sandbox.emit('husky.label.header.reset');
                            this.sandbox.emit(
                                'sulu.labels.error.show',
                                'labels.error.remove-draft-desc',
                                'labels.error'
                            );
                        }.bind(this))
                }.bind(this),
                this.sandbox.translate(translationKeys.deleteDraftConfirmTitle),
                this.sandbox.translate(translationKeys.deleteDraftConfirmText)
            )
        },

        layout: function() {
            if (this.options.display === 'column') {
                return {};
            } else {
                return {
                    navigation: {
                        collapsed: true
                    },
                    content: {
                        shrinkable: !!this.options.preview
                    },
                    sidebar: (!!this.options.preview) ? 'max' : false
                };
            }
        },
        destroy: function() {
            if (!!this.preview) {
                Preview.destroy(this.preview);
            }
        }
    };
});
