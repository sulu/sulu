/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['app-config', 'config', 'websocket-manager'], function(AppConfig, Config, WebsocketManager) {

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
                var def;
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

                    def.then(updateIframe.bind(this));

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

            updateIframe = function(handler, message) {
                this.sandbox.emit('sulu.preview.changes', message.data);
            },

            /**
             * dom event to redirect changes
             * @param {Object} e
             */
            updateEvent = function(e) {
                if (!!this.data.id && !!this.initiated) {
                    var $element = $(e.currentTarget),
                        element = this.sandbox.dom.data($element, 'element'),
                        sequence = this.getSequence($element);

                    if (!!sequence) {
                        update.call(this, sequence, element.getValue());
                    }
                }
            },

            /**
             * dom event wait for pause to redirect changes
             * @param {Object} e
             */
            delayedUpdateEvent = function(e) {
                if (!!this.data.id && !!this.initiated) {
                    var $element = $(e.currentTarget),
                        element = this.sandbox.dom.data($element, 'element'),
                        sequence = this.getSequence($element);

                    if (!!sequence) {
                        updateDelayed.call(this, sequence, element.getValue());
                    }
                }
            },

            updateDelayed = function(sequence, value) {
                if (this.timers[sequence]) {
                    window.clearTimeout(this.timers[sequence]);
                }

                this.timers[sequence] = window.setTimeout(function() {
                    this.timers[sequence] = null;
                    update.call(this, sequence, value);
                }.bind(this), this.config.delay);
            },

            bindCustomEvents = function() {
                this.sandbox.on('sulu.preview.update-property', function(property, value) {
                    update.call(this, property, value);
                }.bind(this));

                this.sandbox.on('sulu.preview.update-only', function() {
                    updateOnly.call(this);
                }.bind(this));

                this.sandbox.on('sulu.preview.update', function($el, value, changeOnKey) {
                    if (!!this.data.id) {
                        var property = this.getSequence($el);
                        updateDelayed.call(this, property, value);
                    }
                }, this);

                var changeFilter = 'input[type="checkbox"].preview-update, input[type="radio"].preview-update, select.preview-update',
                    keyupFilter = '.preview-update:not(' + changeFilter + ')';

                this.sandbox.dom.on(this.formId, 'keyup', delayedUpdateEvent.bind(this), keyupFilter);
                this.sandbox.dom.on(this.formId, 'change', updateEvent.bind(this), changeFilter);
            };

        return {
            timers: {},

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

                this.client = WebsocketManager.getClient(WEBSOCKET_APP_NAME, this.config.websocket);
            },

            start: function(data, options) {
                this.data = data;
                this.options = options;
                start.call(this).then(function() {
                    bindCustomEvents.call(this);

                    this.sandbox.emit('sulu.preview.initiated');
                }.bind(this));
            },

            restart: function(data, options, template) {
                this.options = options;
                stop.call(this).then(function() {
                    start.call(this, template).then(function() {
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
            },

            setContext: function(document, location) {
                this.document = document;
                this.location = location;
            }
        };
    };
});
