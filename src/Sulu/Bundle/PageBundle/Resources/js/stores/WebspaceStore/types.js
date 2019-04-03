// @flow
import type {Localization} from 'sulu-admin-bundle/stores';

export type Webspace = {
    allLocalizations: Array<LocalizationItem>,
    defaultTemplates: {[type: string]: string},
    key: string,
    localizations: Array<Localization>,
    name: string,
    navigations: Array<Navigation>,
    portalInformation: Array<PortalInformation>,
    urls: Array<Url>,
};

export type Navigation = {
    key: string,
    title: string,
};

export type Url = {
    url: string,
    language: string,
    country: string,
    segment: string,
    redirect: string,
    main: boolean,
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
