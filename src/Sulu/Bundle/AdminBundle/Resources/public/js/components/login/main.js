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
 * @param {String} [options.loginCheck] path to post the login-credentials to
 */

define([], function() {

    'use strict';

    var defaults = {
            instanceName: '',
            backgroundImg: '/bundles/suluadmin/img/background.jpg',
            loginUrl: '',
            loginCheck: '',
            resetMailUrl: '',
            resendUrl: '',
            resetUrl: '',
            resetToken: '',
            csrfToken: '',
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

            mediaLogoClass: 'media-logo',
            mediaBackgroundClass: 'media-background',
            mediaLoadingClass: 'media-loading',

            contentLoadingClass: 'content-loading',
            successClass: 'content-success',

            contentBoxClass: 'content-box',
            websiteSwitchClass: 'website-switch',
            successOverlayClass: 'success-overlay',

            frameSliderClass: 'frame-slider',
            frameClass: 'box-frame',

            forgotPasswordSwitchClass: 'forgot-password-switch',
            loginSwitchClass: 'login-switch-span',
            loginButtonId: 'login-button',

            requestResetMailButtonId: 'request-mail-button',
            resendResetMailButtonId: 'resend-mail-button',

            resetPasswordButtonId: 'reset-password-button',
            loginRouteClass: 'login-route-span',

            errorMessageClass: 'error-message',
            errorClass: 'login-error',
            loaderClass: 'login-loader'
        },

        templates = {
            mediaContainer: ['<div class="media-container ' + constants.mediaLoadingClass + '">',
                '   <div class="media-logo"></div>',
                '   <div class="' + constants.mediaBackgroundClass + '">',
                '       <div class="darkener"></div>',
                '   </div>',
                '</div>'].join(''),
            contentContainer: ['<div class="content-container">',
                '   <div class="' + constants.contentBoxClass + '">',
                '       <div class="content-logo navigator' + constants.websiteSwitchClass + '"></div>',
                '       <div class="' + constants.frameSliderClass + '"></div>',
                '   </div>',
                '   <div class="grid-row login-content-footer">',
                '       <span class="navigator ' + constants.websiteSwitchClass + '"><%= backWebsiteMessage %></span>',
                '   </div>',
                '   <div class="' + constants.successOverlayClass + '">',
                '       <div class="success-icon"></div>',
                '   </div>',
                '</div>'].join(''),

            loginFrame: function() {
                return [
                    '<div class="' + constants.frameClass + ' login">',
                    '   <form class="grid inputs">',
                    '       <span class="' + constants.errorMessageClass + '"><%= errorMessage %></span>',
                    '       <div class="grid-row">',
                    '           <input class="form-element input-large husky-validate" type="text" name="username" id="username" placeholder="<%= emailUser %>"/>',
                    '       </div>',
                    '       <div class="grid-row">',
                    '           <input class="form-element input-large husky-validate" type="password" name="password" id="password" placeholder="<%= password %>"/>',
                    '       </div>',
                    '       <input type="hidden" name="_csrf_token" id="_csrf_token" value="', this.options.csrfToken, '"/>',
                    '       <div class="grid-row small box-frame-footer">',
                    '           <span class="navigator ' + constants.forgotPasswordSwitchClass + '"><%= forgotPasswordMessage %></span>',
                    '           <div id="' + constants.loginButtonId + '" class="btn action large fit"><%= login %></div>',
                    '       </div>',
                    '   </form>',
                    '</div>'
                ].join('');
            },
            forgotPasswordFrame: ['<div class="' + constants.frameClass + ' forgot-password">',
                '   <div class="grid inputs">',
                '       <span class="' + constants.errorMessageClass + '"></span>',
                '       <div class="grid-row">',
                '           <input id="user" class="form-element input-large husky-validate" type="text" placeholder="<%= emailUser %>" tabindex="-1"/>',
                '       </div>',
                '   </div>',
                '   <div class="grid-row small box-frame-footer">',
                '       <span class="navigator ' + constants.loginSwitchClass + '"><%= backLoginMessage %></span>',
                '       <div id="' + constants.requestResetMailButtonId + '" class="btn action large fit"><%= reset %></div>',
                '   </div>',
                '</div>'].join(''),
            resendMailFrame: ['<div class="' + constants.frameClass + ' resend-mail">',
                '   <div class="grid-row">',
                '       <span class="message"><%= sentMessage %></span>',
                '       <span class="message to-mail"></span>',
                '   </div>',
                '   <div class="grid-row small box-frame-footer">',
                '       <span class="navigator ' + constants.loginSwitchClass + '"><%= backLoginMessage %></span>',
                '       <div id="' + constants.resendResetMailButtonId + '" class="btn action large fit"><%= resend %></div>',
                '   </div>',
                '</div>'].join(''),
            resetPasswordFrame: ['<div class="' + constants.frameClass + ' reset-password">',
                '   <div class="grid inputs">',
                '       <div class="grid-row">',
                '           <input id="password1" class="form-element input-large husky-validate" type="password" placeholder="<%= password1Label %>"/>',
                '       </div>',
                '       <div class="grid-row">',
                '           <input id="password2" class="form-element input-large husky-validate" type="password" placeholder="<%= password2Label %>"/>',
                '       </div>',
                '   </div>',
                '   <div class="grid-row small box-frame-footer">',
                '       <span class="navigator ' + constants.loginRouteClass + '"><%= loginRouteMessage %></span>',
                '       <div id="' + constants.resetPasswordButtonId + '" class="btn action large fit"><%= login %></div>',
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
         * Initialize component
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
         * Initialize component properties
         */
        initProperties: function() {
            this.dom = {
                $mediaContainer: null,
                $contentContainer: null,
                $successOverlay: null,
                $loader: null,

                $mediaBackground: null,
                $contentBox: null,
                $frameSlider: null,
                $loginFrame: null,
                $forgotPasswordFrame: null,
                $resendMailFrame: null,
                $resetPasswordFrame: null,

                $loginButton: null,
                $loginForm: null,
                $forgotPasswordSwitch: null,
                $requestResetMailButton: null,
                $resendResetMailButton: null,
                $resetPasswordButton: null
            };
            this.resetMailUser = null
        },

        /**
         * Render component
         */
        render: function() {
            this.sandbox.dom.addClass(this.$el, constants.componentClass);
            this.renderMediaContainer();
            this.renderContentContainer();

            // init left-property for animation and focus first input field
            this.moveFrameSliderTo(this.sandbox.dom.find('.' + constants.frameClass, this.dom.$frameSlider)[0]);
        },

        /**
         * Render left-side media container
         */
        renderMediaContainer: function() {
            // use pseudo dom-element to load background image
            var $img = this.sandbox.dom.createElement('<img/>');
            this.sandbox.dom.one($img, 'load', this.showMediaBackground.bind(this));
            this.sandbox.dom.attr($img, 'src', this.options.backgroundImg);

            this.dom.$mediaContainer = this.sandbox.dom.createElement(templates.mediaContainer);
            this.dom.$mediaBackground =
                this.sandbox.dom.find('.' + constants.mediaBackgroundClass, this.dom.$mediaContainer);
            this.sandbox.dom.css(
                this.dom.$mediaBackground, 'background-image', 'url("' + this.options.backgroundImg + '")'
            );

            this.sandbox.dom.append(this.$el, this.dom.$mediaContainer);
        },

        /**
         * Fade in media-container background by css-transition
         */
        showMediaBackground: function() {
            this.sandbox.dom.removeClass(this.dom.$mediaContainer, constants.mediaLoadingClass);
        },

        /**
         * Render right-side content container
         */
        renderContentContainer: function() {
            this.dom.$contentContainer =
                this.sandbox.dom.createElement(this.sandbox.util.template(templates.contentContainer)({
                    backWebsiteMessage: this.sandbox.translate(this.options.translations.backWebsite)
                }));

            this.dom.$contentBox = this.sandbox.dom.find('.' + constants.contentBoxClass, this.dom.$contentContainer);
            this.dom.$frameSlider = this.sandbox.dom.find('.' + constants.frameSliderClass, this.dom.$contentContainer);
            this.dom.$successOverlay =
                this.sandbox.dom.find('.' + constants.successOverlayClass, this.dom.$contentContainer);

            // render content-box frames
            if (this.options.resetMode === false) {
                this.renderLoginFrame();
                this.renderForgotPasswordFrame();
                this.renderResendMailFrame();
            } else {
                this.renderResetPasswordFrame();
            }

            this.renderLoader();
            this.sandbox.dom.append(this.$el, this.dom.$contentContainer);
        },

        /**
         * Render frame with login functionality
         */
        renderLoginFrame: function() {
            this.dom.$loginFrame = this.sandbox.dom.createElement(
                this.sandbox.util.template(templates.loginFrame.call(this))({
                    emailUser: this.sandbox.translate(this.options.translations.emailUser),
                    password: this.sandbox.translate(this.options.translations.password),
                    forgotPasswordMessage: this.sandbox.translate(this.options.translations.forgotPassword),
                    errorMessage: this.sandbox.translate(this.options.translations.errorMessage),
                    login: this.sandbox.translate(this.options.translations.login)
                }));

            this.dom.$forgotPasswordSwitch =
                this.sandbox.dom.find('.' + constants.forgotPasswordSwitchClass, this.dom.$loginFrame);
            this.dom.$loginButton = this.sandbox.dom.find('#' + constants.loginButtonId, this.dom.$loginFrame);
            this.dom.$loginForm = this.sandbox.dom.find('form', this.dom.$loginFrame);

            this.sandbox.dom.append(this.dom.$frameSlider, this.dom.$loginFrame);
        },

        /**
         * Render frame with password-reset-mail functionality
         */
        renderForgotPasswordFrame: function() {
            this.dom.$forgotPasswordFrame =
                this.sandbox.dom.createElement(this.sandbox.util.template(templates.forgotPasswordFrame)({
                    label: this.sandbox.translate(this.options.translations.resetPassword),
                    reset: this.sandbox.translate(this.options.translations.reset),
                    emailUser: this.sandbox.translate(this.options.translations.emailUser),
                    backLoginMessage: this.sandbox.translate(this.options.translations.backLogin)
                }));

            this.dom.$requestResetMailButton =
                this.sandbox.dom.find('#' + constants.requestResetMailButtonId, this.dom.$forgotPasswordFrame);

            this.sandbox.dom.append(this.dom.$frameSlider, this.dom.$forgotPasswordFrame);
        },

        /**
         * Render frame with resend reset-mail functionality
         */
        renderResendMailFrame: function() {
            this.dom.$resendMailFrame =
                this.sandbox.dom.createElement(this.sandbox.util.template(templates.resendMailFrame)({
                    resend: this.sandbox.translate(this.options.translations.resendResetMail),
                    sentMessage: this.sandbox.translate(this.options.translations.emailSent),
                    backLoginMessage: this.sandbox.translate(this.options.translations.backLogin)
                }));

            this.dom.$resendResetMailButton =
                this.sandbox.dom.find('#' + constants.resendResetMailButtonId, this.dom.$resendMailFrame);

            this.sandbox.dom.append(this.dom.$frameSlider, this.dom.$resendMailFrame);
        },

        /**
         * Render frame with reset-password functionality (only rendered in resetMode)
         */
        renderResetPasswordFrame: function() {
            this.dom.$resetPasswordFrame =
                this.sandbox.dom.createElement(this.sandbox.util.template(templates.resetPasswordFrame)({
                    password1Label: this.sandbox.translate(this.options.translations.enterNewPassword),
                    password2Label: this.sandbox.translate(this.options.translations.repeatPassword),
                    password: this.sandbox.translate(this.options.translations.password),
                    login: this.sandbox.translate(this.options.translations.login),
                    backWebsiteMessage: this.sandbox.translate(this.options.translations.backWebsite),
                    loginRouteMessage: this.sandbox.translate(this.options.translations.backLogin)
                }));

            this.dom.$resetPasswordButton =
                this.sandbox.dom.find('#' + constants.resetPasswordButtonId, this.dom.$resetPasswordFrame);

            this.sandbox.dom.append(this.dom.$frameSlider, this.dom.$resetPasswordFrame);
        },

        /**
         * Render login loader. Loader is hidden per default by css
         */
        renderLoader: function() {
            this.dom.$loader = this.sandbox.dom.createElement('<div class="' + constants.loaderClass + '"/>');
            this.sandbox.dom.append(this.dom.$contentContainer, this.dom.$loader);
            this.sandbox.start([
                {
                    name: 'loader@husky',
                    options: {
                        el: this.dom.$loader,
                        size: '40px',
                        color: '#fff'
                    }
                }
            ]);
        },

        /**
         * Bind dom-related events
         */
        bindDomEvents: function() {
            this.bindGeneralDomEvents();

            if (this.options.resetMode === false) {
                this.bindLoginDomEvents();
                this.bindForgotPasswordDomEvents();
                this.bindResendMailDomEvents();

                // bind login-switcher for all Frames
                this.sandbox.dom.on(
                    this.dom.$contentBox, 'click', this.moveToLoginFrame.bind(this), '.' + constants.loginSwitchClass
                );

            } else {
                this.bindResetPasswordDomEvents();
            }
        },

        /**
         * Bind frame-unspecific dom events
         */
        bindGeneralDomEvents: function() {
            this.sandbox.dom.on(this.dom.$contentContainer, 'click',
                this.redirectTo.bind(this, this.sandbox.dom.window.location.origin), '.' + constants.websiteSwitchClass);
        },

        /**
         * Bind login-frame related dom events
         */
        bindLoginDomEvents: function() {
            this.sandbox.dom.on(this.dom.$loginForm, 'keydown', this.inputFormKeyHandler.bind(this, this.dom.$loginForm));
            this.sandbox.dom.on(this.dom.$forgotPasswordSwitch, 'click', this.moveToForgotPasswordFrame.bind(this));
            this.sandbox.dom.on(this.dom.$loginButton, 'click', this.loginButtonClickHandler.bind(this));

            // reset error-status on user input-element-change, using keyup because change is only fired when loosing focus
            this.sandbox.dom.on(this.dom.$loginFrame, 'keyup change',
                this.validationInputChangeHandler.bind(this, this.dom.$loginFrame), '.husky-validate');
        },

        /**
         * Bind forgot-password-frame related dom events
         */
        bindForgotPasswordDomEvents: function() {
            this.sandbox.dom.on(this.dom.$forgotPasswordFrame, 'keydown',
                this.inputFormKeyHandler.bind(this, this.dom.$forgotPasswordFrame));
            this.sandbox.dom.on(this.dom.$requestResetMailButton, 'click',
                this.requestResetMailButtonClickHandler.bind(this));

            // reset error-status on user input-element-change
            this.sandbox.dom.on(this.dom.$forgotPasswordFrame, 'keyup change',
                this.validationInputChangeHandler.bind(this, this.dom.$forgotPasswordFrame), '.husky-validate');
        },

        /**
         * Bind resend-mail-frame related dom events
         */
        bindResendMailDomEvents: function() {
            this.sandbox.dom.on(this.dom.$resendResetMailButton, 'click',
                this.resendResetMailButtonClickHandler.bind(this));
        },

        /**
         * Bind reset-password-frame related dom events (reset mode)
         */
        bindResetPasswordDomEvents: function() {
            this.sandbox.dom.on(this.dom.$resetPasswordButton, 'click', this.resetPasswordButtonClickHandler.bind(this));
            this.sandbox.dom.on(this.dom.$resetPasswordFrame, 'keydown',
                this.inputFormKeyHandler.bind(this, this.dom.resetPasswordFrame));
            this.sandbox.dom.on(this.sandbox.dom.find('.' + constants.loginRouteClass), 'click',
                this.loginRouteClickHandler.bind(this));

            // reset error-status on user input-element-change
            this.sandbox.dom.on(this.dom.$resetPasswordFrame, 'keyup change',
                this.validationInputChangeHandler.bind(this, this.dom.$resetPasswordFrame), '.husky-validate');
        },

        /**
         * Handle click on login-button in login-frame
         */
        loginButtonClickHandler: function() {
            var username = this.sandbox.dom.val(this.sandbox.dom.find('#username', this.dom.$loginForm)),
                password = this.sandbox.dom.val(this.sandbox.dom.find('#password', this.dom.$loginForm)),
                csrfToken = $('#_csrf_token').val();

            if (username.length === 0 || password.length === 0) {
                this.displayLoginError();
            } else {
                this.login(username, password, csrfToken);
            }

            return false;
        },

        /**
         * Handle change of validation-input-element value on given frame
         * @param $frame parent frame of changed input-element
         * @param event
         */
        validationInputChangeHandler: function($frame, event) {
            if (event.type === 'keyup' && event.keyCode === 13) {
                return false; // do not reset error status on enter
            }
            else if (this.sandbox.dom.hasClass($frame, constants.errorClass)) {
                this.sandbox.dom.removeClass($frame, constants.errorClass);
            }
        },

        /**
         * Handles click on login-route item (reset-mode)
         */
        loginRouteClickHandler: function() {
            this.redirectTo(this.options.loginUrl);
        },

        /**
         * Handles click on reset-mail button in forgot-password-frame
         */
        requestResetMailButtonClickHandler: function() {
            var user = this.sandbox.dom.trim(this.sandbox.dom.val(
                this.sandbox.dom.find('#user', this.dom.$forgotPasswordFrame)
            ));
            this.requestResetMail(user);
        },

        /**
         * Handles click on reset-button in reset-password-frame (reset mode)
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
         * Handles click on resend-button in resend-mail-frame
         */
        resendResetMailButtonClickHandler: function() {
            if (this.sandbox.dom.hasClass(this.dom.$resendResetMailButton, 'inactive')) {
                return false;
            }
            this.resendResetMail();
        },

        /**
         * Handles keydown-event in given box-frame
         * @param $frame
         * @param event
         */
        inputFormKeyHandler: function($frame, event) {
            if (event.keyCode === 13) { //on enter
                var $button = this.sandbox.dom.find('.btn', $frame);
                this.sandbox.dom.click($button);
            }
        },

        /**
         * Send the username and password to the server
         * @param username
         * @param password
         * @param csrfToken
         */
        login: function(username, password, csrfToken) {
            this.showLoader(this.dom.$loginFrame);
            this.sandbox.util.save(this.options.loginCheck, 'POST', {
                '_username': username,
                '_password': password,
                '_csrf_token': csrfToken
            }).then(function(data) {
                this.displaySuccessAndRedirect(data.url + this.sandbox.dom.window.location.hash);
            }.bind(this)).fail(function() {
                this.hideLoader(this.dom.$loginFrame);
                this.displayLoginError();
            }.bind(this));
        },

        /**
         * Display login-success animation by hiding loader and showing login animation (success-overlay)
         * Forward to given url afterwards
         * @param redirectUrl - string
         */
        displaySuccessAndRedirect: function(redirectUrl) {
            this.sandbox.dom.css(this.dom.$loader, 'opacity', '0');

            // fade in green success-overlay (css animated, duration: 300ms)
            this.sandbox.dom.css(this.dom.$successOverlay, 'z-index', '20');
            // slide out both side-containers (css animated, delay: 500ms, duration 300ms)
            this.sandbox.dom.addClass(this.$el, constants.successClass);

            // css animations are finished after 800ms
            this.sandbox.util.delay(this.redirectTo.bind(this, redirectUrl), 800);
        },

        /**
         * Send user to server to request a resetting email
         * @param user
         */
        requestResetMail: function(user) {
            this.showLoader(this.dom.$forgotPasswordFrame);
            this.sandbox.util.save(this.options.resetMailUrl, 'POST', {
                'user': user
            }).then(function(data) {
                // save given user for optional resending
                this.resetMailUser = user;
                this.hideLoader(this.dom.$forgotPasswordFrame);
                this.showEmailSentLabel();
                this.moveToResendMailFrame(data.email);
            }.bind(this)).fail(function(data) {
                this.hideLoader(this.dom.$forgotPasswordFrame);
                this.displayRequestResetMailError(data.responseJSON.code);
            }.bind(this));
        },

        /**
         * Reset password of the user with reset-token (options) and new password (reset mode)
         * @param newPassword - string
         */
        resetPassword: function(newPassword) {
            this.showLoader(this.dom.$resetPasswordFrame);
            this.sandbox.util.save(this.options.resetUrl, 'POST', {
                'password': newPassword,
                'token': this.options.resetToken
            }).then(function(data) {
                this.displaySuccessAndRedirect(data.url);
            }.bind(this)).fail(function(data) {
                this.hideLoader(this.dom.$resetPasswordFrame);
                this.displayResetPasswordError(data.responseJSON.code);
            }.bind(this));
        },

        /**
         * Send user which requested a resetting email to the server to resend mail
         */
        resendResetMail: function() {
            this.showLoader(this.dom.$resendMailFrame);
            this.sandbox.util.save(this.options.resendUrl, 'POST', {
                'user': this.resetMailUser
            }).then(function() {
                this.hideLoader(this.dom.$resendMailFrame);
                this.showEmailSentLabel();
            }.bind(this)).fail(function(data) {
                this.hideLoader(this.dom.$resendMailFrame);
                this.displayResendResetMailError(data.responseJSON.code);
            }.bind(this));
        },

        /**
         * Show input-loader instead of button in given frame
         * @param $frame
         */
        showLoader: function($frame) {
            if (this.sandbox.dom.hasClass($frame, constants.contentLoadingClass)) {
                return false;
            }

            var $button = this.sandbox.dom.find('.btn', $frame);
            this.sandbox.dom.after($button, this.dom.$loader);
            this.sandbox.dom.css(this.dom.$loader, 'width', this.sandbox.dom.css($button, 'width'));

            this.sandbox.dom.addClass($frame, constants.contentLoadingClass);
        },

        /**
         * Hide input-loader in given frame. Show button of given frame instead
         * @param $frame
         */
        hideLoader: function($frame) {
            this.sandbox.dom.removeClass($frame, constants.contentLoadingClass);
        },

        /**
         * Show an email-sent success-label
         */
        showEmailSentLabel: function() {
            this.sandbox.emit('sulu.labels.success.show', this.options.translations.emailSentSuccess, 'labels.success');
        },

        /**
         * Add css class to visualize login-error
         */
        displayLoginError: function() {
            this.sandbox.dom.addClass(this.dom.$loginFrame, constants.errorClass);
            this.focusFirstInput(this.dom.$loginFrame);
        },

        /**
         * Display request-reset-mail error
         * @param code - integer - the code for the reset-mail-message
         */
        displayRequestResetMailError: function(code) {
            var errorTransKey = this.options.errorTranslations[code] || 'Error';
            this.sandbox.dom.html(this.sandbox.dom.find('.' + constants.errorMessageClass, this.dom.$forgotPasswordFrame),
                this.sandbox.translate(errorTransKey));

            this.sandbox.dom.addClass(this.dom.$forgotPasswordFrame, constants.errorClass);
            this.focusFirstInput(this.dom.$forgotPasswordFrame);
        },

        /**
         * Display resend-reset-mail error
         * @param code - integer - error code
         */
        displayResendResetMailError: function(code) {
            var errorTransKey = this.options.errorTranslations[code] || 'Error';
            this.sandbox.emit('sulu.labels.error.show', this.sandbox.translate(errorTransKey), 'labels.error');
            this.sandbox.dom.addClass(this.dom.$resendResetMailButton, 'inactive');
        },

        /**
         * Displays reset-password error (reset mode)
         * @param code - integer - error code
         */
        displayResetPasswordError: function(code) {
            var errorTransKey = this.options.errorTranslations[code] || 'Error';
            this.sandbox.emit('sulu.labels.error.show', this.sandbox.translate(errorTransKey), 'labels.error');
            this.focusFirstInput(this.dom.$forgotPasswordFrame);
        },

        /**
         * Redirect to given url
         * @param url
         */
        redirectTo: function(url) {
            this.sandbox.dom.window.location = url;
        },

        /**
         * Move frame-slider to forgot-password-frame
         */
        moveToForgotPasswordFrame: function() {
            this.moveFrameSliderTo(this.dom.$forgotPasswordFrame);
        },

        /**
         * Move frame-slider to resend-mail-frame display given email-address
         * @param email
         */
        moveToResendMailFrame: function(email) {
            this.sandbox.dom.html(this.sandbox.dom.find('.to-mail', this.dom.$resendMailFrame), email);
            this.moveFrameSliderTo(this.dom.$resendMailFrame);
        },

        /**
         * Move to login-frame
         */
        moveToLoginFrame: function() {
            this.moveFrameSliderTo(this.dom.$loginFrame);
        },

        /**
         * Move frame-slider to the given frame and focus first input
         * @param $frame
         */
        moveFrameSliderTo: function($frame) {
            this.sandbox.dom.one(this.$el, 'transitionend webkitTransitionEnd oTransitionEnd otransitionend MSTransitionEnd',
                function() {
                    this.focusFirstInput($frame); //workaround for ie; focus input after end of transition;
                }.bind(this));

            this.sandbox.dom.css(this.dom.$frameSlider, 'left', -this.sandbox.dom.position($frame).left + 'px');
        },

        /**
         * Focus first input-field of the given frame
         * @param $frame
         */
        focusFirstInput: function($frame) {
            if (this.sandbox.dom.find('input', $frame).length < 1) {
                return false;
            }

            var input = this.sandbox.dom.find('input', $frame)[0];
            this.sandbox.dom.select(input);

            //set input cursor to end of input-value
            input.setSelectionRange(this.sandbox.dom.val(input).length, this.sandbox.dom.val(input).length);
        }
    };
});
