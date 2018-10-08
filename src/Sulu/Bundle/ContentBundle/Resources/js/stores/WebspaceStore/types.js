// @flow
import type {Localization} from 'sulu-admin-bundle/stores';

export type Webspace = {
    name: string,
    key: string,
    localizations: Array<Localization>,
    urls: Array<Url>,
    allLocalizations: Array<LocalizationItem>,
    portalInformation: Array<PortalInformation>,
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
