/* eslint-disable flowtype/require-valid-file-annotation */
import {when} from 'mobx';
import DatagridStore from '../../stores/DatagridStore';
import metadataStore from '../../stores/MetadataStore';
import Requester from '../../../../services/Requester';

jest.mock('../../../../services/Requester', () => ({
    get: jest.fn(),
}));

jest.mock('../../stores/MetadataStore', () => ({
    getFields: jest.fn(),
}));

test('Do not send request without defined page parameter', () => {
    new DatagridStore('tests', '/api/test');
    expect(Requester.get).not.toBeCalled();
});

test('Send request with default parameters', (done) => {
    const Promise = require.requireActual('promise');
    Requester.get.mockReturnValue(Promise.resolve({
        pages: 3,
        _embedded: {
            tests: [{id: 1}],
        },
    }));
    const datagridStore = new DatagridStore('tests', '/api/test');
    datagridStore.setPage(1);
    expect(Requester.get).toBeCalledWith('/api/test?flat=true&page=1&limit=10');
    when(
        () => !datagridStore.isLoading,
        () => {
            expect(datagridStore.data.toJS()).toEqual([{id: 1}]);
            expect(datagridStore.pageCount).toEqual(3);
            datagridStore.destroy();
            done();
        }
    );
});

test('Send request to other base URL', () => {
    const datagridStore = new DatagridStore('tests', '/api/testing');
    datagridStore.setPage(1);
    expect(Requester.get).toBeCalledWith('/api/testing?flat=true&page=1&limit=10');
    datagridStore.destroy();
});

test('Send request to other page', () => {
    const datagridStore = new DatagridStore('tests', '/api/test');
    datagridStore.setPage(1);
    expect(Requester.get).toBeCalledWith('/api/test?flat=true&page=1&limit=10');
    datagridStore.setPage(2);
    expect(Requester.get).toBeCalledWith('/api/test?flat=true&page=2&limit=10');
    datagridStore.destroy();
});

test('Set loading flag to true before request', () => {
    const datagridStore = new DatagridStore('tests', '/api/test');
    datagridStore.setPage(1);
    datagridStore.setLoading(false);
    datagridStore.sendRequest();
    expect(datagridStore.isLoading).toEqual(true);
    datagridStore.destroy();
});

test('Set loading flag to false after request', () => {
    const datagridStore = new DatagridStore('tests', '/api/test');
    const Promise = require.requireActual('promise');
    Requester.get.mockReturnValue(Promise.resolve({
        _embedded: {
            tests: [],
        },
    }));
    datagridStore.sendRequest();
    when(
        () => !datagridStore.isLoading,
        () => {
            expect(datagridStore.isLoading).toEqual(false);
            datagridStore.destroy();
        }
    );
});

test('Get fields from MetadataStore for correct resourceKey', () => {
    const fields = {
        test: {},
    };
    metadataStore.getFields.mockReturnValue(fields);

    const datagridStore = new DatagridStore('test', '/api/test');
    expect(datagridStore.getFields()).toBe(fields);
    expect(metadataStore.getFields).toBeCalledWith('test');
    datagridStore.destroy();
});

test('After initialization no row should be selected', () => {
    const datagridStore = new DatagridStore('test', '/api/test');
    expect(datagridStore.selections.length).toBe(0);
    datagridStore.destroy();
});

test('Select an item', () => {
    const datagridStore = new DatagridStore('test', '/api/test');
    datagridStore.select(1);
    datagridStore.select(2);
    expect(datagridStore.selections.toJS()).toEqual([1, 2]);

    datagridStore.deselect(1);
    expect(datagridStore.selections.toJS()).toEqual([2]);
    datagridStore.destroy();
});

test('Deselect an item that has not been selected yet', () => {
    const datagridStore = new DatagridStore('test', '/api/test');
    datagridStore.select(1);
    datagridStore.deselect(2);

    expect(datagridStore.selections.toJS()).toEqual([1]);
    datagridStore.destroy();
});

test('Select the entire page', (done) => {
    Requester.get.mockReturnValue(Promise.resolve({
        _embedded: {
            test: [
                {id: 1},
                {id: 2},
                {id: 3},
            ],
        },
    }));

    const datagridStore = new DatagridStore('test', '/api/test');
    datagridStore.selections = [1, 7];
    datagridStore.setPage(1);
    when(
        () => !datagridStore.isLoading,
        () => {
            datagridStore.selectEntirePage();
            expect(datagridStore.selections.toJS()).toEqual([1, 7, 2, 3]);
            datagridStore.destroy();
            done();
        }
    );
});

test('Deselect the entire page', (done) => {
    Requester.get.mockReturnValue(Promise.resolve({
        _embedded: {
            test: [
                {id: 1},
                {id: 2},
                {id: 3},
            ],
        },
    }));

    const datagridStore = new DatagridStore('test', '/api/test');
    datagridStore.selections = [1, 2, 7];
    datagridStore.setPage(1);
    when(
        () => !datagridStore.isLoading,
        () => {
            datagridStore.deselectEntirePage();
            expect(datagridStore.selections.toJS()).toEqual([7]);
            datagridStore.destroy();
            done();
        }
    );
});
