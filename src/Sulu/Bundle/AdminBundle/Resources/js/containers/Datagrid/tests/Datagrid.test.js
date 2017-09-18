/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, shallow} from 'enzyme';
import React from 'react';
import Datagrid from '../Datagrid';
import DatagridStore from '../stores/DatagridStore';

jest.mock('../../../stores/ResourceMetadataStore', () => ({
    getBaseUrl: jest.fn(),
}));

jest.mock('../stores/DatagridStore', () => jest.fn(function() {
    this.setPage = jest.fn();
    this.getPage = jest.fn().mockReturnValue(4);
    this.pageCount = 7;
    this.data = {test: 'value'};
    this.selections = [];
    this.isLoading = false;
    this.getFields = jest.fn().mockReturnValue({test: {}});
    this.select = jest.fn();
    this.deselect = jest.fn();
    this.selectEntirePage = jest.fn();
    this.deselectEntirePage = jest.fn();
}));

jest.mock('../../../services/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
        }
    },
}));

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
    datagridStore.selections.push(1);
    datagridStore.selections.push(3);
    const editClickSpy = jest.fn();

    const datagrid = shallow(<Datagrid store={datagridStore} onRowEditClick={editClickSpy} />);
    const tableAdapter = datagrid.find('TableAdapter');

    expect(tableAdapter.prop('data')).toEqual({test: 'value'});
    expect(tableAdapter.prop('selections')).toEqual([1, 3]);
    expect(tableAdapter.prop('schema')).toEqual({test: {}});
    expect(tableAdapter.prop('onRowEditClick')).toBe(editClickSpy);
});

test('Selecting and deselecting items should update store', () => {
    const datagridStore = new DatagridStore('test', '/api/test');
    datagridStore.data = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = mount(<Datagrid store={datagridStore} />);

    const checkboxes = datagrid.find('input[type="checkbox"]');
    // TODO setting checked explicitly should not be necessary, see https://github.com/airbnb/enzyme/issues/1114
    checkboxes.at(1).getDOMNode().checked = true;
    checkboxes.at(2).getDOMNode().checked = true;
    checkboxes.at(1).simulate('change', {currentTarget: {checked: true}});
    expect(datagridStore.select).toBeCalledWith(1);
    checkboxes.at(2).simulate('change', {currentTarget: {checked: true}});
    expect(datagridStore.select).toBeCalledWith(2);
    checkboxes.at(1).simulate('change', {currentTarget: {checked: false}});
    expect(datagridStore.deselect).toBeCalledWith(1);
});

test('Selecting and unselecting all items on current page should update store', () => {
    const datagridStore = new DatagridStore('test', '/api/test');
    datagridStore.data = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = mount(<Datagrid store={datagridStore} />);

    const headerCheckbox = datagrid.find('input[type="checkbox"]').at(0);
    // TODO setting checked explicitly should not be necessary, see https://github.com/airbnb/enzyme/issues/1114
    headerCheckbox.getDOMNode().checked = true;
    headerCheckbox.simulate('change', {currentTarget: {checked: true}});
    expect(datagridStore.selectEntirePage).toBeCalledWith();
    headerCheckbox.simulate('change', {currentTarget: {checked: false}});
    expect(datagridStore.deselectEntirePage).toBeCalledWith();
});
