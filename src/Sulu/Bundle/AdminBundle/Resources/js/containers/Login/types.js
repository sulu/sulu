// @flow

export type FormTypes = 'login' | 'reset-password' | 'forgot-password' | 'two-factor';

export type ResetPasswordFormData = {
    password: string,
};

export type ForgotPasswordFormData = {
    user: string,
};

export type TwoFactorFormData = {
    _auth_code: string,
};

export type LoginFormData = {
    password: string,
    username: string,
};
