// @flow
import {toJS} from 'mobx';
import ResourceListStore from '../ResourceListStore';
import ResourceRequester from '../../../services/ResourceRequester';

jest.mock('../../../services/ResourceRequester', () => ({
    deleteList: jest.fn(),
    getList: jest.fn(),
    patchList: jest.fn(),
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

    const requestParameters = {
        page: 1,
        limit: 100,
    };
    ResourceRequester.getList.mockReturnValue(requestPromise);

    const resourceListStore = new ResourceListStore('accounts', requestParameters);
    expect(resourceListStore.loading).toEqual(true);

    return requestPromise.then(() => {
        expect(ResourceRequester.getList).toBeCalledWith('accounts', requestParameters);
        expect(resourceListStore.data).toEqual(requestResults);
        expect(resourceListStore.loading).toEqual(false);
    });
});

test('Delete items using the ResourceRequester', () => {
    const requestResults = [{id: 1, name: 'Test1'}, {id: 2, name: 'Test2'}, {id: 3, name: 'Test3'}];
    const requestPromise = Promise.resolve({
        _embedded: {
            contacts: requestResults,
        },
    });

    ResourceRequester.getList.mockReturnValue(requestPromise);

    const resourceListStore = new ResourceListStore('contacts');

    return requestPromise.then(() => {
        expect(resourceListStore.data).toEqual(requestResults);
        expect(resourceListStore.loading).toEqual(false);

        ResourceRequester.deleteList.mockReturnValue(Promise.resolve());
        const deleteListPromise = resourceListStore.deleteList([1, 3]);
        expect(ResourceRequester.deleteList).toBeCalledWith('contacts', {ids: [1, 3]});
        expect(resourceListStore.loading).toEqual(true);

        return deleteListPromise.then(() => {
            expect(resourceListStore.data).toEqual([{id: 2, name: 'Test2'}]);
            expect(resourceListStore.loading).toEqual(false);
        });
    });
});

test('Delete items using the ResourceRequester with api options', () => {
    const requestResults = [{id: 1, name: 'Test1'}, {id: 2, name: 'Test2'}, {id: 3, name: 'Test3'}];
    const requestPromise = Promise.resolve({
        _embedded: {
            contacts: requestResults,
        },
    });

    ResourceRequester.getList.mockReturnValue(requestPromise);

    const resourceListStore = new ResourceListStore('contacts', {webspace: 'sulu_io'});

    return requestPromise.then(() => {
        expect(resourceListStore.data).toEqual(requestResults);
        expect(resourceListStore.loading).toEqual(false);

        ResourceRequester.deleteList.mockReturnValue(Promise.resolve());
        const deleteListPromise = resourceListStore.deleteList([1, 3]);
        expect(ResourceRequester.deleteList).toBeCalledWith('contacts', {ids: [1, 3], webspace: 'sulu_io'});
        expect(resourceListStore.loading).toEqual(true);

        return deleteListPromise.then(() => {
            expect(resourceListStore.data).toEqual([{id: 2, name: 'Test2'}]);
            expect(resourceListStore.loading).toEqual(false);
        });
    });
});

test('Patch items using the ResourceRequester', () => {
    const requestResults = [{id: 1, name: 'Test1'}, {id: 2, name: 'Test2'}, {id: 3, name: 'Test3'}];
    const requestPromise = Promise.resolve({
        _embedded: {
            accounts: requestResults,
        },
    });

    ResourceRequester.getList.mockReturnValue(requestPromise);

    const resourceListStore = new ResourceListStore('accounts');

    return requestPromise.then(() => {
        expect(resourceListStore.data).toEqual(requestResults);
        expect(resourceListStore.loading).toEqual(false);

        ResourceRequester.patchList.mockReturnValue(Promise.resolve([
            {id: 4, name: 'Test4'},
            {id: 2, name: 'Test2 Updated'},
        ]));

        const data = [
            {title: 'Test4'},
            {id: 2, name: 'Test2 Updated'},
        ];
        const patchListPromise = resourceListStore.patchList(data);
        expect(ResourceRequester.patchList).toBeCalledWith('accounts', data);
        expect(resourceListStore.loading).toEqual(true);

        return patchListPromise.then(() => {
            expect(toJS(resourceListStore.data)).toEqual([
                {id: 1, name: 'Test1'},
                {id: 2, name: 'Test2 Updated'},
                {id: 3, name: 'Test3'},
                {id: 4, name: 'Test4'},
            ]);
            expect(resourceListStore.loading).toEqual(false);
        });
    });
});

test('Patch and delete items using the ResourceRequester', () => {
    const requestResults = [{id: 1, name: 'Test1'}, {id: 2, name: 'Test2'}, {id: 3, name: 'Test3'}];
    const requestPromise = Promise.resolve({
        _embedded: {
            accounts: requestResults,
        },
    });

    ResourceRequester.getList.mockReturnValue(requestPromise);

    let deletePromiseResolve;
    const deleteListPromise = new Promise((resolve) => deletePromiseResolve = resolve);
    ResourceRequester.deleteList.mockReturnValue(deleteListPromise);

    let patchPromiseResolve;
    const patchListPromise = new Promise((resolve) => patchPromiseResolve = resolve);
    ResourceRequester.patchList.mockReturnValue(patchListPromise);

    const resourceListStore = new ResourceListStore('accounts');
    expect(resourceListStore.loading).toEqual(true);

    return requestPromise.then(() => {
        expect(ResourceRequester.getList).toBeCalledWith('accounts', {});
        expect(resourceListStore.data).toEqual(requestResults);
        expect(resourceListStore.loading).toEqual(false);

        resourceListStore.deleteList([1]);
        resourceListStore.patchList([
            {name: 'Test4'},
            {id: 2, name: 'Test2 Updated'},
        ]);

        expect(resourceListStore.loading).toEqual(true);

        deletePromiseResolve();

        return deleteListPromise.then(() => {
            expect(toJS(resourceListStore.data)).toEqual([
                {id: 2, name: 'Test2'},
                {id: 3, name: 'Test3'},
            ]);
            patchPromiseResolve([
                {id: 4, name: 'Test4'},
                {id: 2, name: 'Test2 Updated'},
            ]);

            expect(resourceListStore.loading).toEqual(true);

            return patchListPromise.then(() => {
                expect(toJS(resourceListStore.data)).toEqual([
                    {id: 2, name: 'Test2 Updated'},
                    {id: 3, name: 'Test3'},
                    {id: 4, name: 'Test4'},
                ]);
                expect(resourceListStore.loading).toEqual(false);
            });
        });
    });
});
