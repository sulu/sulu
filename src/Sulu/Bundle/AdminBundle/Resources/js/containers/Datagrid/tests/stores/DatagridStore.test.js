/* eslint-disable flowtype/require-valid-file-annotation */
import 'url-search-params-polyfill';
import {when} from 'mobx';
import DatagridStore from '../../stores/DatagridStore';
import metadataStore from '../../stores/MetadataStore';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    cget: jest.fn(),
}));

jest.mock('../../stores/MetadataStore', () => ({
    getFields: jest.fn(),
}));

test('Do not send request without defined page parameter', () => {
    new DatagridStore('tests');
    expect(ResourceRequester.cget).not.toBeCalled();
});

test('Send request with default parameters', (done) => {
    const Promise = require.requireActual('promise');
    ResourceRequester.cget.mockReturnValue(Promise.resolve({
        pages: 3,
        _embedded: {
            tests: [{id: 1}],
        },
    }));
    const datagridStore = new DatagridStore('tests');
    datagridStore.setPage(1);
    expect(ResourceRequester.cget).toBeCalledWith('tests', {page: 1});
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
    const datagridStore = new DatagridStore('tests');
    datagridStore.setPage(1);
    expect(ResourceRequester.cget).toBeCalledWith('tests', {page: 1});
    datagridStore.destroy();
});

test('Send request to other page', () => {
    const datagridStore = new DatagridStore('tests');
    datagridStore.setPage(1);
    expect(ResourceRequester.cget).toBeCalledWith('tests', {page: 1});
    datagridStore.setPage(2);
    expect(ResourceRequester.cget).toBeCalledWith('tests', {page: 2});
    datagridStore.destroy();
});

test('Set loading flag to true before request', () => {
    const datagridStore = new DatagridStore('tests');
    datagridStore.setPage(1);
    datagridStore.setLoading(false);
    datagridStore.sendRequest();
    expect(datagridStore.isLoading).toEqual(true);
    datagridStore.destroy();
});

test('Set loading flag to false after request', () => {
    const datagridStore = new DatagridStore('tests');
    const Promise = require.requireActual('promise');
    ResourceRequester.cget.mockReturnValue(Promise.resolve({
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

    const datagridStore = new DatagridStore('tests');
    expect(datagridStore.getFields()).toBe(fields);
    expect(metadataStore.getFields).toBeCalledWith('tests');
    datagridStore.destroy();
});

test('After initialization no row should be selected', () => {
    const datagridStore = new DatagridStore('tests');
    expect(datagridStore.selections.length).toBe(0);
    datagridStore.destroy();
});

test('Select an item', () => {
    const datagridStore = new DatagridStore('tests');
    datagridStore.select(1);
    datagridStore.select(2);
    expect(datagridStore.selections.toJS()).toEqual([1, 2]);

    datagridStore.deselect(1);
    expect(datagridStore.selections.toJS()).toEqual([2]);
    datagridStore.destroy();
});

test('Deselect an item that has not been selected yet', () => {
    const datagridStore = new DatagridStore('tests');
    datagridStore.select(1);
    datagridStore.deselect(2);

    expect(datagridStore.selections.toJS()).toEqual([1]);
    datagridStore.destroy();
});

test('Select the entire page', (done) => {
    ResourceRequester.cget.mockReturnValue(Promise.resolve({
        _embedded: {
            tests: [
                {id: 1},
                {id: 2},
                {id: 3},
            ],
        },
    }));

    const datagridStore = new DatagridStore('tests');
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
    ResourceRequester.cget.mockReturnValue(Promise.resolve({
        _embedded: {
            tests: [
                {id: 1},
                {id: 2},
                {id: 3},
            ],
        },
    }));

    const datagridStore = new DatagridStore('tests');
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
