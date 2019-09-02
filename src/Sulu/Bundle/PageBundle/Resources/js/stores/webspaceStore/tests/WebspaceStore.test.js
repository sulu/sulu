// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import userStore from 'sulu-admin-bundle/stores/userStore';
import webspaceStore from '../webspaceStore';

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    user: undefined,
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    getList: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
}));

beforeEach(() => {
    webspaceStore.clear();
});

test('Should fail if no user is logged in', () => {
    expect(() => webspaceStore.loadWebspaces()).toThrow(/user must be logged in /);
});

test('Load webspaces', () => {
    userStore.user = {
        id: 1,
        locale: 'de',
        settings: {},
        username: 'test',
    };

    const response = {
        _embedded: {
            webspaces: [
                {
                    name: 'sulu',
                    key: 'sulu',
                },
                {
                    name: 'Sulu Blog',
                    key: 'sulu_blog',
                },
            ],
        },
    };

    const promise = Promise.resolve(response);

    ResourceRequester.getList.mockReturnValue(promise);
    const webspacePromise = webspaceStore.loadWebspaces();

    expect(ResourceRequester.getList).toBeCalledWith('webspaces', {locale: 'de'});

    return webspacePromise.then((webspaces) => {
        // check if promise have been cached
        expect(webspaceStore.webspacePromise).toEqual(promise);
        expect(webspaces).toBe(response._embedded.webspaces);
    });
});

test('Load webspace with given key', () => {
    userStore.user = {
        id: 1,
        locale: 'en',
        settings: {},
        username: 'test',
    };

    const response = {
        _embedded: {
            webspaces: [
                {
                    name: 'sulu',
                    key: 'sulu',
                },
                {
                    name: 'Sulu Blog',
                    key: 'sulu_blog',
                },
            ],
        },
    };

    const promise = Promise.resolve(response);

    ResourceRequester.getList.mockReturnValue(promise);
    const webspacePromise = webspaceStore.loadWebspace('sulu');

    expect(ResourceRequester.getList).toBeCalledWith('webspaces', {locale: 'en'});

    return webspacePromise.then((webspace) => {
        // check if promise have been cached
        expect(webspaceStore.webspacePromise).toEqual(promise);
        expect(webspace.name).toBe(response._embedded.webspaces[0].name);
        expect(webspace.key).toBe(response._embedded.webspaces[0].key);
    });
});
