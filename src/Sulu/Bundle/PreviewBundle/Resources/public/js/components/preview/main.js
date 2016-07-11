/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'jquery',
    'text!./skeleton.html',
    'text!./on-request.html',
    'text!./error.html'
], function($, skeletonTemplate, onRequestTemplate, errorTemplate) {

    'use strict';

    var constants = {
            previewExpandIcon: 'fa-step-backward',
            previewCollapseIcon: 'fa-step-forward',
            MODE_ON_REQUEST: 'on_request'
        },

        defaults = {
            options: {
                permissions: {},
                mode: null,
                webspace: null
            },

            translations: {
                startLabel: 'sulu.preview.start',
                noPreviewLabel: 'sulu.preview.no-preview',
                changeWebspaceLabel: 'sulu.preview.change-webspace',
                objectProviderLabel: 'sulu.preview.no-object-provider',
                defaultsProviderLabel: 'sulu.preview.no-defaults-provider'
            },

            templates: {
                skeleton: skeletonTemplate,
                onRequest: onRequestTemplate,
                error: errorTemplate,
                defaultError: '<h2><%= message %></h2>',
                9900: '<h2><%= translations.objectProviderLabel %></h2>',
                9901: '<h2><%= translations.changeWebspaceLabel %></h2>',
                9902: '<h2><%= translations.defaultsProviderLabel %></h2>'
            }
        };

    return {

        defaults: defaults,

        events: {
            names: {
                setContent: {postFix: 'set-content', type: 'on'},
                updateContent: {postFix: 'update-content', type: 'on'},
                error: {postFix: 'error', type: 'on'},
                webspace: {postFix: 'webspace'},
                render: {postFix: 'render'}
            },
            namespace: 'sulu.preview.'
        },

        initialize: function() {
            this.previewExpanded = false;

            if (this.options.mode === constants.MODE_ON_REQUEST) {
                this.renderOnRequest();
            } else {
                this.render();
            }
        },

        renderOnRequest: function() {
            this.$preview = $(this.templates.onRequest({translations: this.translations}));
            this.html(this.$preview);
        },

        render: function() {
            this.$preview = $(this.templates.skeleton({constants: constants}));
            this.$toggler = this.$preview.find('.toggler');
            this.$newWindow = this.$preview.find('.new-window');
            this.html(this.$preview);

            this.startToolbarComponent();
            this.bindCustomEvents();
            this.bindDomEvents();
        },

        bindDomEvents: function() {
            this.$newWindow.on('click', this.openPreviewInNewWindow.bind(this));
            this.$toggler.on('click', this.togglePreview.bind(this));
        },

        bindCustomEvents: function() {
            this.events.setContent(this.setContent.bind(this));
            this.events.updateContent(this.updateContent.bind(this));
            this.events.error(this.error.bind(this));
        },

        startToolbarComponent: function() {
            var buttons = {
                displayDevices: {
                    options: {
                        dropdownOptions: {
                            callback: function(item) {
                                this.changePreviewStyle(item.style);
                            }.bind(this)
                        }
                    }
                },
                refresh: {options: {callback: this.refreshPreview.bind(this)}}
            };

            if (!!this.options.permissions.live) {
                buttons.cache = {
                    options: {
                        callback: this.sandbox.website.cacheClear
                    }
                };
            }

            if (!this.options.webspace) {
                buttons.webspace = {
                    options: {
                        dropdownOptions: {
                            preSelectedCallback: function(item) {
                                this.events.webspace(item.id);
                            }.bind(this),
                            callback: function(item) {
                                this.events.webspace(item.key);
                            }.bind(this)
                        }
                    }
                };
            } else {
                this.events.webspace(this.options.webspace);
            }

            this.sandbox.start(
                [
                    {
                        name: 'toolbar@husky',
                        options: {
                            el: this.$find('.toolbar'),
                            instanceName: 'preview',
                            skin: 'big',
                            responsive: true,
                            buttons: this.sandbox.sulu.buttons.get(buttons)
                        }
                    }
                ]
            );
        },

        /**
         * Show error message with given code and message.
         *
         * @param {Integer} code
         * @param {String} message
         */
        error: function(code, message) {
            var errorTemplate = this.templates['defaultError'];
            if (!!this.templates[code]) {
                errorTemplate = this.templates[code];
            }

            this.setContent(
                this.templates.error(
                    {
                        code: code,
                        content: errorTemplate({message: message, translations: this.translations}),
                        translations: this.translations
                    }
                )
            );
        },

        /**
         * Set content of preview document.
         *
         * @param {String} htmlFile The html file including doctype etc.
         */
        setContent: function(htmlFile) {
            var previewDocument = this.getPreviewDocument();

            previewDocument.open();
            previewDocument.write(htmlFile);
            previewDocument.close();

            this.avoidNavigate(previewDocument);
        },

        /**
         * Disables all links in the preview document.
         */
        avoidNavigate: function(document) {
            $(document).find('a').click(function() {
                return false;
            });
        },

        /**
         * Returns content of preview document.
         *
         * @returns {String}
         */
        getContent: function() {
            return $(this.getPreviewDocument().documentElement).html();
        },

        /**
         * Update content of preview document.
         *
         * @param {Object} data
         */
        updateContent: function(data) {
            for (var propertyName in data) {
                if (data.hasOwnProperty(propertyName)) {
                    // FIXME refactor this and combine handleSequence with handleSingle
                    if (-1 !== propertyName.indexOf('[')) {
                        this.handleSequence(propertyName, data[propertyName]);
                    } else {
                        this.handleSingle(propertyName, data[propertyName]);
                    }
                }
            }
        },

        /**
         * Refresh the preview document.
         */
        refreshPreview: function() {
            $(this.getPreviewDocument().documentElement).html('');

            this.events.render();
        },

        /**
         * Changes the style of the the preview. E.g. from desktop to smartphone
         *
         * @param newStyle {String} the new style
         */
        changePreviewStyle: function(newStyle) {
            this.$preview.removeClass(this.$preview.data('sulu-preview-style'));
            this.$preview.addClass(newStyle);
            this.$preview.data('sulu-preview-style', newStyle);
        },

        /**
         * Shrinks or expands the content-column and therefore also the preview.
         */
        togglePreview: function() {
            if (!!this.previewExpanded) {
                this.sandbox.emit('sulu.app.toggle-column', false);
                this.sandbox.dom.removeClass(this.$toggler, constants.previewCollapseIcon);
                this.sandbox.dom.prependClass(this.$toggler, constants.previewExpandIcon);
                this.previewExpanded = false;
            } else {
                this.sandbox.emit('sulu.app.toggle-column', true);
                this.sandbox.dom.removeClass(this.$toggler, constants.previewExpandIcon);
                this.sandbox.dom.prependClass(this.$toggler, constants.previewCollapseIcon);
                this.previewExpanded = true;
            }
        },

        /**
         * Hides the sidebar and opens a new window with the preview in it
         */
        openPreviewInNewWindow: function() {
            var content = this.getContent();

            this.sandbox.emit('sulu.app.change-width', 'fixed');
            this.sandbox.emit('husky.navigation.show');
            this.sandbox.emit('sulu.sidebar.hide');
            this.previewWindow = window.open();
            this.setContent(content);
            this.previewWindow.onunload = function() {
                var content = this.getContent();
                this.previewWindow = null;

                this.sandbox.emit('sulu.sidebar.show');
                this.sandbox.emit('sulu.sidebar.change-width', 'max');

                this.setContent(content);
            }.bind(this);
        },

        /**
         * Handle changes for a given sequenced property-name.
         *
         * @param {String} propertyName
         * @param {Object} content
         */
        handleSequence: function(propertyName, content) {
            var sequence = propertyName.split(/([a-zA-Z0-9]+|\[[a-zA-Z0-9]+\])/).filter(Boolean),
                filter = '',
                item, before = 0,
                isInt = /^\d*$/, // regex for integer
                realName;

            for (item in sequence) {
                realName = sequence[item].replace('[', '').replace(']', '');
                // check of integer
                if (!isInt.test(realName)) {
                    before = realName;
                    filter += ' *[property="' + realName + '"]';
                } else {
                    filter += ' *[rel="' + before + '"]:eq(' + parseInt(realName) + ')';
                }
            }

            this.handle(content, filter);
        },

        /**
         * Handle changes for a given sequenced property-name.
         *
         * @param {String} propertyName
         * @param {Object} content
         */
        handleSingle: function(propertyName, content) {
            var filter = '*[property="' + propertyName + '"]';

            this.handle(content, filter, function(element) {
                // check all parents if they has not the attribute property
                // thats currently not supported by the api
                var currentNode = element.parentNode;
                while (null !== currentNode.parentNode) {
                    if (currentNode.hasAttribute('property') && currentNode.getAttribute('typeof') === 'collection') {
                        return false;
                    }
                    currentNode = currentNode.parentNode;
                }

                return true;
            });
        },

        /**
         * Handle changes for given selector.
         *
         * @param {Object} content
         * @param {String} selector
         * @param {function} validate
         */
        handle: function(content, selector, validate) {
            var i = 0,
                elements = $(this.getPreviewDocument()).find(selector),
                nodeArray = [].slice.call(elements);

            nodeArray.forEach(function(element) {
                if (!!validate && !validate(element)) {
                    return;
                }

                $.each(content[i], function(key, value) {
                    if (key !== 'html') {
                        $(element).attr(key, value);

                        return;
                    }

                    element.innerHTML = value;
                });

                // FIXME jump to element: element.scrollIntoView();
                i++;
            });
        },

        /**
         * Returns preview-document.
         * Could be the document of iframe or external browser window.
         *
         * @returns {Document}
         */
        getPreviewDocument: function() {
            if (!!this.previewWindow) {
                return this.previewWindow.document;
            } else {
                return this.sandbox.dom.find('iframe', this.$preview).contents()[0];
            }
        }
    };
});
