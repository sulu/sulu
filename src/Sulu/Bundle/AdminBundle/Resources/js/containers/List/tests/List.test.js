// @flow
import {mount, render, shallow} from 'enzyme';
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {translate} from '../../../utils/Translator';
import SingleListOverlay from '../../SingleListOverlay';
import List from '../List';
import ListStore from '../stores/ListStore';
import listAdapterRegistry from '../registries/ListAdapterRegistry';
import listFieldTransformerRegistry from '../registries/ListFieldTransformerRegistry';
import AbstractAdapter from '../adapters/AbstractAdapter';
import TableAdapter from '../adapters/TableAdapter';
import FolderAdapter from '../adapters/FolderAdapter';
import StringFieldTransformer from '../fieldTransformers/StringFieldTransformer';

let mockStructureStrategyData;

jest.mock('../stores/ListStore', () => {
    return jest.fn(function(
        resourceKey,
        listKey,
        userSettingsKey,
        observableOptions = {},
        options = {},
        metadataOptions = {}
    ) {
        this.resourceKey = resourceKey;
        this.listKey = listKey;
        this.options = options;
        this.userSettingsKey = userSettingsKey;
        this.observableOptions = observableOptions;
        this.options = options;
        this.metadataOptions = metadataOptions;

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
    });
});

jest.mock('../registries/ListAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
    has: jest.fn(),
}));

jest.mock('../registries/ListFieldTransformerRegistry', () => ({
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

jest.mock('../../SingleListOverlay', () => function() {
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
    listAdapterRegistry.has.mockReturnValue(true);
    listAdapterRegistry.get.mockReturnValue(TestAdapter);

    listFieldTransformerRegistry.get.mockReturnValue(new StringFieldTransformer());
});

test('Render Loader instead of Adapter if nothing was loaded yet', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.loading = true;
    listStore.pageCount = 0;

    expect(render(<List adapters={['table']} store={listStore} />)).toMatchSnapshot();
});

test('Render permission hint if permissions are missing', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.loading = true;
    listStore.pageCount = 0;
    listStore.forbidden = true;

    expect(render(<List adapters={['table']} store={listStore} />)).toMatchSnapshot();
});

test('Do not render Loader instead of Adapter if no page count is given', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.loading = true;
    listStore.pageCount = undefined;

    expect(render(<List adapters={['table']} store={listStore} />)).toMatchSnapshot();
});

test('Render TableAdapter with correct values', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    mockStructureStrategyData = [
        {
            title: 'value',
            id: 1,
        },
    ];

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    listStore.active.get.mockReturnValue(3);
    listStore.selectionIds.push(1, 3);
    const editClickSpy = jest.fn();

    const list = shallow(<List adapters={['table']} onItemClick={editClickSpy} store={listStore} />);

    expect(list.find('Search')).not.toBeUndefined();

    const tableAdapter = list.find('TableAdapter');

    expect(tableAdapter.prop('actions')).toEqual(undefined);
    expect(tableAdapter.prop('data')).toEqual([{'id': 1, 'title': 'value'}]);
    expect(tableAdapter.prop('active')).toEqual(3);
    expect(tableAdapter.prop('activeItems')).toBe(listStore.activeItems);
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

test('Render TableAdapter with actions', () => {
    const actions = [
        {
            icon: 'su-process',
            onClick: undefined,
        },
    ];

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    mockStructureStrategyData = [
        {
            title: 'value',
            id: 1,
        },
    ];

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    const list = shallow(<List actions={actions} adapters={['table']} store={listStore} />);

    const tableAdapter = list.find('TableAdapter');
    expect(tableAdapter.prop('actions')).toEqual(actions);
});

test('Render the adapter in non-selectable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} selectable={false} store={listStore} />);

    expect(list.find('TestAdapter').prop('onItemSelectionChange')).toEqual(undefined);
    expect(list.find('TestAdapter').prop('onAllSelectionChange')).toEqual(undefined);
});

test('Render the adapter in non-deletable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} deletable={false} store={listStore} />);

    expect(list.find('TestAdapter').prop('onRequestItemDelete')).toEqual(undefined);
});

test('Render the adapter in non-movable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} movable={false} store={listStore} />);

    expect(list.find('TestAdapter').prop('onRequestItemMove')).toEqual(undefined);
});

test('Render the adapter in non-copyable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} copyable={false} store={listStore} />);

    expect(list.find('TestAdapter').prop('onRequestItemCopy')).toEqual(undefined);
});

test('Render the adapter in non-orderable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} orderable={false} store={listStore} />);

    expect(list.find('TestAdapter').prop('onRequestOrderItem')).toEqual(undefined);
});

test('Render the adapter in non-searchable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    expect(
        render(<List adapters={['test']} header={<h1>Title</h1>} searchable={false} store={listStore} />)
    ).toMatchSnapshot();
});

test('Render the adapter in non-searchable mode if searchable is set to true but adapter does not support it', () => {
    class TestAdapter extends AbstractAdapter {
        static LoadingStrategy = LoadingStrategy;
        static StructureStrategy = StructureStrategy;
        static icon = 'su-th-large';
        static searchable = false;

        render() {
            return (
                <div>Test Adapter</div>
            );
        }
    }

    listAdapterRegistry.get.mockReturnValue(TestAdapter);

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    expect(
        render(<List adapters={['test']} header={<h1>Title</h1>} searchable={true} store={listStore} />)
    ).toMatchSnapshot();
});

test('Render the adapter in disabled state', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    expect(
        render(<List adapters={['test']} disabled={true} header={<h1>Title</h1>} store={listStore} />)
    ).toMatchSnapshot();
});

test('Pass the ids to be disabled to the adapter', () => {
    const disabledIds = [1, 3];
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} disabledIds={disabledIds} store={listStore} />);

    expect(list.find('TestAdapter').prop('disabledIds')).toBe(disabledIds);
});

test('Call activate on store if item is activated', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} store={listStore} />);

    list.find('TestAdapter').prop('onItemActivate')(5);

    expect(listStore.activate).toBeCalledWith(5);
});

test('Do not call activate if item is activated but disabled and allowActivateForDisabledItems is false', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(
        <List adapters={['test']} allowActivateForDisabledItems={false} disabledIds={[5]} store={listStore} />
    );

    list.find('TestAdapter').prop('onItemActivate')(5);
    list.find('TestAdapter').prop('onItemActivate')(7);

    expect(listStore.activate).not.toBeCalledWith(5);
    expect(listStore.activate).toBeCalledWith(7);
});

test('Call deactivate on store if item is deactivated', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const list = shallow(<List adapters={['test']} store={listStore} />);

    list.find('TestAdapter').prop('onItemDeactivate')(5);

    expect(listStore.deactivate).toBeCalledWith(5);
});

test('Pass sortColumn and sortOrder to adapter', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    listStore.sortColumn.get.mockReturnValue('title');
    listStore.sortOrder.get.mockReturnValue('asc');
    const list = shallow(<List adapters={['test']} store={listStore} />);

    expect(list.find('TestAdapter').props()).toEqual(expect.objectContaining({
        sortColumn: 'title',
        sortOrder: 'asc',
    }));
});

test('Pass options to adapter', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    const listAdapterOptions = {test: 'value'};
    listAdapterRegistry.getOptions.mockReturnValue(listAdapterOptions);

    const list = shallow(<List adapters={['test']} store={listStore} />);

    expect(list.find('TestAdapter').props()).toEqual(expect.objectContaining({
        options: listAdapterOptions,
    }));
});

test('Pass correct options and metadataOptions to SingleListOverlays', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)}, {option: 'test'}, {id: 1});

    const list = shallow(<List adapters={['test']} store={listStore} />);

    expect(list.find(SingleListOverlay).at(0).prop('reloadOnOpen')).toEqual(true);
    expect(list.find(SingleListOverlay).at(1).prop('reloadOnOpen')).toEqual(true);

    expect(list.find(SingleListOverlay).at(0).prop('options')).toEqual({option: 'test'});
    expect(list.find(SingleListOverlay).at(1).prop('options')).toEqual({option: 'test'});

    expect(list.find(SingleListOverlay).at(0).prop('metadataOptions')).toEqual({id: 1});
    expect(list.find(SingleListOverlay).at(1).prop('metadataOptions')).toEqual({id: 1});
});

test('Selecting and deselecting items should update store', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    listStore.structureStrategy.data.splice(0, listStore.structureStrategy.data.length);
    listStore.structureStrategy.data.push(
        {id: 1},
        {id: 2},
        {id: 3}
    );

    listStore.findById.mockReturnValueOnce({id: 1}).mockReturnValueOnce({id: 2}).mockReturnValueOnce({id: 1});

    const list = mount(<List adapters={['table']} store={listStore} />);

    const checkboxes = list.find('input[type="checkbox"]');
    // TODO setting checked explicitly should not be necessary, see https://github.com/airbnb/enzyme/issues/1114
    checkboxes.at(1).getDOMNode().checked = true;
    checkboxes.at(2).getDOMNode().checked = true;
    checkboxes.at(1).simulate('change', {currentTarget: {checked: true}});
    expect(listStore.findById).toBeCalledWith(1);
    expect(listStore.select).toBeCalledWith({id: 1});
    checkboxes.at(2).simulate('change', {currentTarget: {checked: true}});
    expect(listStore.findById).toBeCalledWith(2);
    expect(listStore.select).toBeCalledWith({id: 2});
    checkboxes.at(1).simulate('change', {currentTarget: {checked: false}});
    expect(listStore.findById).toBeCalledWith(1);
    expect(listStore.deselect).toBeCalledWith({id: 1});
});

test('Selecting and unselecting all visible items should update store', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const headerCheckbox = list.find('input[type="checkbox"]').at(0);
    // TODO setting checked explicitly should not be necessary, see https://github.com/airbnb/enzyme/issues/1114
    headerCheckbox.getDOMNode().checked = true;
    headerCheckbox.simulate('change', {currentTarget: {checked: true}});
    expect(listStore.selectVisibleItems).toBeCalledWith();
    headerCheckbox.simulate('change', {currentTarget: {checked: false}});
    expect(listStore.deselectVisibleItems).toBeCalledWith();
});

test('Clicking a header cell should sort the table', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const headerCell = list.find('th button').at(0);
    headerCell.simulate('click');
    expect(listStore.sort).toBeCalledWith('title', 'asc');
});

test('Trigger a search should call search on the store', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const search = list.find('Search');
    search.prop('onSearch')('search-value');
    expect(listStore.search).toBeCalledWith('search-value');
});

test('Switching the adapter should render the correct adapter', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    listAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TableAdapter;
            case 'folder':
                return FolderAdapter;
        }
    });
    const list = mount(<List adapters={['table', 'folder']} store={listStore} />);

    expect(list.find('AdapterSwitch').length).toBe(1);
    expect(list.find('TableAdapter').length).toBe(1);

    list.find('AdapterSwitch Button').at(1).simulate('click');
    expect(list.find('TableAdapter').length).toBe(0);
    expect(list.find('FolderAdapter').length).toBe(1);
});

test('ListStore should be initialized correctly on init and update', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    listAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TableAdapter;
            case 'folder':
                return FolderAdapter;
        }
    });
    mount(<List adapters={['table', 'folder']} store={listStore} />);
    expect(listStore.updateLoadingStrategy).toBeCalledWith(expect.any(TableAdapter.LoadingStrategy));
    expect(listStore.updateStructureStrategy).toBeCalledWith(expect.any(TableAdapter.StructureStrategy));
});

test('ListStore should be updated with current active element', () => {
    listAdapterRegistry.get.mockReturnValue(class TestAdapter extends AbstractAdapter {
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
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    expect(listStore.active.get()).toBe(undefined);
    mount(<List adapters={['test']} store={listStore} />);

    expect(listStore.activate).toBeCalledWith('some-uuid');
});

test('SingleListOverlay should disappear when onRequestItemCopy callback is called and overlay is closed', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test_list', 'list_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = shallow(<List adapters={['table']} store={listStore} />);

    const requestCopyPromise = list.find('TableAdapter').prop('onRequestItemCopy')(5);
    list.update();
    expect(list.find(SingleListOverlay).at(1).prop('open')).toEqual(true);
    expect(list.find(SingleListOverlay).at(1).prop('clearSelectionOnClose')).toEqual(true);
    expect(list.find(SingleListOverlay).at(1).prop('disabledIds')).toEqual(undefined);
    expect(list.find(SingleListOverlay).at(1).prop('resourceKey')).toEqual('test');
    expect(list.find(SingleListOverlay).at(1).prop('listKey')).toEqual('test_list');

    list.find(SingleListOverlay).at(1).prop('onClose')();
    return requestCopyPromise.then(() => {
        list.update();
        expect(list.find(SingleListOverlay).at(1).prop('open')).toEqual(false);

        expect(listStore.copy).not.toBeCalled();
    });
});

test('ListStore should copy item when onRequestItemCopy callback is called and overlay is confirmed', () => {
    const copyPromise = Promise.resolve({id: 9});

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.copy.mockReturnValue(copyPromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const requestCopyPromise = list.find('TableAdapter').prop('onRequestItemCopy')(5);
    list.update();
    expect(list.find(SingleListOverlay).at(1).prop('open')).toEqual(true);
    expect(list.find(SingleListOverlay).at(1).prop('clearSelectionOnClose')).toEqual(true);

    list.find(SingleListOverlay).at(1).prop('onConfirm')({id: 8});
    return requestCopyPromise.then(() => {
        expect(listStore.copy).toBeCalledWith(5, 8);

        return copyPromise.then(() => {
            list.update();
            expect(list.find(SingleListOverlay).at(1).prop('open')).toEqual(false);
        });
    });
});

test('SingleListOverlay should disappear when onRequestItemMove callback is called and overlay is closed', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test_list', 'list_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = shallow(<List adapters={['table']} store={listStore} />);

    const requestMovePromise = list.find('TableAdapter').prop('onRequestItemMove')(5);
    list.update();
    expect(list.find(SingleListOverlay).at(0).prop('open')).toEqual(true);
    expect(list.find(SingleListOverlay).at(0).prop('disabledIds')).toEqual([5]);
    expect(list.find(SingleListOverlay).at(0).prop('resourceKey')).toEqual('test');
    expect(list.find(SingleListOverlay).at(0).prop('listKey')).toEqual('test_list');

    list.find(SingleListOverlay).at(0).prop('onClose')();

    return requestMovePromise.then(() => {
        list.update();
        expect(list.find(SingleListOverlay).at(0).prop('open')).toEqual(false);

        expect(listStore.move).not.toBeCalled();
    });
});

test('ListStore should move item when onRequestItemMove callback is called and overlay is confirmed', () => {
    const movePromise = Promise.resolve();

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.move.mockReturnValue(movePromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const requestMovePromise = list.find('TableAdapter').prop('onRequestItemMove')(5);
    list.update();
    expect(list.find(SingleListOverlay).at(0).prop('open')).toEqual(true);

    list.find(SingleListOverlay).at(0).prop('onConfirm')({id: 8});
    return requestMovePromise.then(() => {
        expect(listStore.move).toBeCalledWith(5, 8);

        return movePromise.then(() => {
            list.update();
            expect(list.find(SingleListOverlay).at(0).prop('open')).toEqual(false);
        });
    });
});

test('Delete warning should disappear when deleting selection was requested and overlay is cancelled', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    listStore.selections.push({}, {});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = shallow(<List adapters={['table']} store={listStore} />);

    list.instance().requestSelectionDelete();
    list.update();
    expect(list.find('Dialog').at(0).prop('open')).toEqual(true);
    expect(translate).toHaveBeenCalledWith('sulu_admin.delete_selection_warning_text', {count: 2});

    list.find('Dialog').at(0).prop('onCancel')();
    list.update();
    expect(list.find('Dialog').at(0).prop('open')).toEqual(false);
});

test('ListStore should delete selections when deleting selection was requested and overlay is confirmed', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    listStore.selections.push({}, {}, {});
    const deleteSelectionPromise = Promise.resolve();
    // $FlowFixMe
    listStore.deleteSelection.mockReturnValue(deleteSelectionPromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = shallow(<List adapters={['table']} store={listStore} />);

    list.instance().requestSelectionDelete();
    list.update();
    expect(list.find('Dialog').at(0).prop('open')).toEqual(true);
    expect(translate).toHaveBeenCalledWith('sulu_admin.delete_selection_warning_text', {count: 3});

    list.find('Dialog').at(0).prop('onConfirm')();

    expect(listStore.deleteSelection).toBeCalledWith();

    return deleteSelectionPromise.then(() => {
        list.update();
        expect(list.find('Dialog').at(0).prop('open')).toEqual(false);
    });
});

test('Delete warning should disappear when onRequestItemDelete callback is called and overlay is cancelled', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = shallow(<List adapters={['table']} store={listStore} />);

    const requestDeletePromise = list.find('TableAdapter').prop('onRequestItemDelete')(5);
    list.update();
    expect(list.find('Dialog').at(1).prop('open')).toEqual(true);

    list.find('Dialog').at(1).prop('onCancel')();
    return requestDeletePromise.then(() => {
        list.update();
        expect(list.find('Dialog').at(1).prop('open')).toEqual(false);

        expect(listStore.delete).not.toBeCalled();
    });
});

test('ListStore should delete item when onRequestItemDelete callback is called and overlay is confirmed', () => {
    const deletePromise = Promise.resolve();

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.delete.mockReturnValue(deletePromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const requestDeletePromise = list.find('TableAdapter').prop('onRequestItemDelete')(5);
    list.update();
    expect(list.find('Dialog').at(1).prop('open')).toEqual(true);

    list.find('Dialog').at(1).prop('onConfirm')();
    return requestDeletePromise.then(() => {
        expect(listStore.delete).toBeCalledWith(5);

        return deletePromise.then(() => {
            list.update();
            expect(list.find('Dialog').at(1).prop('open')).toEqual(false);
        });
    });
});

test('Order warning should just disappear when onRequestItemOrder callback is called and overlay is cancelled', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const requestOrderPromise = list.find('TableAdapter').prop('onRequestItemOrder')(5);
    list.update();
    expect(list.find('Dialog').at(2).prop('open')).toEqual(true);

    list.find('Dialog').at(2).prop('onCancel')();

    return requestOrderPromise.then(() => {
        list.update();
        expect(list.find('Dialog').at(2).prop('open')).toEqual(false);

        expect(listStore.order).not.toBeCalled();
    });
});

test('ListStore should order item when onRequestItemOrder callback is called and overlay is confirmed', () => {
    const orderPromise = Promise.resolve();

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.order.mockReturnValue(orderPromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = mount(<List adapters={['table']} store={listStore} />);

    const requestOrderPromise = list.find('TableAdapter').prop('onRequestItemOrder')(5, 8);
    list.update();
    expect(list.find('Dialog').at(2).prop('open')).toEqual(true);
    list.find('Dialog').at(2).prop('onConfirm')();

    return requestOrderPromise.then(() => {
        expect(listStore.order).toBeCalledWith(5, 8);

        return orderPromise.then(() => {
            list.update();
            expect(list.find('Dialog').at(2).prop('open')).toEqual(false);
        });
    });
});
