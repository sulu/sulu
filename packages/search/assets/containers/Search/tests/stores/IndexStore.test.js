// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import indexStore from '../../stores/indexStore';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    getList: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
}));

beforeEach(() => {
    indexStore.clear();
});

test('Load indexes', () => {
    const response = {
        _embedded: {
            search_indexes: [
                {
                    indexName: 'contact',
                    name: 'People',
                },
                {
                    indexName: 'page_example',
                    name: 'example.com',
                },
            ],
        },
    };

    const promise = Promise.resolve(response);

    ResourceRequester.getList.mockReturnValue(promise);
    const indexPromise = indexStore.loadIndexes();

    expect(ResourceRequester.getList).toBeCalledWith('search_indexes');

    return indexPromise.then((webspaces) => {
        // check if promise have been cached
        expect(indexStore.indexPromise).toEqual(promise);
        expect(webspaces).toBe(response._embedded.search_indexes);
    });
});
