// @flow
import type {Localization} from 'sulu-admin-bundle/stores';

export type Webspace = {
    allLocalizations: Array<LocalizationItem>,
    customUrls: Array<CustomUrl>,
    defaultTemplates: {[type: string]: string},
    key: string,
    localizations: Array<Localization>,
    name: string,
    navigations: Array<Navigation>,
    portalInformation: Array<PortalInformation>,
    resourceLocatorStrategy: ResourceLocatorStrategy,
    urls: Array<Url>,
};

export type ResourceLocatorStrategy = {
    inputType: string,
};

export type Navigation = {
    key: string,
    title: string,
};

export type CustomUrl = {
    url: string,
};

export type Url = {
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
