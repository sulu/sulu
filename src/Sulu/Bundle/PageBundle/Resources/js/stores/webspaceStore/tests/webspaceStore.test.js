// @flow
import log from 'loglevel';
import webspaceStore from '../webspaceStore';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

beforeEach(() => {
    webspaceStore.setWebspaces([]);
});

test('Load granted webspaces', () => {
    const webspace1 = {
        _permissions: {
            view: true,
        },
        name: 'sulu',
        key: 'sulu',
        allLocalizations: [],
        customUrls: [],
        defaultTemplates: {},
        localizations: [],
        navigations: [],
        portalInformation: [],
        resourceLocatorStrategy: {inputType: 'leaf'},
        urls: [],
    };

    const webspace2 = {
        _permissions: {
            view: false,
        },
        name: 'Sulu Blog',
        key: 'sulu_blog',
        allLocalizations: [],
        customUrls: [],
        defaultTemplates: {},
        localizations: [],
        navigations: [],
        portalInformation: [],
        resourceLocatorStrategy: {inputType: 'leaf'},
        urls: [],
    };

    const webspaces = [webspace1, webspace2];

    webspaceStore.setWebspaces(webspaces);

    const webspacePromise = webspaceStore.loadWebspaces();

    return webspacePromise.then((webspaces) => {
        expect(log.warn).toBeCalled();
        expect(webspaces).toEqual([webspace1]);
    });
});

test('Load webspace with given key', () => {
    const webspace1 = {
        _permissions: {
            view: true,
        },
        name: 'sulu',
        key: 'sulu',
        allLocalizations: [],
        customUrls: [],
        defaultTemplates: {},
        localizations: [],
        navigations: [],
        portalInformation: [],
        resourceLocatorStrategy: {inputType: 'leaf'},
        urls: [],
    };

    const webspace2 = {
        _permissions: {
            view: false,
        },
        name: 'Sulu Blog',
        key: 'sulu_blog',
        allLocalizations: [],
        customUrls: [],
        defaultTemplates: {},
        localizations: [],
        navigations: [],
        portalInformation: [],
        resourceLocatorStrategy: {inputType: 'leaf'},
        urls: [],
    };

    const webspaces = [webspace1, webspace2];

    webspaceStore.setWebspaces(webspaces);

    const webspacePromise = webspaceStore.loadWebspace('sulu');

    return webspacePromise.then((webspace) => {
        expect(log.warn).toBeCalled();
        expect(webspace).toEqual(webspace1);
    });
});

test('Get granted webspaces', () => {
    const webspace1 = {
        _permissions: {
            view: true,
        },
        name: 'sulu',
        key: 'sulu',
        allLocalizations: [],
        customUrls: [],
        defaultTemplates: {},
        localizations: [],
        navigations: [],
        portalInformation: [],
        resourceLocatorStrategy: {inputType: 'leaf'},
        urls: [],
    };

    const webspace2 = {
        _permissions: {
            view: false,
        },
        name: 'Sulu Blog',
        key: 'sulu_blog',
        allLocalizations: [],
        customUrls: [],
        defaultTemplates: {},
        localizations: [],
        navigations: [],
        portalInformation: [],
        resourceLocatorStrategy: {inputType: 'leaf'},
        urls: [],
    };

    const webspaces = [webspace1, webspace2];

    webspaceStore.setWebspaces(webspaces);

    expect(webspaceStore.grantedWebspaces).toEqual([webspace1]);
    expect(log.warn).not.toBeCalled();
});

test('Get webspace with given key', () => {
    const webspace1 = {
        _permissions: {
            view: true,
        },
        name: 'sulu',
        key: 'sulu',
        allLocalizations: [],
        customUrls: [],
        defaultTemplates: {},
        localizations: [],
        navigations: [],
        portalInformation: [],
        resourceLocatorStrategy: {inputType: 'leaf'},
        urls: [],
    };

    const webspace2 = {
        _permissions: {
            view: false,
        },
        name: 'Sulu Blog',
        key: 'sulu_blog',
        allLocalizations: [],
        customUrls: [],
        defaultTemplates: {},
        localizations: [],
        navigations: [],
        portalInformation: [],
        resourceLocatorStrategy: {inputType: 'leaf'},
        urls: [],
    };

    const webspaces = [webspace1, webspace2];

    webspaceStore.setWebspaces(webspaces);

    expect(webspaceStore.getWebspace('sulu')).toEqual(webspace1);
    expect(log.warn).not.toBeCalled();
});
