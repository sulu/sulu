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
 * @param {String} [options.backgroundImg] url to the background image
 * @param {String} [options.shiftSpace] numbers of pixels on each edge available for moving the image
 * @param {String} [options.fadeInDuration] fade in duration of the image
 * @param {String} [options.loginCheck] path to post the login-credentials to
 */

define([], function() {

    'use strict';

    var defaults = {
            instanceName: '',
            backgroundImg: '/bundles/suluadmin/img/background.jpg',
            shiftSpace: 50, //px
            fadeInDuration: 350,
            loginCheck: '',
            resetUrl: '',
            resendUrl: '',
            translations: {
                resetPassword: 'sulu.login.reset-password',
                reset: 'public.reset',
                email: 'public.email',
                backLogin: 'sulu.login.back-login',
                resend: 'sulu.login.resend-email',
                emailSent: 'sulu.login.email-sent-msg',
                backWebsite: 'sulu.login.back-website',
                login: 'public.login',
                errorMsg: 'sulu.login.error-msg',
                forgotPassword: 'sulu.login.forgot-password',
                emailUser: 'sulu.login.email-username',
                password: 'public.password'
            }
        },

        constants = {
            componentClass: 'sulu-login',
            backgroundClass: 'background',
            imageClass: 'image',
            darkenerClass: 'darkener',
            bgActiveClass: 'active',
            boxClass: 'box',
            boxLargerClass: 'larger',
            frameClass: 'frame',
            loginFrameClass: 'login',
            resetFrameClass: 'reset',
            framesClass: 'frames',
            logoClass: 'login-logo',
            loginBtnId: 'login-btn',
            forgotSwitchClass: 'forgot-password-switch',
            websiteSwitchClass: 'website-switch',
            loginSwitchClass: 'login-switch',
            errorClass: 'husky-validate-error',
            resetMailBoxClass: 'reset-mail',
            resetMsgBoxClass: 'reset-msg',
            loginLoaderClass: 'login-loader'
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
            loginFrame: ['<form class="inputs">',
                         '  <div class="grid-row">',
                         '    <input class="form-element input-large husky-validate" type="text" name="username" id="username" placeholder="<%= emailUser %>"/>',
                         '  </div>',
                         '  <div class="grid-row small">',
                         '    <input class="form-element input-large husky-validate" type="password" name="password" id="password" placeholder="<%= password %>"/>',
                         '  </div>',
                         '  <span class="error-msg"><%= errorMsg %></span>',
                         '</form>',
                         '<div class="grid-row">',
                         '    <div id="'+ constants.loginBtnId +'" class="btn action large fit"><%= login %></div>',
                         '    <div class="'+ constants.loginLoaderClass +'"></div>',
                         '</div>',
                         '<div class="bottom-container small-font">',
                         '  <span class="'+ constants.websiteSwitchClass +'"><%= backWebsiteMsg %></span>',
                         '  <span class="'+ constants.forgotSwitchClass +'"><%= forgotPwdMsg %></span>',
                         '</div>'].join(''),
            resetPwdFrame: ['<div class="'+ constants.resetMailBoxClass +'">',
                            '  <div class="inputs">',
                            '    <div class="grid-row small">',
                            '      <label for="email"><%= label %></label>',
                            '      <input id="email" class="form-element input-large husky-validate" type="text" placeholder="<%= email %>"/>',
                            '    </div>',
                            '  </div>',
                            '  <div class="grid-row">',
                            '      <div class="btn action large fit"><%= reset %></div>',
                            '  </div>',
                            '</div>',
                            '<div class="'+ constants.resetMsgBoxClass +'">',
                            '<span class="msg"><%= sentMsg %></span>',
                            '   <div class="btn action large fit"><%= resend %></div>',
                            '</div>',
                            '<div class="bottom-container small-font">',
                            '  <span class="'+ constants.websiteSwitchClass +'"><%= backWebsiteMsg %></span>',
                            '  <span class="'+ constants.loginSwitchClass +'"><%= backLoginMsg %></span>',
                            '</div>'].join('')

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
            this.focusUsername();
            alert(this.options.resendUrl);
            alert(this.options.resetUrl);
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
                $reset: null,
                $forgotSwitch: null,
                $loginSwitch: null,
                $loginBtn: null,
                $loginForm: null,
                $loginLoader: null
            };
            this.mouseOrigin = {
                x: 0,
                y: 0
            };
            this.setMovementRatio();
        },

        /**
         * Calcualtes ratios which tells how much pixel the background moves per pixel the mosue moved
         */
        setMovementRatio: function() {
            this.movementRatio = {
                x: this.options.shiftSpace/this.sandbox.dom.width(this.sandbox.dom.$window),
                y: this.options.shiftSpace/this.sandbox.dom.height(this.sandbox.dom.$window)
            };
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
            this.setBgPosition(-this.options.shiftSpace, -this.options.shiftSpace);
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
            this.sandbox.dom.addClass(this.dom.$login, constants.loginFrameClass);
            this.sandbox.dom.append(this.dom.$login, this.sandbox.util.template(templates.loginFrame)({
                emailUser: this.sandbox.translate(this.options.translations.emailUser),
                password: this.sandbox.translate(this.options.translations.password),
                forgotPwdMsg: this.sandbox.translate(this.options.translations.forgotPassword),
                errorMsg: this.sandbox.translate(this.options.translations.errorMsg),
                login: this.sandbox.translate(this.options.translations.login),
                backWebsiteMsg: this.sandbox.translate(this.options.translations.backWebsite)
            }));
            this.dom.$forgotSwitch = this.sandbox.dom.find('.' + constants.forgotSwitchClass, this.dom.$login);
            this.dom.$loginBtn = this.sandbox.dom.find('#' + constants.loginBtnId, this.dom.$login);
            this.dom.$loginForm = this.sandbox.dom.find('form', this.dom.$login);
            this.renderLoginLoader();
            this.sandbox.dom.append(this.dom.$frames, this.dom.$login);
        },

        /**
         * Renderes the login loader
         */
        renderLoginLoader: function() {
            this.dom.$loginLoader = this.sandbox.dom.find('.' + constants.loginLoaderClass, this.dom.$login);
            this.sandbox.dom.hide(this.dom.$loginLoader);
            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: this.dom.$loginLoader,
                        size: '20px',
                        color: '#666666'
                    }
                }
            ]);
        },

        /**
         * Sets the focus to the username input
         */
        focusUsername: function() {
            this.sandbox.dom.select(this.sandbox.dom.find('#username', this.dom.$loginForm));
        },

        /**
         * Renders the frame with the password-reset functionality
         */
        renderResetPwdFrame: function() {
            this.dom.$reset = this.sandbox.dom.createElement(templates.frame);
            this.sandbox.dom.addClass(this.dom.$reset, constants.resetFrameClass);
            this.sandbox.dom.append(this.dom.$reset, this.sandbox.util.template(templates.resetPwdFrame)({
                label: this.sandbox.translate(this.options.translations.resetPassword),
                reset: this.sandbox.translate(this.options.translations.reset),
                email: this.sandbox.translate(this.options.translations.email),
                backLoginMsg: this.sandbox.translate(this.options.translations.backLogin),
                resend: this.sandbox.translate(this.options.translations.resend),
                sentMsg: this.sandbox.translate(this.options.translations.emailSent),
                backWebsiteMsg: this.sandbox.translate(this.options.translations.backWebsite)
            }));
            this.dom.$loginSwitch = this.sandbox.dom.find('.' + constants.loginSwitchClass, this.dom.$reset);
            this.sandbox.dom.hide(this.sandbox.dom.find('.' + constants.resetMsgBoxClass, this.dom.$reset));
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
            var positionX = this.sandbox.dom.position(this.dom.$bg).left + shiftX,
                positionY = this.sandbox.dom.position(this.dom.$bg).top + shiftY,
                padding = 3; // additional pixels which can't be seen - makes sure there is never a white border
            if (positionX > -padding) {
                positionX = -padding;
            } else if (positionX < (this.options.shiftSpace* -2) + padding) {
                positionX = (this.options.shiftSpace* -2) + padding;
            }
            if (positionY > -padding) {
                positionY = -padding;
            } else if (positionY < (this.options.shiftSpace* -2) + padding) {
                positionY = (this.options.shiftSpace* -2) + padding;
            }
            this.sandbox.dom.css(this.dom.$bg, 'left', positionX + 'px');
            this.sandbox.dom.css(this.dom.$bg, 'top', positionY + 'px');
        },

        /**
         * Binds Dom-related events
         */
        bindDomEvents: function() {
            this.sandbox.dom.on(this.sandbox.dom.window, 'resize', this.resizeHandler.bind(this));
            this.sandbox.dom.on(this.sandbox.dom.window, 'mouseenter', this.mouseenterHandler.bind(this));
            this.sandbox.dom.on(this.dom.$bg, 'mousedown', this.toggleBgActive.bind(this, true));
            this.sandbox.dom.on(this.sandbox.dom.window, 'mouseup', this.toggleBgActive.bind(this, false));
            this.sandbox.dom.on(this.dom.$forgotSwitch, 'click', this.moveToFrame.bind(this, this.dom.$reset));
            this.sandbox.dom.on(this.dom.$loginSwitch, 'click', this.moveToFrame.bind(this, this.dom.$login));
            this.sandbox.dom.on(this.dom.$loginBtn, 'click', this.loginBtnClickHandler.bind(this));
            this.sandbox.dom.on(this.dom.$loginForm, 'submit', this.loginFormSubmitHandler.bind(this));
            this.sandbox.dom.on(this.dom.$loginForm, 'keydown', this.loginFormKeyHandler.bind(this));
            this.sandbox.dom.on(this.sandbox.dom.window, 'mousemove', this.mousemoveHandler.bind(this));
            this.sandbox.dom.on(this.dom.$box, 'click',
                this.redirect.bind(this, this.sandbox.dom.window.location.origin), '.' + constants.websiteSwitchClass);
        },

        /**
         * Handles the window's mousenter-event
         * @param event
         */
        mouseenterHandler: function(event) {
            if (event.relatedTarget === null) {
                this.mouseOrigin.x = event.pageX;
                this.mouseOrigin.y = event.pageY;
            }
        },

        /**
         * Handles the window's mousemove-event
         * @param event
         */
        mousemoveHandler: function(event) {
            var changeInX = (event.pageX - this.mouseOrigin.x) * -this.movementRatio.x,
                changeInY = (event.pageY - this.mouseOrigin.y) * -this.movementRatio.y;
            this.setBgPosition(changeInX, changeInY);
            this.mouseenterHandler(event);
        },

        /**
         * Handles a click on the login-button
         */
        loginBtnClickHandler: function() {
            this.sandbox.dom.submit(this.dom.$loginForm);
        },

        /**
         * Handles the submit event of the login-form
         */
        loginFormSubmitHandler: function() {
            var username = this.sandbox.dom.trim(this.sandbox.dom.val(this.sandbox.dom.find('#username', this.dom.$loginForm))),
                password = this.sandbox.dom.trim(this.sandbox.dom.val(this.sandbox.dom.find('#password', this.dom.$loginForm)));
            if (username.length === 0 || password.length === 0) {
                this.displayLoginError();
            } else {
                this.login(username, password);
            }
            return false;
        },
        /**
         * Handles the keydown-event of the login-form
         * @param event
         */
        loginFormKeyHandler: function(event) {
            if (event.keyCode === 13) { //on enter
                this.loginFormSubmitHandler();
            }
        },

        /**
         * Sends toe username and password to the server
         * @param username
         * @param password
         */
        login: function(username, password) {
            this.sandbox.dom.show(this.dom.$loginLoader);
            this.sandbox.util.save(this.options.loginCheck, 'POST', {
                '_username': username,
                '_password': password
            }).then(function(data) {
                if (data.success === true && !!data.url) {
                    this.redirect(data.url + this.sandbox.dom.window.location.hash);
                } else {
                    this.sandbox.dom.hide(this.dom.$loginLoader);
                    this.displayLoginError();
                }
            }.bind(this))
        },

        /**
         * Redirects the page to a url
         * @param url
         */
        redirect: function(url) {
            this.sandbox.dom.window.location = url;
        },

        /**
         * Adds a css class -> inputs become red..
         */
        displayLoginError: function() {
            this.sandbox.dom.addClass(this.dom.$box, constants.boxLargerClass);
            this.sandbox.dom.addClass(this.dom.$login, constants.errorClass);
        },

        /**
         * Moves the frames container so that a given frame is
         * visible in the box
         * @param $frame
         */
        moveToFrame: function($frame) {
            this.sandbox.dom.css(this.dom.$frames, 'left', -(this.sandbox.dom.position($frame).left) + 'px');
        },

        /**
         * Handles the window resize event
         */
        resizeHandler: function() {
            this.setBgSize();
            this.setMovementRatio();
        }
    };
});
