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
            loginUrl: '',
            loginCheck: '',
            resetMailUrl: '',
            resendUrl: '',
            resetUrl: '',
            resetToken: '',
            resetMode: false,
            translations: {
                resetPassword: 'sulu.login.reset-password',
                reset: 'public.reset',
                backLogin: 'sulu.login.back-login',
                resend: 'sulu.login.resend-email',
                emailSent: 'sulu.login.email-sent-message',
                backWebsite: 'sulu.login.back-website',
                login: 'public.login',
                errorMessage: 'sulu.login.error-message',
                forgotPassword: 'sulu.login.forgot-password',
                emailUser: 'sulu.login.email-username',
                password: 'public.password',
                emailSentSuccess: 'sulu.login.email-sent-success',
                enterNewPassword: 'sulu.login.enter-new-password',
                repeatPassword: 'sulu.login.repeat-password'
            },
            errorTranslations: {
                0: 'sulu.login.mail.user-not-found',
                1003: 'sulu.login.mail.already-sent',
                1005: 'sulu.login.token-does-not-exist',
                1007: 'sulu.login.mail.limit-reached'
            }
        },

        constants = {
            componentClass: 'sulu-login',
            backgroundClass: 'background',
            imageClass: 'image',
            darkenerClass: 'darkener',
            backgroundActiveClass: 'active',
            boxClass: 'box',
            boxLargerClass: 'larger',
            frameClass: 'frame',
            loginFrameClass: 'login',
            resetMailFrameClass: 'reset-mail',
            resetFrameClass: 'reset',
            framesClass: 'frames',
            logoClass: 'login-logo',
            loginButtonId: 'login-button',
            resetButtonId: 'reset-button',
            resetMailButtonId: 'reset-mail-button',
            resendButtonId: 'resend-button',
            forgotSwitchClass: 'forgot-password-switch',
            websiteSwitchClass: 'website-switch',
            loginSwitchClass: 'login-switch',
            errorClass: 'husky-validate-error',
            resetMailBoxClass: 'reset-mail',
            resetMailMessageBoxClass: 'reset-mail-message',
            loaderClass: 'login-loader',
            loginRouteClass: 'login-route'
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
                         '  <span class="error-message"><%= errorMessage %></span>',
                         '</form>',
                         '<div class="grid-row">',
                         '    <div id="'+ constants.loginButtonId +'" class="btn action large fit"><%= login %></div>',
                         '</div>',
                         '<div class="bottom-container small-font">',
                         '  <span class="'+ constants.websiteSwitchClass +'"><%= backWebsiteMessage %></span>',
                         '  <span class="'+ constants.forgotSwitchClass +'"><%= forgotPasswordMessage %></span>',
                         '</div>'].join(''),
            resetMailFrame: ['<div class="'+ constants.resetMailBoxClass +'">',
                            '  <div class="inputs">',
                            '    <div class="grid-row small">',
                            '      <label for="user"><%= label %></label>',
                            '      <input id="user" class="form-element input-large husky-validate" type="text" placeholder="<%= emailUser %>" tabindex="-1"/>',
                            '    </div>',
                            '    <span class="error-message"></span>',
                            '  </div>',
                            '  <div class="grid-row">',
                            '      <div id="'+ constants.resetMailButtonId +'" class="btn action large fit"><%= reset %></div>',
                            '  </div>',
                            '</div>',
                            '<div class="'+ constants.resetMailMessageBoxClass +'">',
                            '<span class="message"><%= sentMessage %> <span class="to-mail"></span></span>',
                            '   <div id="'+ constants.resendButtonId +'" class="btn action large fit"><%= resend %></div>',
                            '</div>',
                            '<div class="bottom-container small-font">',
                            '  <span class="'+ constants.websiteSwitchClass +'"><%= backWebsiteMessage %></span>',
                            '  <span class="'+ constants.loginSwitchClass +'"><%= backLoginMessage %></span>',
                            '</div>'].join(''),
                resetFrame: ['<div class="'+ constants.resetMailBoxClass +'">',
                             '  <div class="inputs">',
                             '    <div class="grid-row">',
                             '      <label for="password1"><%= password1Label %></label>',
                             '      <input id="password1" class="form-element input-large husky-validate" type="password" placeholder="<%= password %>"/>',
                             '    </div>',
                             '    <div class="grid-row small">',
                             '      <label for="password2"><%= password2Label %></label>',
                             '      <input id="password2" class="form-element input-large husky-validate" type="password" placeholder="<%= password %>"/>',
                             '    </div>',
                             '  </div>',
                             '  <div class="grid-row">',
                             '      <div id="'+ constants.resetButtonId +'" class="btn action large fit"><%= login %></div>',
                             '  </div>',
                             '</div>',
                             '<div class="bottom-container small-font">',
                             '  <span class="'+ constants.websiteSwitchClass +'"><%= backWebsiteMessage %></span>',
                             '  <span class="'+ constants.loginRouteClass +'"><%= loginRouteMessage %></span>',
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
            if (this.options.resetMode === false) {
                this.bindDomEvents();
            } else {
                this.bindResetDomEvents();
            }
            this.focusUsername();
            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Initializes the component properties
         */
        initProperties: function() {
            this.dom = {
                $background: null,
                $box: null,
                $frames: null,
                $login: null,
                $resetMail: null,
                $forgotSwitch: null,
                $loginSwitch: null,
                $loginButton: null,
                $resetMailButton: null,
                $resendButton: null,
                $loginForm: null,
                $loader: null,
                $reset: null,
                $resetButton: null
            };
            this.mouseOrigin = {
                x: 0,
                y: 0
            };
            this.resetMailUser = null;
            this.setMovementRatio();
        },

        /**
         * Calcualtes ratios which tells how much pixel the background moves per pixel the mosue moved
         */
        setMovementRatio: function() {
            this.movementRatio = {
                x: this.options.shiftSpace / this.sandbox.dom.width(this.sandbox.dom.$window),
                y: this.options.shiftSpace / this.sandbox.dom.height(this.sandbox.dom.$window)
            };
        },

        /**
         * Renders the component
         */
        render: function() {
            this.sandbox.dom.addClass(this.$el, constants.componentClass);
            this.renderBackground();
            this.renderBox();
        },

        /**
         * Render background
         */
        renderBackground: function() {
            var $img = this.sandbox.dom.createElement('<img/>'); // used to load the image
            this.sandbox.dom.one($img, 'load', this.showBackground.bind(this));
            this.dom.$background = this.sandbox.dom.createElement(templates.background);
            this.sandbox.dom.attr($img, 'src', this.options.backgroundImg);
            this.sandbox.dom.css(
                this.sandbox.dom.find('.' + constants.imageClass, this.dom.$background),
                'background-image', 'url("'+ this.options.backgroundImg +'")'
            );
            this.setBackgroundSize();
            this.setBackgroundPosition(-this.options.shiftSpace, -this.options.shiftSpace);
            this.sandbox.dom.append(this.$el, this.dom.$background);
        },

        /**
         * Renders the content-box
         */
        renderBox: function() {
            this.dom.$box = this.sandbox.dom.createElement(templates.box);
            this.dom.$frames = this.sandbox.dom.find('.' + constants.framesClass, this.dom.$box);
            if (this.options.resetMode === false) {
                this.renderLoginFrame();
                this.renderResetMailFrame();
            } else {
                this.renderResetFrame();
            }
            this.renderLoader();
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
                forgotPasswordMessage: this.sandbox.translate(this.options.translations.forgotPassword),
                errorMessage: this.sandbox.translate(this.options.translations.errorMessage),
                login: this.sandbox.translate(this.options.translations.login),
                backWebsiteMessage: this.sandbox.translate(this.options.translations.backWebsite)
            }));
            this.dom.$forgotSwitch = this.sandbox.dom.find('.' + constants.forgotSwitchClass, this.dom.$login);
            this.dom.$loginButton = this.sandbox.dom.find('#' + constants.loginButtonId, this.dom.$login);
            this.dom.$loginForm = this.sandbox.dom.find('form', this.dom.$login);
            this.sandbox.dom.append(this.dom.$frames, this.dom.$login);
        },

        /**
         * Renderes the login loader
         */
        renderLoader: function() {
            this.dom.$loader = this.sandbox.dom.createElement('<div class="'+ constants.loaderClass +'"/>');
            this.sandbox.dom.hide(this.dom.$loader);
            this.sandbox.dom.append(this.dom.$box, this.dom.$loader);
            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: this.dom.$loader,
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
         * Renders the frame with the password-reset-mail functionality
         */
        renderResetMailFrame: function() {
            this.dom.$resetMail = this.sandbox.dom.createElement(templates.frame);
            this.sandbox.dom.addClass(this.dom.$resetMail, 'hide');
            this.sandbox.dom.addClass(this.dom.$resetMail, constants.resetMailFrameClass);
            this.sandbox.dom.append(this.dom.$resetMail, this.sandbox.util.template(templates.resetMailFrame)({
                label: this.sandbox.translate(this.options.translations.resetPassword),
                reset: this.sandbox.translate(this.options.translations.reset),
                emailUser: this.sandbox.translate(this.options.translations.emailUser),
                backLoginMessage: this.sandbox.translate(this.options.translations.backLogin),
                resend: this.sandbox.translate(this.options.translations.resend),
                sentMessage: this.sandbox.translate(this.options.translations.emailSent),
                backWebsiteMessage: this.sandbox.translate(this.options.translations.backWebsite)
            }));
            this.dom.$loginSwitch = this.sandbox.dom.find('.' + constants.loginSwitchClass, this.dom.$resetMail);
            this.dom.$resetMailButton = this.sandbox.dom.find('#' + constants.resetMailButtonId, this.dom.$resetMail);
            this.dom.$resendButton = this.sandbox.dom.find('#' + constants.resendButtonId, this.dom.$resetMail);
            this.sandbox.dom.hide(this.sandbox.dom.find('.' + constants.resetMailMessageBoxClass, this.dom.$resetMail));
            this.sandbox.dom.append(this.dom.$frames, this.dom.$resetMail);
        },

        /**
         * Renders the frame for resetting the password
         */
        renderResetFrame: function() {
            this.sandbox.dom.addClass(this.dom.$box, constants.boxLargerClass);
            this.dom.$reset = this.sandbox.dom.createElement(templates.frame);
            this.sandbox.dom.addClass(this.dom.$reset, constants.resetFrameClass);
            this.sandbox.dom.append(this.dom.$reset, this.sandbox.util.template(templates.resetFrame)({
                password1Label: this.sandbox.translate(this.options.translations.enterNewPassword),
                password2Label: this.sandbox.translate(this.options.translations.repeatPassword),
                password: this.sandbox.translate(this.options.translations.password),
                login: this.sandbox.translate(this.options.translations.login),
                backWebsiteMessage: this.sandbox.translate(this.options.translations.backWebsite),
                loginRouteMessage: this.sandbox.translate(this.options.translations.backLogin)
            }));
            this.dom.$resetButton = this.sandbox.dom.find('#' + constants.resetButtonId, this.dom.$reset);
            this.sandbox.dom.append(this.dom.$frames, this.dom.$reset);
        },

        /**
         * Fades the background in
         */
        showBackground: function() {
            this.sandbox.dom.fadeIn(this.dom.$background, this.options.fadeInDuration);
        },

        /**
         * Sets the the size of the background to window size
         * plus a the shift-space
         */
        setBackgroundSize: function() {
            this.sandbox.dom.width(this.dom.$background, this.sandbox.dom.width(this.sandbox.dom.window) + this.options.shiftSpace * 2);
            this.sandbox.dom.height(this.dom.$background, this.sandbox.dom.height(this.sandbox.dom.window) + this.options.shiftSpace * 2);
        },

        /**
         * Sets the Background active or inactive
         * @param active - true to set active, false to set unactive
         */
        toggleBackgroundActive: function(active) {
            if (active === true) {
                this.sandbox.dom.addClass(this.dom.$background, constants.backgroundActiveClass);
            } else {
                this.sandbox.dom.removeClass(this.dom.$background, constants.backgroundActiveClass);
            }
        },

        /**
         * centers the background plus a shift in x and a shift in y
         * @param {Number} shiftX - shift in x (horizontal) (in pixel)
         * @param {Number} shiftY - shift in y (vertical) (in pixel)
         */
        setBackgroundPosition: function(shiftX, shiftY) {
            var positionX = this.sandbox.dom.position(this.dom.$background).left + shiftX,
                positionY = this.sandbox.dom.position(this.dom.$background).top + shiftY,
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
            this.sandbox.dom.css(this.dom.$background, 'left', positionX + 'px');
            this.sandbox.dom.css(this.dom.$background, 'top', positionY + 'px');
        },

        /**
         * Binds Dom-related events
         */
        bindDomEvents: function() {
            this.sandbox.dom.on(this.sandbox.dom.window, 'resize', this.resizeHandler.bind(this));
            this.sandbox.dom.on(this.sandbox.dom.window, 'mouseenter', this.mouseenterHandler.bind(this));
            this.sandbox.dom.on(this.dom.$background, 'mousedown', this.toggleBackgroundActive.bind(this, true));
            this.sandbox.dom.on(this.sandbox.dom.window, 'mouseup', this.toggleBackgroundActive.bind(this, false));
            this.sandbox.dom.on(this.dom.$forgotSwitch, 'click', this.moveToResetMail.bind(this));
            this.sandbox.dom.on(this.dom.$loginSwitch, 'click', this.moveToLogin.bind(this));
            this.sandbox.dom.on(this.dom.$loginButton, 'click', this.loginButtonClickHandler.bind(this));
            this.sandbox.dom.on(this.dom.$resetMailButton, 'click', this.resetMailButtonClickHandler.bind(this));
            this.sandbox.dom.on(this.dom.$resendButton, 'click', this.resendButtonClickHandler.bind(this));
            this.sandbox.dom.on(this.dom.$loginForm, 'submit', this.loginFormSubmitHandler.bind(this));
            this.sandbox.dom.on(this.dom.$loginForm, 'keydown', this.loginFormKeyHandler.bind(this));
            this.sandbox.dom.on(this.dom.$resetMail, 'keydown', this.resetMailKeyHandler.bind(this));
            this.sandbox.dom.on(this.sandbox.dom.window, 'mousemove', this.mousemoveHandler.bind(this));
            this.sandbox.dom.on(this.dom.$box, 'click',
                this.redirect.bind(this, this.sandbox.dom.window.location.origin), '.' + constants.websiteSwitchClass);
        },

        /**
         * Binds dom events for the reset-mode
         */
        bindResetDomEvents: function() {
            this.sandbox.dom.on(this.dom.$resetButton, 'click', this.resetButtonClickHandler.bind(this));
            this.sandbox.dom.on(this.dom.$reset, 'keydown', this.resetKeyHandler.bind(this));
            this.sandbox.dom.on(this.sandbox.dom.find('.' + constants.loginRouteClass), 'click', this.loginRouteClickHandler.bind(this));
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
            this.setBackgroundPosition(changeInX, changeInY);
            this.mouseenterHandler(event);
        },

        /**
         * Handles a click on the login-button
         */
        loginButtonClickHandler: function() {
            this.sandbox.dom.submit(this.dom.$loginForm);
        },

        /**
         * Handles the click on the login-route item (reset-mode)
         */
        loginRouteClickHandler: function() {
            this.redirect(this.options.loginUrl);
        },

        /**
         * Handles the click on the reset-mail button
         */
        resetMailButtonClickHandler: function() {
            var user = this.sandbox.dom.trim(this.sandbox.dom.val(this.sandbox.dom.find('#user', this.dom.$resetMail)));
            this.resetMail(user);
        },

        /**
         * Handles the click on the reset button
         */
        resetButtonClickHandler: function() {
            var password1 = this.sandbox.dom.trim(this.sandbox.dom.val(this.sandbox.dom.find('#password1', this.dom.$reset))),
                password2 = this.sandbox.dom.trim(this.sandbox.dom.val(this.sandbox.dom.find('#password2', this.dom.$reset)));
            if (password1 !== password2 || password1.length === 0) {
                this.sandbox.dom.addClass(this.dom.$reset, constants.errorClass);
                return false;
            }
            this.sandbox.dom.removeClass(this.dom.$reset, constants.errorClass);
            this.reset(password1);
        },

        /**
         * Handles the click on the resend Button
         */
        resendButtonClickHandler: function() {
            if (this.sandbox.dom.hasClass(this.dom.$resendButton, 'inactive')) {
                return false;
            }
            this.resend();
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
         * Handles the keydown-event of the reset-mail-form
         * @param event
         */
        resetMailKeyHandler: function(event) {
            if (event.keyCode === 13) { //on enter
                this.resetMailButtonClickHandler();
            }
        },

        /**
         * Handles the keydown-event of the reset-form
         * @param event
         */
        resetKeyHandler: function(event) {
            if (event.keyCode === 13) { //on enter
                this.resetButtonClickHandler();
            }
        },

        /**
         * Sends the username and password to the server
         * @param username
         * @param password
         */
        login: function(username, password) {
            this.sandbox.dom.after(this.dom.$loginButton, this.dom.$loader);
            this.sandbox.dom.show(this.dom.$loader);
            this.sandbox.util.save(this.options.loginCheck, 'POST', {
                '_username': username,
                '_password': password
            }).then(function(data) {
                this.redirect(data.url + this.sandbox.dom.window.location.hash);
            }.bind(this)).fail(function() {
                this.sandbox.dom.hide(this.dom.$loader);
                this.displayLoginError();
            }.bind(this));
        },

        /**
         * Sends the user to the server to request a resetting email
         * @param user
         */
        resetMail: function(user) {
            this.sandbox.dom.after(this.dom.$resetMailButton, this.dom.$loader);
            this.sandbox.dom.show(this.dom.$loader);
            this.sandbox.util.save(this.options.resetMailUrl, 'POST', {
                'user': user
            }).then(function(data) {
                this.resetMailUser = user;
                this.sandbox.dom.hide(this.dom.$loader);
                this.showEmailSentLabel();
                this.showResendElement(data.email);
            }.bind(this)).fail(function(data) {
                this.sandbox.dom.hide(this.dom.$loader);
                this.displayResetMailError(data.responseJSON.code);
            }.bind(this));
        },

        /**
         * Resets the password of the user with the reset-token (options) with a new password
         * @param newPassword - string
         */
        reset: function(newPassword) {
            this.sandbox.dom.after(this.dom.$resetButton, this.dom.$loader);
            this.sandbox.dom.show(this.dom.$loader);
            this.sandbox.util.save(this.options.resetUrl, 'POST', {
                'password': newPassword,
                'token': this.options.resetToken
            }).then(function(data) {
                this.redirect(data.url);
            }.bind(this)).fail(function(data) {
                this.sandbox.dom.hide(this.dom.$loader);
                this.displayResetError(data.responseJSON.code);
            }.bind(this));
        },

        /**
         * Sends the last user which requested a resetting email
         * to the server to resend the mail
         */
        resend: function() {
            this.sandbox.dom.after(this.dom.$resendButton, this.dom.$loader);
            this.sandbox.dom.show(this.dom.$loader);
            this.sandbox.util.save(this.options.resendUrl, 'POST', {
                'user': this.resetMailUser
            }).then(function() {
               this.sandbox.dom.hide(this.dom.$loader);
                this.showEmailSentLabel();
            }.bind(this)).fail(function(data) {
                this.sandbox.dom.hide(this.dom.$loader);
                this.displayResendError(data.responseJSON.code);
            }.bind(this));
        },

        /**
         * Shows the resend element with a given email in the test
         * @param email - string
         */
        showResendElement: function(email) {
            this.sandbox.dom.html(this.sandbox.dom.find('.to-mail', this.dom.$resetMail), email);
            this.sandbox.dom.hide(this.sandbox.dom.find('.' + constants.resetMailBoxClass, this.dom.$resetMail));
            this.sandbox.dom.show(this.sandbox.dom.find('.' + constants.resetMailMessageBoxClass, this.dom.$resetMail));
        },

        /**
         * Shows an email-sent success-label
         */
        showEmailSentLabel: function() {
            this.sandbox.emit('sulu.labels.success.show', this.options.translations.emailSentSuccess, 'labels.success');
        },

        /**
         * Displays a reset-mail error
         * @param code - integer - the code for the reset-mail-message
         */
        displayResetMailError: function(code) {
            var errorTransKey = this.options.errorTranslations[code] || 'Error';
            this.sandbox.dom.html(this.sandbox.dom.find('.error-message', this.dom.$resetMail), this.sandbox.translate(errorTransKey));
            this.sandbox.dom.addClass(this.dom.$box, constants.boxLargerClass);
            this.sandbox.dom.addClass(this.dom.$resetMail, constants.errorClass);
        },

        /**
         * Displays a resend error
         * @param code - integer - error code
         */
        displayResendError: function(code) {
            var errorTransKey = this.options.errorTranslations[code] || 'Error';
            this.sandbox.emit('sulu.labels.error.show', this.sandbox.translate(errorTransKey), 'labels.error');
            this.sandbox.dom.removeClass(this.dom.$resendButton, 'action');
            this.sandbox.dom.addClass(this.dom.$resendButton, 'inactive gray-dark');
        },

        /**
         * Displays a reset error
         * @param code
         */
        displayResetError: function(code) {
            var errorTransKey = this.options.errorTranslations[code] || 'Error';
            this.sandbox.emit('sulu.labels.error.show', this.sandbox.translate(errorTransKey), 'labels.error');
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

        moveToResetMail: function() {
            this.sandbox.dom.removeClass(this.dom.$resetMail, 'hide');
            this.moveToFrame(this.dom.$resetMail);
        },

        moveToLogin: function() {
            this.moveToFrame(this.dom.$login).then(function() {
                this.sandbox.dom.addClass(this.dom.$resetMail, 'hide');
            }.bind(this));
        },

        /**
         * Moves the frames container so that a given frame is
         * visible in the box
         * @param $frame
         */
        moveToFrame: function($frame) {
            var def = this.sandbox.data.deferred();

            this.sandbox.dom.animate(this.dom.$frames, {left: -(this.sandbox.dom.position($frame).left) + 'px'}, {
                duration: 300,
                complete: function() {
                    def.resolve();
                }
            });

            // focus first input
            if (this.sandbox.dom.find('input', $frame).length > 0) {
                this.sandbox.dom.select(this.sandbox.dom.find('input', $frame)[0]);
            }

            return def;
        },

        /**
         * Handles the window resize event
         */
        resizeHandler: function() {
            this.setBackgroundSize();
            this.setMovementRatio();
        }
    };
});
