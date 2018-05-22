// @flow
export type Webspace = {
    allLocalizations: Array<LocalizationItem>,
    key: string,
    localizations: Array<Localization>,
    name: string,
    portalInformation: Array<PortalInformation>,
    urls: Array<Url>,
};

export type Localization = {
    children: Array<Localization>,
    country: string,
    default: boolean,
    language: string,
    locale: string,
    shadow: string,
    xDefault: boolean,
};

export type Url = {
    analyticsKey: string,
    country: string,
    environment: string,
    language: string,
    main: boolean,
    redirect: string,
    segment: string,
    url: string,
};

export type LocalizationItem = {
    localization: string,
    name: string,
};

export type PortalInformation = {
    locale: string,
    main: boolean,
    portalKey: string,
    url: string,
    webspaceKey: string,
};
