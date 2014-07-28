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

        layout: {
            changeNothing: true
        },

        // if ws != null then use it
        ws: null,
        wsUrl: '',
        wsPort: '',
        previewInitiated: false,
        opened: false,
        template: '',

        saved: true,
        contentChanged: false,
        animateTemplateDropdown: false,

        initialize: function() {
            this.sandbox.emit('husky.toolbar.header.item.enable', 'template', false);

            this.dfdListenForChange = this.sandbox.data.deferred();
            this.load();
        },

        bindCustomEvents: function() {
            // set preview params
            this.sandbox.on('sulu.preview.set-params', function(url, port) {
                this.wsUrl = url;
                this.wsPort = port;
            }, this);

            // change template
            this.sandbox.on('sulu.dropdown.template.item-clicked', function(item) {
                this.animateTemplateDropdown = true;
                this.checkRenderTemplate(item);
            }, this);

            // content save
            this.sandbox.on('sulu.header.toolbar.save', function() {
                this.submit();
            }, this);
        },

        bindDomEvents: function() {
            this.startListening = false;
            this.getDomElementsForTagName('sulu.rlp', function(property) {
                var element = property.$el.data('element');
                if (!element || element.getValue() === '' || element.getValue() === undefined || element.getValue() === null) {
                    this.startListening = true;
                }
            }.bind(this));

            if (this.startListening) {
                this.sandbox.dom.one(this.getDomElementsForTagName('sulu.rlp.part'), 'focusout', this.setResourceLocator.bind(this));
            } else {
                this.dfdListenForChange.resolve();
            }
        },

        load: function() {
            // get content data
            this.sandbox.emit('sulu.content.contents.get-data', function(data) {
                this.render(data);
            }.bind(this));
        },

        render: function(data) {
            this.bindCustomEvents();
            this.listenForChange();

            this.data = data;

            if (!!this.data.template) {
                this.checkRenderTemplate(this.data.template);
            } else {
                this.checkRenderTemplate();
            }
        },

        checkRenderTemplate: function(item) {
            if (typeof item === 'string') {
                item = {template: item};
            }
            if (!!item && this.template === item.template) {
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'template', false);
                return;
            }

            this.sandbox.emit('sulu.header.toolbar.item.loading', 'template');

            if (this.template !== '' && this.contentChanged) {
                this.showRenderTemplateDialog(item);
            } else {
                this.loadFormTemplate(item);
            }
        },

        showRenderTemplateDialog: function(item) {
            // show warning dialog
            this.sandbox.emit('sulu.overlay.show-warning',
                'sulu.overlay.be-careful',
                'content.template.dialog.content',

                function() {
                    // cancel callback
                    return false;
                }.bind(this),

                function() {
                    // ok callback
                    this.loadFormTemplate(item);
                }.bind(this)
            );
        },

        loadFormTemplate: function(item) {
            var tmp, url;
            if (!!item) {
                this.template = item.template;
            }
            this.formId = '#content-form-container';
            this.$container = this.sandbox.dom.createElement('<div id="content-form-container"/>');
            this.html(this.$container);

            if (!!this.sandbox.form.getObject(this.formId)) {
                tmp = this.data;
                this.data = this.sandbox.form.getData(this.formId);
                if (!!tmp.id) {
                    this.data.id = tmp.id;
                }

                this.data = this.sandbox.util.extend({}, tmp, this.data);
            }

            this.writeStartMessage();
            if (!!item) {
                this.sandbox.emit('sulu.content.preview.change-url', {template: item.template});
            }
            //only update the tabs-content if the content tab is selected
            url = this.getTemplateUrl(item);

            require([url], function(template) {
                this.renderFormTemplate(template);
            }.bind(this));
        },

        renderFormTemplate: function(template) {
            var data = this.initData(),
                defaults = {
                    translate: this.sandbox.translate,
                    content: data,
                    options: this.options
                },
                context = this.sandbox.util.extend({}, defaults),
                tpl = this.sandbox.util.template(template, context);

            this.sandbox.dom.html(this.formId, tpl);
            this.setStateDropdown(data);

            this.propertyConfiguration = {};
            this.createForm(data).then(function() {
                this.bindDomEvents();
                this.updatePreviewOnly();

                this.changeTemplateDropdownHandler();
            }.bind(this));
        },

        createForm: function(data) {
            var formObject = this.sandbox.form.create(this.formId),
                dfd = this.sandbox.data.deferred();

            formObject.initialized.then(function() {
                this.createConfiguration(this.formId);

                this.setFormData(data).then(function() {
                    this.sandbox.start(this.$el, {reset: true});

                    this.initSortableBlock();
                    this.bindFormEvents();

                    if (!!this.options.preview) {
                        this.initPreview();
                        this.updatePreview();
                        this.options.preview = false;
                    }

                    dfd.resolve();
                }.bind(this));
            }.bind(this));

            return dfd.promise();
        },

        createConfiguration: function($el) {
            var $items = this.sandbox.dom.find('*[data-property]', $el);
            // foreach property
            this.sandbox.dom.each($items, function(key, item) {
                var property = this.sandbox.dom.data(item, 'property');
                property.$el = this.sandbox.dom.$(item);

                // remove property from data
                this.sandbox.dom.data(item, 'property', null);
                this.sandbox.dom.removeAttr(item, 'data-property', null);

                // foreach tag
                this.sandbox.util.foreach(property.tags, function(tag) {
                    if (!this.propertyConfiguration[tag.name]) {
                        this.propertyConfiguration[tag.name] = {
                            properties: {},
                            highestProperty: property,
                            highestPriority: tag.priority,
                            lowestProperty: property,
                            lowestPriority: tag.priority
                        };
                        this.propertyConfiguration[tag.name].properties[tag.priority] = [property];
                    } else {
                        if (!this.propertyConfiguration[tag.name].properties[tag.priority]) {
                            this.propertyConfiguration[tag.name].properties[tag.priority] = [property];
                        } else {
                            this.propertyConfiguration[tag.name].properties[tag.priority].push(property);
                        }

                        // replace highest if priority is higher
                        if (this.propertyConfiguration[tag.name].highestPriority < tag.priority) {
                            this.propertyConfiguration[tag.name].highestProperty = property;
                            this.propertyConfiguration[tag.name].highestPriority = tag.priority;
                        }

                        // replace lowest if priority is lower
                        if (this.propertyConfiguration[tag.name].lowestPriority > tag.priority) {
                            this.propertyConfiguration[tag.name].lowestProperty = property;
                            this.propertyConfiguration[tag.name].lowestPriority = tag.priority;
                        }
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

                sortable.bind('sortupdate', function(event) {
                    var changes = this.sandbox.form.getData(this.formId),
                        propertyName = this.sandbox.dom.data(event.currentTarget, 'mapperProperty');

                    this.updatePreview(propertyName, changes[propertyName]);
                }.bind(this));
            }
        },

        bindFormEvents: function() {
            this.sandbox.dom.on(this.formId, 'form-remove', function(e, propertyName) {
                // TODO removed elements remove from config
                var changes = this.sandbox.form.getData(this.formId);
                this.initSortableBlock();
                this.updatePreview(propertyName, changes[propertyName]);
                this.setHeaderBar(false);
            }.bind(this));

            this.sandbox.dom.on(this.formId, 'form-add', function(e, propertyName, data) {
                this.createConfiguration(e.currentTarget);

                // start new subcomponents
                this.sandbox.start(
                    this.sandbox.dom.last(
                        this.sandbox.dom.children(this.$find('[data-mapper-property="'+ propertyName +'"]'))
                    )
                );

                // update changes
                var changes = this.sandbox.form.getData(this.formId);
                this.initSortableBlock();
                this.updatePreview(propertyName, changes[propertyName]);
            }.bind(this));
        },

        setFormData: function(data) {
            var initialize = this.sandbox.form.setData(this.formId, data),
                titleAttr = 'title'; // default value

            this.getDomElementsForTagName('sulu.node.name', function(property) {
                titleAttr = property.name;
            }.bind(this));

            if (!!data.id && (data[titleAttr] === '' || typeof data[titleAttr] === 'undefined' || data[titleAttr] === null)) {
                this.sandbox.util.load('/admin/api/nodes/' + data.id + '?webspace=' + this.options.webspace + '&language=' + this.options.language + '&complete=false&ghost-content=true')
                    .then(function(data) {
                        if (!!data.type) {
                            this.sandbox.dom.attr('#title', 'placeholder', data.type.value + ': ' + data[titleAttr]);
                        }
                    }.bind(this));
            }

            if (this.options.id === 'index') {
                this.sandbox.dom.remove('#show-in-navigation-container');
            }
            this.sandbox.dom.attr('#show-in-navigation', 'checked', data.navigation);

            return initialize;
        },

        getDomElementsForTagName: function(tagName, callback) {
            var result = $(), key;
            if (this.propertyConfiguration.hasOwnProperty(tagName)) {
                for (key in this.propertyConfiguration[tagName].properties) {
                    if (this.propertyConfiguration[tagName].properties.hasOwnProperty(key)) {
                        this.sandbox.util.foreach(this.propertyConfiguration[tagName].properties[key], function(property) {
                            $.merge(result, property.$el);
                            if (!!callback) {
                                callback(property);
                            }
                        });
                    }
                }
            }
            return result;
        },

        getTemplateUrl: function(item) {
            var url = 'text!/admin/content/template/form';
            if (!!item) {
                url += '/' + item.template + '.html';
            } else {
                url += '.html';
            }
            url += '?webspace=' + this.options.webspace + '&language=' + this.options.language;

            return url;
        },

        setHeaderBar: function(saved) {
            this.sandbox.emit('sulu.content.contents.set-header-bar', saved);

            this.saved = saved;
            if (this.saved) {
                this.contentChanged = false;
            }
        },

        setStateDropdown: function(data) {
            this.sandbox.emit('sulu.content.contents.set-state', data);
        },

        initData: function() {
            return this.data;
        },

        setResourceLocator: function() {
            if (this.dfdListenForChange.state() !== 'pending') {
                return;
            }

            var parts = {},
                complete = true;

            // check if each part has a value
            this.getDomElementsForTagName('sulu.rlp.part', function(property) {
                var value = property.$el.data('element').getValue();
                if (value !== '') {
                    parts[this.getSequence(property.$el)] = value;
                } else {
                    complete = false;
                }
            }.bind(this));

            if (!!complete) {
                this.startListening = true;
                this.sandbox.emit('sulu.content.contents.get-rl', parts, function(rl) {
                    // set resource locator to empty input fields
                    this.getDomElementsForTagName('sulu.rlp', function(property) {
                        var element = property.$el.data('element');
                        if (element.getValue() === '' || element.getValue() === undefined || element.getValue() === null) {
                            element.setValue(rl);
                        }
                    }.bind(this));

                    this.dfdListenForChange.resolve();

                    this.setHeaderBar(false);
                    this.contentChanged = true;
                }.bind(this));
            } else {
                this.sandbox.dom.one(this.getDomElementsForTagName('sulu.rlp.part'), 'focusout', this.setResourceLocator.bind(this));
            }
        },

        listenForChange: function() {
            this.dfdListenForChange.then(function() {
                this.sandbox.dom.on(this.$el, 'keyup change', function() {
                    this.setHeaderBar(false);
                    this.contentChanged = true;
                }.bind(this), '.trigger-save-button');
            }.bind(this));

            this.sandbox.on('sulu.content.changed', function() {
                this.setHeaderBar(false);
                this.contentChanged = true;
            }.bind(this));
        },

        changeTemplateDropdownHandler: function() {
            if (!!this.template) {
                this.sandbox.emit('sulu.header.toolbar.item.change', 'template', this.template);
            }
            this.sandbox.emit('sulu.header.toolbar.item.enable', 'template', this.animateTemplateDropdown);
            this.animateTemplateDropdown = false;
        },

        submit: function() {
            this.sandbox.logger.log('save Model');
            var data;

            if (this.sandbox.form.validate(this.formId)) {
                data = this.sandbox.form.getData(this.formId);

                if (this.options.id === 'index') {
                    data.navigation = true;
                } else if (!!this.sandbox.dom.find('#show-in-navigation', this.$el).length) {
                    data.navigation = this.sandbox.dom.prop('#show-in-navigation', 'checked');
                }

                this.sandbox.logger.log('data', data);

                this.options.data = this.sandbox.util.extend(true, {}, this.options.data, data);
                this.sandbox.emit('sulu.content.contents.save', data);
            }
        },

        /**
         * PREVIEW
         */
        initPreview: function() {
            if (this.wsDetection()) {
                this.initWs();
            } else {
                this.initAjax();
            }
            this.previewInitiated = true;

            this.sandbox.on('sulu.preview.update', function($el, value, changeOnKey) {
                if (!!this.data.id) {
                    var property = this.getSequence($el);
                    if (this.ws !== null || !changeOnKey) {
                        this.updatePreview(property, value);
                    }
                }
            }, this);
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

        getSequence: function($element) {
            $element = $($element);
            var sequence = this.sandbox.dom.data($element, 'mapperProperty'),
                $parents = $element.parents('*[data-mapper-property]'),
                item = $element.parents('*[data-mapper-property-tpl]')[0],
                parentProperty;

            while (!$element.data('element')) {
                $element = $element.parent();
            }

            if ($parents.length > 0) {
                parentProperty = this.sandbox.dom.data($parents[0], 'mapperProperty');
                if (typeof parentProperty !== 'string') {
                    parentProperty = this.sandbox.dom.data($parents[0], 'mapperProperty')[0].data;
                }
                sequence = [
                    parentProperty,
                    $(item).index(),
                    this.sandbox.dom.data($element, 'mapperProperty')
                ];
            }
            return sequence;
        },

        updateEvent: function(e) {
            if (!!this.data.id && !!this.previewInitiated) {
                var $element = $(e.currentTarget),
                    element = this.sandbox.dom.data($element, 'element');

                this.updatePreview(this.getSequence($element), element.getValue());
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

                this.sandbox.dom.on(this.formId, 'keyup change', this.updateEvent.bind(this), '.preview-update');

                // write start message
                this.writeStartMessage();
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
                this.sandbox.logger.log('Message:', data);
            }.bind(this);

            this.ws.onerror = function(e) {
                this.sandbox.logger.warn(e);

                // no connection can be opened use fallback
                this.ws = null;
                this.initAjax();
            }.bind(this);
        },

        writeStartMessage: function() {
            if (this.ws !== null) {
                // send start command
                var message = {
                    command: 'start',
                    content: this.data.id,
                    type: 'form',
                    user: AppConfig.getUser().id,
                    webspaceKey: this.options.webspace,
                    languageCode: this.options.language,
                    templateKey: this.template,
                    params: {}
                };
                this.ws.send(JSON.stringify(message));
            }
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
            var updateUrl = '/admin/content/preview/' + this.data.id + '?template=' + this.template + '&webspace=' + this.options.webspace + '&language=' + this.options.language;

            this.sandbox.util.ajax({
                url: updateUrl,
                type: 'POST',

                data: {
                    changes: changes
                }
            });
        },

        updateWs: function(changes) {
            if (this.ws !== null && this.ws.readyState === this.ws.OPEN) {
                var message = {
                    command: 'update',
                    content: this.data.id,
                    type: 'form',
                    user: AppConfig.getUser().id,
                    webspaceKey: this.options.webspace,
                    languageCode: this.options.language,
                    templateKey: this.template,
                    params: {changes: changes}
                };
                this.ws.send(JSON.stringify(message));
            }
        }
    };
});
