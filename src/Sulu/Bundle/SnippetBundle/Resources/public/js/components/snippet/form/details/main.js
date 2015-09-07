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
        layout: {
            content: {
                leftSpace: false,
                rightSpace: false
            }
        },

        initialize: function() {
            this.bindCustomEvents();
            this.config = AppConfig.getSection('sulu-snippet');
            this.defaultType = this.config.defaultType;

            this.loadData();
        },

        bindCustomEvents: function() {
            // change template
            this.sandbox.on('sulu.dropdown.template.item-clicked', function(item) {
                this.checkRenderTemplate(item);
            }, this);

            // content save
            this.sandbox.on('sulu.toolbar.save', function(action) {
                this.submit(action);
            }, this);
        },

        loadData: function() {
            // get content data
            this.sandbox.emit('sulu.snippets.snippet.get-data', function(data) {
                this.render(data);
            }.bind(this));
        },

        render: function(data) {
            this.data = data;

            if (!!this.data.template) {
                this.checkRenderTemplate(this.data.template);
            } else {
                this.checkRenderTemplate();
            }
            this.listenForChange();
        },

        checkRenderTemplate: function(item) {
            if (typeof item === 'string') {
                item = {template: item};
            }
            if (!!item && !!this.template && this.template === item.template) {
                this.sandbox.emit('sulu.header.toolbar.item.enable', 'template', false);
                return;
            }

            if (!this.template) {
                this.template = this.defaultType;
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
                    this.sandbox.emit('sulu.header.toolbar.item.enable', 'template', false);

                    if (!!this.template) {
                        this.sandbox.emit('sulu.header.toolbar.item.change', 'template', this.template);
                    } else {
                        this.sandbox.emit('sulu.header.toolbar.item.change', 'template', this.defaultType);
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
            this.formId = '#snippet-form-container';
            this.$container = this.sandbox.dom.createElement('<div id="snippet-form-container"/>');
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
            var data = this.initData(),
                defaults = {
                    translate: this.sandbox.translate,
                    content: data,
                    options: this.options
                },
                context = this.sandbox.util.extend({}, defaults),
                tpl = this.sandbox.util.template(template, context);

            this.sandbox.dom.html(this.formId, tpl);

            this.createForm(data).then(function() {
                this.changeTemplateDropdownHandler();
            }.bind(this));
        },

        createForm: function(data) {
            var formObject = this.sandbox.form.create(this.formId),
                dfd = this.sandbox.data.deferred();

            formObject.initialized.then(function() {
                this.setFormData(data).then(function() {
                    this.sandbox.start(this.$el, {reset: true});

                    this.initSortableBlock();
                    this.bindFormEvents();

                    dfd.resolve();
                }.bind(this));
            }.bind(this));

            return dfd.promise();
        },

        setFormData: function(data) {
            return this.sandbox.form.setData(this.formId, data);
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
            }
        },

        bindFormEvents: function() {
            this.sandbox.dom.on(this.formId, 'form-remove', function() {
                this.initSortableBlock();
                this.setHeaderBar(false);
            }.bind(this));

            this.sandbox.dom.on(this.formId, 'form-add', function(e, propertyName, data, index) {
                var $elements = this.sandbox.dom.children(this.$find('[data-mapper-property="' + propertyName + '"]')),
                    $element = (index !== undefined && $elements.length > index) ? $elements[index] : this.sandbox.dom.last($elements);

                // start new subcomponents
                this.sandbox.start($element);

                // reinit sorting
                this.initSortableBlock();
            }.bind(this));


            this.sandbox.dom.on(this.formId, 'init-sortable', function(e) {
                // reinit sorting
                this.initSortableBlock();
            }.bind(this));
        },

        listenForChange: function() {
            this.sandbox.dom.on(this.$el, 'keyup change', function() {
                this.setHeaderBar(false);
                this.contentChanged = true;
            }.bind(this), '.trigger-save-button');

            this.sandbox.on('sulu.content.changed', function() {
                this.setHeaderBar(false);
                this.contentChanged = true;
            }.bind(this));
        },

        setHeaderBar: function(saved) {
            // FIXME add event
            this.sandbox.emit('sulu.snippets.snippet.set-header-bar', saved);

            this.saved = saved;
            if (this.saved) {
                this.contentChanged = false;
            }
        },

        getTemplateUrl: function(item) {
            var url = 'text!/admin/content/template/form';
            if (!!item) {
                url += '/' + item.template + '.html';
            } else {
                url += '/' + this.defaultType + '.html';
            }
            url += '?type=snippet&language=' + this.options.language;

            return url;
        },

        initData: function() {
            return this.data;
        },

        changeTemplateDropdownHandler: function() {
            this.sandbox.emit('sulu.header.toolbar.item.enable', 'template');
            if (!!this.template) {
                this.sandbox.emit('sulu.header.toolbar.item.change', 'template', this.template);
            }
        },

        submit: function(action) {
            this.sandbox.logger.log('save Model');
            var data;

            if (this.sandbox.form.validate(this.formId)) {
                data = this.sandbox.form.getData(this.formId);

                this.sandbox.logger.log('data', data);

                this.options.data = this.sandbox.util.extend(true, {}, this.options.data, data);
                this.sandbox.emit('sulu.snippets.snippet.save', data, action);
            }
        }
    };
});
