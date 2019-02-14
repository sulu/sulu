// @flow
import {mount, render, shallow} from 'enzyme';
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {translate} from '../../../utils/Translator';
import SingleDatagridOverlay from '../../SingleDatagridOverlay';
import Datagrid from '../Datagrid';
import DatagridStore from '../stores/DatagridStore';
import datagridAdapterRegistry from '../registries/DatagridAdapterRegistry';
import datagridFieldTransformerRegistry from '../registries/DatagridFieldTransformerRegistry';
import AbstractAdapter from '../adapters/AbstractAdapter';
import TableAdapter from '../adapters/TableAdapter';
import FolderAdapter from '../adapters/FolderAdapter';
import StringFieldTransformer from '../fieldTransformers/StringFieldTransformer';

let mockStructureStrategyData;

jest.mock('../stores/DatagridStore', () => jest.fn(function(resourceKey, datagridKey, observableOptions = {}) {
    this.resourceKey = resourceKey;
    this.datagridKey = datagridKey;
    this.observableOptions = observableOptions;
    this.setPage = jest.fn();
    this.setActive = jest.fn();
    this.activeItems = [];
    this.activate = jest.fn();
    this.active = {
        get: jest.fn(),
    };
    this.deactivate = jest.fn();
    this.delete = jest.fn();
    this.deleteSelection = jest.fn();
    this.order = jest.fn();
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
    this.limit = {
        get: jest.fn().mockReturnValue(10),
    };
    this.setLimit = jest.fn();
    this.updateLoadingStrategy = jest.fn();
    this.updateStructureStrategy = jest.fn();
    this.getPage = jest.fn().mockReturnValue(4);
    this.pageCount = 7;
    this.selections = [];
    this.selectionIds = [];
    this.loading = false;
    this.userSchema = {
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
    this.selectVisibleItems = jest.fn();
    this.deselectVisibleItems = jest.fn();
    this.updateLoadingStrategy = jest.fn();
    this.structureStrategy = {
        data: mockStructureStrategyData,
    };
    this.data = this.structureStrategy.data;
    this.search = jest.fn();
    this.move = jest.fn();
    this.copy = jest.fn();

    mockExtendObservable(this, {
        copying: false,
        ordering: false,
    });
}));

jest.mock('../registries/DatagridAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
    has: jest.fn(),
}));

jest.mock('../registries/DatagridFieldTransformerRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn(function(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
        }
    }),
}));

jest.mock('../../SingleDatagridOverlay', () => function() {
    return null;
});

class LoadingStrategy {
    destroy = jest.fn();
    initialize = jest.fn();
    load = jest.fn();
    reset = jest.fn();
    setStructureStrategy = jest.fn();
}

class StructureStrategy {
    data: Array<Object>;
    visibleItems: Array<Object>;

    addItem = jest.fn();
    clear = jest.fn();
    findById = jest.fn();
    order = jest.fn();
    remove = jest.fn();
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

test('Render Loader instead of Adapter if nothing was loaded yet', () => {
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    // $FlowFixMe
    datagridStore.loading = true;
    datagridStore.pageCount = 0;

    expect(render(<Datagrid adapters={['table']} store={datagridStore} />)).toMatchSnapshot();
});

test('Do not render Loader instead of Adapter if no page count is given', () => {
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    // $FlowFixMe
    datagridStore.loading = true;
    datagridStore.pageCount = undefined;

    expect(render(<Datagrid adapters={['table']} store={datagridStore} />)).toMatchSnapshot();
});

test('Render TableAdapter with correct values', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    mockStructureStrategyData = [
        {
            title: 'value',
            id: 1,
        },
    ];

    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    datagridStore.active.get.mockReturnValue(3);
    datagridStore.selectionIds.push(1, 3);
    const editClickSpy = jest.fn();

    const datagrid = shallow(<Datagrid adapters={['table']} onItemClick={editClickSpy} store={datagridStore} />);

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
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    const datagrid = shallow(<Datagrid adapters={['test']} selectable={false} store={datagridStore} />);

    expect(datagrid.find('TestAdapter').prop('onItemSelectionChange')).toEqual(undefined);
    expect(datagrid.find('TestAdapter').prop('onAllSelectionChange')).toEqual(undefined);
});

test('Render the adapter in non-deletable mode', () => {
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    const datagrid = shallow(<Datagrid adapters={['test']} deletable={false} store={datagridStore} />);

    expect(datagrid.find('TestAdapter').prop('onRequestItemDelete')).toEqual(undefined);
});

test('Render the adapter in non-movable mode', () => {
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    const datagrid = shallow(<Datagrid adapters={['test']} movable={false} store={datagridStore} />);

    expect(datagrid.find('TestAdapter').prop('onRequestItemMove')).toEqual(undefined);
});

test('Render the adapter in non-copyable mode', () => {
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    const datagrid = shallow(<Datagrid adapters={['test']} copyable={false} store={datagridStore} />);

    expect(datagrid.find('TestAdapter').prop('onRequestItemCopy')).toEqual(undefined);
});

test('Render the adapter in non-orderable mode', () => {
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    const datagrid = shallow(<Datagrid adapters={['test']} orderable={false} store={datagridStore} />);

    expect(datagrid.find('TestAdapter').prop('onRequestOrderItem')).toEqual(undefined);
});

test('Render the adapter in non-searchable mode', () => {
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    expect(
        render(<Datagrid adapters={['test']} header={<h1>Title</h1>} searchable={false} store={datagridStore} />)
    ).toMatchSnapshot();
});

test('Render the adapter in disabled state', () => {
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    expect(
        render(<Datagrid adapters={['test']} disabled={true} header={<h1>Title</h1>} store={datagridStore} />)
    ).toMatchSnapshot();
});

test('Pass the ids to be disabled to the adapter', () => {
    const disabledIds = [1, 3];
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    const datagrid = shallow(<Datagrid adapters={['test']} disabledIds={disabledIds} store={datagridStore} />);

    expect(datagrid.find('TestAdapter').prop('disabledIds')).toBe(disabledIds);
});

test('Call activate on store if item is activated', () => {
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    const datagrid = shallow(<Datagrid adapters={['test']} store={datagridStore} />);

    datagrid.find('TestAdapter').prop('onItemActivate')(5);

    expect(datagridStore.activate).toBeCalledWith(5);
});

test('Do not call activate if item is activated but disabled and allowActivateForDisabledItems is false', () => {
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    const datagrid = shallow(
        <Datagrid adapters={['test']} allowActivateForDisabledItems={false} disabledIds={[5]} store={datagridStore} />
    );

    datagrid.find('TestAdapter').prop('onItemActivate')(5);
    datagrid.find('TestAdapter').prop('onItemActivate')(7);

    expect(datagridStore.activate).not.toBeCalledWith(5);
    expect(datagridStore.activate).toBeCalledWith(7);
});

test('Call deactivate on store if item is deactivated', () => {
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    const datagrid = shallow(<Datagrid adapters={['test']} store={datagridStore} />);

    datagrid.find('TestAdapter').prop('onItemDeactivate')(5);

    expect(datagridStore.deactivate).toBeCalledWith(5);
});

test('Pass sortColumn and sortOrder to adapter', () => {
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    datagridStore.sortColumn.get.mockReturnValue('title');
    datagridStore.sortOrder.get.mockReturnValue('asc');
    const datagrid = shallow(<Datagrid adapters={['test']} store={datagridStore} />);

    expect(datagrid.find('TestAdapter').props()).toEqual(expect.objectContaining({
        sortColumn: 'title',
        sortOrder: 'asc',
    }));
});

test('Pass options to adapter', () => {
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});

    const datagridAdapterOptions = {test: 'value'};
    datagridAdapterRegistry.getOptions.mockReturnValue(datagridAdapterOptions);

    const datagrid = shallow(<Datagrid adapters={['test']} store={datagridStore} />);

    expect(datagrid.find('TestAdapter').props()).toEqual(expect.objectContaining({
        options: datagridAdapterOptions,
    }));
});

test('Pass correct options to SingleDatagridOverlays', () => {
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});

    const datagrid = shallow(<Datagrid adapters={['test']} store={datagridStore} />);

    expect(datagrid.find(SingleDatagridOverlay).at(0).prop('reloadOnOpen')).toEqual(true);
    expect(datagrid.find(SingleDatagridOverlay).at(1).prop('reloadOnOpen')).toEqual(true);
});

test('Selecting and deselecting items should update store', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
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

test('Selecting and unselecting all visible items should update store', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
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
    expect(datagridStore.selectVisibleItems).toBeCalledWith();
    headerCheckbox.simulate('change', {currentTarget: {checked: false}});
    expect(datagridStore.deselectVisibleItems).toBeCalledWith();
});

test('Clicking a header cell should sort the table', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
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
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
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
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});

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
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});

    datagridAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TableAdapter;
            case 'folder':
                return FolderAdapter;
        }
    });
    mount(<Datagrid adapters={['table', 'folder']} store={datagridStore} />);
    expect(datagridStore.updateLoadingStrategy).toBeCalledWith(expect.any(TableAdapter.LoadingStrategy));
    expect(datagridStore.updateStructureStrategy).toBeCalledWith(expect.any(TableAdapter.StructureStrategy));
});

test('DatagridStore should be updated with current active element', () => {
    datagridAdapterRegistry.get.mockReturnValue(class TestAdapter extends AbstractAdapter {
        static LoadingStrategy = class {
            destroy = jest.fn();
            initialize = jest.fn();
            load = jest.fn();
            reset = jest.fn();
            setStructureStrategy = jest.fn();
        };

        static StructureStrategy = class {
            data = [];
            visibleItems = [];
            addItem = jest.fn();
            clear = jest.fn();
            findById = jest.fn();
            remove = jest.fn();
            order = jest.fn();
        };

        static icon = 'su-th-large';

        constructor(props: *) {
            super(props);

            const {onItemActivate} = this.props;
            if (onItemActivate) {
                onItemActivate('some-uuid');
            }
        }

        render() {
            return null;
        }
    });
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    expect(datagridStore.active.get()).toBe(undefined);
    mount(<Datagrid adapters={['test']} store={datagridStore} />);

    expect(datagridStore.activate).toBeCalledWith('some-uuid');
});

test('SingleDatagridOverlay should disappear when onRequestItemCopy callback is called and overlay is closed', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', 'test_datagrid', 'datagrid_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = shallow(<Datagrid adapters={['table']} store={datagridStore} />);

    const requestCopyPromise = datagrid.find('TableAdapter').prop('onRequestItemCopy')(5);
    datagrid.update();
    expect(datagrid.find(SingleDatagridOverlay).at(1).prop('open')).toEqual(true);
    expect(datagrid.find(SingleDatagridOverlay).at(1).prop('clearSelectionOnClose')).toEqual(true);
    expect(datagrid.find(SingleDatagridOverlay).at(1).prop('disabledIds')).toEqual(undefined);
    expect(datagrid.find(SingleDatagridOverlay).at(1).prop('resourceKey')).toEqual('test');
    expect(datagrid.find(SingleDatagridOverlay).at(1).prop('datagridKey')).toEqual('test_datagrid');

    datagrid.find(SingleDatagridOverlay).at(1).prop('onClose')();
    return requestCopyPromise.then(() => {
        datagrid.update();
        expect(datagrid.find(SingleDatagridOverlay).at(1).prop('open')).toEqual(false);

        expect(datagridStore.copy).not.toBeCalled();
    });
});

test('DatagridStore should copy item when onRequestItemCopy callback is called and overlay is confirmed', () => {
    const copyPromise = Promise.resolve({id: 9});

    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    // $FlowFixMe
    datagridStore.copy.mockReturnValue(copyPromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = mount(<Datagrid adapters={['table']} store={datagridStore} />);

    const requestCopyPromise = datagrid.find('TableAdapter').prop('onRequestItemCopy')(5);
    datagrid.update();
    expect(datagrid.find(SingleDatagridOverlay).at(1).prop('open')).toEqual(true);
    expect(datagrid.find(SingleDatagridOverlay).at(1).prop('clearSelectionOnClose')).toEqual(true);

    datagrid.find(SingleDatagridOverlay).at(1).prop('onConfirm')({id: 8});
    return requestCopyPromise.then(() => {
        expect(datagridStore.copy).toBeCalledWith(5, 8);

        return copyPromise.then(() => {
            datagrid.update();
            expect(datagrid.find(SingleDatagridOverlay).at(1).prop('open')).toEqual(false);
        });
    });
});

test('SingleDatagridOverlay should disappear when onRequestItemMove callback is called and overlay is closed', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', 'test_datagrid', 'datagrid_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = shallow(<Datagrid adapters={['table']} store={datagridStore} />);

    const requestMovePromise = datagrid.find('TableAdapter').prop('onRequestItemMove')(5);
    datagrid.update();
    expect(datagrid.find(SingleDatagridOverlay).at(0).prop('open')).toEqual(true);
    expect(datagrid.find(SingleDatagridOverlay).at(0).prop('disabledIds')).toEqual([5]);
    expect(datagrid.find(SingleDatagridOverlay).at(0).prop('resourceKey')).toEqual('test');
    expect(datagrid.find(SingleDatagridOverlay).at(0).prop('datagridKey')).toEqual('test_datagrid');

    datagrid.find(SingleDatagridOverlay).at(0).prop('onClose')();

    return requestMovePromise.then(() => {
        datagrid.update();
        expect(datagrid.find(SingleDatagridOverlay).at(0).prop('open')).toEqual(false);

        expect(datagridStore.move).not.toBeCalled();
    });
});

test('DatagridStore should move item when onRequestItemMove callback is called and overlay is confirmed', () => {
    const movePromise = Promise.resolve();

    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    // $FlowFixMe
    datagridStore.move.mockReturnValue(movePromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = mount(<Datagrid adapters={['table']} store={datagridStore} />);

    const requestMovePromise = datagrid.find('TableAdapter').prop('onRequestItemMove')(5);
    datagrid.update();
    expect(datagrid.find(SingleDatagridOverlay).at(0).prop('open')).toEqual(true);

    datagrid.find(SingleDatagridOverlay).at(0).prop('onConfirm')({id: 8});
    return requestMovePromise.then(() => {
        expect(datagridStore.move).toBeCalledWith(5, 8);

        return movePromise.then(() => {
            datagrid.update();
            expect(datagrid.find(SingleDatagridOverlay).at(0).prop('open')).toEqual(false);
        });
    });
});

test('Delete warning should disappear when deleting selection was requested and overlay is cancelled', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    datagridStore.selections.push({}, {});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = shallow(<Datagrid adapters={['table']} store={datagridStore} />);

    datagrid.instance().requestSelectionDelete();
    datagrid.update();
    expect(datagrid.find('Dialog').at(0).prop('open')).toEqual(true);
    expect(translate).toHaveBeenCalledWith('sulu_admin.delete_selection_warning_text', {count: 2});

    datagrid.find('Dialog').at(0).prop('onCancel')();
    datagrid.update();
    expect(datagrid.find('Dialog').at(0).prop('open')).toEqual(false);
});

test('DatagridStore should delete selections when deleting selection was requested and overlay is confirmed', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    datagridStore.selections.push({}, {}, {});
    const deleteSelectionPromise = Promise.resolve();
    // $FlowFixMe
    datagridStore.deleteSelection.mockReturnValue(deleteSelectionPromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = shallow(<Datagrid adapters={['table']} store={datagridStore} />);

    datagrid.instance().requestSelectionDelete();
    datagrid.update();
    expect(datagrid.find('Dialog').at(0).prop('open')).toEqual(true);
    expect(translate).toHaveBeenCalledWith('sulu_admin.delete_selection_warning_text', {count: 3});

    datagrid.find('Dialog').at(0).prop('onConfirm')();

    expect(datagridStore.deleteSelection).toBeCalledWith();

    return deleteSelectionPromise.then(() => {
        datagrid.update();
        expect(datagrid.find('Dialog').at(0).prop('open')).toEqual(false);
    });
});

test('Delete warning should disappear when onRequestItemDelete callback is called and overlay is cancelled', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = shallow(<Datagrid adapters={['table']} store={datagridStore} />);

    const requestDeletePromise = datagrid.find('TableAdapter').prop('onRequestItemDelete')(5);
    datagrid.update();
    expect(datagrid.find('Dialog').at(1).prop('open')).toEqual(true);

    datagrid.find('Dialog').at(1).prop('onCancel')();
    return requestDeletePromise.then(() => {
        datagrid.update();
        expect(datagrid.find('Dialog').at(1).prop('open')).toEqual(false);

        expect(datagridStore.delete).not.toBeCalled();
    });
});

test('DatagridStore should delete item when onRequestItemDelete callback is called and overlay is confirmed', () => {
    const deletePromise = Promise.resolve();

    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    // $FlowFixMe
    datagridStore.delete.mockReturnValue(deletePromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = mount(<Datagrid adapters={['table']} store={datagridStore} />);

    const requestDeletePromise = datagrid.find('TableAdapter').prop('onRequestItemDelete')(5);
    datagrid.update();
    expect(datagrid.find('Dialog').at(1).prop('open')).toEqual(true);

    datagrid.find('Dialog').at(1).prop('onConfirm')();
    return requestDeletePromise.then(() => {
        expect(datagridStore.delete).toBeCalledWith(5);

        return deletePromise.then(() => {
            datagrid.update();
            expect(datagrid.find('Dialog').at(1).prop('open')).toEqual(false);
        });
    });
});

test('Order warning should just disappear when onRequestItemOrder callback is called and overlay is cancelled', () => {
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = mount(<Datagrid adapters={['table']} store={datagridStore} />);

    const requestOrderPromise = datagrid.find('TableAdapter').prop('onRequestItemOrder')(5);
    datagrid.update();
    expect(datagrid.find('Dialog').at(2).prop('open')).toEqual(true);

    datagrid.find('Dialog').at(2).prop('onCancel')();

    return requestOrderPromise.then(() => {
        datagrid.update();
        expect(datagrid.find('Dialog').at(2).prop('open')).toEqual(false);

        expect(datagridStore.order).not.toBeCalled();
    });
});

test('DatagridStore should order item when onRequestItemOrder callback is called and overlay is confirmed', () => {
    const orderPromise = Promise.resolve();

    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
    const datagridStore = new DatagridStore('test', 'test', 'datagrid_test', {page: observable.box(1)});
    // $FlowFixMe
    datagridStore.order.mockReturnValue(orderPromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const datagrid = mount(<Datagrid adapters={['table']} store={datagridStore} />);

    const requestOrderPromise = datagrid.find('TableAdapter').prop('onRequestItemOrder')(5, 8);
    datagrid.update();
    expect(datagrid.find('Dialog').at(2).prop('open')).toEqual(true);
    datagrid.find('Dialog').at(2).prop('onConfirm')();

    return requestOrderPromise.then(() => {
        expect(datagridStore.order).toBeCalledWith(5, 8);

        return orderPromise.then(() => {
            datagrid.update();
            expect(datagrid.find('Dialog').at(2).prop('open')).toEqual(false);
        });
    });
});
