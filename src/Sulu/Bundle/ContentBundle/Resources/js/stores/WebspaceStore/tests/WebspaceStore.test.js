// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import webspaceStore from '../WebspaceStore';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    getList: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
}));

test('Load webspaces', () => {
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

    return webspacePromise.then((webspaces) => {
        // check if promise have been cached
        expect(webspaceStore.webspacePromise).toEqual(promise);
        expect(webspaces).toBe(response._embedded.webspaces);
    });
});

test('Load webspace with given key', () => {
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

    return webspacePromise.then((webspace) => {
        // check if promise have been cached
        expect(webspaceStore.webspacePromise).toEqual(promise);
        expect(webspace.name).toBe(response._embedded.webspaces[0].name);
        expect(webspace.key).toBe(response._embedded.webspaces[0].key);
    });
});
