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

test('Send request with default parameters', () => {
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
});
