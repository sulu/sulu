// @flow
import SearchStore from '../SearchStore';
import ResourceRequester from '../../../services/ResourceRequester';

jest.mock('../../../services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

test('Clear search results from store', () => {
    const searchStore = new SearchStore('test', []);
    searchStore.searchResults = [{}];

    searchStore.clearSearchResults();

    expect(searchStore.searchResults).toHaveLength(0);
});

test('Send a request using the ResourceRequester when something is being searched', () => {
    const searchStore = new SearchStore('accounts', ['name', 'number']);
    const searchResults = [{id: 1, name: 'Sulu'}];
    const searchPromise = Promise.resolve({
        _embedded: {
            accounts: searchResults,
        },
    });

    ResourceRequester.getList.mockReturnValue(searchPromise);

    const autoCompletePromise = searchStore.search('Sulu');
    expect(searchStore.loading).toEqual(true);

    return autoCompletePromise.then(() => {
        expect(ResourceRequester.getList).toBeCalledWith('accounts', {
            limit: 10,
            page: 1,
            search: 'Sulu',
            searchFields: ['name', 'number'],
        });
        expect(searchStore.searchResults).toEqual(searchResults);
        expect(searchStore.loading).toEqual(false);
    });
});

test('Send a request using the ResourceRequester with excludedIds when something is being searched', () => {
    const searchStore = new SearchStore('accounts', ['name', 'number']);
    const searchResults = [{id: 1, name: 'Sulu'}];
    const searchPromise = Promise.resolve({
        _embedded: {
            accounts: searchResults,
        },
    });

    ResourceRequester.getList.mockReturnValue(searchPromise);

    const autoCompletePromise = searchStore.search('Sulu', [1, 4]);
    expect(searchStore.loading).toEqual(true);

    return autoCompletePromise.then(() => {
        expect(ResourceRequester.getList).toBeCalledWith('accounts', {
            excludedIds: [1, 4],
            limit: 10,
            page: 1,
            search: 'Sulu',
            searchFields: ['name', 'number'],
        });
        expect(searchStore.searchResults).toEqual(searchResults);
        expect(searchStore.loading).toEqual(false);
    });
});

test('Reset loading flag in case the request errors', () => {
    const searchStore = new SearchStore('accounts', ['name', 'number']);
    const searchPromise = Promise.reject();

    ResourceRequester.getList.mockReturnValue(searchPromise);

    const autoCompletePromise = searchStore.search('Sulu');
    expect(searchStore.loading).toEqual(true);

    return autoCompletePromise.catch(() => {
        expect(searchStore.loading).toEqual(false);
    });
});
