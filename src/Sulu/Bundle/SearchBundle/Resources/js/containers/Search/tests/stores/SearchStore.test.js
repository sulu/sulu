// @flow
import {ResourceRequester} from 'sulu-admin-bundle/services';
import searchStore from '../../stores/SearchStore';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

beforeEach(() => {
    searchStore.search(undefined);
});

test.each([
    ['test1', undefined],
    ['test2', undefined],
    ['test1', 'page'],
    ['test2', 'snippet'],
])('Search results for "%s" in index "%s" should be loaded from server', (query, index) => {
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
    searchStore.search(query, index);
    expect(ResourceRequester.getList).toBeCalledWith('search', {q: query, index});
    expect(searchStore.loading).toEqual(true);

    return searchPromise.then(() => {
        expect(searchStore.loading).toEqual(false);
        expect(searchStore.result).toEqual(result);
    });
});

test('Do not send search request when no search term is given and reset to empty array', () => {
    searchStore.search(undefined);
    expect(ResourceRequester.getList).not.toBeCalled();
    expect(searchStore.result).toEqual([]);
});
