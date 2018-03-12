// @flow
import {observable, toJS} from 'mobx';
import AssignmentStore from '../../stores/AssignmentStore';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    getList: jest.fn().mockReturnValue(Promise.resolve({})),
}));

test('Should load items when being constructed', () => {
    const listPromise = Promise.resolve({
        _embedded: {
            snippets: [
                {id: 1},
            ],
        },
    });

    ResourceRequester.getList.mockReturnValue(listPromise);

    const assignmentStore = new AssignmentStore('snippets', [1, 3, 4], observable('en'));

    expect(ResourceRequester.getList).toBeCalledWith(
        'snippets',
        {
            ids: '1,3,4',
            limit: undefined,
            locale: 'en',
            page: 1,
        }
    );

    return listPromise.then(() => {
        expect(toJS(assignmentStore.items)).toEqual([
            {id: 1},
        ]);
    });
});

test('Should load items when being constructed in the given locale', () => {
    const listPromise = Promise.resolve({
        _embedded: {
            snippets: [
                {id: 1},
            ],
        },
    });

    ResourceRequester.getList.mockReturnValue(listPromise);

    const assignmentStore = new AssignmentStore('snippets', [1, 3, 4], observable('de'));

    expect(ResourceRequester.getList).toBeCalledWith(
        'snippets',
        {
            ids: '1,3,4',
            limit: undefined,
            locale: 'de',
            page: 1,
        }
    );

    return listPromise.then(() => {
        expect(toJS(assignmentStore.items)).toEqual([
            {id: 1},
        ]);
    });
});

test('Should load items when being constructed without a locale', () => {
    const listPromise = Promise.resolve({
        _embedded: {
            snippets: [
                {id: 1},
            ],
        },
    });

    ResourceRequester.getList.mockReturnValue(listPromise);

    const assignmentStore = new AssignmentStore('snippets', [1, 3, 4]);

    expect(ResourceRequester.getList).toBeCalledWith(
        'snippets',
        {
            ids: '1,3,4',
            limit: undefined,
            locale: undefined,
            page: 1,
        }
    );

    return listPromise.then(() => {
        expect(toJS(assignmentStore.items)).toEqual([
            {id: 1},
        ]);
    });
});

test('Should remove an item from the store', () => {
    const listPromise = Promise.resolve({
        _embedded: {
            snippets: [
                {id: 1},
                {id: 2},
            ],
        },
    });

    ResourceRequester.getList.mockReturnValue(listPromise);

    const assignmentStore = new AssignmentStore('snippets', [1, 3, 4], observable('en'));

    expect(ResourceRequester.getList).toBeCalledWith(
        'snippets',
        {
            ids: '1,3,4',
            limit: undefined,
            locale: 'en',
            page: 1,
        }
    );

    return listPromise.then(() => {
        expect(toJS(assignmentStore.items)).toEqual([
            {id: 1},
            {id: 2},
        ]);

        assignmentStore.removeById(1);

        expect(toJS(assignmentStore.items)).toEqual([{id: 2}]);
    });
});

test('Should move the items in a store', () => {
    const listPromise = Promise.resolve({
        _embedded: {
            snippets: [
                {id: 1},
                {id: 2},
            ],
        },
    });

    ResourceRequester.getList.mockReturnValue(listPromise);

    const assignmentStore = new AssignmentStore('snippets', [1, 2], observable('en'));

    expect(ResourceRequester.getList).toBeCalledWith(
        'snippets',
        {
            ids: '1,2',
            limit: undefined,
            locale: 'en',
            page: 1,
        }
    );

    return listPromise.then(() => {
        expect(toJS(assignmentStore.items)).toEqual([
            {id: 1},
            {id: 2},
        ]);

        assignmentStore.move(0, 1);

        expect(toJS(assignmentStore.items)).toEqual([{id: 2}, {id: 1}]);
    });
});

test('Should set all items on the store', () => {
    const listPromise = Promise.resolve({
        _embedded: {
            snippets: [
                {id: 1},
                {id: 2},
            ],
        },
    });

    ResourceRequester.getList.mockReturnValue(listPromise);

    const assignmentStore = new AssignmentStore('snippets', [1, 2], observable('en'));

    expect(ResourceRequester.getList).toBeCalledWith(
        'snippets',
        {
            ids: '1,2',
            limit: undefined,
            locale: 'en',
            page: 1,
        }
    );

    return listPromise.then(() => {
        expect(toJS(assignmentStore.items)).toEqual([
            {id: 1},
            {id: 2},
        ]);

        assignmentStore.set([
            {id: 3},
            {id: 4},
        ]);

        expect(toJS(assignmentStore.items)).toEqual([{id: 3}, {id: 4}]);
    });
});
