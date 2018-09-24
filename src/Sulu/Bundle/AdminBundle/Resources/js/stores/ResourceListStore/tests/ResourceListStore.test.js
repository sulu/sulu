// @flow
import ResourceListStore from '../ResourceListStore';
import ResourceRequester from '../../../services/ResourceRequester';

jest.mock('../../../services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

test('Send a request using the ResourceRequester', () => {
    const requestResults = [{id: 1, name: 'Sulu'}, {id: 2, name: 'Test'}];
    const requestPromise = Promise.resolve({
        _embedded: {
            accounts: requestResults,
        },
    });

    ResourceRequester.getList.mockReturnValue(requestPromise);

    const resourceListStore = new ResourceListStore('accounts');
    expect(resourceListStore.loading).toEqual(true);

    return requestPromise.then(() => {
        expect(ResourceRequester.getList).toBeCalledWith('accounts', {
            limit: 100,
            page: 1,
        });
        expect(resourceListStore.data).toEqual(requestResults);
        expect(resourceListStore.loading).toEqual(false);
    });
});
