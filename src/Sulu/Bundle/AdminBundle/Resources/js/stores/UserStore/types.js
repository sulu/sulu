// @flow

export type User = {
    id: number,
    username: string,
    locale: string,
    settings: {[string]: string},
};

export type Contact = {
    id: number,
    firstName: string,
    lastName: string,
    fullName: string,
    avatar?: Avatar,
};

export type Avatar = {
    id: number,
    url: string,
    thumbnails: {[string]: string},
};
