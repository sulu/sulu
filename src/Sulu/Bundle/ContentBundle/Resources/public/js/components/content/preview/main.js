/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['jquery', 'app-config', 'config', 'websocket-manager'], function($, AppConfig, Config, WebsocketManager) {

    'use strict';

    var WEBSOCKET_APP_NAME = 'admin',
        MESSAGE_HANDLER_NAME = 'sulu_content.preview';

    return function() {
        var start = function(template) {
                if (!!this.initiated) {
                    return;
                }

                var def = this.client.send(MESSAGE_HANDLER_NAME, {
                    command: 'start',
                    content: this.options.id,
                    locale: this.options.language,
                    webspaceKey: this.options.webspace,
                    template: template,
                    data: this.data,
                    user: AppConfig.getUser().id
                });
                this.initiated = true;

                return def.promise();
            },

            stop = function() {
                var def = $.Deferred();
                if (!!this.initiated) {
                    def = this.client.send(MESSAGE_HANDLER_NAME, {
                        command: 'stop',
                        user: AppConfig.getUser().id
                    });
                    this.initiated = false;
                }

                return def.promise();
            },

            update = function(property, value) {
                if (!!this.initiated) {
                    var changes = {}, def;
                    if (!!property) {
                        changes[property] = value;
                    } else if (this.sandbox.form.getObject(this.formId)) {
                        changes = this.sandbox.form.getData(this.formId);
                    } else {
                        return;
                    }

                    def = this.client.send(MESSAGE_HANDLER_NAME, {
                        command: 'update',
                        data: changes
                    });

                    def.then(applyChanges.bind(this));
                    def.fail(function(handler, message) {
                        this.sandbox.logger.error(
                            'Error in rendering preview:',
                            message.code,
                            message.message,
                            message.type
                        );
                    }.bind(this));

                    return def.promise();
                }
            },

            updateOnly = function() {
                if (!!this.initiated) {
                    var changes = {};

                    return this.client.send(MESSAGE_HANDLER_NAME, {
                        command: 'update',
                        data: changes
                    });
                }
            },

            applyChanges = function(handler, message) {
                if (!!message && !!message.data) {
                    this.sandbox.emit('sulu.preview.changes', message.data);
                }
            },

            /**
             * dom event to redirect changes
             * @param {Object} e
             */
            updateEvent = function(e) {
                if (!!this.data.id && !!this.initiated) {
                    var $element = $(e.currentTarget),
                        element = this.sandbox.dom.data($element, 'element'),
                        elementGroup = this.sandbox.dom.data($element, 'elementGroup'),
                        sequence = this.getSequence($element),
                        value;

                    if (!sequence || (!element && !elementGroup)) {
                        return;
                    }

                    if (!!elementGroup) {
                        value = elementGroup.getValue();
                    } else if (!!element) {
                        value = element.getValue();
                    }

                    update.call(this, sequence, value);
                }
            },

            bindCustomEvents = function() {
                this.sandbox.on('sulu.preview.update-property', function(property, value) {
                    update.call(this, property, value);
                }.bind(this));

                this.sandbox.on('sulu.preview.update-only', function() {
                    updateOnly.call(this);
                }.bind(this));

                this.sandbox.on('sulu.app.before-navigate', function() {
                    stop.call(this);
                }.bind(this));

                this.sandbox.on('sulu.preview.update', _.debounce(function($el, value) {
                    if (!!this.data.id) {
                        var property = this.getSequence($el);
                        update.call(this, property, value);
                    }
                }, this.config.delay), this);
            },

            bindDomEvents = function() {
                var changeFilter = '.preview-change-update, input[type="checkbox"].preview-update, input[type="radio"].preview-update, select.preview-update',
                    keyupFilter = '.preview-update:not(' + changeFilter + ', .no-preview-update)';

                this.sandbox.dom.on(this.formId, 'keyup', _.debounce(updateEvent.bind(this), this.config.delay), keyupFilter);
                this.sandbox.dom.on(this.formId, 'change', _.debounce(updateEvent.bind(this), 10), changeFilter);
            };

        return {
            sandbox: null,
            options: null,
            data: null,
            $el: null,

            initiated: false,

            formId: '#content-form',

            initialize: function(sandbox, options, $el) {
                this.sandbox = sandbox;
                this.options = options;
                this.$el = $el;

                this.config = Config.get('sulu.content.preview');

                bindCustomEvents.call(this);
                this.client = WebsocketManager.getClient(WEBSOCKET_APP_NAME, this.config.websocket);
            },

            start: function(data, options) {
                this.data = data;
                this.options = options;
                start.call(this).then(function() {
                    bindDomEvents.call(this);

                    this.sandbox.emit('sulu.preview.initiated');
                }.bind(this));
            },

            restart: function(data, options, template) {
                this.options = options;
                stop.call(this).then(function() {
                    start.call(this, template).then(function() {
                        bindDomEvents.call(this);

                        this.sandbox.emit('sulu.preview.initiated');
                    }.bind(this));
                }.bind(this));
            },

            getSequence: function($element, sandbox) {
                if (!!this.sandbox) {
                    sandbox = this.sandbox;
                }

                $element = $($element);
                var sequence = sandbox.dom.data($element, 'mapperProperty'),
                    $parents = $element.parents('*[data-mapper-property]'),
                    item = $element.parents('*[data-mapper-property-tpl]')[0],
                    parentProperty;

                while (!$element.data('element')) {
                    if ($element.length === 0) {
                        return false;
                    }

                    $element = $element.parent();
                }

                if ($parents.length > 0) {
                    parentProperty = sandbox.dom.data($parents[0], 'mapperProperty');
                    if (typeof parentProperty !== 'string') {
                        parentProperty = sandbox.dom.data($parents[0], 'mapperProperty')[0].data;
                    }
                    sequence = [
                        parentProperty,
                        $(item).index(),
                        sandbox.dom.data($element, 'mapperProperty')
                    ];
                }
                return sequence;
            }
        };
    };
});
