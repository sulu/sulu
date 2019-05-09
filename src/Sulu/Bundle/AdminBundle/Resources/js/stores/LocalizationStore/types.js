// @flow
export type Localization = {
    children?: Array<Localization>,
    country: string,
    default: string,
    language: string,
    locale: string,
    shadow: string,
    xDefault: string,
};
