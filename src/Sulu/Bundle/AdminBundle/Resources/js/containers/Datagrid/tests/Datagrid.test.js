// @flow
import {mount, render, shallow} from 'enzyme';
import React from 'react';
import {observable} from 'mobx';
import Datagrid from '../Datagrid';
import DatagridStore from '../stores/DatagridStore';
import datagridAdapterRegistry from '../registries/DatagridAdapterRegistry';
import datagridFieldTransformerRegistry from '../registries/DatagridFieldTransformerRegistry';
import AbstractAdapter from '../adapters/AbstractAdapter';
import TableAdapter from '../adapters/TableAdapter';
import FolderAdapter from '../adapters/FolderAdapter';
import StringFieldTransformer from '../fieldTransformers/StringFieldTransformer';

let mockStructureStrategyData;

jest.mock('../stores/DatagridStore', () => jest.fn(function() {
    this.setPage = jest.fn();
    this.setActive = jest.fn();
    this.activeItems = [];
    this.sort = jest.fn();
    this.sortColumn = {
        get: jest.fn(),
    };
    this.sortOrder = {
        get: jest.fn(),
    };
    this.searchTerm = {
        get: jest.fn(),
    };
    this.updateStrategies = jest.fn();
    this.getPage = jest.fn().mockReturnValue(4);
    this.pageCount = 7;
    this.selections = [];
    this.selectionIds = [];
    this.loading = false;
    this.schema = {
        title: {
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
    };
    this.findById = jest.fn();
    this.select = jest.fn();
    this.deselect = jest.fn();
    this.selectEntirePage = jest.fn();
    this.deselectEntirePage = jest.fn();
    this.updateLoadingStrategy = jest.fn();
    this.structureStrategy = {
        data: mockStructureStrategyData,
    };
    this.data = this.structureStrategy.data;
    this.search = jest.fn();
}));

jest.mock('../registries/DatagridAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

jest.mock('../registries/DatagridFieldTransformerRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
        }
    },
}));

class LoadingStrategy {
    load = jest.fn();
    destroy = jest.fn();
    initialize = jest.fn();
    reset = jest.fn();
}

class StructureStrategy {
    data: Array<Object>;

    clear = jest.fn();
    getData = jest.fn();
    findById = jest.fn();
    enhanceItem = jest.fn();
}

class TestAdapter extends AbstractAdapter {
    static LoadingStrategy = LoadingStrategy;

    static StructureStrategy = StructureStrategy;

    static icon = 'su-th-large';

    render() {
        return (
            <div>Test Adapter</div>
        );
    }
}

beforeEach(() => {
    mockStructureStrategyData = [];
    datagridAdapterRegistry.has.mockReturnValue(true);
    datagridAdapterRegistry.get.mockReturnValue(TestAdapter);

    datagridFieldTransformerRegistry.get.mockReturnValue(new StringFieldTransformer());
});

test('Render TableAdapter with correct values', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    mockStructureStrategyData = [
        {
            title: 'value',
            id: 1,
        },
    ];

    const datagridStore = new DatagridStore('test', {page: observable.box(1)});
    datagridStore.active = 3;
    datagridStore.selectionIds.push(1, 3);
    const editClickSpy = jest.fn();

    const datagrid = shallow(<Datagrid adapters={['table']} store={datagridStore} onItemClick={editClickSpy} />);

    expect(datagrid.find('Search')).not.toBeUndefined();

    const tableAdapter = datagrid.find('TableAdapter');

    expect(tableAdapter.prop('data')).toEqual([{'id': 1, 'title': 'value'}]);
    expect(tableAdapter.prop('active')).toEqual(3);
    expect(tableAdapter.prop('activeItems')).toBe(datagridStore.activeItems);
    expect(tableAdapter.prop('selections')).toEqual([1, 3]);
    expect(tableAdapter.prop('schema')).toEqual({
        title: {
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
    });
    expect(tableAdapter.prop('onItemClick')).toBe(editClickSpy);
    expect(tableAdapter.prop('onItemSelectionChange')).toBeInstanceOf(Function);
    expect(tableAdapter.prop('onAllSelectionChange')).toBeInstanceOf(Function);
});

test('Render the adapter in non-selectable mode', () => {
    const datagridStore = new DatagridStore('test', {page: observable.box(1)});
    const datagrid = shallow(<Datagrid adapters={['test']} selectable={false} store={datagridStore} />);

    expect(datagrid.find('TestAdapter').prop('onItemSelectionChange')).toEqual(undefined);
    expect(datagrid.find('TestAdapter').prop('onAllSelectionChange')).toEqual(undefined);
});

test('Render the adapter in non-searchable mode', () => {
    const datagridStore = new DatagridStore('test', {page: observable.box(1)});
    expect(
        render(<Datagrid adapters={['test']} searchable={false} store={datagridStore} />)
    ).toMatchSnapshot();
});

test('Pass the ids to be disabled to the adapter', () => {
    const disabledIds = [1, 3];
    const datagridStore = new DatagridStore('test', {page: observable.box(1)});
    const datagrid = shallow(<Datagrid adapters={['test']} disabledIds={disabledIds} store={datagridStore} />);

    expect(datagrid.find('TestAdapter').prop('disabledIds')).toBe(disabledIds);
});

test('Pass sortColumn and sortOrder to adapter', () => {
    const datagridStore = new DatagridStore('test', {page: observable.box(1)});
    datagridStore.sortColumn.get.mockReturnValue('title');
    datagridStore.sortOrder.get.mockReturnValue('asc');
    const datagrid = shallow(<Datagrid adapters={['test']} store={datagridStore} />);

    expect(datagrid.find('TestAdapter').props()).toEqual(expect.objectContaining({
        sortColumn: 'title',
        sortOrder: 'asc',
    }));
});

test('Selecting and deselecting items should update store', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', {page: observable.box(1)});
    datagridStore.structureStrategy.data.splice(0, datagridStore.structureStrategy.data.length);
    datagridStore.structureStrategy.data.push(
        {id: 1},
        {id: 2},
        {id: 3}
    );

    datagridStore.findById.mockReturnValueOnce({id: 1}).mockReturnValueOnce({id: 2}).mockReturnValueOnce({id: 1});

    const datagrid = mount(<Datagrid adapters={['table']} store={datagridStore} />);

    const checkboxes = datagrid.find('input[type="checkbox"]');
    // TODO setting checked explicitly should not be necessary, see https://github.com/airbnb/enzyme/issues/1114
    checkboxes.at(1).getDOMNode().checked = true;
    checkboxes.at(2).getDOMNode().checked = true;
    checkboxes.at(1).simulate('change', {currentTarget: {checked: true}});
    expect(datagridStore.findById).toBeCalledWith(1);
    expect(datagridStore.select).toBeCalledWith({id: 1});
    checkboxes.at(2).simulate('change', {currentTarget: {checked: true}});
    expect(datagridStore.findById).toBeCalledWith(2);
    expect(datagridStore.select).toBeCalledWith({id: 2});
    checkboxes.at(1).simulate('change', {currentTarget: {checked: false}});
    expect(datagridStore.findById).toBeCalledWith(1);
    expect(datagridStore.deselect).toBeCalledWith({id: 1});
});

test('Selecting and unselecting all items on current page should update store', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = mount(<Datagrid adapters={['table']} store={datagridStore} />);

    const headerCheckbox = datagrid.find('input[type="checkbox"]').at(0);
    // TODO setting checked explicitly should not be necessary, see https://github.com/airbnb/enzyme/issues/1114
    headerCheckbox.getDOMNode().checked = true;
    headerCheckbox.simulate('change', {currentTarget: {checked: true}});
    expect(datagridStore.selectEntirePage).toBeCalledWith();
    headerCheckbox.simulate('change', {currentTarget: {checked: false}});
    expect(datagridStore.deselectEntirePage).toBeCalledWith();
});

test('Clicking a header cell should sort the table', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = mount(<Datagrid adapters={['table']} store={datagridStore} />);

    const headerCell = datagrid.find('th button').at(0);
    headerCell.simulate('click');
    expect(datagridStore.sort).toBeCalledWith('title', 'asc');
});

test('Trigger a search should call search on the store', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = mount(<Datagrid adapters={['table']} store={datagridStore} />);

    const search = datagrid.find('Search');
    search.prop('onSearch')('search-value');
    expect(datagridStore.search).toBeCalledWith('search-value');
});

test('Switching the adapter should render the correct adapter', () => {
    const datagridStore = new DatagridStore('test', {page: observable.box(1)});

    datagridAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TableAdapter;
            case 'folder':
                return FolderAdapter;
        }
    });
    const datagrid = mount(<Datagrid adapters={['table', 'folder']} store={datagridStore} />);

    expect(datagrid.find('AdapterSwitch').length).toBe(1);
    expect(datagrid.find('TableAdapter').length).toBe(1);

    datagrid.find('AdapterSwitch Button').at(1).simulate('click');
    expect(datagrid.find('TableAdapter').length).toBe(0);
    expect(datagrid.find('FolderAdapter').length).toBe(1);
});

test('DatagridStore should be initialized correctly on init and update', () => {
    const datagridStore = new DatagridStore('test', {page: observable.box(1)});
    datagridStore.updateStrategies = jest.fn();

    datagridAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TableAdapter;
            case 'folder':
                return FolderAdapter;
        }
    });
    mount(<Datagrid adapters={['table', 'folder']} store={datagridStore} />);
    expect(datagridStore.updateStrategies)
        .toBeCalledWith(expect.any(TableAdapter.LoadingStrategy), expect.any(TableAdapter.StructureStrategy));
});

test('DatagridStore should be updated with current active element', () => {
    datagridAdapterRegistry.get.mockReturnValue(class TestAdapter extends AbstractAdapter {
        static LoadingStrategy = class {
            paginationAdapter = undefined;
            load = jest.fn();
            destroy = jest.fn();
            initialize = jest.fn();
            reset = jest.fn();
        };

        static StructureStrategy = class {
            data = [];
            clear = jest.fn();
            getData = jest.fn();
            findById = jest.fn();
            enhanceItem = jest.fn();
        };

        static icon = 'su-th-large';

        constructor(props: *) {
            super(props);

            const {onItemActivation} = this.props;
            if (onItemActivation) {
                onItemActivation('some-uuid');
            }
        }

        render() {
            return null;
        }
    });
    const datagridStore = new DatagridStore('test', {page: observable.box(1)});
    expect(datagridStore.active).toBe(undefined);
    mount(<Datagrid adapters={['test']} store={datagridStore} />);

    expect(datagridStore.setActive).toBeCalledWith('some-uuid');
});
