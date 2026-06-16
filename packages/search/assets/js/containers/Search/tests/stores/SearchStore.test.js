// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import searchStore from '../../stores/searchStore';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

beforeEach(() => {
    searchStore.search(undefined);
});

test.each([
    ['test1', undefined, 1, 10],
    ['test2', undefined, 1, undefined],
    ['test1', 'page', 1, undefined],
    ['test2', 'snippet', 1, undefined],
])('Search results for "%s" in resource "%s" should be loaded from server', async(query, resourceKey, page, limit) => {
    const result = [
        {id: 1},
    ];

    const searchPromise = Promise.resolve({
        _embedded: {
            result,
        },
    });

    ResourceRequester.getList.mockReturnValue(searchPromise);

    expect(searchStore.loading).toEqual(false);
    searchStore.search(query, resourceKey);
    expect(ResourceRequester.getList).toHaveBeenCalledWith('search', {q: query, resourceKey, page, limit});
    expect(searchStore.loading).toEqual(true);

    await searchPromise; // Wait for the promise to resolve
    return searchPromise.then(() => {
        expect(searchStore.loading).toEqual(false);
        expect(searchStore.result).toEqual(result);
    });
});

test('Do not send search request when no search term is given', () => {
    searchStore.search(undefined);
    expect(ResourceRequester.getList).not.toHaveBeenCalled();
    expect(searchStore.result).toEqual([{id: 1}]);
});
