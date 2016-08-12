/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Overlay for internal-link plugin.
 *
 * @class CKEditorInternalLink
 * @constructor
 */
define(['underscore', 'text!./form.html'], function(_, formTemplate) {

    'use strict';

    var formSelector = '#internal-link-form';

    return {

        defaults: {
            options: {
                link: {},
                saveCallback: function(label) {
                },
                removeCallback: function() {
                }
            },

            templates: {
                form: formTemplate,
                contentDatasource: '<div id="href-select" class="data-source-content"/>'
            },

            translations: {
                save: 'public.save',
                back: 'public.previous',
                remove: 'content.ckeditor.internal-link.remove',
                altTitle: 'content.ckeditor.internal-link.alt-title',
                href: 'content.ckeditor.internal-link.href',
                target: 'content.ckeditor.internal-link.target',
                targetBlank: 'content.ckeditor.internal-link.target-blank',
                targetSelf: 'content.ckeditor.internal-link.target-self',
                internalLink: 'content.ckeditor.internal-link'
            }
        },

        initialize: function() {
            this.initializeDialog();
        },

        bindDomEvents: function() {
            this.sandbox.dom.on(this.$el, 'click', function() {
                this.sandbox.emit('husky.overlay.internal-link.slide-to', 1);

                return false;
            }.bind(this), '.internal-link-href, #internal-link-href-button');

            this.sandbox.dom.on(this.$el, 'click', function() {
                this.href = null;
                $('#internal-link-href-button-clear').hide();
                $('#internal-link-href-value').val('');

                return false;
            }.bind(this), '#internal-link-href-button-clear');
        },

        save: function() {
            if (!this.validate()) {
                return false;
            }

            this.options.saveCallback(this.getData());
        },

        validate: function() {
            if (!this.href) {
                $('.href-container').addClass('husky-validate-error');

                return false;
            }

            return this.sandbox.form.validate(formSelector);
        },

        getData: function() {
            var data = this.sandbox.form.getData(formSelector, data);

            return _.defaults(data, {
                href: this.href,
                published: this.hrefPublished,
                title: !!this.options.link.title ? this.options.link.title : this.hrefTitle
            });
        },

        setData: function(data) {
            return this.sandbox.form.setData(formSelector, data);
        },

        setHref: function(href) {
            this.href = href.id;
            this.hrefTitle = href.title;

            var $value = $('#internal-link-href-value');
            $value.val(href.title);
            $('#internal-link-href-button-clear').show();
        },

        initializeDialog: function() {
            var $element = this.sandbox.dom.createElement('<div class="overlay-container"/>');
            this.sandbox.dom.append(this.$el, $element);

            var buttons = [
                {
                    type: 'cancel',
                    align: 'left'
                },
                {
                    type: 'ok',
                    text: this.translations.save,
                    align: 'right'
                }
            ];

            if (!!this.options.link.href) {
                buttons.push({
                    text: this.translations.remove,
                    align: 'center',
                    classes: 'just-text',
                    callback: function() {
                        this.options.removeCallback();
                        this.sandbox.emit('husky.overlay.internal-link.close');
                    }.bind(this)
                });
            }

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        openOnStart: true,
                        removeOnClose: true,
                        el: $element,
                        container: this.$el,
                        skin: 'wide',
                        instanceName: 'internal-link',
                        slides: [
                            {
                                title: this.translations.internalLink,
                                data: this.templates.form({translations: this.translations}),
                                buttons: buttons,
                                okCallback: this.save.bind(this)
                            },
                            {
                                title: this.translations.internalLink,
                                data: this.templates.contentDatasource(),
                                cssClass: 'data-source-slide',
                                buttons: [
                                    {
                                        type: 'cancel',
                                        text: this.translations.back,
                                        align: 'left'
                                    }
                                ],
                                cancelCallback: function() {
                                    this.sandbox.emit('husky.overlay.internal-link.slide-to', 0);

                                    return false;
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]).then(function() {
                this.sandbox.form.create(formSelector).initialized.then(function() {
                    this.setData(this.options.link).then(this.initializeFormComponents.bind(this));

                    this.bindDomEvents();
                }.bind(this));
            }.bind(this));
        },

        initializeFormComponents: function() {
            this.sandbox.start(
                [
                    {
                        name: 'loader@husky',
                        options: {
                            el: this.$find('.loader')
                        }
                    },
                    {
                        name: 'content-datasource@sulucontent',
                        options: {
                            el: '#href-select',
                            selected: this.options.link.href,
                            webspace: this.options.webspace,
                            locale: this.options.locale,
                            selectedUrl: '/admin/api/nodes/{datasource}?tree=true&webspace={webspace}&language={locale}&fields=title,order,published&webspace-nodes=all',
                            rootUrl: '/admin/api/nodes?webspace={webspace}&language={locale}&fields=title,order,published&webspace-nodes=all',
                            resultKey: 'nodes',
                            instanceName: 'internal-link',
                            instanceNamePrefix: '',
                            showStatus: true,
                            selectCallback: function(id, path, title, item) {
                                var $value = $('#internal-link-href-value');
                                $value.val(title);
                                $('#internal-link-href-button-clear').show();

                                this.href = id;
                                this.hrefTitle = title;
                                this.hrefPublished = !!item.published;
                                this.sandbox.emit('husky.overlay.internal-link.slide-to', 0);
                                $('.href-container').removeClass('husky-validate-error');
                            }.bind(this)
                        }
                    }
                ]
            ).then(function() {
                if (!this.options.link.href) {
                    this.showHrefInput();

                    return;
                }

                this.sandbox.once('husky.column-navigation.internal-link.loaded', function() {
                    this.sandbox.emit('husky.column-navigation.internal-link.get-breadcrumb', function(breadcrumb) {
                        if (breadcrumb.length === 0) {
                            this.showHrefInput();

                            return;
                        }

                        this.setHref(breadcrumb[breadcrumb.length - 1]);
                        this.showHrefInput();
                    }.bind(this));
                }.bind(this));
            }.bind(this));
        },

        showHrefInput: function() {
            this.$find('.loader').hide();
            this.$find('.href-container').show();
        }
    };
});
