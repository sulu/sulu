// @flow

export type User = {
    id: number,
    locale: string,
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
