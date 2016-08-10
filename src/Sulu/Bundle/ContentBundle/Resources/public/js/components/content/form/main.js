/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app-config', 'config', 'services/sulupreview/preview'], function(AppConfig, Config, Preview) {

    'use strict';

    return {

        tabOptions: {
            noTitle: true
        },

        layout: function() {
            return {
                extendExisting: true,
                content: {
                    width: (!!this.options.preview) ? 'fixed' : 'max',
                    rightSpace: false,
                    leftSpace: false
                }
            };
        },

        template: '',

        whenResourceLocatorIsLoaded: $.Deferred(),

        // content change detection
        saved: true,
        animateTemplateDropdown: false,

        initialize: function() {
            this.sandbox.emit('husky.toolbar.header.item.enable', 'template', false);
            this.load();
        },

        bindCustomEvents: function() {
            // change template
            this.sandbox.on('sulu.dropdown.template.item-clicked', function(item) {
                this.animateTemplateDropdown = true;
                this.checkRenderTemplate(item);
            }, this);

            // content save
            this.sandbox.on('sulu.toolbar.save', function(action) {
                this.submit(action);
            }, this);

            // navigate away
            this.sandbox.on('sulu.content.navigate', this.navigate, this);

            this.sandbox.on('sulu.header.saved', function(data) {
                this.data = data;
                this.initializeResourceLocator();
            }, this);
        },

        /**
         * Initializes the rlp-inputs as well as the rlp-part inputs.
         * When there is already an url for the page, the rlp-inputs and the rlp-part inputs
         * are just like any other inputs and the corresponding promise is resolved right away.
         */
        initializeResourceLocator: function() {
            if (!this.data.url) {
                // when a rlp-part gets changed other parts (e.g. submitting) has to wait
                this.sandbox.dom.on(this.getDomElementsForTagName('sulu.rlp.part'), 'change.resourcelocator', function() {
                    if (!this.whenResourceLocatorIsLoaded || this.whenResourceLocatorIsLoaded.state() === 'resolved') {
                        this.whenResourceLocatorIsLoaded = $.Deferred();
                    }
                }.bind(this));

                this.sandbox.dom.on(this.getDomElementsForTagName('sulu.rlp.part'), 'focusout.resourcelocator', this.loadResourceLocator.bind(this));

                // initialize all rlp-inputs as not edited by the user
                this.getDomElementsForTagName('sulu.rlp', function(property) {
                    property.$el.data('user-edited', false);
                    // manually edited rlp-inputs get marked as such
                    this.sandbox.dom.on(property.$el, 'change', function() {
                        property.$el.data('user-edited', property.$el.data('element').getValue().length > 1);
                    }.bind(this));
                }.bind(this));

            } else {
                // saving a ghost page does not reinitialize the component, therefore we need to remove these handlers
                this.sandbox.dom.off(this.getDomElementsForTagName('sulu.rlp.part'), '.resourcelocator');
                this.whenResourceLocatorIsLoaded.resolve();
            }
        },

        load: function() {
            // get content data
            this.sandbox.emit('sulu.content.contents.get-data', this.render.bind(this));
        },

        render: function(data, preview) {
            this.bindCustomEvents();
            this.listenForChange();

            this.preview = preview;
            this.data = data;

            if (!!this.data.template) {
                this.checkRenderTemplate(this.data.template);
            } else {
                this.checkRenderTemplate();
            }

            this.sandbox.emit('sulu.content.contents.show-save-items', 'content');
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

            if (this.template !== '' && !this.saved) {
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
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'template', false);

                    if (!!this.template) {
                        this.sandbox.emit('sulu.header.toolbar.item.change', 'template', this.template);
                    }
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

            //only update the tabs-content if the content tab is selected
            url = this.getTemplateUrl(item);

            require([url], function(template) {
                this.renderFormTemplate(template);
            }.bind(this));
        },

        renderFormTemplate: function(template) {
            var defaults = {
                    translate: this.sandbox.translate,
                    content: this.data,
                    options: this.options
                },
                context = this.sandbox.util.extend({}, defaults, {
                    categoryLocale: this.options.language
                }),
                tpl = this.sandbox.util.template(template, context);

            this.sandbox.dom.html(this.formId, tpl);
            this.setStateDropdown(this.data);

            this.propertyConfiguration = {};
            this.createForm(this.data).then(function() {
                this.initializeResourceLocator();
                this.changeTemplateDropdownHandler();

                if (!!this.preview) {
                    this.preview.bindDomEvents(this.$el);
                }

                if (!!Config.has('sulu-collaboration')) {
                    this.startCollaborationComponent();
                }
            }.bind(this));
        },

        createForm: function(data) {
            var formObject = this.sandbox.form.create(this.formId),
                dfd = this.sandbox.data.deferred();

            formObject.initialized.then(function() {
                this.createConfiguration(this.formId);

                this.setFormData(data).then(function() {
                    this.sandbox.start(this.$el, {reset: true}).then(function(){
                        this.initSortableBlock();
                        this.bindFormEvents();

                        var data = this.sandbox.form.getData(this.formId);

                        this.sandbox.emit('sulu.content.initialized', data);
                        dfd.resolve();

                        if (!this.preview) {
                            return;
                        }

                        this.preview.updateContext({template: this.template}, data);
                    }.bind(this));
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

                    if (!!this.preview) {
                        this.preview.updateProperty(propertyName, changes[propertyName]);
                    }
                    this.sandbox.emit('sulu.content.changed');
                }.bind(this));
            }
        },

        bindFormEvents: function() {
            this.sandbox.dom.on(this.formId, 'form-remove', function(e, propertyName) {
                // TODO removed elements remove from config
                var changes = this.sandbox.form.getData(this.formId);
                this.initSortableBlock();

                if (!!this.preview) {
                    this.preview.updateProperty(propertyName, changes[propertyName]);
                }
                this.setHeaderBar(false);
            }.bind(this));

            this.sandbox.dom.on(this.formId, 'form-add', function(e, propertyName, data, index) {
                this.createConfiguration(e.currentTarget);

                var $elements = this.sandbox.dom.children(this.$find('[data-mapper-property="' + propertyName + '"]')),
                    $element = (index !== undefined && $elements.length > index) ? $elements[index] : this.sandbox.dom.last($elements),
                    changes;

                // start new subcomponents
                this.sandbox.start($element);

                // update changes
                try {
                    changes = this.sandbox.form.getData(this.formId);

                    if (!!this.preview) {
                        this.preview.updateProperty(propertyName, changes[propertyName]);
                    }
                } catch (ex) {
                    // ignore exceptions
                }

                // enable save button
                this.setHeaderBar(false);

                // reinit sorting
                this.initSortableBlock();
            }.bind(this));

            this.sandbox.dom.on(this.formId, 'init-sortable', function(e) {
                // reinit sorting
                this.initSortableBlock();
            }.bind(this));
        },

        setFormData: function(data) {
            var initialize = this.sandbox.form.setData(this.formId, data),
                titleAttr = 'title'; // default value

            if (!!data.id && (data[titleAttr] === '' || typeof data[titleAttr] === 'undefined' || data[titleAttr] === null)) {
                this.sandbox.util.load('/admin/api/nodes/' + data.id + '?webspace=' + this.options.webspace + '&language=' + this.options.language + '&complete=false&ghost-content=true')
                    .then(function(data) {
                        if (!!data.type) {
                            this.sandbox.dom.attr('#title', 'placeholder', data.type.value + ': ' + data[titleAttr]);
                        }
                    }.bind(this));
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

            if (!!this.data.id) {
                url += '&uuid=' + this.data.id;
            }

            return url;
        },

        setHeaderBar: function(saved) {
            this.sandbox.emit('sulu.content.contents.set-header-bar', saved);
            this.saved = saved;
        },

        setStateDropdown: function(data) {
            this.sandbox.emit('sulu.content.contents.set-state', data);
        },

        /**
         * Iterates over all rlp-parts and asks the server for a resource locator
         * constructed out of all non empty rlp-parts. After retrieving the resource-locator
         * it is set into the rlp inputs.
         */
        loadResourceLocator: function() {
            var parts = this.getNonEmptyRlpParts();
            this.sandbox.emit('sulu.content.contents.get-rl', parts, this.setResourceLocator.bind(this));
        },

        /**
         * Returns all the rlp parts which have a non-empty value
         * @returns {Object} an object containing all the non-empty rlp parts
         */
        getNonEmptyRlpParts: function() {
            var parts = {}, value, sequence;

            this.getDomElementsForTagName('sulu.rlp.part', function(property) {
                value = property.$el.data('element').getValue();
                if (value !== '') {
                    sequence = Preview.getSequence(property.$el);
                    if (!!sequence) {
                        parts[sequence] = value;
                    }
                }
            }.bind(this));

            return parts;
        },

        /**
         * Sets a given resource-locator as the value of the resource-locator inputs.
         * Only those resource-locator inputs get updated, which have not been manually edited by the user
         * @param {String} resourceLocator The resource locator to insert
         */
        setResourceLocator: function(resourceLocator) {
            this.getDomElementsForTagName('sulu.rlp', function(property) {
                if (!property.$el.data('user-edited')) {
                    property.$el.data('element').setValue(resourceLocator);
                }
            }.bind(this));
            this.whenResourceLocatorIsLoaded.resolve();
            this.setHeaderBar(false);
        },

        /**
         * @returns {boolean} True iff all resource locator parts are valid
         */
        areRlpPartsValid: function() {
            var valid = true;
            this.getDomElementsForTagName('sulu.rlp.part', function(property) {
                if (!property.$el.data('element').validate()) {
                    valid = false;
                }
            }.bind(this));

            return valid;
        },

        listenForChange: function() {
            this.sandbox.dom.on(this.$el, 'keyup change', _.debounce(function() {
                this.setHeaderBar(false);
            }.bind(this), 10), '.trigger-save-button');

            this.sandbox.on('sulu.content.changed', function() {
                this.setHeaderBar(false);
            }.bind(this));
        },

        changeTemplateDropdownHandler: function() {
            if (!!this.template) {
                this.sandbox.emit('sulu.header.toolbar.item.change', 'template', this.template);
            }
            this.sandbox.emit('sulu.header.toolbar.item.enable', 'template', this.animateTemplateDropdown);
            this.animateTemplateDropdown = false;
        },

        submit: function(action) {
            // only submit if all rlp parts are valid (a resource locator needs to be constructed)
            if (!this.areRlpPartsValid()) {
                return;
            }

            this.whenResourceLocatorIsLoaded.then(function() {
                if (this.sandbox.form.validate(this.formId)) {
                    this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
                    var data = this.sandbox.form.getData(this.formId);
                    data.navigation = this.sandbox.dom.prop('#show-in-navigation', 'checked');

                    this.sandbox.emit('sulu.content.contents.save', data, action);
                }
            }.bind(this));
        },

        startCollaborationComponent: function() {
            if (!this.options.id) {
                return;
            }

            var $container = this.sandbox.dom.createElement('<div id="content-column-collaboration"/>');
            this.$el.prepend($container);

            this.sandbox.start([
                {
                    name: 'collaboration@sulucollaboration',
                    options: {
                        el: $container,
                        id: this.options.id,
                        webspace: this.options.webspace,
                        userId: AppConfig.getUser().id,
                        type: 'page'
                    }
                }
            ]);
        },

        navigate: function(route) {
            var doNavigate = function(route) {
                this.sandbox.emit('sulu.router.navigate', route);
            }.bind(this);

            if (!this.saved) {
                this.sandbox.emit('sulu.overlay.show-warning',
                    'sulu.overlay.be-careful',
                    'content.template.dialog.content',
                    function() {
                    },
                    function() {
                        // ok callback
                        doNavigate.call(this, route);
                    }.bind(this)
                );
            } else {
                doNavigate.call(this, route);
            }
        }
    };
});
