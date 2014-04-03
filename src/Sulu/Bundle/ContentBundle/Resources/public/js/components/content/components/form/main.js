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

    return {

        view: true,

        templates: ['/admin/content/template/content/settings'],

        // if ws != null then use it
        ws: null,
        wsUrl: '',
        wsPort: '',
        previewInitiated: false,
        opened: false,
        template: '',

        templateChanged: false,
        contentChanged: false,

        hiddenTemplate: true,

        initialize: function() {
            this.saved = true;
            this.highlightSaveButton = this.sandbox.sulu.viewStates.justSaved;
            delete this.sandbox.sulu.viewStates.justSaved;

            this.state = null;
            this.dfdListenForChange = this.sandbox.data.deferred();

            this.formId = '#contacts-form-container';

            this.render();
            this.setTitle();

            this.setHeaderBar(true);
        },

        render: function() {
            this.bindCustomEvents();

            if (this.options.tab.content === true) {
                this.renderContent();
            } else if (this.options.tab.settings === true) {
                this.renderSettings();
            }
        },

        renderContent: function() {
            if (!!this.options.data.template) {
                this.changeTemplate(this.options.data.template);
            } else {
                this.changeTemplate();
            }
        },

        showStateDropdown: function() {
            this.sandbox.emit('sulu.edit-toolbar.content.item.enable', 'state', false);
        },

        renderSettings: function() {
            this.setHeaderBar(false);

            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/content/template/content/settings'));
            this.createForm(this.initData());
            this.bindDomEvents();
            this.listenForChange();

            // enable state button
            this.setStateDropdown(this.options.data);
            this.showStateDropdown();

            // set current template and enable button
            this.template = (this.template !== '') ? this.template : this.options.data.template;
            this.changeTemplateDropdownHandler();
        },

        setStateDropdown: function(data) {
            this.state = data.nodeState || 0;

            // get the dropdownds
            this.sandbox.emit('sulu.content.contents.getDropdownForState', this.state, function(items) {
                if (items.length > 0) {
                    this.sandbox.emit('sulu.edit-toolbar.content.items.set', 'state', items);
                }
            }.bind(this));

            // set the current state
            this.sandbox.emit('sulu.content.contents.getStateDropdownItem', this.state, function(item) {
                this.sandbox.emit('sulu.edit-toolbar.content.button.set', 'state', item);
            }.bind(this));
        },


        /**
         * Sets the title of the page and if in edit mode calls a method to set the breadcrumb
         */
        setTitle: function() {
            if (!!this.options.id && !!this.options.data.title) {
                this.sandbox.emit('sulu.content.set-title', this.options.data.title);
                this.setBreadcrumb();
            } else {
                this.sandbox.emit('sulu.content.set-title', this.sandbox.translate('content.contents.title'));
            }
        },

        /**
         * Generates the Breadcrumb-string and sets it for the title-additon
         */
        setBreadcrumb: function() {
            var breadcrumb = this.options.webspace.replace(/_/g, '.');
            if (!!this.options.data.breadcrumb) {
                // loop through breadcrumb skip home-page
                for (var i = 0, length = this.options.data.breadcrumb.length; ++i < length;) {
                    breadcrumb += ' &#187; ' + this.options.data.breadcrumb[i].title;
                }
            }
            this.sandbox.emit('sulu.content.set-title-addition', breadcrumb);
        },

        createForm: function(data) {
            var formObject = this.sandbox.form.create(this.formId);
            formObject.initialized.then(function() {
                this.setFormData(data).then(function() {

                    this.sandbox.start(this.$el, {reset: true});
                    this.initSortableBlock();
                    this.bindFormEvents();

                    if (!!this.options.preview) {
                        this.initPreview();
                        this.options.preview = false;
                    }
                }.bind(this));
            }.bind(this));
        },

        initSortableBlock: function() {
            var $sortable = this.sandbox.dom.find('.sortable', this.$el),
                sortable;

            if (!!$sortable && $sortable.length > 0) {
                this.sandbox.dom.sortable($sortable, 'destroy');
                sortable = this.sandbox.dom.sortable($sortable, {
                    handle: '.move',
                    forcePlaceholderSize: true
                });

                // (un)bind event listener
                this.sandbox.dom.unbind(sortable, 'sortupdate');

                sortable.bind('sortupdate', function(event, ui) {
                    var changes = this.sandbox.form.getData(this.formId),
                        propertyName = this.sandbox.dom.data(event.currentTarget, 'mapperProperty');

                    this.updatePreview(propertyName, changes[propertyName]);
                }.bind(this));
            }
        },

        bindFormEvents: function() {
            this.sandbox.dom.on(this.formId, 'form-collection-init', function(e, propertyName) {
                this.updatePreview();
            }.bind(this));

            this.sandbox.dom.on(this.formId, 'form-remove', function(e, propertyName) {
                var changes = this.sandbox.form.getData(this.formId);
                this.initSortableBlock();
                this.updatePreview(propertyName, changes[propertyName]);
            }.bind(this));

            this.sandbox.dom.on(this.formId, 'form-add', function(e, propertyName) {
                var changes = this.sandbox.form.getData(this.formId);
                this.initSortableBlock();
                this.updatePreview(propertyName, changes[propertyName]);
            }.bind(this));
        },

        setFormData: function(data) {
            var initialize = this.sandbox.form.setData(this.formId, data);

            if (!!data.id && (data.title === '' || typeof data.title === 'undefined' || data.title === null)) {
                this.sandbox.util.load('/admin/api/nodes/' + data.id + '?webspace=' + this.options.webspace + '&language=' + this.options.language + '&complete=false&ghost-content=true')
                    .then(function(data) {
                        this.sandbox.dom.attr('#title', 'placeholder', data.type.value + ': ' + data.title);
                    }.bind(this));
            }

            this.sandbox.emit('sulu.edit-toolbar.content.item.change', 'language', this.options.language);
            this.sandbox.emit('sulu.edit-toolbar.content.item.show', 'language');

            if (this.options.id === 'index') {
                this.sandbox.dom.remove('#show-in-navigation-container');
            }
            this.sandbox.dom.attr('#show-in-navigation', 'checked', data.navigation);
            return initialize;
        },

        bindDomEvents: function() {
            if (!this.options.data.id || !this.options.data.url) {
                this.sandbox.dom.one('#title', 'focusout', this.setResourceLocator.bind(this));
            } else {
                this.dfdListenForChange.resolve();
            }
        },

        setResourceLocator: function() {
            var title = this.sandbox.dom.val('#title'),
                url = '#url';

            if (title !== '') {
                this.sandbox.dom.addClass(url, 'is-loading');
                this.sandbox.dom.css(url, 'background-position', '99%');

                this.sandbox.emit('sulu.content.contents.getRL', title, function(rl) {
                    this.sandbox.dom.removeClass(url, 'is-loading');
                    this.sandbox.dom.val(url, rl);

                    this.dfdListenForChange.resolve();

                    this.setHeaderBar(false);
                    this.contentChanged = true;
                }.bind(this));
            } else {
                this.sandbox.dom.one('#title', 'focusout', this.setResourceLocator.bind(this));
            }
        },

        bindCustomEvents: function() {
            // content saved
            this.sandbox.on('sulu.content.contents.saved', function() {
                this.highlightSaveButton = true;
                this.setHeaderBar(true);
                this.setTitle();

                this.sandbox.emit('sulu.labels.success.show', 'labels.success.content-save-desc', 'labels.success');
            }, this);

            // content save-error
            this.sandbox.on('sulu.content.contents.save-error', function() {
                this.sandbox.emit('sulu.labels.error.show', 'labels.error.content-save-desc', 'labels.error');
            }, this);

            // content save
            this.sandbox.on('sulu.edit-toolbar.save', function() {
                this.submit();
            }, this);
            this.sandbox.on('sulu.preview.save', function() {
                this.submit();
            }, this);

            // content delete
            this.sandbox.on('sulu.preview.delete', function() {
                this.sandbox.emit('sulu.content.content.delete', this.options.data.id);
            }, this);
            this.sandbox.on('sulu.edit-toolbar.delete', function() {
                this.sandbox.emit('sulu.content.content.delete', this.options.data.id);
            }, this);

            // back to list
            this.sandbox.on('sulu.edit-toolbar.back', function() {
                this.sandbox.emit('sulu.content.contents.list');
            }, this);

            this.sandbox.on('sulu.edit-toolbar.preview.new-window', function() {
                this.openPreviewWindow();
            }, this);

            this.sandbox.on('sulu.edit-toolbar.preview.split-screen', function() {
                this.openSplitScreen();
            }, this);

            // set preview params
            this.sandbox.on('sulu.preview.set-params', function(url, port) {
                this.wsUrl = url;
                this.wsPort = port;
            }, this);

            // set default template
            this.sandbox.on('sulu.content.contents.default-template', function(name) {
                this.template = name;
                this.sandbox.emit('sulu.edit-toolbar.content.item.change', 'template', name);
                if (this.hiddenTemplate) {
                    this.hiddenTemplate = false;
                    this.sandbox.emit('sulu.edit-toolbar.content.item.show', 'template', name);
                }
            }, this);

            // change template
            this.sandbox.on('sulu.edit-toolbar.dropdown.template.item-clicked', function(item) {
                this.sandbox.emit('sulu.edit-toolbar.content.item.loading', 'template');
                this.templateChanged = true;
                this.changeTemplate(item);
            }, this);

            // change language
            this.sandbox.on('sulu.edit-toolbar.dropdown.languages.item-clicked', function(item) {
                this.sandbox.emit('sulu.content.contents.load', this.options.id, this.options.webspace, item.localization);
            }, this);

            // set state button in loading state
            this.sandbox.on('sulu.content.contents.state.change', function() {
                this.sandbox.emit('sulu.edit-toolbar.content.item.loading', 'state');
            }, this);

            // set save button in loading state
            this.sandbox.on('sulu.content.contents.save', function() {
                this.sandbox.emit('sulu.edit-toolbar.content.item.loading', 'save-button');
            }, this);

            // change dropdown if state has changed
            this.sandbox.on('sulu.content.contents.state.changed', function(state) {
                this.state = state;
                //set new dropdown
                this.sandbox.emit('sulu.content.contents.getDropdownForState', this.state, function(items) {
                    this.sandbox.emit('sulu.edit-toolbar.content.items.set', 'state', items, null);
                }.bind(this));
                // set the current state
                this.sandbox.emit('sulu.content.contents.getStateDropdownItem', this.state, function(item) {
                    this.sandbox.emit('sulu.edit-toolbar.content.button.set', 'state', item);
                }.bind(this));
                //enable button with highlight-effect
                this.sandbox.emit('sulu.edit-toolbar.content.item.enable', 'state', true);
            }.bind(this));

            //set button back if state-change failed
            this.sandbox.on('sulu.content.contents.state.changeFailed', function() {
                // set the current state
                this.sandbox.emit('sulu.content.contents.getStateDropdownItem', this.state, function(item) {
                    this.sandbox.emit('sulu.edit-toolbar.content.button.set', 'state', item);
                }.bind(this));
                //enable button without highlight-effect
                this.sandbox.emit('sulu.edit-toolbar.content.item.enable', 'state', false);
            }.bind(this));

            // expand navigation if navigation item is clicked
            this.sandbox.on('husky.navigation.item.select', function() {
                this.sandbox.emit('husky.navigation.collapse');
                this.sandbox.emit('husky.navigation.uncollapse', false);
            }.bind(this));

            // expand navigation if back gets clicked
            this.sandbox.on('sulu.edit-toolbar.back', function() {
                this.sandbox.emit('husky.navigation.collapse');
                this.sandbox.emit('husky.navigation.size.update');
            }.bind(this));
        },

        initData: function() {
            return this.options.data;
        },

        submit: function() {
            this.sandbox.logger.log('save Model');
            var data,
                template = (this.template !== '') ? this.template : this.options.data.template;

            if (this.sandbox.form.validate(this.formId)) {
                data = this.sandbox.form.getData(this.formId);

                if (this.options.id === 'index') {
                    data.navigation = true;
                } else if (!!this.sandbox.dom.find('#show-in-navigation', this.$el).length) {
                    data.navigation = this.sandbox.dom.prop('#show-in-navigation', 'checked');
                }

                this.sandbox.logger.log('data', data);

                this.options.data = this.sandbox.util.extend(true, {}, this.options.data, data);
                this.sandbox.emit('sulu.content.contents.save', data, template);
            }
        },

        changeTemplateDropdownHandler: function(forceNoHighlight) {
            if (typeof forceNoHighlight === 'undefined') {
                forceNoHighlight = false;
            }
            this.sandbox.emit('sulu.edit-toolbar.content.item.change', 'template', this.template);
            this.sandbox.emit('sulu.edit-toolbar.content.item.enable', 'template', (this.templateChanged && !forceNoHighlight));
            if (this.hiddenTemplate) {
                this.hiddenTemplate = false;
                this.sandbox.emit('sulu.edit-toolbar.content.item.show', 'template');
            }
        },

        changeTemplate: function(item) {
            if (typeof item === 'string') {
                item = {template: item};
            }
            if (!!item && this.template === item.template) {
                this.sandbox.emit('sulu.edit-toolbar.content.item.enable', 'template', false);
                return;
            }

            var doIt = function() {
                    if (!!item) {
                        this.template = item.template;
                    }
                    this.setHeaderBar(false);

                    var tmp, url;
                    if (!!this.sandbox.form.getObject(this.formId)) {
                        tmp = this.options.data;
                        this.options.data = this.sandbox.form.getData(this.formId);
                        if (!!tmp.id) {
                            this.options.data.id = tmp.id;
                        }

                        this.options.data = this.sandbox.util.extend({}, tmp, this.options.data);
                    }

                    //only update the tabs-content if the content tab is selected
                    if (this.options.tab.content === true) {
                        url = 'text!/admin/content/template/form';
                        if (!!item) {
                            url += '/' + item.template + '.html';
                        } else {
                            url += '.html';
                        }
                        url += '?webspace=' + this.options.webspace + '&language=' + this.options.language;

                        require([url], function(template) {
                            var defaults = {
                                    translate: this.sandbox.translate
                                },
                                context = this.sandbox.util.extend({}, defaults),
                                tpl = this.sandbox.util.template(template, context),
                                data = this.initData();

                            this.sandbox.dom.remove(this.formId + ' *');
                            this.sandbox.dom.html(this.$el, tpl);
                            this.setStateDropdown(data);
                            this.createForm(data);

                            this.bindDomEvents();
                            this.listenForChange();

                            this.updatePreviewOnly();

                            this.changeTemplateDropdownHandler();
                        }.bind(this));
                    } else {
                        this.changeTemplateDropdownHandler();
                    }
                }.bind(this),
                showDialog = function() {
                    this.sandbox.emit('sulu.dialog.confirmation.show', {
                        content: {
                            title: this.sandbox.translate('content.template.dialog.title'),
                            content: this.sandbox.translate('content.template.dialog.content')
                        },
                        footer: {
                            buttonCancelText: this.sandbox.translate('content.template.dialog.cancel-button'),
                            buttonSubmitText: this.sandbox.translate('content.template.dialog.submit-button')
                        },
                        callback: {
                            submit: function() {
                                this.sandbox.emit('husky.dialog.hide');

                                doIt();
                            }.bind(this),
                            cancel: function() {
                                this.sandbox.emit('husky.dialog.hide');
                                this.changeTemplateDropdownHandler(true);
                            }.bind(this)
                        }
                    }, null);
                }.bind(this);

            if (this.template !== '' && this.contentChanged) {
                showDialog();
            } else {
                doIt();
            }
        },

        // @var Bool saved - defines if saved state should be shown
        setHeaderBar: function(saved) {
            if (saved !== this.saved) {
                var type = (!!this.options.data && !!this.options.data.id) ? 'edit' : 'add';
                this.sandbox.emit('sulu.edit-toolbar.content.state.change', type, saved, this.highlightSaveButton);
                this.sandbox.emit('sulu.preview.state.change', saved);
            }
            this.saved = saved;
            if (this.saved) {
                this.contentChanged = false;
                this.highlightSaveButton = false;
            }
        },

        listenForChange: function() {
            this.dfdListenForChange.then(function() {
                this.sandbox.dom.on(this.formId, 'keyup', function() {
                    this.setHeaderBar(false);
                    this.contentChanged = true;
                }.bind(this), '.trigger-save-button');

                this.sandbox.dom.on(this.formId, 'change', function() {
                    this.setHeaderBar(false);
                    this.contentChanged = true;
                }.bind(this), '.trigger-save-button');

                this.sandbox.on('sulu.content.changed', function() {
                    this.setHeaderBar(false);
                    this.contentChanged = true;
                }.bind(this));
            }.bind(this));
        },

        openPreviewWindow: function() {
            if (!!this.options.data.id) {
                this.initPreview();
                window.open('/admin/content/preview/' + this.options.data.id + '?webspace=' + this.options.webspace + '&language=' + this.options.language);
            }
        },

        openSplitScreen: function() {
            window.open('/admin/content/split-screen/' + this.options.webspace + '/' + this.options.language + '/' + this.options.data.id);
        },

        /**
         * returns true if there is a websocket
         * @returns {boolean}
         */
        wsDetection: function() {
            var support = "MozWebSocket" in window ? 'MozWebSocket' : ("WebSocket" in window ? 'WebSocket' : null);
            // no support
            if (support === null) {
                this.sandbox.logger.log("Your browser doesn't support Websockets.");
                return false;
            }
            // let's invite Firefox to the party.
            if (window.MozWebSocket) {
                window.WebSocket = window.MozWebSocket;
            }
            // support exists
            return true;
        },

        initPreview: function() {
            if (this.wsDetection()) {
                this.initWs();
            } else {
                this.initAjax();
            }
            this.previewInitiated = true;

            this.sandbox.on('sulu.preview.update', function(property, value, changeOnKey) {
                if (!!this.options.data.id) {
                    if (this.ws !== null) {
                        this.updatePreview(property, value);
                    } else if(!changeOnKey) {
                        this.updatePreview(property, value);
                    }
                }
            }, this);
        },

        updateEvent: function(e) {
            if (!!this.options.data.id && !!this.previewInitiated) {
                var $element = $(e.currentTarget),
                    sequence = this.sandbox.dom.data($element, 'mapperProperty'),
                    element = this.sandbox.dom.data($element, 'element'),
                    $parents = $element.parents('*[data-mapper-property]'),
                    item = $element.parents('*[data-mapper-property-tpl]')[0];

                while (!$element.data('element')) {
                    $element = $element.parent();
                }

                if ($parents.length > 0) {
                    sequence = [
                        this.sandbox.dom.data($parents[0], 'mapperProperty')[0].data,
                        $(item).index(),
                        this.sandbox.dom.data($element, 'mapperProperty')
                    ];
                }
                this.updatePreview(sequence, element.getValue());
            }
        },

        initAjax: function() {
            this.sandbox.dom.on(this.formId, 'focusout', this.updateEvent.bind(this), '.preview-update');

            var data = this.sandbox.form.getData(this.formId);

            this.updateAjax(data);
        },

        initWs: function() {
            var url = this.wsUrl + ':' + this.wsPort;
            this.sandbox.logger.log('Connect to url: ' + url);
            this.ws = new WebSocket(url);
            this.ws.onopen = function() {
                this.sandbox.logger.log('Connection established!');
                this.opened = true;

                this.sandbox.dom.on(this.formId, 'keyup', this.updateEvent.bind(this), '.preview-update');

                // send start command
                var message = {
                    command: 'start',
                    content: this.options.data.id,
                    type: 'form',
                    user: AppConfig.getUser().id,
                    webspace: this.options.webspace,
                    language: this.options.language,
                    params: {}
                };
                this.ws.send(JSON.stringify(message));
            }.bind(this);

            this.ws.onclose = function() {
                if (!this.opened) {
                    // no connection can be opened use fallback (safari)
                    this.ws = null;
                    this.initAjax();
                }
            }.bind(this);

            this.ws.onmessage = function(e) {
                var data = JSON.parse(e.data);

                if (data.command === 'start' && data.content === this.options.id && !!data.params.other) {
                    // FIXME do it after restart form
                    this.updatePreview();
                }

                this.sandbox.logger.log('Message:', data);
            }.bind(this);

            this.ws.onerror = function(e) {
                this.sandbox.logger.warn(e);

                // no connection can be opened use fallback
                this.ws = null;
                this.initAjax();
            }.bind(this);
        },

        updatePreview: function(property, value) {
            if (!!this.previewInitiated) {
                var changes = {};
                if (!!property && !!value) {
                    changes[property] = value;
                } else {
                    changes = this.sandbox.form.getData(this.formId);
                }

                if (this.ws !== null) {
                    this.updateWs(changes);
                } else {
                    this.updateAjax(changes);
                }
            }
        },

        updatePreviewOnly: function() {
            if (!!this.previewInitiated) {
                var changes = {};

                if (this.ws !== null) {
                    this.updateWs(changes);
                } else {
                    this.updateAjax(changes);
                }
            }
        },

        updateAjax: function(changes) {
            var updateUrl = '/admin/content/preview/' + this.options.data.id + '?template=' + this.template + '&webspace=' + this.options.webspace + '&language=' + this.options.language;

            this.sandbox.util.ajax({
                url: updateUrl,
                type: 'POST',

                data: {
                    changes: changes
                }
            });
        },

        updateWs: function(changes) {
            var message = {
                command: 'update',
                content: this.options.data.id,
                type: 'form',
                user: AppConfig.getUser().id,
                webspaceKey: this.options.webspace,
                languageCode: this.options.language,
                params: {changes: changes, template: this.template}
            };
            this.ws.send(JSON.stringify(message));
        }

    };
});
