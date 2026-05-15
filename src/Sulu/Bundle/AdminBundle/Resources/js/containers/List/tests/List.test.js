// @flow
/* eslint-disable max-len, testing-library/prefer-explicit-assert */
import React from 'react';
import {act, render as rtlRender, screen} from '@testing-library/react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import Dialog from '../../../components/Dialog';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';
import {translate} from '../../../utils/Translator';
import userStore from '../../../stores/userStore';
import DeleteDependantResourcesDialog from '../../DeleteDependantResourcesDialog';
import DeleteReferencedResourceDialog from '../../DeleteReferencedResourceDialog';
import SingleListOverlay from '../../SingleListOverlay';
import List from '../List';
import AdapterSwitch from '../AdapterSwitch';
import FieldFilter from '../FieldFilter';
import Search from '../Search';
import ListStore from '../stores/ListStore';
import listAdapterRegistry from '../registries/listAdapterRegistry';
import listFieldFilterTypeRegistry from '../registries/listFieldFilterTypeRegistry';
import listFieldTransformerRegistry from '../registries/listFieldTransformerRegistry';
import AbstractAdapter from '../adapters/AbstractAdapter';
import TableAdapter from '../adapters/TableAdapter';
import FolderAdapter from '../adapters/FolderAdapter';
import StringFieldTransformer from '../fieldTransformers/StringFieldTransformer';
import ColumnListAdapter from '../adapters/ColumnListAdapter';

let mockStructureStrategyData;
let mockStructureStrategyVisibleItems;

const getMockCallPropsFromEnd = (mockComponent: Function, indexFromEnd: number = 0) => {
    const calls = (mockComponent: any).mock.calls;
    return calls[calls.length - 1 - indexFromEnd] && calls[calls.length - 1 - indexFromEnd][0];
};

const getMoveOverlayProps = () => getMockCallPropsFromEnd((SingleListOverlay: any), 1);
const getCopyOverlayProps = () => getMockCallPropsFromEnd((SingleListOverlay: any));
const getDialogPropsByTitle = (title: string, indexFromEnd: number = 0) => {
    const matchingCalls = (Dialog: any).mock.calls.filter((args) => args[0].title === title);
    const call = matchingCalls[matchingCalls.length - 1 - indexFromEnd];
    return call && call[0];
};
const getDeleteSelectionDialogProps = () => getDialogPropsByTitle('sulu_admin.delete_warning_title', 1);
const getDeleteItemDialogProps = () => getDialogPropsByTitle('sulu_admin.delete_warning_title');
const getMovePermissionDialogProps = () => getDialogPropsByTitle('sulu_security.move_permission_title');
const getOrderDialogProps = () => getDialogPropsByTitle('sulu_admin.order_warning_title');
const getDeleteReferencedResourceDialogProps = () => getLatestMockProps((DeleteReferencedResourceDialog: any));
const getDeleteDependantResourcesDialogProps = () => getLatestMockProps((DeleteDependantResourcesDialog: any));

const renderList = (element: React$Element<any>): any => {
    const view = rtlRender(element);

    return {
        ...view,
        unmount: () => view.unmount(),
        rerenderList: () => {
            view.rerender(element);
        },
    };
};

const ListWithSelectionDeleteButton = ({allowConflictDeletion = true, ...props}: any) => {
    let list;
    const setList = (listInstance) => {
        list = listInstance;
    };
    const handleSelectionDeleteClick = () => {
        if (!list) {
            throw new Error('Expected List ref to be set');
        }

        list.requestSelectionDelete(allowConflictDeletion);
    };

    return (
        <>
            <button
                // eslint-disable-next-line react/jsx-no-bind
                onClick={handleSelectionDeleteClick}
                type="button"
            >
                delete selection
            </button>
            {/* eslint-disable-next-line react/jsx-no-bind */}
            <List {...props} ref={setList} />
        </>
    );
};

const renderListWithSelectionDeleteButton = (props: Object, allowConflictDeletion?: boolean = true) => (
    renderList(<ListWithSelectionDeleteButton {...props} allowConflictDeletion={allowConflictDeletion} />)
);

const requestSelectionDelete = () => {
    act(() => {
        screen.getByRole('button', {name: 'delete selection'}).click();
    });
};

const renderOnce = (element: React$Element<any>) => {
    const view = rtlRender(element);
    const html = view.container.firstChild;
    view.unmount();
    return html;
};

jest.mock('../../../stores/userStore', () => ({
    setPersistentSetting: jest.fn(),
    getPersistentSetting: jest.fn(),
}));

beforeEach(() => {
    userStore.getPersistentSetting.mockReturnValue(undefined);
});

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
        this.userSettingsKey = userSettingsKey;
        this.observableOptions = observableOptions;
        this.options = options;
        this.metadataOptions = metadataOptions;
        this.filterableFields = {};
        this.filterOptions = {
            get: jest.fn().mockReturnValue({}),
        };

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
            visibleItems: mockStructureStrategyVisibleItems,
        };
        this.data = this.structureStrategy.data;
        this.visibleItems = this.structureStrategy.visibleItems;
        this.search = jest.fn();
        this.filter = jest.fn();
        this.move = jest.fn();
        this.copy = jest.fn();

        mockExtendObservable(this, {
            copying: false,
            ordering: false,
        });
    });
});

jest.mock('../registries/listAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
    has: jest.fn(),
}));

jest.mock('../registries/listFieldFilterTypeRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../registries/listFieldTransformerRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

jest.mock('../../../components/Dialog', () => jest.fn(() => null));
jest.mock('../../DeleteDependantResourcesDialog', () => jest.fn(() => {
    const React = require('react');
    return React.createElement('div', {'data-testid': 'delete-dependant-resources-dialog'});
}));
jest.mock('../../DeleteReferencedResourceDialog', () => jest.fn(() => {
    const React = require('react');
    return React.createElement('div', {'data-testid': 'delete-referenced-resource-dialog'});
}));
jest.mock('../../SingleListOverlay', () => jest.fn(() => null));
jest.mock('../AdapterSwitch', () => jest.fn(() => null));
jest.mock('../FieldFilter', () => jest.fn(() => null));
jest.mock('../Search', () => jest.fn(() => null));

jest.mock('../adapters/TableAdapter', () => {
    const TableAdapter: any = jest.fn(() => null);
    TableAdapter.LoadingStrategy = class {};
    TableAdapter.StructureStrategy = class {};
    TableAdapter.hasColumnOptions = true;
    TableAdapter.icon = 'su-align-justify';
    TableAdapter.paginatable = true;
    TableAdapter.searchable = true;

    return TableAdapter;
});

jest.mock('../adapters/FolderAdapter', () => {
    const FolderAdapter: any = jest.fn(() => null);
    FolderAdapter.LoadingStrategy = class {};
    FolderAdapter.StructureStrategy = class {};
    FolderAdapter.hasColumnOptions = false;
    FolderAdapter.icon = 'su-folder';
    FolderAdapter.paginatable = true;
    FolderAdapter.searchable = true;

    return FolderAdapter;
});

jest.mock('../adapters/ColumnListAdapter', () => {
    const ColumnListAdapter: any = jest.fn(() => null);
    ColumnListAdapter.LoadingStrategy = class {};
    ColumnListAdapter.StructureStrategy = class {};
    ColumnListAdapter.hasColumnOptions = false;
    ColumnListAdapter.icon = 'su-columns';
    ColumnListAdapter.paginatable = false;
    ColumnListAdapter.searchable = false;

    return ColumnListAdapter;
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

const TestAdapter: any = jest.fn(() => <div>Test Adapter</div>);
TestAdapter.LoadingStrategy = LoadingStrategy;
TestAdapter.StructureStrategy = StructureStrategy;
TestAdapter.hasColumnOptions = false;
TestAdapter.icon = 'su-th-large';
TestAdapter.paginatable = true;
TestAdapter.searchable = true;

beforeEach(() => {
    jest.clearAllMocks();
    mockStructureStrategyData = [];
    TestAdapter.hasColumnOptions = false;
    TestAdapter.paginatable = true;
    TestAdapter.searchable = true;
    listAdapterRegistry.has.mockReturnValue(true);
    listAdapterRegistry.get.mockReturnValue(TestAdapter);

    listFieldTransformerRegistry.get.mockReturnValue(new StringFieldTransformer());
});

test('Render Loader instead of Adapter if nothing was loaded yet', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.loading = true;
    listStore.pageCount = 0;

    expect(renderOnce(<List adapters={['table']} store={listStore} />)).toMatchSnapshot();
});

test('Render permission hint if permissions are missing', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.loading = true;
    listStore.pageCount = 0;
    listStore.forbidden = true;

    expect(renderOnce(<List adapters={['table']} store={listStore} />)).toMatchSnapshot();
});

test('Do not render Loader instead of Adapter if no page count is given', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.loading = true;
    listStore.pageCount = undefined;

    expect(renderOnce(<List adapters={['table']} store={listStore} />)).toMatchSnapshot();
});

test('Render toolbar with with search field and actions', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    const listActions = [
        {
            disabled: true,
            icon: 'su-upload',
            label: translate('sulu_admin.upload'),
            onClick: jest.fn(),
        },
        {
            disabled: false,
            icon: 'su-refresh',
            label: translate('sulu_admin.refresh'),
            onClick: jest.fn(),
        },
    ];

    expect(renderOnce(<List actions={listActions} adapters={['table']} store={listStore} />)).toMatchSnapshot();
});

test('Render toolbar with given toolbar class', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.loading = true;
    listStore.pageCount = undefined;

    const list = renderList(<List adapters={['table']} store={listStore} toolbarClassName="test-class" />);

    const toolbar = list.container.querySelector('.toolbar');
    expect(toolbar && toolbar.className).toEqual(expect.stringContaining('test-class'));
});

test('Do not render toolbar if list is not searchable and adapter has column options but List deactivated them', () => {
    TestAdapter.hasColumnOptions = true;

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    const list = renderList(<List adapters={['table']} searchable={false} showColumnOptions={false} store={listStore} />);

    expect(list.container.querySelector('.toolbar')).toEqual(null);
});

test('Do not render toolbar if list is not searchable and adapter has no column options', () => {
    TestAdapter.hasColumnOptions = false;

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    const list = renderList(<List adapters={['table']} searchable={false} store={listStore} />);

    expect(list.container.querySelector('.toolbar')).toEqual(null);
});

test('Render toolbar if list is not searchable but adapter has column options', () => {
    TestAdapter.hasColumnOptions = true;

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    const list = renderList(<List adapters={['table']} searchable={false} store={listStore} />);

    expect(list.container.querySelector('.toolbar')).not.toEqual(null);
});

test('Render toolbar with multiple adapters if list is not searchable and adapter has no column options', () => {
    TestAdapter.hasColumnOptions = false;

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    const list = renderList(<List adapters={['table', 'other-table']} searchable={false} store={listStore} />);

    expect(list.container.querySelector('.toolbar')).not.toEqual(null);
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

    renderList(<List adapters={['table']} onItemClick={editClickSpy} store={listStore} />);

    expect(Search).toBeCalled();

    const tableAdapterProps = getLatestMockProps((TableAdapter: any));

    expect(tableAdapterProps.actions).toEqual(undefined);
    expect(tableAdapterProps.data).toEqual([{'id': 1, 'title': 'value'}]);
    expect(tableAdapterProps.active).toEqual(3);
    expect(tableAdapterProps.activeItems).toBe(listStore.activeItems);
    expect(tableAdapterProps.selections).toEqual([1, 3]);
    expect(tableAdapterProps.schema).toEqual({
        title: {
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
    });
    expect(tableAdapterProps.onItemClick).toBe(editClickSpy);
    expect(tableAdapterProps.onItemSelectionChange).toBeInstanceOf(Function);
    expect(tableAdapterProps.onAllSelectionChange).toBeInstanceOf(Function);
});

test('Render TableAdapter with itemActions', () => {
    const actionsProvider = () => [
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

    // eslint-disable-next-line react/jsx-no-bind
    renderList(<List adapters={['table']} itemActionsProvider={actionsProvider} store={listStore} />);

    expect(getLatestMockProps((TableAdapter: any)).itemActionsProvider).toEqual(actionsProvider);
});

test('Render the adapter in non-selectable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(<List adapters={['test']} selectable={false} store={listStore} />);

    expect(getLatestMockProps(TestAdapter).onItemSelectionChange).toEqual(undefined);
    expect(getLatestMockProps(TestAdapter).onAllSelectionChange).toEqual(undefined);
});

test('Render the adapter in non-deletable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(<List adapters={['test']} deletable={false} store={listStore} />);

    expect(getLatestMockProps(TestAdapter).onRequestItemDelete).toEqual(undefined);
});

test('Render the adapter in non-movable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(<List adapters={['test']} movable={false} store={listStore} />);

    expect(getLatestMockProps(TestAdapter).onRequestItemMove).toEqual(undefined);
});

test('Render the adapter in non-copyable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(<List adapters={['test']} copyable={false} store={listStore} />);

    expect(getLatestMockProps(TestAdapter).onRequestItemCopy).toEqual(undefined);
});

test('Render the adapter in non-orderable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(<List adapters={['test']} orderable={false} store={listStore} />);

    expect(getLatestMockProps(TestAdapter).onRequestOrderItem).toEqual(undefined);
});

test('Render the adapter in non-searchable mode', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    expect(
        renderOnce(<List adapters={['test']} header={<h1>Title</h1>} searchable={false} store={listStore} />)
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
        renderOnce(<List adapters={['test']} header={<h1>Title</h1>} searchable={true} store={listStore} />)
    ).toMatchSnapshot();
});

test('Render the adapter in disabled state', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    expect(
        renderOnce(<List adapters={['test']} disabled={true} header={<h1>Title</h1>} store={listStore} />)
    ).toMatchSnapshot();
});

test('Render the adapter with filters', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    // $FlowFixMe
    listStore.filterableFields = {
        title: {
            filterType: 'text',
            label: 'Title',
        },
        created: {
            filterType: 'datetime',
            label: 'Created at',
        },
        changed: {
            label: 'Changed at',
        },
    };

    listFieldFilterTypeRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'datetime':
            case 'text':
                return class {
                    destroy = jest.fn();
                    getFormNode = jest.fn();
                    getValueNode = jest.fn();
                    setValue = jest.fn();
                };
        }
    });

    listStore.filterOptions.get.mockReturnValue({
        title: undefined,
        created: undefined,
    });

    const {container} = renderList(
        <List adapters={['test']} disabled={true} header={<h1>Title</h1>} store={listStore} />
    );
    expect(container.firstChild).toMatchSnapshot();
});

test('Render the adapter with filters but filterable disabled', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    // $FlowFixMe
    listStore.filterableFields = {
        title: {
            filterType: 'text',
            label: 'Title',
        },
        created: {
            filterType: 'datetime',
            label: 'Created at',
        },
        changed: {
            label: 'Changed at',
        },
    };

    listFieldFilterTypeRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'datetime':
            case 'text':
                return class {
                    destroy = jest.fn();
                    getFormNode = jest.fn();
                    getValueNode = jest.fn();
                    setValue = jest.fn();
                };
        }
    });

    listStore.filterOptions.get.mockReturnValue({
        title: undefined,
        created: undefined,
    });

    const {container} = renderList(
        <List adapters={['test']} disabled={true} filterable={false} header={<h1>Title</h1>} store={listStore} />
    );
    expect(container.firstChild).toMatchSnapshot();
});

test('Pass the given disabledIds to the adapter', () => {
    const disabledIds = [1, 3];
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(<List adapters={['test']} disabledIds={disabledIds} store={listStore} />);

    expect(getLatestMockProps(TestAdapter).disabledIds).toEqual(disabledIds);
});

test('Pass given disabledIds and ids of items that fulfill given itemDisabledCondition to the adapter', () => {
    mockStructureStrategyVisibleItems = [
        {
            id: 1,
            status: 'active',
        },
        {
            id: 2,
            status: 'active',
        },
        {
            id: 3,
            status: 'active',
        },
        {
            id: 4,
            status: 'inactive',
        },
    ];

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(
        <List
            adapters={['test']}
            disabledIds={[1, 3]}
            itemDisabledCondition='status == "inactive"'
            store={listStore}
        />
    );

    expect(getLatestMockProps(TestAdapter).disabledIds).toEqual([1, 3, 4]);
});

test('Pass adapterOptions to the adapter', () => {
    const adapterOptions = {table: {show_header: true}, test: {skin: 'light'}};
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(<List adapterOptions={adapterOptions} adapters={['test']} store={listStore} />);

    expect(getLatestMockProps(TestAdapter).adapterOptions).toEqual({skin: 'light'});
});

test('Pass undefined as adapterOptions to the adapter if no options for current adapter are passed', () => {
    const adapterOptions = {table: {skin: 'flat'}};
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(<List adapterOptions={adapterOptions} adapters={['test']} store={listStore} />);

    expect(getLatestMockProps(TestAdapter).adapterOptions).toEqual(undefined);
});

test('Call activate on store if item is activated', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(<List adapters={['test']} store={listStore} />);

    getLatestMockProps(TestAdapter).onItemActivate(5);

    expect(listStore.activate).toBeCalledWith(5);
});

test('Do not call activate if item is activated but disabled and allowActivateForDisabledItems is false', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(
        <List adapters={['test']} allowActivateForDisabledItems={false} disabledIds={[5]} store={listStore} />
    );

    getLatestMockProps(TestAdapter).onItemActivate(5);
    getLatestMockProps(TestAdapter).onItemActivate(7);

    expect(listStore.activate).not.toBeCalledWith(5);
    expect(listStore.activate).toBeCalledWith(7);
});

test('Do not call activate if item fulfills itemDisabledCondition and allowActivateForDisabledItems is false', () => {
    mockStructureStrategyVisibleItems = [
        {
            id: 1,
            status: 'active',
        },
        {
            id: 2,
            status: 'inactive',
        },
    ];

    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(
        <List
            adapters={['test']}
            allowActivateForDisabledItems={false}
            itemDisabledCondition='status == "inactive"'
            store={listStore}
        />
    );

    getLatestMockProps(TestAdapter).onItemActivate(1);
    getLatestMockProps(TestAdapter).onItemActivate(2);

    expect(listStore.activate).toBeCalledWith(1);
    expect(listStore.activate).not.toBeCalledWith(2);
});

test('Call deactivate on store if item is deactivated', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(<List adapters={['test']} store={listStore} />);

    getLatestMockProps(TestAdapter).onItemDeactivate(5);

    expect(listStore.deactivate).toBeCalledWith(5);
});

test('Pass sortColumn and sortOrder to adapter', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    listStore.sortColumn.get.mockReturnValue('title');
    listStore.sortOrder.get.mockReturnValue('asc');
    renderList(<List adapters={['test']} store={listStore} />);

    expect(getLatestMockProps(TestAdapter)).toEqual(expect.objectContaining({
        sortColumn: 'title',
        sortOrder: 'asc',
    }));
});

test('Pass options to adapter', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    const listAdapterOptions = {test: 'value'};
    listAdapterRegistry.getOptions.mockReturnValue(listAdapterOptions);

    renderList(<List adapters={['test']} store={listStore} />);

    expect(getLatestMockProps(TestAdapter)).toEqual(expect.objectContaining({
        options: listAdapterOptions,
    }));
});

test('Pass correct options and metadataOptions to SingleListOverlays', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)}, {}, {id: 1});

    renderList(<List adapters={['test']} store={listStore} />);

    expect(getMoveOverlayProps().reloadOnOpen).toEqual(true);
    expect(getCopyOverlayProps().reloadOnOpen).toEqual(true);

    expect(getMoveOverlayProps().metadataOptions).toEqual({id: 1});
    expect(getCopyOverlayProps().metadataOptions).toEqual({id: 1});
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

    renderList(<List adapters={['table']} store={listStore} />);
    const tableAdapterProps = getLatestMockProps((TableAdapter: any));

    tableAdapterProps.onItemSelectionChange(1, true);
    expect(listStore.findById).toBeCalledWith(1);
    expect(listStore.select).toBeCalledWith({id: 1});
    tableAdapterProps.onItemSelectionChange(2, true);
    expect(listStore.findById).toBeCalledWith(2);
    expect(listStore.select).toBeCalledWith({id: 2});
    tableAdapterProps.onItemSelectionChange(1, false);
    expect(listStore.findById).toBeCalledWith(1);
    expect(listStore.deselect).toBeCalledWith({id: 1});
});

test('Selecting and unselecting all visible items should update store', () => {
    mockStructureStrategyVisibleItems = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(<List adapters={['table']} store={listStore} />);
    const tableAdapterProps = getLatestMockProps((TableAdapter: any));

    tableAdapterProps.onAllSelectionChange(true);
    expect(listStore.select).toBeCalledWith({id: 1});
    expect(listStore.select).toBeCalledWith({id: 2});
    expect(listStore.select).toBeCalledWith({id: 3});

    tableAdapterProps.onAllSelectionChange(false);
    expect(listStore.deselect).toBeCalledWith({id: 1});
    expect(listStore.deselect).toBeCalledWith({id: 2});
    expect(listStore.deselect).toBeCalledWith({id: 3});
});

test('Should select and unselect all non-disabled items when adapter fires onAllSelectionChange callback', () => {
    mockStructureStrategyVisibleItems = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    renderList(<List adapters={['table']} disabledIds={[2]} store={listStore} />);
    const tableAdapterProps = getLatestMockProps((TableAdapter: any));

    tableAdapterProps.onAllSelectionChange(true);
    expect(listStore.select).toBeCalledWith({id: 1});
    expect(listStore.select).not.toBeCalledWith({id: 2});
    expect(listStore.select).toBeCalledWith({id: 3});

    tableAdapterProps.onAllSelectionChange(false);
    expect(listStore.deselect).toBeCalledWith({id: 1});
    expect(listStore.deselect).not.toBeCalledWith({id: 2});
    expect(listStore.deselect).toBeCalledWith({id: 3});
});

test('Clicking a header cell should sort the table', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    renderList(<List adapters={['table']} store={listStore} />);

    getLatestMockProps((TableAdapter: any)).onSort('title', 'asc');
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
    renderList(<List adapters={['table']} store={listStore} />);

    getLatestMockProps((Search: any)).onSearch('search-value');
    expect(listStore.search).toBeCalledWith('search-value');
});

test('Trigger a filter should call filter on the store', () => {
    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    // $FlowFixMe
    listStore.filterableFields = {
        title: {
            label: 'Title',
        },
    };

    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];

    renderList(<List adapters={['table']} store={listStore} />);

    getLatestMockProps((FieldFilter: any)).onChange({title: undefined});
    expect(listStore.filter).toBeCalledWith({title: undefined});
});

test('Should start with adapter from user settings', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    listAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TableAdapter;
            case 'folder':
                return FolderAdapter;
        }
    });

    userStore.getPersistentSetting.mockReturnValue('folder');

    renderList(<List adapters={['table', 'folder']} store={listStore} />);

    expect(userStore.getPersistentSetting).toBeCalledWith('sulu_admin.list.test.list_test.adapter');
    expect(AdapterSwitch).toBeCalledTimes(1);
    expect(FolderAdapter).toBeCalledTimes(1);
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
    const list = renderList(<List adapters={['table', 'folder']} store={listStore} />);

    expect(AdapterSwitch).toBeCalledTimes(1);
    expect(TableAdapter).toBeCalledTimes(1);

    act(() => {
        getLatestMockProps((AdapterSwitch: any)).onAdapterChange('folder');
    });
    list.rerenderList();
    expect(TableAdapter).toBeCalledTimes(1);
    expect(FolderAdapter).toBeCalledTimes(1);
    expect(userStore.setPersistentSetting).toBeCalledWith('sulu_admin.list.test.list_test.adapter', 'folder');
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
    renderList(<List adapters={['table', 'folder']} store={listStore} />);
    expect(listStore.updateLoadingStrategy).toBeCalledWith(expect.any(TableAdapter.LoadingStrategy));
    expect(listStore.updateStructureStrategy).toBeCalledWith(expect.any(TableAdapter.StructureStrategy));
});

test('Correct LoadingStrategyOptions should be passed to the LoadingStrategy if paginated prop is set', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    TableAdapter.LoadingStrategy = (jest.fn(): any);

    listAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TableAdapter;
            case 'folder':
                return FolderAdapter;
        }
    });
    renderList(<List adapters={['table', 'folder']} paginated={true} store={listStore} />);
    expect(TableAdapter.LoadingStrategy).toBeCalledWith({paginated: true});
});

test('Correct LoadingStrategyOptions should not be passed to the LoadingStrategy if adapter is not paginatable', () => {
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});

    ColumnListAdapter.LoadingStrategy = (jest.fn(): any);

    listAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'column_list':
                return ColumnListAdapter;
        }
    });
    renderList(<List adapters={['column_list']} paginated={true} store={listStore} />);
    expect(ColumnListAdapter.LoadingStrategy).toBeCalledWith({paginated: false});
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
    renderList(<List adapters={['test']} store={listStore} />);

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
    const list = renderList(<List adapters={['table']} store={listStore} />);

    let requestCopyPromise: Promise<any> = Promise.resolve();
    act(() => {
        requestCopyPromise = getLatestMockProps((TableAdapter: any)).onRequestItemCopy(5);
    });
    list.rerenderList();
    expect(getCopyOverlayProps().open).toEqual(true);
    expect(getCopyOverlayProps().clearSelectionOnClose).toEqual(true);
    expect(getCopyOverlayProps().disabledIds).toEqual(undefined);
    expect(getCopyOverlayProps().resourceKey).toEqual('test');
    expect(getCopyOverlayProps().listKey).toEqual('test_list');

    act(() => {
        getCopyOverlayProps().onClose();
    });
    return requestCopyPromise.then(() => {
        list.rerenderList();
        expect(getCopyOverlayProps().open).toEqual(false);

        expect(listStore.copy).not.toBeCalled();
    });
});

test('ListStore should copy item when onRequestItemCopy callback is called and overlay is confirmed', () => {
    const copyPromise = Promise.resolve({id: 9});
    const copyFinishedSpy = jest.fn();

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.copy.mockReturnValue(copyPromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = renderList(<List adapters={['table']} onCopyFinished={copyFinishedSpy} store={listStore} />);

    let requestCopyPromise: Promise<any> = Promise.resolve();
    act(() => {
        requestCopyPromise = getLatestMockProps((TableAdapter: any)).onRequestItemCopy(5);
    });
    list.rerenderList();
    expect(getCopyOverlayProps().open).toEqual(true);
    expect(getCopyOverlayProps().clearSelectionOnClose).toEqual(true);

    act(() => {
        getCopyOverlayProps().onConfirm({id: 8});
    });
    return requestCopyPromise.then(() => {
        expect(listStore.copy).toBeCalledWith(5, 8, copyFinishedSpy);

        return copyPromise.then(() => {
            list.rerenderList();
            expect(getCopyOverlayProps().open).toEqual(false);
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
    const list = renderList(<List adapters={['table']} store={listStore} />);

    let requestMovePromise: Promise<any> = Promise.resolve();
    act(() => {
        requestMovePromise = getLatestMockProps((TableAdapter: any)).onRequestItemMove(5);
    });
    list.rerenderList();
    expect(getMoveOverlayProps().open).toEqual(true);
    expect(getMoveOverlayProps().disabledIds).toEqual([5]);
    expect(getMoveOverlayProps().resourceKey).toEqual('test');
    expect(getMoveOverlayProps().listKey).toEqual('test_list');

    act(() => {
        getMoveOverlayProps().onClose();
    });

    return requestMovePromise.then(() => {
        list.rerenderList();
        expect(getMoveOverlayProps().open).toEqual(false);

        expect(listStore.move).not.toBeCalled();
    });
});

test('ListStore should move item when onRequestItemMove callback is called and overlay is confirmed', () => {
    const movePromise = Promise.resolve();

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.move.mockReturnValue(movePromise);
    listStore.findById.mockReturnValue({_hasPermissions: false});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = renderList(<List adapters={['table']} store={listStore} />);

    let requestMovePromise: Promise<any> = Promise.resolve();
    act(() => {
        requestMovePromise = getLatestMockProps((TableAdapter: any)).onRequestItemMove(5);
    });
    list.rerenderList();
    expect(getMoveOverlayProps().open).toEqual(true);

    act(() => {
        getMoveOverlayProps().onConfirm({id: 8});
    });
    return requestMovePromise.then(() => {
        expect(listStore.move).toBeCalledWith(5, 8);

        return movePromise.then(() => {
            list.rerenderList();
            expect(getMoveOverlayProps().open).toEqual(false);
        });
    });
});

test('ListStore should move item when onRequestItemMove callback is called and permission dialog is confirmed', () => {
    const movePromise = Promise.resolve();

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.move.mockReturnValue(movePromise);
    listStore.findById.mockReturnValue({_hasPermissions: true});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = renderList(<List adapters={['table']} store={listStore} />);

    let requestMovePromise: Promise<any> = Promise.resolve();
    act(() => {
        requestMovePromise = getLatestMockProps((TableAdapter: any)).onRequestItemMove(5);
    });
    list.rerenderList();
    expect(getMoveOverlayProps().open).toEqual(true);

    act(() => {
        getMoveOverlayProps().onConfirm({id: 8});
    });

    list.rerenderList();
    expect(getMovePermissionDialogProps().open).toEqual(true);
    act(() => {
        getMovePermissionDialogProps().onConfirm();
    });
    return requestMovePromise.then(() => {
        expect(listStore.move).toBeCalledWith(5, 8);

        return movePromise.then(() => {
            list.rerenderList();
            expect(getMoveOverlayProps().open).toEqual(false);
        });
    });
});

test('ListStore should not move when onRequestItemMove callback is called and permission dialog is denied', () => {
    const movePromise = Promise.resolve();

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.move.mockReturnValue(movePromise);
    listStore.findById.mockReturnValue({_hasPermissions: false});
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = renderList(<List adapters={['table']} store={listStore} />);

    act(() => {
        getLatestMockProps((TableAdapter: any)).onRequestItemMove(5);
    });
    list.rerenderList();
    expect(getMoveOverlayProps().open).toEqual(true);

    act(() => {
        getMoveOverlayProps().onConfirm({id: 8, _hasPermissions: true});
    });

    list.rerenderList();
    expect(getMovePermissionDialogProps().open).toEqual(true);
    act(() => {
        getMovePermissionDialogProps().onCancel();
    });
    list.rerenderList();
    expect(getMovePermissionDialogProps().open).toEqual(false);
    expect(getMoveOverlayProps().open).toEqual(true);
    expect(listStore.move).not.toBeCalledWith(5, 8);
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
    const list = renderListWithSelectionDeleteButton({adapters: ['table'], store: listStore});

    requestSelectionDelete();
    list.rerenderList();
    expect(getDeleteSelectionDialogProps().open).toEqual(true);
    expect(translate).toHaveBeenCalledWith('sulu_admin.delete_selection_warning_text', {count: 2});

    act(() => {
        getDeleteSelectionDialogProps().onCancel();
    });
    list.rerenderList();
    expect(getDeleteSelectionDialogProps().open).toEqual(false);
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
    const list = renderListWithSelectionDeleteButton({adapters: ['table'], store: listStore});

    requestSelectionDelete();
    list.rerenderList();
    expect(getDeleteSelectionDialogProps().open).toEqual(true);
    expect(translate).toHaveBeenCalledWith('sulu_admin.delete_selection_warning_text', {count: 3});

    act(() => {
        getDeleteSelectionDialogProps().onConfirm();
    });

    expect(listStore.deleteSelection).toBeCalledWith();

    return deleteSelectionPromise.then(() => {
        list.rerenderList();
        expect(getDeleteSelectionDialogProps().open).toEqual(false);
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
    const list = renderList(<List adapters={['table']} store={listStore} />);

    let requestDeletePromise: Promise<any> = Promise.resolve();
    act(() => {
        requestDeletePromise = getLatestMockProps((TableAdapter: any)).onRequestItemDelete(5);
    });
    list.rerenderList();
    expect(getDeleteItemDialogProps().open).toEqual(true);

    act(() => {
        getDeleteItemDialogProps().onCancel();
    });
    return requestDeletePromise.then(() => {
        list.rerenderList();
        expect(getDeleteItemDialogProps().open).toEqual(false);

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
    const list = renderList(<List adapters={['table']} store={listStore} />);

    let requestDeletePromise: Promise<any> = Promise.resolve();
    act(() => {
        requestDeletePromise = getLatestMockProps((TableAdapter: any)).onRequestItemDelete(5);
    });
    list.rerenderList();
    expect(getDeleteItemDialogProps().open).toEqual(true);

    act(() => {
        getDeleteItemDialogProps().onConfirm();
    });
    return requestDeletePromise.then(() => {
        expect(listStore.delete).toBeCalledWith(5);

        return deletePromise.then(() => {
            list.rerenderList();
            expect(getDeleteItemDialogProps().open).toEqual(false);
            expect(getMovePermissionDialogProps().open).toEqual(false);
        });
    });
});

test('ListStore should delete linked item when onRequestItemDelete callback is is confirmed twice', (done) => {
    const jsonDeletePromise = Promise.resolve({
        code: 1106,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        referencingResources: [
            {id: 7, resourceKey: 'pages', title: 'Item 1'},
            {id: 8, resourceKey: 'pages', title: 'Item 2'},
        ],
        referencingResourcesCount: 2,
    });

    const deletePromise = Promise.reject({
        json: jest.fn().mockReturnValue(jsonDeletePromise),
        status: 409,
    });
    deletePromise.catch(() => {});

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.delete.mockReturnValueOnce(deletePromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = renderList(<List adapters={['table']} store={listStore} />);

    let requestDeletePromise: Promise<any> = Promise.resolve();
    act(() => {
        requestDeletePromise = getLatestMockProps((TableAdapter: any)).onRequestItemDelete(5);
    });
    list.rerenderList();
    expect(getDeleteItemDialogProps().open).toEqual(true);

    act(() => {
        getDeleteItemDialogProps().onConfirm();
    });
    requestDeletePromise.then(() => {
        expect(listStore.delete).toBeCalledWith(5);

        setTimeout(() => {
            list.rerenderList();
            expect(getDeleteItemDialogProps().open).toEqual(false);
            expect(screen.getByTestId('delete-referenced-resource-dialog')).toBeInTheDocument();

            const deletePromise = Promise.resolve();
            // $FlowFixMe
            listStore.delete.mockReturnValueOnce(deletePromise);
            act(() => {
                getDeleteReferencedResourceDialogProps().onConfirm();
            });

            setTimeout(() => {
                expect(listStore.delete).toBeCalledWith(5, {force: true});
                list.rerenderList();
                expect(getDeleteItemDialogProps().open).toEqual(false);
                expect(screen.queryByTestId('delete-referenced-resource-dialog')).not.toBeInTheDocument();
                done();
            });
        });
    });
});

test('ListStore should not delete linked item when onRequestItemDelete callback is is confirmed once', (done) => {
    const jsonDeletePromise = Promise.resolve({
        code: 1106,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        referencingResources: [
            {id: 7, resourceKey: 'pages', title: 'Item 1'},
            {id: 8, resourceKey: 'pages', title: 'Item 2'},
        ],
        referencingResourcesCount: 2,
    });

    const deletePromise = Promise.reject({
        json: jest.fn().mockReturnValue(jsonDeletePromise),
        status: 409,
    });
    deletePromise.catch(() => {});

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.delete.mockReturnValueOnce(deletePromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = renderList(<List adapters={['table']} store={listStore} />);

    let requestDeletePromise: Promise<any> = Promise.resolve();
    act(() => {
        requestDeletePromise = getLatestMockProps((TableAdapter: any)).onRequestItemDelete(5);
    });
    list.rerenderList();
    expect(getDeleteItemDialogProps().open).toEqual(true);

    act(() => {
        getDeleteItemDialogProps().onConfirm();
    });
    requestDeletePromise.then(() => {
        expect(listStore.delete).toBeCalledWith(5);
        // $FlowFixMe
        listStore.delete.mockReset();

        setTimeout(() => {
            list.rerenderList();
            expect(getDeleteItemDialogProps().open).toEqual(false);
            expect(screen.getByTestId('delete-referenced-resource-dialog')).toBeInTheDocument();
            expect(getDeleteReferencedResourceDialogProps().referencingResourcesData).toEqual({
                resource: {
                    id: 5,
                    resourceKey: 'pages',
                },
                referencingResources: [
                    {id: 7, resourceKey: 'pages', title: 'Item 1'},
                    {id: 8, resourceKey: 'pages', title: 'Item 2'},
                ],
                referencingResourcesCount: 2,
            });

            const deletePromise = Promise.resolve();
            // $FlowFixMe
            listStore.delete.mockReturnValueOnce(deletePromise);
            act(() => {
                getDeleteReferencedResourceDialogProps().onCancel();
            });

            setTimeout(() => {
                expect(listStore.delete).not.toBeCalled();
                list.rerenderList();
                expect(getDeleteItemDialogProps().open).toEqual(false);
                expect(screen.queryByTestId('delete-referenced-resource-dialog')).not.toBeInTheDocument();
                done();
            });
        });
    });
});

test('ListStore should delete linked item when called with allowConflictDeletion value of true', (done) => {
    const jsonDeletePromise = Promise.resolve({
        code: 1106,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        referencingResources: [
            {id: 7, resourceKey: 'pages', title: 'Item 1'},
            {id: 8, resourceKey: 'pages', title: 'Item 2'},
        ],
        referencingResourcesCount: 2,
    });

    const deletePromise = Promise.reject({
        json: jest.fn().mockReturnValue(jsonDeletePromise),
        status: 409,
    });
    deletePromise.catch(() => {});

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.deleteSelection.mockReturnValueOnce(deletePromise);
    listStore.selectionIds.push(5);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = renderListWithSelectionDeleteButton({adapters: ['table'], store: listStore}, true);

    requestSelectionDelete();
    list.rerenderList();
    expect(getDeleteSelectionDialogProps().open).toEqual(true);

    act(() => {
        getDeleteSelectionDialogProps().onConfirm();
    });

    setTimeout(() => {
        list.rerenderList();
        expect(getDeleteSelectionDialogProps().open).toEqual(false);
        expect(getDeleteItemDialogProps().open).toEqual(false);
        expect(screen.getByTestId('delete-referenced-resource-dialog')).toBeInTheDocument();
        expect(getDeleteReferencedResourceDialogProps().allowDeletion).toEqual(true);

        const deletePromise = Promise.resolve();
        // $FlowFixMe
        listStore.delete.mockReturnValueOnce(deletePromise);
        act(() => {
            getDeleteReferencedResourceDialogProps().onConfirm();
        });

        setTimeout(() => {
            expect(listStore.delete).toBeCalledWith(5, {force: true});
            list.rerenderList();
            expect(getDeleteSelectionDialogProps().open).toEqual(false);
            expect(getDeleteItemDialogProps().open).toEqual(false);
            expect(screen.queryByTestId('delete-referenced-resource-dialog')).not.toBeInTheDocument();
            done();
        });
    });
});

test('ListStore should not delete linked item when called with allowConflictDeletion value of false', (done) => {
    const jsonDeletePromise = Promise.resolve({
        code: 1106,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        referencingResources: [
            {id: 7, resourceKey: 'pages', title: 'Item 1'},
            {id: 8, resourceKey: 'pages', title: 'Item 2'},
        ],
        referencingResourcesCount: 2,
    });

    const deletePromise = Promise.reject({
        json: jest.fn().mockReturnValue(jsonDeletePromise),
        status: 409,
    });
    deletePromise.catch(() => {});

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.deleteSelection.mockReturnValueOnce(deletePromise);
    listStore.selectionIds.push(5);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = renderListWithSelectionDeleteButton({adapters: ['table'], store: listStore}, false);

    requestSelectionDelete();
    list.rerenderList();
    expect(getDeleteSelectionDialogProps().open).toEqual(true);

    act(() => {
        getDeleteSelectionDialogProps().onConfirm();
    });

    setTimeout(() => {
        list.rerenderList();
        expect(getDeleteSelectionDialogProps().open).toEqual(false);
        expect(getDeleteItemDialogProps().open).toEqual(false);
        expect(screen.getByTestId('delete-referenced-resource-dialog')).toBeInTheDocument();
        expect(getDeleteReferencedResourceDialogProps().allowDeletion).toEqual(false);

        act(() => {
            getDeleteReferencedResourceDialogProps().onCancel();
        });

        setTimeout(() => {
            expect(listStore.delete).not.toBeCalledWith(5, {force: true});
            list.rerenderList();
            expect(getDeleteSelectionDialogProps().open).toEqual(false);
            expect(getDeleteItemDialogProps().open).toEqual(false);
            expect(screen.queryByTestId('delete-referenced-resource-dialog')).not.toBeInTheDocument();
            done();
        });
    });
});

test('ListStore should delete item with dependants when onFinish callback called', (done) => {
    const jsonDeletePromise = Promise.resolve({
        code: 1105,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        dependantResourceBatches: [
            {id: 7, resourceKey: 'pages'},
            {id: 8, resourceKey: 'pages'},
        ],
        dependantResourcesCount: 2,
    });

    const deletePromise = Promise.reject({
        json: jest.fn().mockReturnValue(jsonDeletePromise),
        status: 409,
    });
    deletePromise.catch(() => {});

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.delete.mockReturnValueOnce(deletePromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = renderList(<List adapters={['table']} store={listStore} />);

    let requestDeletePromise: Promise<any> = Promise.resolve();
    act(() => {
        requestDeletePromise = getLatestMockProps((TableAdapter: any)).onRequestItemDelete(5);
    });
    list.rerenderList();
    expect(getDeleteItemDialogProps().open).toEqual(true);

    act(() => {
        getDeleteItemDialogProps().onConfirm();
    });
    requestDeletePromise.then(() => {
        expect(listStore.delete).toHaveBeenCalledWith(5);

        setTimeout(() => {
            list.rerenderList();
            expect(getDeleteItemDialogProps().open).toEqual(false);
            expect(screen.getByTestId('delete-dependant-resources-dialog')).toBeInTheDocument();
            expect(getDeleteDependantResourcesDialogProps().dependantResourcesData).toEqual({
                dependantResourceBatches: [
                    {id: 7, resourceKey: 'pages'},
                    {id: 8, resourceKey: 'pages'},
                ],
                dependantResourcesCount: 2,
                detail: undefined,
                title: undefined,
            });

            const deletePromise = Promise.resolve();
            // $FlowFixMe
            listStore.delete.mockReturnValueOnce(deletePromise);
            act(() => {
                getDeleteDependantResourcesDialogProps().onFinish();
            });

            setTimeout(() => {
                expect(listStore.delete).toHaveBeenCalledWith(5);
                expect(listStore.delete).toHaveBeenCalledTimes(2);
                list.rerenderList();
                expect(getDeleteItemDialogProps().open).toEqual(false);
                expect(screen.queryByTestId('delete-dependant-resources-dialog')).not.toBeInTheDocument();
                done();
            });
        });
    });
});

test('ListStore should not delete item with dependants when onCancel callback called', (done) => {
    const jsonDeletePromise = Promise.resolve({
        code: 1105,
        resource: {
            id: 5,
            resourceKey: 'pages',
        },
        dependantResourceBatches: [
            {id: 7, resourceKey: 'pages'},
            {id: 8, resourceKey: 'pages'},
        ],
        dependantResourcesCount: 2,
    });

    const deletePromise = Promise.reject({
        json: jest.fn().mockReturnValue(jsonDeletePromise),
        status: 409,
    });
    deletePromise.catch(() => {});

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    // $FlowFixMe
    listStore.delete.mockReturnValueOnce(deletePromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = renderList(<List adapters={['table']} store={listStore} />);

    let requestDeletePromise: Promise<any> = Promise.resolve();
    act(() => {
        requestDeletePromise = getLatestMockProps((TableAdapter: any)).onRequestItemDelete(5);
    });
    list.rerenderList();
    expect(getDeleteItemDialogProps().open).toEqual(true);

    act(() => {
        getDeleteItemDialogProps().onConfirm();
    });
    requestDeletePromise.then(() => {
        expect(listStore.delete).toHaveBeenCalledWith(5);

        setTimeout(() => {
            list.rerenderList();
            expect(getDeleteItemDialogProps().open).toEqual(false);
            expect(screen.getByTestId('delete-dependant-resources-dialog')).toBeInTheDocument();

            const deletePromise = Promise.resolve();
            // $FlowFixMe
            listStore.delete.mockReturnValueOnce(deletePromise);
            act(() => {
                getDeleteDependantResourcesDialogProps().onCancel();
            });

            setTimeout(() => {
                expect(listStore.delete).toHaveBeenCalledTimes(1);
                list.rerenderList();
                expect(getDeleteItemDialogProps().open).toEqual(false);
                expect(screen.queryByTestId('delete-dependant-resources-dialog')).not.toBeInTheDocument();
                done();
            });
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
    const list = renderList(<List adapters={['table']} store={listStore} />);

    let requestOrderPromise: Promise<any> = Promise.resolve();
    act(() => {
        requestOrderPromise = getLatestMockProps((TableAdapter: any)).onRequestItemOrder(5);
    });
    list.rerenderList();
    expect(getOrderDialogProps().open).toEqual(true);

    act(() => {
        getOrderDialogProps().onCancel();
    });

    return requestOrderPromise.then(() => {
        list.rerenderList();
        expect(getOrderDialogProps().open).toEqual(false);

        expect(listStore.order).not.toBeCalled();
    });
});

test('ListStore should order item when onRequestItemOrder callback is called and overlay is confirmed', () => {
    const orderPromise = Promise.resolve();

    listAdapterRegistry.get.mockReturnValue(TableAdapter);
    const listStore = new ListStore('test', 'test', 'list_test', {page: observable.box(1)});
    listStore.order.mockReturnValue(orderPromise);
    mockStructureStrategyData = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const list = renderList(<List adapters={['table']} store={listStore} />);

    let requestOrderPromise: Promise<any> = Promise.resolve();
    act(() => {
        requestOrderPromise = getLatestMockProps((TableAdapter: any)).onRequestItemOrder(5, 8);
    });
    list.rerenderList();
    expect(getOrderDialogProps().open).toEqual(true);
    act(() => {
        getOrderDialogProps().onConfirm();
    });

    return requestOrderPromise.then(() => {
        expect(listStore.order).toBeCalledWith(5, 8);

        return orderPromise.then(() => {
            list.rerenderList();
            expect(getOrderDialogProps().open).toEqual(false);
        });
    });
});
