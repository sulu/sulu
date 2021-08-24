// @flow

export type FormTypes = 'login' | 'reset-password' | 'forgot-password';

export type ResetPasswordFormData = {
    password: string,
};

export type ForgotPasswordFormData = {
    user: string,
};

export type LoginFormData = {
    password: string,
    username: string,
};
