/**
 * This file is part of Husky frontend development framework.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 * @module husky/components/ckeditor
 */


/**
 * @class CKEditor
 * @constructor
 *
 * @params {Object} [options] Configuration object
 * @params {Function} [options.initialzedCallback] Callback when initialization is finished
 *
 */
define([], function() {

    'use strict';

    var defaults = {
            initializedCallback: null,
            instanceName: null,
            tableEnabled: true,
            linksEnabled: true,
            scriptEnabled: true,
            iframeEnabled: true,
            pasteFromWord: true,
            height: null,
            maxHeight: null,
            enterMode: null
        },

        /**
         * namespace for events
         * @type {string}
         */
        eventNamespace = 'husky.ckeditor.',

        /**
         * @event husky.ckeditor.changed
         * @description the component has loaded everything successfully and will be rendered
         */
        CHANGED = function() {
            return eventNamespace + (this.options.instanceName !== null ? this.options.instanceName + '.' : '') + 'changed';
        },

        /**
         * @event husky.ckeditor.focusout
         * @description triggered when focus of editor is lost
         */
        FOCUSOUT = function() {
            return eventNamespace + (this.options.instanceName !== null ? this.options.instanceName + '.' : '') + 'focusout';
        },

        /**
         * @event husky.ckeditor.start
         * @description starts the used editor plugin
         */
        START = function() {
            return eventNamespace + (this.options.instanceName !== null ? this.options.instanceName + '.' : '') + 'start';
        },

        /**
         * @event husky.ckeditor.destroy
         * @description destroys the used editor plugin
         */
        DESTROY = function() {
            return eventNamespace + (this.options.instanceName !== null ? this.options.instanceName + '.' : '') + 'destroy';
        },

        /**
         * Removes the not needed elements from the config object for the ckeditor
         * @returns {Object} configuration object for ckeditor
         */
        getConfig = function() {
            var config = this.sandbox.util.extend(false, {}, this.options);

            config.toolbar = [
                { name: 'semantics', items: ['Format']},
                { name: 'basicstyles', items: [ 'Superscript', 'Subscript', 'Italic', 'Bold', 'Underline', 'Strike'] },
                { name: 'blockstyles', items: [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'] },
                { name: 'list', items: [ 'NumberedList', 'BulletedList'] }
            ];

            // activate paste from Word
            if (this.options.pasteFromWord === true) {
                config.toolbar.push({ name: 'paste', items: [ 'PasteFromWord' ] });
            }

            // activate embed links
            if (this.options.linksEnabled === true) {
                config.toolbar.push({ name: 'links', items: [ 'Link', 'Unlink' ] });
                config.linkShowTargetTab = false;
            }

            // activate tables
            if (this.options.tableEnabled === true) {
                config.toolbar.push({ name: 'insert', items: [ 'Table' ] });
            }

            // set height
            if (!!this.options.height) {
                config.autoGrow_minHeight = this.options.height;
                config.height = this.options.height;
            }

            // set maxHeight
            if (!!this.options.maxHeight) {
                config.autoGrow_maxHeight = this.options.maxHeight;
                // if height bigger maxHeight height = maxHeight
                if (config.height > config.autoGrow_maxHeight) {
                    config.autoGrow_maxHeight = config.height;
                }
            }

            // ENTER MODE
            if (!!this.options.enterMode) {
                config.enterMode = CKEDITOR['ENTER_' + this.options.enterMode.toUpperCase()];
            }


            // extra allowed
            var extraAllowedContent = '';

            // extra allowed content iframe
            if (this.options.iframeEnabled === true) {
                extraAllowedContent += ' iframe(*)[src,border,frameborder,width,height,style,allowfullscreen,name,marginheight,marginwidth,seamless,srcdoc];';
            }

            // extra allowed content iframe
            if (this.options.scriptEnabled === true) {
                extraAllowedContent += ' script(*)[src,type,defer,async,charset];';
            }

            config.toolbar.push({ name: 'code', items: [ 'Source' ] });

            delete config.initializedCallback;
            delete config.baseUrl;
            delete config.el;
            delete config.property;
            delete config.name;
            delete config.ref;
            delete config._ref;
            delete config.require;
            delete config.element;
            delete config.linksEnabled;
            delete config.tableEnabled;
            delete config.scriptEnabled;
            delete config.iframeEnabled;
            delete config.maxHeight;

            // allow img tags to have any class (*) and any attribute [*]
            config.extraAllowedContent = 'img(*)[src,width,height,title,alt]; a(*)[href,target,type,rel,name,title];' + extraAllowedContent;

            return config;
        };

    return {

        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.editorContent = null;

            this.startEditor();
            this.data = this.editor.getData();

            this.bindChangeEvents();

            this.editor.on('instanceReady', function() {
                // bind class to editor
                this.sandbox.dom.addClass(this.sandbox.dom.find('.cke', this.sandbox.dom.parent(this.$el)), 'form-element');
            }.bind(this));

            this.editor.on('blur', function() {
                this.sandbox.emit(FOCUSOUT.call(this), this.editor.getData(), this.$el);
            }.bind(this));

            this.sandbox.on(START.call(this), this.startEditor.bind(this));

            this.sandbox.on(DESTROY.call(this), this.destroyEditor.bind(this));
        },

        /**
         * Binds Events to emit a custom changed event
         */
        bindChangeEvents: function() {
            this.editor.on('change', function() {
                this.emitChangedEvent();
            }.bind(this));

            // check if the content of the editor has changed if the mode is switched (html/wisiwig)
            this.editor.on('mode', function() {
                if (this.data !== this.editor.getData()) {
                    this.emitChangedEvent();
                }
            }.bind(this));
        },

        /**
         * Emits the custom changed event
         */
        emitChangedEvent: function() {
            this.data = this.editor.getData();
            this.sandbox.emit(CHANGED.call(this), this.data, this.$el);
        },

        startEditor: function() {
            var config = getConfig.call(this);
            this.editor = this.sandbox.ckeditor.init(this.$el, this.options.initializedCallback, config);

            if (!!this.editorContent) {
                this.editor.setData(this.editorContent);
            }
        },

        destroyEditor: function() {
            if (!!this.editor) {
                this.editorContent = this.editor.getData();
                if (this.editor.window.getFrame()) {
                    this.editor.destroy();
                } else {
                    delete CKEDITOR.instances[this.editor.name];
                }
            }
        },

        remove: function() {
            var instance = this.sandbox.ckeditor.getInstance(this.options.instanceName);

            if (!!instance) {
                // FIXME HACK
                // this hack fix 'clearCustomData' not null on template change
                // this error come when editor dom element don't exists
                // check if dom element exist else remove the instance from object
                // should also fix memory leak that the instances are not deleted from CKEDITOR
                if (instance.window.getFrame()) {
                    instance.destroy();
                } else {
                    delete CKEDITOR.instances[this.options.instanceName];
                }
            }
        }
    };

});
