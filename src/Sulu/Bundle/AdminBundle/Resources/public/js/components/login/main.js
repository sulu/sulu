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
                resendResetMail: 'sulu.login.resend-email',
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

            mediaContainerClass: 'media-container',
            mediaLogoClass: 'media-logo',
            mediaBackgroundClass: 'media-background',
            mediaFlareClass: 'flare',

            contentContainerClass: 'content-container',
            //blurClass: 'blur',
            loadingClass: 'content-loading',
            contentBoxClass: 'content-box',
            websiteSwitchClass: 'website-switch',
            contentFooterClass: 'login-content-footer',
            navigatorSpanClass: 'navigator',
            successOverlayClass: 'success-overlay',
            successIconClass: 'success-icon',

            frameSliderClass: 'frame-slider',
            frameClass: 'box-frame',
            loginFrameClass: 'login',
            forgotPasswordFrameClass: 'forgot-password',
            resendMailFrameClass: 'resend-mail',
            resetPasswordFrameClass: 'reset-password',

            forgotPasswordSwitchClass: 'forgot-password-switch',
            loginSwitchClass: 'login-switch-span',
            loginButtonId: 'login-button',

            requestResetMailButtonId: 'request-mail-button',
            resendResetMailButtonId: 'resend-mail-button',

            resetPasswordButtonId: 'reset-password-button',

            loginRouteClass: 'login-route-span',

            frameFooterClass: 'box-frame-footer',
            errorMessageClass: 'error-message',
            errorClass: 'login-error',
            loaderClass: 'login-loader'
        },

        templates = {
            mediaContainer: ['<div class="'+ constants.mediaContainerClass +'">',
                '   <div class="'+ constants.mediaLogoClass+'"></div>',
                '   <div class="'+ constants.mediaBackgroundClass+'"></div>',
                '</div>'].join(''),
            contentContainer: ['<div class="'+ constants.contentContainerClass +'">',
                '   <div class="'+ constants.contentBoxClass +'">',
                '       <div class="'+ constants.frameSliderClass +'"></div>',
                '   </div>',
                '   <div class="grid-row '+ constants.contentFooterClass +'">',
                '       <span class="'+ [constants.websiteSwitchClass, constants.navigatorSpanClass].join(" ") +'"><%= backWebsiteMessage %></span>',
                '   </div>',
                '   <div class="'+ constants.successOverlayClass +'">',
                '       <span class="fa-check '+ constants.successIconClass+'"></span>', //testing
                '   </div>',
                '</div>'].join(''),


            loginFrame: ['<div class="'+ [constants.frameClass, constants.loginFrameClass].join(" ") +'">',
                        '   <form class="grid inputs">',
                        '       <div class="grid-row">',
                        '           <input class="form-element input-large husky-validate" type="text" name="username" id="username" placeholder="<%= emailUser %>"/>',
                        '       </div>',
                        '       <div class="grid-row">',
                        '           <input class="form-element input-large husky-validate" type="password" name="password" id="password" placeholder="<%= password %>"/>',
                        '       </div>',
                        '       <span class="'+ constants.errorMessageClass +'"><%= errorMessage %></span>',
                        '   </form>',
                        '   <div class="grid-row small '+ constants.frameFooterClass +'">',
                        '       <span class="'+ [constants.forgotPasswordSwitchClass, constants.navigatorSpanClass].join(" ") +'"><%= forgotPasswordMessage %></span>',
                        '       <div id="'+ constants.loginButtonId +'" class="btn action large fit"><%= login %></div>',
                        '   </div>',
                        '</div>'].join(''),
            forgotPasswordFrame: ['<div class="'+ [constants.frameClass, constants.forgotPasswordFrameClass].join(" ") +'">',
                            '   <div class="grid inputs">',
                            '       <div class="grid-row">',
                            '           <input id="user" class="form-element input-large husky-validate" type="text" placeholder="<%= emailUser %>" tabindex="-1"/>',
                            '       </div>',
                            '       <span class="'+ constants.errorMessageClass +'"></span>',
                            '   </div>',
                            '   <div class="grid-row small '+ constants.frameFooterClass +'">',
                            '       <span class="'+ [constants.loginSwitchClass, constants.navigatorSpanClass].join(" ") +'"><%= backLoginMessage %></span>',
                            '       <div id="'+ constants.requestResetMailButtonId +'" class="btn action large fit"><%= reset %></div>',
                            '   </div>',
                            '</div>'].join(''),
            resendMailFrame: ['<div class="'+ [constants.frameClass, constants.resendMailFrameClass].join(" ") +'">',
                            '   <div class="grid-row">',
                            '       <span class="message"><%= sentMessage %> <span class="to-mail"></span></span>',
                            '   </div>',
                            '   <div class="grid-row small '+ constants.frameFooterClass +'">',
                            '       <span class="'+ [constants.loginSwitchClass, constants.navigatorSpanClass].join(" ") +'"><%= backLoginMessage %></span>',
                            '       <div id="'+ constants.resendResetMailButtonId +'" class="btn action large fit"><%= resend %></div>',
                            '   </div>',
                            '</div>'].join(''),
            resetPasswordFrame: ['<div class="'+ [constants.frameClass, constants.resetPasswordFrameClass].join(" ") +'">',
                                '   <div class="grid inputs">',
                                '       <div class="grid-row">',
                                '           <input id="password1" class="form-element input-large husky-validate" type="password" placeholder="<%= password1Label %>"/>',
                                '       </div>',
                                '       <div class="grid-row">',
                                '           <input id="password2" class="form-element input-large husky-validate" type="password" placeholder="<%= password2Label %>"/>',
                                '       </div>',
                                '   </div>',
                                '   <div class="grid-row small '+ constants.frameFooterClass +'">',
                                '       <span class="'+ [constants.loginRouteClass, constants.navigatorSpanClass].join(" ") +'"><%= loginRouteMessage %></span>',
                                '       <div id="'+ constants.resetPasswordButtonId +'" class="btn action large fit"><%= login %></div>',
                                '   </div>',
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

            this.sandbox.emit(INITIALIZED.call(this));
        },

        /**
         * Initializes the component properties
         */
        initProperties: function() {
            this.dom = {
                $mediaContainer: null,
                $contentContainer: null,

                $mediaBackground: null,
                $frameSlider: null,
                $loginFrame: null,
                $forgotPasswordFrame: null,
                $resendMailFrame: null,
                $resetPasswordFrame: null,

                $loginForm: null,
                $forgotPasswordSwitch: null,
                $loginSwitchForgotFrame: null,
                $loginSwitchResendFrame: null,
                $loginButton: null,
                $requestResetMailButton: null,
                $resendResetMailButton: null,
                $resetPasswordButton: null
            };
            this.resetMailUser = null
        },

        /**
         * Renders the component
         */
        render: function() {
            this.sandbox.dom.addClass(this.$el, constants.componentClass);
            this.renderMediaFrame();
            this.renderContentFrame();
        },

        /**
         * Render left media frame
         */
        renderMediaFrame: function() {
            var $img = this.sandbox.dom.createElement('<img/>'); // used to load the image
            this.sandbox.dom.one($img, 'load', this.showBackground.bind(this));
            this.sandbox.dom.attr($img, 'src', this.options.backgroundImg);

            this.dom.$mediaContainer = this.sandbox.dom.createElement(templates.mediaContainer);
            this.dom.$mediaBackground = this.sandbox.dom.find('.' + constants.mediaBackgroundClass, this.dom.$mediaContainer);

            this.sandbox.dom.css(
                this.dom.$mediaBackground, 'background-image', 'url("'+ this.options.backgroundImg +'")'
            );
            this.sandbox.dom.hide(this.dom.$mediaBackground); //fade in later
            this.sandbox.dom.append(this.$el, this.dom.$mediaContainer);
        },

        /**
         * Fades the background in
         */
        showBackground: function() {
            this.sandbox.dom.fadeIn(this.dom.$mediaBackground, this.options.fadeInDuration);
        },

        /**
         * Renders the content-box
         */
        renderContentFrame: function() {
            this.dom.$contentContainer = this.sandbox.dom.createElement(this.sandbox.util.template(templates.contentContainer)({
                backWebsiteMessage: this.sandbox.translate(this.options.translations.backWebsite)
            }));

            this.dom.$box = this.sandbox.dom.find('.' + constants.contentBoxClass, this.dom.$contentContainer);
            this.dom.$frameSlider = this.sandbox.dom.find('.' + constants.frameSliderClass, this.dom.$contentContainer);

            if (this.options.resetMode === false) {
                this.renderLoginFrame();
                this.renderForgotPasswordFrame();
                this.renderResendMailFrame();
            } else {
                this.renderResetPasswordFrame();
            }

            this.renderLoader();
            this.sandbox.dom.append(this.$el, this.dom.$contentContainer);

            this.moveFrameSliderTo(this.sandbox.dom.find('.' + constants.frameClass, this.dom.$frameSlider)[0]);
        },

        /**
         * Renders the frame with the login inputs
         */
        renderLoginFrame: function() {
            this.dom.$loginFrame = this.sandbox.dom.createElement(this.sandbox.util.template(templates.loginFrame)({
                emailUser: this.sandbox.translate(this.options.translations.emailUser),
                password: this.sandbox.translate(this.options.translations.password),
                forgotPasswordMessage: this.sandbox.translate(this.options.translations.forgotPassword),
                errorMessage: this.sandbox.translate(this.options.translations.errorMessage),
                login: this.sandbox.translate(this.options.translations.login),
            }));

            this.dom.$forgotPasswordSwitch = this.sandbox.dom.find('.' + constants.forgotPasswordSwitchClass, this.dom.$loginFrame);
            this.dom.$loginButton = this.sandbox.dom.find('#' + constants.loginButtonId, this.dom.$loginFrame);
            this.dom.$loginForm = this.sandbox.dom.find('form', this.dom.$loginFrame);

            this.sandbox.dom.append(this.dom.$frameSlider, this.dom.$loginFrame);
        },

        /**
         * Renderes the login loader
         */
        renderLoader: function() {
            this.dom.$loader = this.sandbox.dom.createElement('<div class="'+ constants.loaderClass +'"/>');
            this.sandbox.dom.append(this.dom.$contentContainer, this.dom.$loader);
            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: this.dom.$loader,
                        size: '200px',
                        color: '#fff'
                    }
                }
            ]);
        },

        /**
         * Renders the frame with the password-reset-mail functionality
         */
        renderForgotPasswordFrame: function() {
            this.dom.$forgotPasswordFrame = this.sandbox.dom.createElement(this.sandbox.util.template(templates.forgotPasswordFrame)({
                label: this.sandbox.translate(this.options.translations.resetPassword),
                reset: this.sandbox.translate(this.options.translations.reset),
                emailUser: this.sandbox.translate(this.options.translations.emailUser),
                backLoginMessage: this.sandbox.translate(this.options.translations.backLogin),
            }));

            this.dom.$loginSwitchForgotFrame = this.sandbox.dom.find('.' + constants.loginSwitchClass, this.dom.$forgotPasswordFrame);
            this.dom.$requestResetMailButton = this.sandbox.dom.find('#' + constants.requestResetMailButtonId, this.dom.$forgotPasswordFrame);

            this.sandbox.dom.append(this.dom.$frameSlider, this.dom.$forgotPasswordFrame);
        },

        renderResendMailFrame: function() {
            this.dom.$resendMailFrame = this.sandbox.dom.createElement(this.sandbox.util.template(templates.resendMailFrame)({
                resend: this.sandbox.translate(this.options.translations.resendResetMail),
                sentMessage: this.sandbox.translate(this.options.translations.emailSent),
                backLoginMessage: this.sandbox.translate(this.options.translations.backLogin),
            }));

            this.dom.$loginSwitchResendFrame = this.sandbox.dom.find('.' + constants.loginSwitchClass, this.dom.$resendMailFrame);
            this.dom.$resendResetMailButton = this.sandbox.dom.find('#' + constants.resendResetMailButtonId, this.dom.$resendMailFrame);

            this.sandbox.dom.append(this.dom.$frameSlider, this.dom.$resendMailFrame);
        },

        /**
         * Renders the frame for resetting the password
         */
        renderResetPasswordFrame: function() {
            this.dom.$resetPasswordFrame = this.sandbox.dom.createElement(this.sandbox.util.template(templates.resetPasswordFrame)({
                password1Label: this.sandbox.translate(this.options.translations.enterNewPassword),
                password2Label: this.sandbox.translate(this.options.translations.repeatPassword),
                password: this.sandbox.translate(this.options.translations.password),
                login: this.sandbox.translate(this.options.translations.login),
                backWebsiteMessage: this.sandbox.translate(this.options.translations.backWebsite),
                loginRouteMessage: this.sandbox.translate(this.options.translations.backLogin)
            }));

            this.dom.$resetPasswordButton = this.sandbox.dom.find('#' + constants.resetPasswordButtonId, this.dom.$resetPasswordFrame);

            this.sandbox.dom.append(this.dom.$frameSlider, this.dom.$resetPasswordFrame);
        },

        /**
         * Binds Dom-related events
         */
        bindDomEvents: function() {
            this.bindGeneralDomEvents();

            if (this.options.resetMode === false) {
                this.bindLoginDomEvents();
                this.bindForgotPasswordDomEvents();
                this.bindResendMailDomEvents();
            } else {
                this.bindResetPasswordDomEvents();
            }
        },

        bindGeneralDomEvents: function() {
            this.sandbox.dom.on(this.dom.$contentContainer, 'click',
                this.redirectTo.bind(this, this.sandbox.dom.window.location.origin), '.' + constants.websiteSwitchClass);

            this.sandbox.dom.on(this.sandbox.dom.window, 'mousedown', this.toggleMediaFrameFlare.bind(this, true));
            this.sandbox.dom.on(this.sandbox.dom.window, 'mouseup', this.toggleMediaFrameFlare.bind(this, false));
        },

        bindLoginDomEvents: function() {
            this.sandbox.dom.on(this.dom.$loginForm, 'submit', this.loginFormSubmitHandler.bind(this));
            this.sandbox.dom.on(this.dom.$loginForm, 'keydown', this.loginFormKeyHandler.bind(this));
            this.sandbox.dom.on(this.dom.$forgotPasswordSwitch, 'click', this.moveToForgotPasswordFrame.bind(this));
            this.sandbox.dom.on(this.dom.$loginButton, 'click', this.loginButtonClickHandler.bind(this));

            // reset errorstatus on input-change
            this.sandbox.dom.on(this.dom.$box, 'keyup', this.validationInputChangeHandler.bind(this, this.dom.$loginFrame), '.husky-validate');
        },

        bindForgotPasswordDomEvents: function() {
            this.sandbox.dom.on(this.dom.$forgotPasswordFrame, 'keydown', this.forgotPasswordKeyHandler.bind(this));
            this.sandbox.dom.on(this.dom.$loginSwitchForgotFrame, 'click', this.moveToLoginFrame.bind(this));
            this.sandbox.dom.on(this.dom.$requestResetMailButton, 'click', this.requestResetMailButtonClickHandler.bind(this));

            this.sandbox.dom.on(this.dom.$box, 'keyup', this.validationInputChangeHandler.bind(this, this.dom.$forgotPasswordFrame), '.husky-validate');
        },

        bindResendMailDomEvents: function() {
            this.sandbox.dom.on(this.dom.$resendResetMailButton, 'click', this.resendResetMailButtonClickHandler.bind(this));
            this.sandbox.dom.on(this.dom.$loginSwitchResendFrame, 'click', this.moveToLoginFrame.bind(this));
        },

        bindResetPasswordDomEvents: function() {
            this.sandbox.dom.on(this.dom.$resetPasswordButton, 'click', this.resetPasswordButtonClickHandler.bind(this));
            this.sandbox.dom.on(this.dom.$resetPasswordFrame, 'keydown', this.resetPasswordKeyHandler.bind(this));
            this.sandbox.dom.on(this.sandbox.dom.find('.' + constants.loginRouteClass), 'click', this.loginRouteClickHandler.bind(this));

            this.sandbox.dom.on(this.dom.$box, 'keyup', this.validationInputChangeHandler.bind(this, this.dom.$resetPasswordFrame), '.husky-validate');
        },

        /**
         * Sets the Background active or inactive
         * @param active - true to set active, false to set unactive
         */
        toggleMediaFrameFlare: function(active) {
            if (active === true) {
                this.sandbox.dom.addClass(this.dom.$mediaContainer, constants.mediaFlareClass);
            } else {
                this.sandbox.dom.removeClass(this.dom.$mediaContainer, constants.mediaFlareClass);
            }
        },

        /**
         * Handles a click on the login-button
         */
        loginButtonClickHandler: function() {
            this.sandbox.dom.submit(this.dom.$loginForm);
        },

        /**
         * Handles a click on the login-button
         */
        validationInputChangeHandler: function($frame) {
            this.sandbox.dom.removeClass($frame, constants.errorClass);
        },

        /**
         * Handles the click on the login-route item (reset-mode)
         */
        loginRouteClickHandler: function() {
            this.redirectTo(this.options.loginUrl);
        },

        /**
         * Handles the click on the reset-mail button
         */
        requestResetMailButtonClickHandler: function() {
            var user = this.sandbox.dom.trim(this.sandbox.dom.val(this.sandbox.dom.find('#user', this.dom.$forgotPasswordFrame)));
            this.requestResetMail(user);
        },

        /**
         * Handles the click on the reset button
         */
        resetPasswordButtonClickHandler: function() {
            var password1 = this.sandbox.dom.val(this.sandbox.dom.find('#password1', this.dom.$resetPasswordFrame)),
                password2 = this.sandbox.dom.val(this.sandbox.dom.find('#password2', this.dom.$resetPasswordFrame));
            if (password1 !== password2 || password1.length === 0) {
                this.sandbox.dom.addClass(this.dom.$resetPasswordFrame, constants.errorClass);
                this.focusFirstInput(this.dom.$resetPasswordFrame);
                return false;
            }
            this.resetPassword(password1);
        },

        /**
         * Handles the click on the resend Button
         */
        resendResetMailButtonClickHandler: function() {
            if (this.sandbox.dom.hasClass(this.dom.$resendResetMailButton, 'inactive')) {
                return false;
            }
            this.resendResetMail();
        },

        /**
         * Handles the submit event of the login-form
         */
        loginFormSubmitHandler: function() {
            var username = this.sandbox.dom.val(this.sandbox.dom.find('#username', this.dom.$loginForm)),
                password = this.sandbox.dom.val(this.sandbox.dom.find('#password', this.dom.$loginForm));
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
        forgotPasswordKeyHandler: function(event) {
            if (event.keyCode === 13) { //on enter
                this.requestResetMailButtonClickHandler();
            }
        },

        /**
         * Handles the keydown-event of the reset-form
         * @param event
         */
        resetPasswordKeyHandler: function(event) {
            if (event.keyCode === 13) { //on enter
                this.resetPasswordButtonClickHandler();
            }
        },

        /**
         * Sends the username and password to the server
         * @param username
         * @param password
         */
        login: function(username, password) {
            this.showLoader();
            this.sandbox.util.save(this.options.loginCheck, 'POST', {
                '_username': username,
                '_password': password
            }).then(function(data) {
                this.redirectTo(data.url + this.sandbox.dom.window.location.hash);
            }.bind(this)).fail(function() {
                this.hideLoader();
                this.displayLoginError();
            }.bind(this));
        },

        /**
         * Sends the user to the server to request a resetting email
         * @param user
         */
        requestResetMail: function(user) {
            this.showLoader();
            this.sandbox.util.save(this.options.resetMailUrl, 'POST', {
                'user': user
            }).then(function(data) {
                this.resetMailUser = user;
                this.hideLoader();
                this.showEmailSentLabel();
                this.moveToResendMailFrame(data.email);
            }.bind(this)).fail(function(data) {
                this.hideLoader();
                this.displayRequestResetMailError(data.responseJSON.code);
            }.bind(this));
        },

        /**
         * Resets the password of the user with the reset-token (options) with a new password
         * @param newPassword - string
         */
        resetPassword: function(newPassword) {
            this.showLoader();
            this.sandbox.util.save(this.options.resetUrl, 'POST', {
                'password': newPassword,
                'token': this.options.resetToken
            }).then(function(data) {
                this.redirectTo(data.url);
            }.bind(this)).fail(function(data) {
                this.hideLoader();
                this.displayResetPasswordError(data.responseJSON.code);
            }.bind(this));
        },

        /**
         * Sends the last user which requested a resetting email
         * to the server to resend the mail
         */
        resendResetMail: function() {
            this.showLoader();
            this.sandbox.util.save(this.options.resendUrl, 'POST', {
                'user': this.resetMailUser
            }).then(function() {
                this.hideLoader();
                this.showEmailSentLabel();
            }.bind(this)).fail(function(data) {
                this.hideLoader();
                this.displayResendResetMailError(data.responseJSON.code);
            }.bind(this));
        },


        showLoader: function() {
            this.sandbox.dom.addClass(this.dom.$contentContainer, constants.loadingClass);
        },

        hideLoader: function() {
            this.sandbox.dom.removeClass(this.dom.$contentContainer, constants.loadingClass);
        },

        /**
         * Shows an email-sent success-label
         */
        showEmailSentLabel: function() {
            this.sandbox.emit('sulu.labels.success.show', this.options.translations.emailSentSuccess, 'labels.success');
        },

        /**
         * Adds a css class -> inputs become red..
         */
        displayLoginError: function() {
            this.sandbox.dom.addClass(this.dom.$loginFrame, constants.errorClass);
            this.focusFirstInput(this.dom.$loginFrame);
        },

        /**
         * Displays a reset-mail error
         * @param code - integer - the code for the reset-mail-message
         */
        displayRequestResetMailError: function(code) {
            var errorTransKey = this.options.errorTranslations[code] || 'Error';
            this.sandbox.dom.html(this.sandbox.dom.find('.'+ constants.errorMessageClass, this.dom.$forgotPasswordFrame), this.sandbox.translate(errorTransKey));
            this.sandbox.dom.addClass(this.dom.$forgotPasswordFrame, constants.errorClass);
            this.focusFirstInput(this.dom.$forgotPasswordFrame);
        },

        /**
         * Displays a resend error
         * @param code - integer - error code
         */
        displayResendResetMailError: function(code) {
            var errorTransKey = this.options.errorTranslations[code] || 'Error';
            this.sandbox.emit('sulu.labels.error.show', this.sandbox.translate(errorTransKey), 'labels.error');
            this.sandbox.dom.addClass(this.dom.$resendResetMailButton, 'inactive');
        },

        /**
         * Displays a reset error
         * @param code
         */
        displayResetPasswordError: function(code) {
            var errorTransKey = this.options.errorTranslations[code] || 'Error';
            this.sandbox.emit('sulu.labels.error.show', this.sandbox.translate(errorTransKey), 'labels.error');
        },

        /**
         * Redirects the page to a url
         * @param url
         */
        redirectTo: function(url) {
            this.sandbox.dom.window.location = url;
        },


        moveToForgotPasswordFrame: function() {
            this.moveFrameSliderTo(this.dom.$forgotPasswordFrame);
        },

        moveToResendMailFrame: function(email) {
            this.sandbox.dom.html(this.sandbox.dom.find('.to-mail', this.dom.$resendMailFrame), email);
            this.moveFrameSliderTo(this.dom.$resendMailFrame);
        },

        moveToLoginFrame: function() {
            this.moveFrameSliderTo(this.dom.$loginFrame);
        },

        focusFirstInput: function($frame){
            if (this.sandbox.dom.find('input', $frame).length > 0) {
                this.sandbox.dom.select(this.sandbox.dom.find('input', $frame)[0]);
            }
        },

        /**
         * Moves the frames container so that a given frame is
         * visible in the box
         * @param $frame
         */
        moveFrameSliderTo: function($frame) {
            this.sandbox.dom.css(this.dom.$frameSlider, 'left', -this.sandbox.dom.position($frame).left);
            this.focusFirstInput($frame);
        }
    };
});
