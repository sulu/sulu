/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

/**
 * @class Login
 * @constructor
 *
 * @param {Object} [options] Configuration object
 * @param {String} [options.instanceName] The instance name of the sidebar
 */

define([], function() {

    'use strict';

    var defaults = {
            instanceName: '',
            backgroundImg: 'http://upload.wikimedia.org/wikipedia/commons/3/33/Kanisfluh_Au1.JPG',
            shiftSpace: 60, //px
            fadeInDuration: 350
        },

        constants = {
            componentClass: 'sulu-login',
            backgroundClass: 'background',
            imageClass: 'image',
            darkenerClass: 'darkener',
            bgActiveClass: 'active',
            boxClass: 'box',
            frameClass: 'frame',
            framesClass: 'frames',
            logoClass: 'login-logo'
        },

        templates = {
            background: ['<div class="'+ constants.backgroundClass +'">',
                         '    <div class="'+ constants.imageClass+'"></div>',
                         '    <div class="'+ constants.darkenerClass+'"></div>',
                         '</div>'].join(''),
            box: ['<div class="'+ constants.boxClass +'">',
                  '    <div class="'+ constants.framesClass +'"></div>',
                  '</div>'].join(''),
            frame: ['<div class="'+ constants.frameClass +'">',
                    '   <div class="'+ constants.logoClass +'"></div>',
                    '</div>'].join(''),
            loginFrame: ['<input class="form-element input-large husky-validate" type="text" placeholder="<%= username %>"/>',
                         '<input class="form-element input-large husky-validate" type="password" placeholder="<%= password %>"/>'].join('')
        },

        /**
         * trigger after initialization has finished
         *
         * @event sulu.login.[INSTANCE_NAME].initialized
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        },

        createEventName = function(postfix) {
            return 'sulu.login.' + ((!!this.options.instanceName) ? this.options.instanceName + '.' : '') + postfix;
        };

    return {

        /**
         * Initializes the component
         */
        initialize: function() {
            // merge defaults
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.initProperties();
            this.render();
            this.bindDomEvents();
            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Initializes the component properties
         */
        initProperties: function() {
            this.dom = {
                $bg: null,
                $box: null,
                $frames: null,
                $login: null,
                $reset: null
            }
        },

        /**
         * Renders the component
         */
        render: function() {
            this.sandbox.dom.addClass(this.$el, constants.componentClass);
            this.renderBg();
            this.renderBox();
        },

        /**
         * Render background
         */
        renderBg: function() {
            var $img = this.sandbox.dom.createElement('<img/>'); // used to load the image
            this.sandbox.dom.one($img, 'load', this.showBg.bind(this));
            this.dom.$bg = this.sandbox.dom.createElement(templates.background);
            this.sandbox.dom.attr($img, 'src', this.options.backgroundImg);
            this.sandbox.dom.css(
                this.sandbox.dom.find('.' + constants.imageClass, this.dom.$bg),
                'background-image', 'url("'+ this.options.backgroundImg +'")'
            );
            this.setBgSize();
            this.setBgPosition(0, 0);
            this.sandbox.dom.append(this.$el, this.dom.$bg);
        },

        /**
         * Renders the content-box
         */
        renderBox: function() {
            this.dom.$box = this.sandbox.dom.createElement(templates.box);
            this.dom.$frames = this.sandbox.dom.find('.' + constants.framesClass, this.dom.$box);
            this.renderLoginFrame();
            this.renderResetPwdFrame();
            this.sandbox.dom.append(this.$el, this.dom.$box);
        },

        /**
         * Renders the frame with the login inputs
         */
        renderLoginFrame: function() {
            this.dom.$login = this.sandbox.dom.createElement(templates.frame);
            this.sandbox.dom.append(this.dom.$login, this.sandbox.util.template(templates.loginFrame)({
                username: 'Username',
                password: 'Password'
            }));
            this.sandbox.dom.append(this.dom.$frames, this.dom.$login);
        },

        /**
         * Renders the frame with the password-reset functionality
         */
        renderResetPwdFrame: function() {
            this.dom.$reset = this.sandbox.dom.createElement(templates.frame);
            this.sandbox.dom.append(this.dom.$frames, this.dom.$reset);
        },

        /**
         * Fades the background in
         */
        showBg: function() {
            this.sandbox.dom.fadeIn(this.dom.$bg, this.options.fadeInDuration);
        },

        /**
         * Sets the the size of the background to window size
         * plus a the shift-space
         */
        setBgSize: function() {
            this.sandbox.dom.width(this.dom.$bg, this.sandbox.dom.width(this.sandbox.dom.window) + this.options.shiftSpace * 2);
            this.sandbox.dom.height(this.dom.$bg, this.sandbox.dom.height(this.sandbox.dom.window) + this.options.shiftSpace * 2);
        },

        /**
         * Sets the Bg active or inactive
         * @param active - true to set active, false to set unactive
         */
        toggleBgActive: function(active) {
            if (active === true) {
                this.sandbox.dom.addClass(this.dom.$bg, constants.bgActiveClass);
            } else {
                this.sandbox.dom.removeClass(this.dom.$bg, constants.bgActiveClass);
            }
        },

        /**
         * centers the background plus a shift in x and a shift in y
         * @param {Number} shiftX - shift in x (horizontal) (in pixel)
         * @param {Number} shiftY - shift in y (vertical) (in pixel)
         */
        setBgPosition: function(shiftX, shiftY) {
            shiftX = shiftX || 0;
            shiftY = shiftY || 0;
            this.sandbox.dom.css(this.dom.$bg, 'left', (shiftX - this.options.shiftSpace) + 'px');
            this.sandbox.dom.css(this.dom.$bg, 'top', (shiftY - this.options.shiftSpace) + 'px');
        },

        /**
         * Binds Dom-related events
         */
        bindDomEvents: function() {
            this.sandbox.dom.on(this.sandbox.dom.window, 'resize', this.resizeHandler.bind(this));
            this.sandbox.dom.on(this.dom.$bg, 'mousedown', this.toggleBgActive.bind(this, true));
            this.sandbox.dom.on(this.dom.$bg, 'mouseup', this.toggleBgActive.bind(this, false));
        },

        /**
         * Handles the window resize event
         * @param event
         */
        resizeHandler: function(event) {
            this.setBgSize();
        }
    };
});
