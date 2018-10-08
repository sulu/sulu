// @flow
export type Localization = {
    locale: string,
    language: string,
    country: string,
    shadow: string,
    default: boolean,
    xDefault: boolean,
    children: Array<Localization>,
};
