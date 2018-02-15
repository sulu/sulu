// @flow
import Requester from 'sulu-admin-bundle/services/Requester';
import webspaceStore from '../WebspaceStore';

jest.mock('sulu-admin-bundle/services/Requester', () => ({
    get: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
}));

test('Load configuration for given key', () => {
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

    Requester.get.mockReturnValue(promise);

    const webspacePromise = webspaceStore.loadWebspaces();

    return webspacePromise.then((webspaces) => {
        // check if promise have been cached
        expect(webspaceStore.webspacePromise).toEqual(promise);
        expect(webspaces).toBe(response._embedded.webspaces);
    });
});
