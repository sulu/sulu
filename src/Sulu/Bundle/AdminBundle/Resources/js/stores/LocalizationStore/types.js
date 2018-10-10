// @flow
export type Localization = {
    locale: string,
    language: string,
    country: string,
    shadow: string,
    default: string,
    xDefault: string,
    children?: Array<Localization>,
};
