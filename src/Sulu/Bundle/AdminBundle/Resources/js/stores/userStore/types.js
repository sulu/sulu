// @flow

export type User = {
    id: number,
    locale: string,
    roles: string[],
    settings: {[string]: string},
    username: string,
};

export type Contact = {
    avatar?: Avatar,
    firstName: string,
    fullName: string,
    id: number,
    lastName: string,
};

export type Avatar = {
    id: number,
    thumbnails: {[string]: string},
    url: string,
};

export type ForgotPasswordData = {
    user: string,
};

export type ResetPasswordData = {
    password: string,
    token: string,
};

export type LoginData = {
    password: string,
    username: string,
};

export type TwoFactorData = {
    _authCode: string,
};
