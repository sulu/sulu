// @flow
import AutoCompleteStore from '../../stores/AutoCompleteStore';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

test('Clear search results from store', () => {
    const autoCompleteStore = new AutoCompleteStore('test', []);
    autoCompleteStore.searchResults = [{}];

    autoCompleteStore.clearSearchResults();

    expect(autoCompleteStore.searchResults).toHaveLength(0);
});

test('Send a request using the ResourceRequester when something is being searched', () => {
    const autoCompleteStore = new AutoCompleteStore('accounts', ['name', 'number']);
    const searchResults = [{id: 1, name: 'Sulu'}];
    const searchPromise = Promise.resolve({
        _embedded: {
            accounts: searchResults,
        },
    });

    ResourceRequester.getList.mockReturnValue(searchPromise);

    const autoCompletePromise = autoCompleteStore.search('Sulu');
    expect(autoCompleteStore.loading).toEqual(true);

    return autoCompletePromise.then(() => {
        expect(ResourceRequester.getList).toBeCalledWith('accounts', {
            limit: 10,
            page: 1,
            search: 'Sulu',
            searchFields: ['name', 'number'],
        });
        expect(autoCompleteStore.searchResults).toEqual(searchResults);
        expect(autoCompleteStore.loading).toEqual(false);
    });
});

test('Reset loading flag in case the request errors', () => {
    const autoCompleteStore = new AutoCompleteStore('accounts', ['name', 'number']);
    const searchPromise = Promise.reject();

    ResourceRequester.getList.mockReturnValue(searchPromise);

    const autoCompletePromise = autoCompleteStore.search('Sulu');
    expect(autoCompleteStore.loading).toEqual(true);

    return autoCompletePromise.catch(() => {
        expect(autoCompleteStore.loading).toEqual(false);
    });
});
