/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['text!./form.html'], function(form) {

    'use strict';

    const formSelector = '#custom-url-form';

    var defaults = {
        options: {
            saveCallback: function() {
            }
        },
        templates: {
            form: form,
            skeleton: '<div id="webspace-custom-urls-overlay"/>',
            url: '/admin/api/webspaces/<%= webspaceKey %>/custom-urls<% if (!!id) { %>/<%= id %><% } %>'
        },
        translations: {
            overlayTitle: 'custom-urls.webspace.settings.edit.title'
        }
    };

    return {

        defaults: defaults,

        initialize: function() {
            this.$el.html(this.templates.skeleton);

            this.startOverlay();
        },

        bindDomEvents: function() {
            this.sandbox.dom.on('#analytics-all-domains', 'change', function() {
                $('#analytics-domains-container').toggle();
            });
        },

        startOverlay: function() {
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        el: '#webspace-custom-urls-overlay',
                        openOnStart: true,
                        removeOnClose: true,
                        slides: [
                            {
                                title: this.translations.overlayTitle,
                                data: this.templates.form({translations: this.translations}),
                                okCallback: function() {
                                    if (this.sandbox.form.validate(formSelector)) {
                                        this.options.saveCallback(this.options.id, this.getData());
                                    } else {
                                        return false;
                                    }
                                }.bind(this)
                            }
                        ]
                    }
                }
            ]).then(function() {
                this.sandbox.form.create(formSelector).initialized.then(function() {
                    this.sandbox.form.setData(formSelector, this.data).then(this.initializeFormComponents.bind(this));
                    this.bindDomEvents();
                }.bind(this));
            }.bind(this));
        },

        getData: function() {
            return this.sandbox.form.getData(formSelector);
        },

        initializeFormComponents: function() {
            this.sandbox.start(
                [
                    {
                        name: 'toggler@husky',
                        options: {
                            el: '#custom-url-published'
                        }
                    },
                    {
                        name: 'webspace/settings/custom-url/input@sulucustomurl',
                        options: {
                            el: '#custom-url-input'
                        }
                    }
                ]
            )
        },

        loadComponentData: function() {
            var deferred = this.sandbox.data.deferred();
            if (!this.options.id) {
                deferred.resolve({});

                return deferred.promise();
            }

            this.sandbox.util.load(
                this.templates.url(this.options)
            ).then(function(data) {
                deferred.resolve(data);
            });

            return deferred;
        }
    };
});
