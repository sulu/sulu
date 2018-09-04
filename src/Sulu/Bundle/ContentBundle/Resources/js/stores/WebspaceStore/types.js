// @flow
export type Webspace = {
    name: string,
    key: string,
    localizations: Array<Localization>,
    urls: Array<Url>,
    allLocalizations: Array<LocalizationItem>,
    portalInformation: Array<PortalInformation>,
    navigations: Array<Navigation>,
};

export type Navigation = {
    key: string,
    title: string,
};

export type Localization = {
    locale: string,
    language: string,
    country: string,
    shadow: string,
    default: boolean,
    xDefault: boolean,
    children: Array<Localization>,
};

export type Url = {
    url: string,
    language: string,
    country: string,
    segment: string,
    redirect: string,
    main: boolean,
    analyticsKey: string,
    environment: string,
};

export type LocalizationItem = {
    localization: string,
    name: string,
};

export type PortalInformation = {
    webspaceKey: string,
    portalKey: string,
    locale: string,
    url: string,
    main: boolean,
};
