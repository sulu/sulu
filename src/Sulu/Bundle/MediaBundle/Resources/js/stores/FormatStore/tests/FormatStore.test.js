// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import userStore from 'sulu-admin-bundle/stores/UserStore';
import formatStore from '../FormatStore';

jest.mock('sulu-admin-bundle/stores/UserStore', () => ({
    user: undefined,
}));

jest.mock('sulu-admin-bundle/services', () => ({
    ResourceRequester: {
        getList: jest.fn().mockReturnValue({
            then: jest.fn(),
        }),
    },
}));

test('Should fail if no user is logged in', () => {
    expect(() => formatStore.loadFormats()).toThrow(/user must be logged in /);
});

test('Load localizations', () => {
    userStore.user = {
        id: 1,
        locale: 'de',
        settings: {},
        username: 'test',
    };

    const response = {
        _embedded: {
            formats: [
                {
                    internal: false,
                    key: '400x400',
                    options: null,
                    scale: {
                        x: 400,
                        y: 400,
                        mode: 'outbound',
                        retina: false,
                        forceRatio: true,
                    },
                    title: 'Test EN',
                },
                {
                    internal: false,
                    key: '800x800',
                    options: null,
                    scale: {
                        x: 800,
                        y: 800,
                        mode: 'outbound',
                        retina: false,
                        forceRatio: true,
                    },
                    title: 'Test1 EN',
                },
            ],
        },
    };

    const promise = Promise.resolve(response);

    ResourceRequester.getList.mockReturnValue(promise);

    const formatPromise = formatStore.loadFormats();

    return formatPromise.then((formats) => {
        // check if promise has been cached
        expect(formatStore.formatPromise).toEqual(formatPromise);
        expect(formats).toBe(response._embedded.formats);
    });
});
