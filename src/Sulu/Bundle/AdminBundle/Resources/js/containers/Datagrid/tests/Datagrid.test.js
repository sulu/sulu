/* eslint-disable flowtype/require-valid-file-annotation */
import {shallow} from 'enzyme';
import React from 'react';
import Datagrid from '../Datagrid';
import DatagridStore from '../stores/DatagridStore';

jest.mock('../../../stores/ResourceMetadataStore', () => ({
    getBaseUrl: jest.fn(),
}));

jest.mock('../stores/DatagridStore', () => jest.fn(function() {
    this.setPage = jest.fn();
    this.getPage = function() {
        return 4;
    };
    this.pageCount = 7;
    this.data = {test: 'value'};
    this.isLoading = false;
    this.getFields = function() {
        return {
            test: {},
        };
    };
}));

jest.mock('../adapters/TableAdapter', () => {
    return function TableAdapter() {
        return <table />;
    };
});

test('Change page in DatagridStore on pagination click', () => {
    const datagridStore = new DatagridStore('test', '/api/test');
    const datagrid = shallow(<Datagrid store={datagridStore} />);
    datagrid.find('Pagination').simulate('change', 3);
    expect(datagridStore.setPage).toBeCalledWith(3);
});

test ('Render Pagination with correct values', () => {
    const datagridStore = new DatagridStore('test', '/api/test');

    const datagrid = shallow(<Datagrid store={datagridStore} />);
    const pagination = datagrid.find('Pagination');

    expect(pagination.prop('current')).toEqual(4);
    expect(pagination.prop('total')).toEqual(7);
});

test('Render TableAdapter with correct values', () => {
    const datagridStore = new DatagridStore('test', '/api/test');
    const editClickSpy = jest.fn();

    const datagrid = shallow(<Datagrid store={datagridStore} onRowEditClick={editClickSpy} />);
    const tableAdapter = datagrid.find('TableAdapter');

    expect(tableAdapter.prop('data')).toEqual({test: 'value'});
    expect(tableAdapter.prop('schema')).toEqual({test: {}});
    expect(tableAdapter.prop('onRowEditClick')).toBe(editClickSpy);
});
