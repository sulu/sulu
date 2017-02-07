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
 * @class ckeditor/link
 * @constructor
 */
define(['underscore', 'config', 'text!./form.html'], function(_, Config, formTemplate) {

    'use strict';

    var formSelector = '#internal-link-form',
        config = Config.get('sulu_content.link_provider.configuration');

    return {

        defaults: {
            options: {
                provider: 'page',
                link: {},
                saveCallback: function(label) {
                },
                removeCallback: function() {
                }
            },

            templates: {
                form: formTemplate,
                providerDatasource: '<div id="provider-data-source"/>'
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
            this.config = config[this.options.provider];

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

            this.sandbox.stop();
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
                provider: this.options.provider,
                published: this.hrefPublished,
                title: !!this.options.link.title ? this.options.link.title : this.hrefTitle
            });
        },

        setData: function(data) {
            return this.sandbox.form.setData(formSelector, data);
        },

        setHref: function(id, title, published) {
            this.href = id;
            this.hrefTitle = title;
            this.hrefPublished = !!published;

            var $value = $('#internal-link-href-value');
            $value.val(title);
            $('#internal-link-href-button-clear').show();
        },

        initializeDialog: function() {
            var title = this.translations.internalLink + ': ' + this.sandbox.translate(this.config.title),
                $element = this.sandbox.dom.createElement('<div class="overlay-container"/>'),
                data = $(this.templates.providerDatasource()),
                tabs = null,
                buttons = [
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

            this.sandbox.dom.append(this.$el, $element);

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

            if (!!this.config.slideOptions.tabs) {
                tabs = _.map(this.config.slideOptions.tabs, function(tab) {
                    return _.extend({data: data}, tab)
                });

                data = null;
            }

            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        openOnStart: true,
                        removeOnClose: true,
                        el: $element,
                        container: this.$el,
                        skin: 'large',
                        instanceName: 'internal-link',
                        slides: [
                            {
                                title: title,
                                data: this.templates.form({translations: this.translations}),
                                buttons: buttons,
                                okCallback: this.save.bind(this)
                            },
                            {
                                title: title,
                                data: data,
                                tabs: tabs,
                                cssClass: 'data-source-slide',
                                contentSpacing: !this.config.slideOptions.noSpacing,
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
                        name: this.config.component,
                        options: _.extend({}, this.config.componentOptions, {
                            el: '#provider-data-source',
                            link: this.options.link,
                            webspace: this.options.webspace,
                            locale: this.options.locale,
                            setHref: function(id, title, published) {
                                if (!!id && !!title) {
                                    this.setHref(id, title, published);
                                }

                                this.showHrefInput();
                            }.bind(this),
                            selectCallback: function(id, title, published) {
                                var $value = $('#internal-link-href-value');
                                $value.val(title);
                                $('#internal-link-href-button-clear').show();

                                this.href = id;
                                this.hrefTitle = title;
                                this.hrefPublished = !!published;
                                this.sandbox.emit('husky.overlay.internal-link.slide-to', 0);
                                $('.href-container').removeClass('husky-validate-error');
                            }.bind(this)
                        })
                    }
                ]
            );
        },

        showHrefInput: function() {
            this.$find('.loader').hide();
            this.$find('.href-container').show();
        }
    };
});
