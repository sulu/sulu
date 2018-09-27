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
        expect(ResourceRequester.getList).toBeCalledWith('accounts', {});
        expect(resourceListStore.data).toEqual(requestResults);
        expect(resourceListStore.loading).toEqual(false);
    });
});

test('Send a request with options using the ResourceRequester', () => {
    const requestResults = [{id: 1, name: 'Sulu'}, {id: 2, name: 'Test'}];
    const requestPromise = Promise.resolve({
        _embedded: {
            accounts: requestResults,
        },
    });

    const apiOptions = {
        page: 1,
        limit: 100,
    };
    ResourceRequester.getList.mockReturnValue(requestPromise);

    const resourceListStore = new ResourceListStore('accounts', apiOptions);
    expect(resourceListStore.loading).toEqual(true);

    return requestPromise.then(() => {
        expect(ResourceRequester.getList).toBeCalledWith('accounts', apiOptions);
        expect(resourceListStore.data).toEqual(requestResults);
        expect(resourceListStore.loading).toEqual(false);
    });
});
