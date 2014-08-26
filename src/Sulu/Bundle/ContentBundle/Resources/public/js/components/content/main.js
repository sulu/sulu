/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'sulucontent/model/content',
    'sulucontent/components/content/preview/main'
], function(Content, Preview) {

    'use strict';

    var CONTENT_LANGUAGE = 'contentLanguage',

        /**
         * node type constant for content
         * @type {number}
         */
        TYPE_CONTENT = 1,

        constants = {
            resolutionDropdownData: [
                {id: 1, name: 'sulu.preview.auto', cssClass: 'auto'},
                {id: 2, name: 'sulu.preview.desktop', cssClass: 'desktop'},
                {id: 3, name: 'sulu.preview.tablet', cssClass: 'tablet'},
                {id: 4, name: 'sulu.preview.smartphone', cssClass: 'smartphone'}
            ]
        },

        templates = {
            preview: [
                    '<div class="sulu-content-preview ' + constants.resolutionDropdownData[0].cssClass + '">',
                '   <div class="wrapper">',
                '       <div class="viewport">',
                '           <iframe src="<%= url %>"></iframe>',
                '       </div>',
                '   </div>',
                '</div>',
                '<div id="preview-toolbar" class="sulu-preview-toolbar">',
                '    <div id="preview-toolbar-right" class="right">',
                '       <div id="preview-toolbar-new-window" class="new-window pull-right pointer"><span class="fa-external-link"></span></div>',
                '       <div id="preview-toolbar-resolutions" class="resolutions pull-right pointer">',
                '           <label class="drop-down-trigger">',
                '               <span class="dropdown-label"><%= resolution %></span>',
                '               <span class="dropdown-toggle"></span>',
                '           </label>',
                '       </div>',
                '   </div>',
                '</div>'
            ].join(''),
            previewUrl: '<%= url %><%= uuid %>/render?webspace=<%= webspace %>&language=<%= language %>'
        };

    return {

        initialize: function() {
            // init vars
            this.saved = true;
            this.previewUrl = null;
            this.previewWindow = null;
            this.$preview = null;
            this.contentChanged = false;

            if (this.options.display === 'column') {
                this.renderColumn();
            } else {
                this.loadData();
            }
            this.bindCustomEvents();
        },

        renderColumn: function() {
            var $column = this.sandbox.dom.createElement('<div id="content-column-container"/>');
            this.html($column);
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

        loadData: function() {
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
                            this.render(content.toJSON());
                            this.loadDataDeferred.resolve();
                        }.bind(this)
                    }
                );
            } else {
                this.render(this.content.toJSON());
                this.loadDataDeferred.resolve();
            }
        },

        bindCustomEvents: function() {
            // back button
            this.sandbox.on('sulu.header.back', function() {
                this.sandbox.emit('sulu.content.contents.list');
            }.bind(this));

            // load column view
            this.sandbox.on('sulu.content.contents.list', function(webspace, language) {
                this.sandbox.emit('sulu.app.ui.reset', { navigation: 'auto', content: 'auto'});
                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + (!webspace ? this.options.webspace : webspace) + '/' + (!language ? this.options.language : language));
            }, this);

            // getter for content data
            this.sandbox.on('sulu.content.contents.get-data', function(callback) {
                this.loadDataDeferred.then(function() {
                    callback(this.data);
                }.bind(this));
            }.bind(this));

            // setter for header bar buttons
            this.sandbox.on('sulu.content.contents.set-header-bar', function(saved) {
                this.setHeaderBar(saved);
            }.bind(this));

            // setter for state bar buttons
            this.sandbox.on('sulu.content.contents.set-state', function(data) {
                this.setState(data);
            }.bind(this));

            // change language
            this.sandbox.on('sulu.header.toolbar.language-changed', function(item) {
                this.sandbox.sulu.saveUserSetting(CONTENT_LANGUAGE, item.localization);
                if (this.options.display !== 'column') {
                    this.sandbox.emit('sulu.content.contents.load', this.content.toJSON(), this.options.webspace, item.localization);
                } else {
                    this.sandbox.emit('sulu.content.contents.list', this.options.webspace, item.localization);
                }
            }, this);

            // change template
            this.sandbox.on('sulu.dropdown.template.item-clicked', function() {
                this.setHeaderBar(false);
            }.bind(this));

            // content delete
            this.sandbox.on('sulu.header.toolbar.delete', function() {
                this.sandbox.emit('sulu.content.content.delete', this.data.id);
            }, this);

            // content saved
            this.sandbox.on('sulu.content.contents.saved', function(id, data) {
                this.highlightSaveButton = true;

                this.data = data;
                this.setHeaderBar(true);
                this.setTitle(this.data);

                this.sandbox.emit('sulu.labels.success.show', 'labels.success.content-save-desc', 'labels.success');
            }, this);

            // content save-error
            this.sandbox.on('sulu.content.contents.save-error', function() {
                this.sandbox.emit('sulu.labels.error.show', 'labels.error.content-save-desc', 'labels.error');
                this.setHeaderBar(false);
            }, this);

            // content delete
            this.sandbox.on('sulu.preview.delete', function() {
                this.sandbox.emit('sulu.content.content.delete', this.data.id);
            }, this);

            // set default template
            this.sandbox.on('sulu.content.contents.default-template', function(name) {
                this.template = name;
                if (this.data.nodeType !== TYPE_CONTENT) {
                    this.sandbox.emit('sulu.header.toolbar.item.change', 'template', name);
                    if (this.hiddenTemplate) {
                        this.hiddenTemplate = false;
                        this.sandbox.emit('sulu.header.toolbar.item.show', 'template', name);
                    }
                }
            }, this);

            // expand navigation if navigation item is clicked
            this.sandbox.on('husky.navigation.item.select', function(event) {
                // when navigation item is already opended do nothing - relevant for homepage
                if (event.id !== this.options.id) {
                    this.sandbox.emit('sulu.app.ui.reset', { navigation: 'auto', content: 'auto'});
                }
            }.bind(this));

            // get changed state
            this.sandbox.on('sulu.dropdown.state.item-clicked', function(state) {
                if (this.state !== state) {
                    this.state = state;
                    this.setHeaderBar(false);
                }
            }.bind(this));

            // change url of preview
            this.sandbox.on('sulu.content.preview.change-url', this.changePreviewUrl.bind(this));

            // change the preview style if the resolution dropdown gets changed
            this.sandbox.on('husky.dropdown.resolutionsDropdown.item.click', this.changePreviewStyle.bind(this));

            // bind model data events
            this.bindModelEvents();
        },

        bindModelEvents: function() {
            // delete content
            this.sandbox.on('sulu.content.content.delete', function(id) {
                this.del(id);
            }, this);

            // save the current package
            this.sandbox.on('sulu.content.contents.save', function(data) {
                this.save(data).then(function() {
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

            // move selected content
            this.sandbox.on('sulu.content.contents.copy', this.copy, this);

            // order selected content
            this.sandbox.on('sulu.content.contents.order', this.order, this);

            // get resource locator
            this.sandbox.once('sulu.content.contents.get-rl', function(title, callback) {
                this.getResourceLocator(title, this.template, callback);
            }, this);

            // load list view
            this.sandbox.on('sulu.content.contents.list', function(webspace, language) {
                this.sandbox.emit('sulu.app.ui.reset', { navigation: 'auto', content: 'auto'});
                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + (!webspace ? this.options.webspace : webspace) + '/' + (!language ? this.options.language : language));
            }, this);
        },

        getResourceLocator: function(parts, template, callback) {
            var url = '/admin/api/nodes/resourcelocators/generates?' + (!!this.options.parent ? 'parent=' + this.options.parent + '&' : '') + (!!this.options.id ? 'uuid=' + this.options.id + '&' : '') + '&webspace=' + this.options.webspace + '&language=' + this.options.language + '&template=' + template;
            this.sandbox.util.save(url, 'POST', {parts: parts})
                .then(function(data) {
                    callback(data.resourceLocator);
                });
        },

        move: function(id, parentId, successCallback, errorCallback) {
            var url = [
                '/admin/api/nodes/', id, '?webspace=', this.options.webspace, '&language=' , this.options.language , '&action=move&destination=', parentId
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
                '/admin/api/nodes/', id, '?webspace=', this.options.webspace, '&language=' , this.options.language , '&action=copy&destination=', parentId
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

        order: function(id, parentId, successCallback, errorCallback) {
            var url = [
                '/admin/api/nodes/', id, '?webspace=', this.options.webspace, '&language=' , this.options.language , '&action=order&destination=', parentId
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

        del: function(id) {
            this.showConfirmSingleDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'options-button');
                    if (!this.content || id !== this.content.get('id')) {
                        var content = new Content({id: id});
                        content.fullDestroy(this.options.webspace, this.options.language, {
                            processData: true,

                            success: function() {
                                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language);
                                this.sandbox.emit('sulu.preview.deleted', id);
                            }.bind(this)
                        });
                    } else {
                        this.content.fullDestroy(this.options.webspace, this.options.language, {
                            processData: true,

                            success: function() {
                                this.sandbox.emit('sulu.app.ui.reset', { navigation: 'auto', content: 'auto'});

                                this.sandbox.sulu.unlockDeleteSuccessLabel();
                                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language);
                                this.sandbox.emit('sulu.preview.deleted', id);
                            }.bind(this)
                        });
                    }
                }
            }.bind(this), this.options.id);
        },

        delContents: function(ids) {
            this.confirmDeleteDialog(function(wasConfirmed) {
                if (wasConfirmed) {
                    // TODO: show loading icon
                    ids.forEach(function(id) {
                        var content = new Content({id: id});
                        content.fullDestroy(this.options.webspace, this.options.language, {
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

        showConfirmSingleDeleteDialog: function(callbackFunction) {
            // check if callback is a function
            if (!!callbackFunction && typeof(callbackFunction) !== 'function') {
                throw 'callback is not a function';
            }

            // show warning dialog
            this.sandbox.emit('sulu.overlay.show-warning',
                'sulu.overlay.be-careful',
                'sulu.overlay.delete-desc',

                function() {
                    // cancel callback
                    callbackFunction(false);
                }.bind(this),

                function() {
                    // ok callback
                    callbackFunction(true);
                }.bind(this)
            );
        },

        changeState: function(state) {
            this.sandbox.emit('sulu.content.contents.state.change');

            this.content.stateSave(this.options.webspace, this.options.language, state, null, {
                success: function() {
                    this.sandbox.emit('sulu.content.contents.state.changed', state);
                    this.sandbox.emit('sulu.labels.success.show',
                        'labels.state-changed.success-desc',
                        'labels.success',
                        'sulu.content.contents.state.label');
                }.bind(this),
                error: function() {
                    this.sandbox.emit('sulu.content.contents.state.changeFailed');
                    this.sandbox.emit('sulu.labels.error.show',
                        'labels.state-changed.error-desc',
                        'labels.error',
                        'sulu.content.contents.state.label');
                    this.sandbox.logger.log("error while saving profile");
                }.bind(this)
            });
        },

        save: function(data) {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save-button');

            var def = this.sandbox.data.deferred();

            if (!!this.content) {
                this.content.set(data);
            } else {
                this.content = new Content(data);
            }
            if (!!this.options.id) {
                this.content.set({id: this.options.id});
            }
            this.content.fullSave(this.template, this.options.webspace, this.options.language, this.options.parent, this.state, null, {
                // on success save contents id
                success: function(response) {
                    var model = response.toJSON();
                    if (!!this.options.id) {
                        this.sandbox.emit('sulu.content.contents.saved', model.id, model);
                    } else {
                        this.sandbox.sulu.viewStates.justSaved = true;
                        this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language + '/edit:' + model.id + '/content');
                    }
                    def.resolve();
                }.bind(this),
                error: function() {
                    this.sandbox.logger.log("error while saving profile");
                    this.sandbox.emit('sulu.content.contents.save-error');
                }.bind(this)
            });

            return def;
        },

        load: function(item, webspace, language) {
            var action = 'content';
            if (
                (!!item.nodeType && item.nodeType !== TYPE_CONTENT) ||
                (!!item.type && !!item.type.name && item.type.name === 'shadow')
                ) {
                action = 'settings';
            }

            this.sandbox.emit('sulu.router.navigate', 'content/contents/' + (!webspace ? this.options.webspace : webspace) + '/' + (!language ? this.options.language : language) + '/edit:' + item.id + '/' + action);
        },

        add: function(parent) {
            if (!!parent) {
                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language + '/add:' + parent.id + '/content');
            } else {
                this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language + '/add/content');
            }
        },

        render: function(data) {
            this.data = data;
            this.headerInitialized.then(function() {
                this.setTitle(data);
                this.setBreadcrumb(data);
                this.setTemplate(data);
                this.setState(data);

                // disable remove for homepage
                if (this.options.id === 'index') {
                    this.sandbox.emit('husky.toolbar.header.item.disable', 'options-button', false);
                }


                if (!!this.options.preview && this.data.nodeType === TYPE_CONTENT && !this.data.shadowOn) {
                    this.sandbox.emit('husky.tabs.header.item.show', 'tab-content');
                    this.sandbox.on('sulu.preview.initiated', function() {
                        this.renderPreview(data);
                    }.bind(this));

                    this.sandbox.on('sulu.preview.initialize', function(data) {
                        data = this.sandbox.util.extend(true, {}, this.data, data);
                        Preview.initialize(this.sandbox, this.options, data, this.$el);
                    }.bind(this));
                } else {
                    this.sandbox.emit('sulu.sidebar.hide');
                    this.sandbox.emit('sulu.app.toggle-shrinker', false);
                }

                if (!!this.options.id) {
                    // disable content tab
                    if (this.data.shadowOn === true || this.data.nodeType !== TYPE_CONTENT) {
                        this.sandbox.emit('husky.tabs.header.item.hide', 'tab-content');
                    }

                    // route to settings
                    if (
                        (this.options.content !== 'settings' && this.data.shadowOn === true) ||
                        (this.options.content === 'content' && this.data.nodeType !== TYPE_CONTENT)
                        ) {
                        this.sandbox.emit('sulu.router.navigate', 'content/contents/' + this.options.webspace + '/' + this.options.language + '/edit:' + data.id + '/settings');
                    }
                }

                this.setHeaderBar(true);
            }.bind(this));
        },

        /**
         * Render preview for loaded content node
         * @param data
         */
        renderPreview: function(data) {
            this.sandbox.emit('sulu.app.toggle-shrinker', true);
            this.sandbox.emit('sulu.sidebar.change-width', 'max');
            if (this.$preview === null) {
                this.previewUrl = this.sandbox.util.template(templates.previewUrl, {
                    url: '/admin/content/preview/',
                    webspace: this.options.webspace,
                    language: this.options.language,
                    uuid: data.id
                });
                this.$preview = this.sandbox.dom.createElement(this.sandbox.util.template(templates.preview, {
                    resolution: this.sandbox.translate('content.preview.resolutions'),
                    url: this.previewUrl
                }));
                this.bindPreviewDomEvents();
                this.startPreviewResolutionDropdown();
                this.sandbox.emit('sulu.sidebar.set-widget', null, this.$preview);
            }
        },

        /**
         * Starts the resolution dropdown for the preview
         */
        startPreviewResolutionDropdown: function() {
            this.sandbox.start([
                {
                    name: 'dropdown@husky',
                    options: {
                        el: this.sandbox.dom.find('#preview-toolbar-resolutions', this.$preview),
                        trigger: '.drop-down-trigger',
                        setParentDropDown: true,
                        instanceName: 'resolutionsDropdown',
                        alignment: 'left',
                        data: constants.resolutionDropdownData
                    }
                }
            ]);
        },

        /**
         * Binds Dom-related events on the preview
         */
        bindPreviewDomEvents: function() {
            this.sandbox.dom.on(this.sandbox.dom.find('#preview-toolbar-new-window', this.$preview),
                'click', this.openPreviewInNewWindow.bind(this));
        },

        /**
         * Changes the url of the preview
         * @param params {Object} object with parameteres like webspace language etc.
         */
        changePreviewUrl: function(params) {
            if (this.$preview !== null && !!this.content) {
                var $iframe,
                    content = this.content.toJSON(),
                    defaults = {
                        url: '/admin/content/preview/',
                        webspace: this.options.webspace,
                        language: this.options.language,
                        uuid: content.id,
                        template: content.template
                    };
                defaults = this.sandbox.util.extend(true, {}, defaults, params);
                this.previewUrl = this.sandbox.util.template(templates.previewUrl)(defaults);
                $iframe = this.sandbox.dom.find('iframe', this.$preview);
                this.sandbox.dom.attr($iframe, 'src', this.previewUrl);
                if (!!this.previewWindow && this.previewWindow.closed === false) {
                    this.previewWindow.close();
                }
            }
        },

        /**
         * Changes the style of the the preview. E.g. from desktop to smartphone
         * @param newStyle {Object} the new style object. Has to have a cssClass property
         */
        changePreviewStyle: function(newStyle) {
            if (this.$preview !== null) {
                var $container = this.$preview[0],
                    $toolbar = this.$preview[1];
                // remove all styles
                this.sandbox.util.foreach(constants.resolutionDropdownData, function(style) {
                    this.sandbox.dom.removeClass($container, style.cssClass);
                }.bind(this));
                this.sandbox.dom.addClass($container, newStyle.cssClass);
                this.sandbox.dom.html(this.sandbox.dom.find('.dropdown-label', $toolbar), this.sandbox.translate(newStyle.name));
            }
        },

        /**
         * Hides the sidebar and opens a new window with the preview in it
         */
        openPreviewInNewWindow: function() {
            this.sandbox.emit('sulu.app.toggle-shrinker', false);
            this.sandbox.emit('sulu.app.change-width', 'fixed');
            this.sandbox.emit('husky.navigation.show');
            this.sandbox.emit('sulu.sidebar.hide');
            this.previewWindow = window.open(this.previewUrl);
            this.previewWindow.onload = function() {
                this.previewWindow.onunload = function() {
                    this.sandbox.emit('sulu.app.toggle-shrinker', true);
                    this.sandbox.emit('sulu.sidebar.change-width', 'max');
                }.bind(this);
            }.bind(this);
        },

        /**
         * Sets template to header
         * @param {Object} data
         */
        setTemplate: function(data) {
            this.template = data.originTemplate;

            if (this.data.nodeType === TYPE_CONTENT && this.template !== '' && this.template !== undefined && this.template !== null) {
                this.sandbox.emit('sulu.header.toolbar.item.change', 'template', this.template);
                this.sandbox.emit('sulu.header.toolbar.item.show', 'template');
            }
        },

        /**
         * Sets state to header
         * @param {Object} data
         */
        setState: function(data) {
            this.state = data.nodeState;

            if (this.state !== '' && this.state !== undefined && this.state !== null) {
                this.sandbox.emit('sulu.header.toolbar.item.change', 'state', data.nodeState);
            }
        },

        /**
         * Sets the title of the page and if in edit mode calls a method to set the breadcrumb
         * @param {Object} data
         */
        setTitle: function(data) {
            if (!!this.options.id && data['sulu.node.name'] !== '') {
                this.sandbox.emit('sulu.header.set-title', data['sulu.node.name']);
            } else {
                this.sandbox.emit('sulu.header.set-title', this.sandbox.translate('content.contents.title'));
            }
        },

        /**
         * Sets the breadcrump of the selected node
         * @param data
         */
        setBreadcrumb: function(data) {
            if (!!data.breadcrumb) {
                var breadcrumb = [
                    {
                        title: this.options.webspace.replace(/_/g, '.'),
                        event: 'sulu.content.contents.list'
                    }
                ], length, i;

                // loop through breadcrumb skip home-page
                for (i = 0, length = data.breadcrumb.length; ++i < length;) {
                    breadcrumb.push({
                        title: data.breadcrumb[i].title,
                        link: this.getBreadcrumbRoute(data.breadcrumb[i].uuid)
                    });
                }

                this.sandbox.emit('sulu.header.set-breadcrumb', breadcrumb);
            }
        },

        /**
         * Returns routes for the breadcrumbs. Replaces the current uuid with a passed one in the active URI
         * @param uuid {string} uuid to replace the current one with
         * @returns {string} the route for the breadcrumb
         */
        getBreadcrumbRoute: function(uuid) {
            return this.sandbox.mvc.history.fragment.replace(this.options.id, uuid);
        },

        /**
         * Sets header bar
         * @param {Boolean} saved
         */
        setHeaderBar: function(saved) {
            if (saved !== this.saved) {
                var type = (!!this.data && !!this.data.id) ? 'edit' : 'add';
                this.sandbox.emit('sulu.header.toolbar.state.change', type, saved, this.highlightSaveButton);
                this.sandbox.emit('sulu.preview.state.change', saved);
            }
            this.saved = saved;
            if (this.saved) {
                this.contentChanged = false;
            }
        },

        header: function() {
            // because it is called first
            this.headerInitialized = this.sandbox.data.deferred();
            this.loadDataDeferred = this.sandbox.data.deferred();

            this.sandbox.once('sulu.header.initialized', function() {
                this.headerInitialized.resolve();
            }.bind(this));

            var noBack = (this.options.id === 'index'), def;

            if (this.options.display === 'column') {
                return {
                    title: this.options.webspace.replace(/_/g, '.'),
                    noBack: true,
                    breadcrumb: [
                        {title: this.options.webspace.replace(/_/g, '.')}
                    ],
                    toolbar: {
                        template: [],
                        languageChanger: {
                            url: '/admin/content/languages/' + this.options.webspace,
                            preSelected: this.options.language
                        }
                    }
                };
            } else {
                def = this.sandbox.data.deferred();
                this.loadDataDeferred.then(function() {
                    var url = '/admin/content/navigation/content' + (!!this.data.id ? '?id=' + this.data.id : ''),
                        x = {
                            noBack: noBack,

                            tabs: {
                                url: url
                            },

                            toolbar: {
                                parentTemplate: 'default',

                                languageChanger: {
                                    url: '/admin/content/languages/' + this.options.webspace,
                                    preSelected: this.options.language
                                },

                                template: [
                                    {
                                        'id': 'state',
                                        'group': 'left',
                                        'position': 100,
                                        'type': 'select',
                                        items: [
                                            {
                                                'id': 2,
                                                'title': this.sandbox.translate('toolbar.state-publish'),
                                                'icon': 'husky-publish',
                                                'callback': function() {
                                                    this.sandbox.emit('sulu.dropdown.state.item-clicked', 2);
                                                }.bind(this)
                                            },
                                            {
                                                'id': 1,
                                                'title': this.sandbox.translate('toolbar.state-test'),
                                                'icon': 'husky-test',
                                                'callback': function() {
                                                    this.sandbox.emit('sulu.dropdown.state.item-clicked', 1);
                                                }.bind(this)
                                            }
                                        ]
                                    },
                                    {
                                        id: 'template',
                                        icon: 'pencil',
                                        iconSize: 'large',
                                        group: 'left',
                                        position: 10,
                                        type: 'select',
                                        title: '',
                                        hidden: false,
                                        itemsOption: {
                                            url: '/admin/content/template',
                                            titleAttribute: 'title',
                                            idAttribute: 'template',
                                            translate: false,
                                            callback: function(item) {
                                                this.template = item.template;
                                                this.sandbox.emit('sulu.dropdown.template.item-clicked', item);
                                            }.bind(this)
                                        }
                                    }
                                ]
                            }
                        };
                    def.resolveWith(this, [x]);
                }.bind(this));

                return def;
            }
        },

        layout: function() {
            if (this.options.display === 'column') {
                return {
                    content: {
                        width: 'max',
                        leftSpace: false,
                        rightSpace: false,
                        topSpace: false
                    }
                };
            } else {
                var sidebar = {
                    width: 'max',
                    cssClasses: 'dark-border'
                };
                if (!this.options.preview) {
                    sidebar = false;
                }
                return {
                    navigation: {
                        collapsed: true
                    },
                    content: {
                        width: 'fixed',
                        shrinkable: (!!this.options.preview) ? true : false
                    },
                    sidebar: sidebar
                };
            }
        }
    };
});
