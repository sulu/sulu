// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import searchResourcesStores from '../../stores/searchResourceStore';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    getList: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
}));

beforeEach(() => {
    searchResourcesStores.clear();
});

test('Load searchResources', () => {
    const response = {
        _embedded: {
            search_resources: {
                contacts: {
                    name: 'People',
                },
                pages: {
                    name: 'example.com',
                },
            },
        },
    };

    const promise = Promise.resolve(response);

    ResourceRequester.getList.mockReturnValue(promise);
    const searchResourcesPromise = searchResourcesStores.loadSearchResources();

    expect(ResourceRequester.getList).toBeCalledWith('search_resources');

    return searchResourcesPromise.then((webspaces) => {
        // check if promise have been cached
        expect(searchResourcesStores.searchResourcePromise).toEqual(promise);
        expect(webspaces).toBe(response._embedded.search_resources);
    });
});
