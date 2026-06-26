// @flow
/* eslint-disable react/jsx-no-bind, testing-library/prefer-explicit-assert */
import React from 'react';
import {act, render, screen, waitFor, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {translate} from '../../../utils/Translator';
import userStore from '../../../stores/userStore';
import List from '../List';
import ListStore from '../stores/ListStore';
import listAdapterRegistry from '../registries/listAdapterRegistry';
import listFieldFilterTypeRegistry from '../registries/listFieldFilterTypeRegistry';
import listFieldTransformerRegistry from '../registries/listFieldTransformerRegistry';
import AbstractAdapter from '../adapters/AbstractAdapter';
import StringFieldTransformer from '../fieldTransformers/StringFieldTransformer';

let mockStructureStrategyData;
let mockStructureStrategyVisibleItems;

jest.mock('../../../components/Dialog', () => {
    const React = require('react');

    return jest.fn((props) => (
        <div data-open={props.open ? 'true' : 'false'} data-testid={'dialog-' + props.title}>
            {props.open &&
                <div>
                    <span>{props.title}</span>
                    <div>{props.children}</div>
                    <button disabled={props.confirmDisabled} onClick={props.onConfirm} type="button">
                        {props.confirmText}
                    </button>
                    {props.onCancel && props.cancelText &&
                        <button onClick={props.onCancel} type="button">
                            {props.cancelText}
                        </button>
                    }
                </div>
            }
        </div>
    ));
});

jest.mock('../../SingleListOverlay', () => {
    const React = require('react');

    return jest.fn((props) => {
        const overlayType = props.disabledIds ? 'move' : 'copy';

        return (
            <div
                data-clear-selection-on-close={props.clearSelectionOnClose ? 'true' : 'false'}
                data-disabled-ids={JSON.stringify(props.disabledIds)}
                data-list-key={props.listKey}
                data-metadata-options={JSON.stringify(props.metadataOptions)}
                data-open={props.open ? 'true' : 'false'}
                data-reload-on-open={props.reloadOnOpen ? 'true' : 'false'}
                data-resource-key={props.resourceKey}
                data-testid={overlayType + '-overlay'}
            >
                {props.open &&
                    <div>
                        <button onClick={props.onClose} type="button">
                            {'close-' + overlayType}
                        </button>
                        <button onClick={() => props.onConfirm({id: 8})} type="button">
                            {'confirm-' + overlayType}
                        </button>
                        <button onClick={() => props.onConfirm({id: 8, _hasPermissions: true})} type="button">
                            {'confirm-restricted-' + overlayType}
                        </button>
                    </div>
                }
            </div>
        );
    });
});

jest.mock('../../DeleteReferencedResourceDialog', () => {
    const React = require('react');

    return jest.fn((props) => (
        <div data-allow-deletion={props.allowDeletion ? 'true' : 'false'} data-testid="delete-referenced-dialog">
            {props.referencingResourcesData.referencingResources.map((item, index) => (
                <li key={index}>{item.title}</li>
            ))}
            <button
                onClick={() => props.allowDeletion ? props.onConfirm() : props.onCancel()}
                type="button"
            >
                confirm-referenced-delete
            </button>
            <button onClick={props.onCancel} type="button">
                cancel-referenced-delete
            </button>
        </div>
    ));
});

jest.mock('../../DeleteDependantResourcesDialog', () => {
    const React = require('react');

    return jest.fn((props) => (
        <div data-testid="delete-dependant-dialog">
            <button onClick={props.onFinish} type="button">
                finish-dependant-delete
            </button>
            <button onClick={props.onCancel} type="button">
                cancel-dependant-delete
            </button>
        </div>
    ));
});

jest.mock('../../../stores/userStore', () => ({
    setPersistentSetting: jest.fn(),
    getPersistentSetting: jest.fn(),
}));

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

jest.mock('../../../utils/Translator');

class LoadingStrategy {
    options: Object;

    destroy = jest.fn();
    initialize = jest.fn();
    reset = jest.fn();

    constructor(options: Object = {}) {
        this.options = options;
    }

    load(): any {
        return Promise.resolve();
    }

    setStructureStrategy() {}
}

class StructureStrategy {
    data: Array<Object> = [];
    visibleItems: Array<Object> = [];

    addItem = jest.fn();
    clear = jest.fn();
    findById = jest.fn();
    order = jest.fn();
    remove = jest.fn();
}

class TestAdapter extends AbstractAdapter {
    static LoadingStrategy = (LoadingStrategy: any);

    static StructureStrategy = StructureStrategy;

    static icon = 'su-th-large';

    render() {
        const {
            active,
            activeItems,
            adapterOptions,
            data,
            disabledIds,
            itemActionsProvider,
            limit,
            onAllSelectionChange,
            onItemActivate,
            onItemClick,
            onItemDeactivate,
            onItemSelectionChange,
            onLimitChange,
            onPageChange,
            onRequestItemCopy,
            onRequestItemDelete,
            onRequestItemMove,
            onRequestItemOrder,
            onSort,
            options,
            page,
            pageCount,
            paginated,
            schema,
            selections,
            sortColumn,
            sortOrder,
        } = this.props;

        return (
            <div
                data-active={String(active)}
                data-active-items={JSON.stringify(activeItems)}
                data-adapter-options={JSON.stringify(adapterOptions)}
                data-data={JSON.stringify(data)}
                data-disabled-ids={JSON.stringify(disabledIds)}
                data-has-all-selection-callback={onAllSelectionChange ? 'true' : 'false'}
                data-has-copy-callback={onRequestItemCopy ? 'true' : 'false'}
                data-has-delete-callback={onRequestItemDelete ? 'true' : 'false'}
                data-has-item-actions-provider={itemActionsProvider ? 'true' : 'false'}
                data-has-move-callback={onRequestItemMove ? 'true' : 'false'}
                data-has-order-callback={onRequestItemOrder ? 'true' : 'false'}
                data-has-selection-callback={onItemSelectionChange ? 'true' : 'false'}
                data-limit={String(limit)}
                data-options={JSON.stringify(options)}
                data-page={String(page)}
                data-page-count={String(pageCount)}
                data-paginated={String(paginated)}
                data-schema={JSON.stringify(schema)}
                data-selections={JSON.stringify(selections)}
                data-sort-column={String(sortColumn)}
                data-sort-order={String(sortOrder)}
                data-testid="test-adapter"
            >
                <span>Test Adapter</span>
                {data.map((item) => (
                    <div data-testid={'item-' + item.id} key={item.id}>
                        {item.title || item.id}
                    </div>
                ))}
                <button onClick={() => onItemActivate && onItemActivate(5)} type="button">activate-5</button>
                <button onClick={() => onItemActivate && onItemActivate(7)} type="button">activate-7</button>
                <button onClick={() => onItemActivate && onItemActivate(1)} type="button">activate-1</button>
                <button onClick={() => onItemActivate && onItemActivate(2)} type="button">activate-2</button>
                <button onClick={() => onItemDeactivate && onItemDeactivate(5)} type="button">deactivate-5</button>
                <button onClick={() => onItemClick && onItemClick(5)} type="button">click-5</button>
                <button onClick={() => onItemSelectionChange && onItemSelectionChange(1, true)} type="button">
                    select-1
                </button>
                <button onClick={() => onItemSelectionChange && onItemSelectionChange(2, true)} type="button">
                    select-2
                </button>
                <button onClick={() => onItemSelectionChange && onItemSelectionChange(1, false)} type="button">
                    deselect-1
                </button>
                <button onClick={() => onAllSelectionChange && onAllSelectionChange(true)} type="button">
                    select-all
                </button>
                <button onClick={() => onAllSelectionChange && onAllSelectionChange(false)} type="button">
                    deselect-all
                </button>
                <button onClick={() => onSort('title', 'asc')} type="button">sort-title</button>
                <button onClick={() => onPageChange(3)} type="button">page-3</button>
                <button onClick={() => onLimitChange(20)} type="button">limit-20</button>
                <button onClick={() => onRequestItemCopy && onRequestItemCopy(5)} type="button">
                    request-copy
                </button>
                <button onClick={() => onRequestItemMove && onRequestItemMove(5)} type="button">
                    request-move
                </button>
                <button onClick={() => onRequestItemDelete && onRequestItemDelete(5)} type="button">
                    request-delete
                </button>
                <button onClick={() => onRequestItemOrder && onRequestItemOrder(5, 8)} type="button">
                    request-order
                </button>
            </div>
        );
    }
}

class NoColumnOptionsAdapter extends TestAdapter {
    static hasColumnOptions = false;
}

class ColumnOptionsAdapter extends TestAdapter {
    static hasColumnOptions = true;
}

class NonSearchableAdapter extends TestAdapter {
    static searchable = false;
}

class FolderTestAdapter extends TestAdapter {
    static icon = 'su-folder';

    render() {
        return <div data-testid="folder-adapter">Folder Adapter</div>;
    }
}

function createListStore(
    resourceKey: string = 'test',
    listKey: string = 'test',
    userSettingsKey: string = 'list_test',
    metadataOptions: Object = {}
): any {
    return new ListStore(resourceKey, listKey, userSettingsKey, {page: observable.box(1)}, {}, metadataOptions);
}

function createListRef(): any {
    return React.createRef();
}

function getAdapter(): HTMLElement {
    return screen.getByTestId('test-adapter');
}

function getButtonsByIcon(icon: string): Array<HTMLButtonElement> {
    const buttons: Array<HTMLButtonElement> = [];

    screen.queryAllByLabelText(icon).forEach((iconElement) => {
        const button = iconElement.closest('button');

        if (button instanceof HTMLButtonElement) {
            buttons.push(button);
        }
    });

    return buttons;
}

function getToolbar(): HTMLElement {
    const toolbar = document.querySelector('.toolbar');

    if (!(toolbar instanceof HTMLElement)) {
        throw new Error('Toolbar was not rendered.');
    }

    return toolbar;
}

function queryToolbar(): ?Element {
    return document.querySelector('.toolbar');
}

function getOpenDialog(title: string): HTMLElement {
    const dialog = screen.getAllByTestId('dialog-' + title)
        .find((dialog) => dialog.getAttribute('data-open') === 'true');

    if (!(dialog instanceof HTMLElement)) {
        throw new Error('Open dialog "' + title + '" was not rendered.');
    }

    return dialog;
}

function createReferencedResponse() {
    return {
        json: jest.fn().mockReturnValue(Promise.resolve({
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
        })),
        status: 409,
    };
}

function createDependantResponse() {
    return {
        json: jest.fn().mockReturnValue(Promise.resolve({
            code: 1105,
            resource: {
                id: 5,
                resourceKey: 'pages',
            },
            dependantResourceBatches: [
                [{id: 7, resourceKey: 'pages'}],
                [{id: 8, resourceKey: 'pages'}],
            ],
            dependantResourcesCount: 2,
            detail: 'Delete dependant detail',
            title: 'Delete dependant title',
        })),
        status: 409,
    };
}

async function clickOpenDialogButton(title: string, name: string) {
    await userEvent.click(within(getOpenDialog(title)).getByRole('button', {name}));
}

beforeEach(() => {
    jest.clearAllMocks();

    mockStructureStrategyData = [];
    mockStructureStrategyVisibleItems = [];

    userStore.getPersistentSetting.mockReturnValue(undefined);
    listAdapterRegistry.has.mockReturnValue(true);
    listAdapterRegistry.get.mockReturnValue(TestAdapter);
    listAdapterRegistry.getOptions.mockReturnValue({});
    listFieldTransformerRegistry.get.mockReturnValue(new StringFieldTransformer());
});

test('Render Loader instead of Adapter if nothing was loaded yet', () => {
    const listStore = createListStore();
    // $FlowFixMe
    listStore.loading = true;
    listStore.pageCount = 0;

    render(<List adapters={['table']} store={listStore} />);

    expect(screen.queryByText('Test Adapter')).not.toBeInTheDocument();
    expect(document.querySelector('.spinner')).toBeInTheDocument();
});

test('Render permission hint if permissions are missing', () => {
    const listStore = createListStore();
    // $FlowFixMe
    listStore.loading = true;
    listStore.pageCount = 0;
    listStore.forbidden = true;

    render(<List adapters={['table']} store={listStore} />);

    expect(screen.getByText('sulu_admin.no_permissions')).toBeInTheDocument();
});

test('Do not render Loader instead of Adapter if no page count is given', () => {
    const listStore = createListStore();
    // $FlowFixMe
    listStore.loading = true;
    listStore.pageCount = undefined;

    render(<List adapters={['table']} store={listStore} />);

    expect(screen.getByText('Test Adapter')).toBeInTheDocument();
});

test('Render toolbar with with search field and actions', () => {
    const listStore = createListStore();
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

    render(<List actions={listActions} adapters={['table']} store={listStore} />);

    expect(screen.getByLabelText('sulu_admin.list_search_placeholder')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /sulu_admin.upload/})).toBeDisabled();
    expect(screen.getByRole('button', {name: /sulu_admin.refresh/})).toBeEnabled();
});

test('Render toolbar with given toolbar class', () => {
    const listStore = createListStore();

    render(<List adapters={['table']} store={listStore} toolbarClassName="test-class" />);

    expect(getToolbar()).toHaveClass('test-class');
});

test('Do not render toolbar if list is not searchable and adapter has column options but List deactivated them', () => {
    listAdapterRegistry.get.mockReturnValue(ColumnOptionsAdapter);
    const listStore = createListStore();

    render(<List adapters={['table']} searchable={false} showColumnOptions={false} store={listStore} />);

    expect(queryToolbar()).not.toBeInTheDocument();
});

test('Do not render toolbar if list is not searchable and adapter has no column options', () => {
    listAdapterRegistry.get.mockReturnValue(NoColumnOptionsAdapter);
    const listStore = createListStore();

    render(<List adapters={['table']} searchable={false} store={listStore} />);

    expect(queryToolbar()).not.toBeInTheDocument();
});

test('Render toolbar if list is not searchable but adapter has column options', () => {
    listAdapterRegistry.get.mockReturnValue(ColumnOptionsAdapter);
    const listStore = createListStore();

    render(<List adapters={['table']} searchable={false} store={listStore} />);

    expect(getToolbar()).toBeInTheDocument();
});

test('Render toolbar with multiple adapters if list is not searchable and adapter has no column options', () => {
    listAdapterRegistry.get.mockReturnValue(NoColumnOptionsAdapter);
    const listStore = createListStore();

    render(<List adapters={['table', 'other-table']} searchable={false} store={listStore} />);

    expect(getToolbar()).toBeInTheDocument();
});

test('Render TableAdapter with correct values', () => {
    mockStructureStrategyData = [
        {
            title: 'value',
            id: 1,
        },
    ];

    const listStore = createListStore();
    listStore.active.get.mockReturnValue(3);
    listStore.selectionIds.push(1, 3);
    listStore.activeItems.push(2, 4);
    const editClickSpy = jest.fn();

    render(<List adapters={['table']} onItemClick={editClickSpy} store={listStore} />);

    const adapterData = getAdapter().getAttribute('data-data');

    if (typeof adapterData !== 'string') {
        throw new Error('Adapter data attribute was not rendered.');
    }

    expect(JSON.parse(adapterData)).toEqual([{id: 1, title: 'value'}]);
    expect(getAdapter()).toHaveAttribute('data-active', '3');
    expect(getAdapter()).toHaveAttribute('data-active-items', JSON.stringify([2, 4]));
    expect(getAdapter()).toHaveAttribute('data-selections', JSON.stringify([1, 3]));
    expect(getAdapter()).toHaveAttribute('data-schema', JSON.stringify({
        title: {
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
    }));
    expect(getAdapter()).toHaveAttribute('data-has-selection-callback', 'true');
    expect(getAdapter()).toHaveAttribute('data-has-all-selection-callback', 'true');
});

test('Render TableAdapter with itemActions', () => {
    const actionsProvider = () => [
        {
            icon: 'su-process',
            onClick: undefined,
        },
    ];

    const listStore = createListStore();

    render(<List adapters={['table']} itemActionsProvider={actionsProvider} store={listStore} />);

    expect(getAdapter()).toHaveAttribute('data-has-item-actions-provider', 'true');
});

test('Render the adapter in non-selectable mode', () => {
    const listStore = createListStore();

    render(<List adapters={['test']} selectable={false} store={listStore} />);

    expect(getAdapter()).toHaveAttribute('data-has-selection-callback', 'false');
    expect(getAdapter()).toHaveAttribute('data-has-all-selection-callback', 'false');
});

test('Render the adapter in non-deletable mode', () => {
    const listStore = createListStore();

    render(<List adapters={['test']} deletable={false} store={listStore} />);

    expect(getAdapter()).toHaveAttribute('data-has-delete-callback', 'false');
});

test('Render the adapter in non-movable mode', () => {
    const listStore = createListStore();

    render(<List adapters={['test']} movable={false} store={listStore} />);

    expect(getAdapter()).toHaveAttribute('data-has-move-callback', 'false');
});

test('Render the adapter in non-copyable mode', () => {
    const listStore = createListStore();

    render(<List adapters={['test']} copyable={false} store={listStore} />);

    expect(getAdapter()).toHaveAttribute('data-has-copy-callback', 'false');
});

test('Render the adapter in non-orderable mode', () => {
    const listStore = createListStore();

    render(<List adapters={['test']} orderable={false} store={listStore} />);

    expect(getAdapter()).toHaveAttribute('data-has-order-callback', 'false');
});

test('Render the adapter in non-searchable mode', () => {
    const listStore = createListStore();

    render(<List adapters={['test']} header={<h1>Title</h1>} searchable={false} store={listStore} />);

    expect(screen.getByRole('heading', {name: 'Title'})).toBeInTheDocument();
    expect(screen.queryByLabelText('sulu_admin.list_search_placeholder')).not.toBeInTheDocument();
    expect(screen.getByText('Test Adapter')).toBeInTheDocument();
});

test('Render the adapter in non-searchable mode if searchable is set to true but adapter does not support it', () => {
    listAdapterRegistry.get.mockReturnValue(NonSearchableAdapter);
    const listStore = createListStore();

    render(<List adapters={['test']} header={<h1>Title</h1>} searchable={true} store={listStore} />);

    expect(screen.getByRole('heading', {name: 'Title'})).toBeInTheDocument();
    expect(screen.queryByLabelText('sulu_admin.list_search_placeholder')).not.toBeInTheDocument();
    expect(screen.getByText('Test Adapter')).toBeInTheDocument();
});

test('Render the adapter in disabled state', () => {
    const listStore = createListStore();

    render(<List adapters={['test']} disabled={true} header={<h1>Title</h1>} store={listStore} />);

    expect(getAdapter().closest('.list')).toHaveClass('disabled');
});

test('Render the adapter with filters', () => {
    const listStore = createListStore();

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

    render(<List adapters={['test']} disabled={true} header={<h1>Title</h1>} store={listStore} />);

    expect(getButtonsByIcon('su-filter')).toHaveLength(1);
});

test('Render the adapter with filters but filterable disabled', () => {
    const listStore = createListStore();

    // $FlowFixMe
    listStore.filterableFields = {
        title: {
            filterType: 'text',
            label: 'Title',
        },
    };

    render(
        <List adapters={['test']} disabled={true} filterable={false} header={<h1>Title</h1>} store={listStore} />
    );

    expect(getButtonsByIcon('su-filter')).toHaveLength(0);
});

test('Pass the given disabledIds to the adapter', () => {
    const disabledIds = [1, 3];
    const listStore = createListStore();

    render(<List adapters={['test']} disabledIds={disabledIds} store={listStore} />);

    expect(getAdapter()).toHaveAttribute('data-disabled-ids', JSON.stringify(disabledIds));
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

    const listStore = createListStore();

    render(
        <List
            adapters={['test']}
            disabledIds={[1, 3]}
            itemDisabledCondition='status == "inactive"'
            store={listStore}
        />
    );

    expect(getAdapter()).toHaveAttribute('data-disabled-ids', JSON.stringify([1, 3, 4]));
});

test('Pass adapterOptions to the adapter', () => {
    const adapterOptions = {table: {show_header: true}, test: {skin: 'light'}};
    const listStore = createListStore();

    render(<List adapterOptions={adapterOptions} adapters={['test']} store={listStore} />);

    expect(getAdapter()).toHaveAttribute('data-adapter-options', JSON.stringify({skin: 'light'}));
});

test('Pass undefined as adapterOptions to the adapter if no options for current adapter are passed', () => {
    const adapterOptions = {table: {skin: 'flat'}};
    const listStore = createListStore();

    render(<List adapterOptions={adapterOptions} adapters={['test']} store={listStore} />);

    expect(getAdapter()).not.toHaveAttribute('data-adapter-options');
});

test('Call activate on store if item is activated', async() => {
    const user = userEvent.setup();
    const listStore = createListStore();

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'activate-5'}));

    expect(listStore.activate).toHaveBeenCalledWith(5);
});

test('Do not call activate if item is activated but disabled and allowActivateForDisabledItems is false', async() => {
    const user = userEvent.setup();
    const listStore = createListStore();

    render(<List adapters={['test']} allowActivateForDisabledItems={false} disabledIds={[5]} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'activate-5'}));
    await user.click(screen.getByRole('button', {name: 'activate-7'}));

    expect(listStore.activate).not.toHaveBeenCalledWith(5);
    expect(listStore.activate).toHaveBeenCalledWith(7);
});

test(
    'Do not call activate if item fulfills itemDisabledCondition and '
    + 'allowActivateForDisabledItems is false',
    async() => {
        const user = userEvent.setup();
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

        const listStore = createListStore();

        render(
            <List
                adapters={['test']}
                allowActivateForDisabledItems={false}
                itemDisabledCondition='status == "inactive"'
                store={listStore}
            />
        );

        await user.click(screen.getByRole('button', {name: 'activate-1'}));
        await user.click(screen.getByRole('button', {name: 'activate-2'}));

        expect(listStore.activate).toHaveBeenCalledWith(1);
        expect(listStore.activate).not.toHaveBeenCalledWith(2);
    }
);

test('Call deactivate on store if item is deactivated', async() => {
    const user = userEvent.setup();
    const listStore = createListStore();

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'deactivate-5'}));

    expect(listStore.deactivate).toHaveBeenCalledWith(5);
});

test('Pass sortColumn and sortOrder to adapter', () => {
    const listStore = createListStore();
    listStore.sortColumn.get.mockReturnValue('title');
    listStore.sortOrder.get.mockReturnValue('asc');

    render(<List adapters={['test']} store={listStore} />);

    expect(getAdapter()).toHaveAttribute('data-sort-column', 'title');
    expect(getAdapter()).toHaveAttribute('data-sort-order', 'asc');
});

test('Pass options to adapter', () => {
    const listStore = createListStore();
    const listAdapterOptions = {test: 'value'};
    listAdapterRegistry.getOptions.mockReturnValue(listAdapterOptions);

    render(<List adapters={['test']} store={listStore} />);

    expect(getAdapter()).toHaveAttribute('data-options', JSON.stringify(listAdapterOptions));
});

test('Pass correct options and metadataOptions to SingleListOverlays', () => {
    const listStore = createListStore('test', 'test', 'list_test', {id: 1});

    render(<List adapters={['test']} store={listStore} />);

    expect(screen.getByTestId('move-overlay')).toHaveAttribute('data-reload-on-open', 'true');
    expect(screen.getByTestId('copy-overlay')).toHaveAttribute('data-reload-on-open', 'true');
    expect(screen.getByTestId('move-overlay')).toHaveAttribute('data-metadata-options', JSON.stringify({id: 1}));
    expect(screen.getByTestId('copy-overlay')).toHaveAttribute('data-metadata-options', JSON.stringify({id: 1}));
});

test('Selecting and deselecting items should update store', async() => {
    const user = userEvent.setup();
    const listStore = createListStore();
    listStore.findById.mockReturnValueOnce({id: 1}).mockReturnValueOnce({id: 2}).mockReturnValueOnce({id: 1});

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'select-1'}));
    expect(listStore.findById).toHaveBeenCalledWith(1);
    expect(listStore.select).toHaveBeenCalledWith({id: 1});

    await user.click(screen.getByRole('button', {name: 'select-2'}));
    expect(listStore.findById).toHaveBeenCalledWith(2);
    expect(listStore.select).toHaveBeenCalledWith({id: 2});

    await user.click(screen.getByRole('button', {name: 'deselect-1'}));
    expect(listStore.findById).toHaveBeenCalledWith(1);
    expect(listStore.deselect).toHaveBeenCalledWith({id: 1});
});

test('Selecting and unselecting all visible items should update store', async() => {
    const user = userEvent.setup();
    mockStructureStrategyVisibleItems = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];

    const listStore = createListStore();

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'select-all'}));
    expect(listStore.select).toHaveBeenCalledWith({id: 1});
    expect(listStore.select).toHaveBeenCalledWith({id: 2});
    expect(listStore.select).toHaveBeenCalledWith({id: 3});

    await user.click(screen.getByRole('button', {name: 'deselect-all'}));
    expect(listStore.deselect).toHaveBeenCalledWith({id: 1});
    expect(listStore.deselect).toHaveBeenCalledWith({id: 2});
    expect(listStore.deselect).toHaveBeenCalledWith({id: 3});
});

test('Should select and unselect all non-disabled items when adapter fires onAllSelectionChange callback', async() => {
    const user = userEvent.setup();
    mockStructureStrategyVisibleItems = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];

    const listStore = createListStore();

    render(<List adapters={['test']} disabledIds={[2]} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'select-all'}));
    expect(listStore.select).toHaveBeenCalledWith({id: 1});
    expect(listStore.select).not.toHaveBeenCalledWith({id: 2});
    expect(listStore.select).toHaveBeenCalledWith({id: 3});

    await user.click(screen.getByRole('button', {name: 'deselect-all'}));
    expect(listStore.deselect).toHaveBeenCalledWith({id: 1});
    expect(listStore.deselect).not.toHaveBeenCalledWith({id: 2});
    expect(listStore.deselect).toHaveBeenCalledWith({id: 3});
});

test('Clicking a header cell should sort the table', async() => {
    const user = userEvent.setup();
    const listStore = createListStore();

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'sort-title'}));

    expect(listStore.sort).toHaveBeenCalledWith('title', 'asc');
});

test('Trigger a search should call search on the store', async() => {
    const user = userEvent.setup();
    const listStore = createListStore();

    render(<List adapters={['test']} store={listStore} />);

    await user.type(screen.getByLabelText('sulu_admin.list_search_placeholder'), 'search-value{enter}');

    expect(listStore.search).toHaveBeenCalledWith('search-value');
});

test('Trigger a filter should call filter on the store', async() => {
    const user = userEvent.setup();
    const listStore = createListStore();

    // $FlowFixMe
    listStore.filterableFields = {
        title: {
            label: 'Title',
        },
    };

    render(<List adapters={['test']} store={listStore} />);

    await user.click(getButtonsByIcon('su-filter')[0]);
    await user.click(screen.getByRole('button', {name: 'Title'}));

    expect(listStore.filter).toHaveBeenCalledWith({title: undefined});
});

test('Should start with adapter from user settings', () => {
    const listStore = createListStore();

    listAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TestAdapter;
            case 'folder':
                return FolderTestAdapter;
        }
    });

    userStore.getPersistentSetting.mockReturnValue('folder');

    render(<List adapters={['table', 'folder']} store={listStore} />);

    expect(userStore.getPersistentSetting).toHaveBeenCalledWith('sulu_admin.list.test.list_test.adapter');
    expect(screen.getByText('Folder Adapter')).toBeInTheDocument();
    expect(getButtonsByIcon('su-folder')).toHaveLength(1);
});

test('Switching the adapter should render the correct adapter', async() => {
    const user = userEvent.setup();
    const listStore = createListStore();

    listAdapterRegistry.get.mockImplementation((adapter) => {
        switch (adapter) {
            case 'table':
                return TestAdapter;
            case 'folder':
                return FolderTestAdapter;
        }
    });

    render(<List adapters={['table', 'folder']} store={listStore} />);

    expect(screen.getByText('Test Adapter')).toBeInTheDocument();

    await user.click(getButtonsByIcon('su-folder')[0]);

    expect(screen.queryByText('Test Adapter')).not.toBeInTheDocument();
    expect(screen.getByText('Folder Adapter')).toBeInTheDocument();
    expect(userStore.setPersistentSetting).toHaveBeenCalledWith('sulu_admin.list.test.list_test.adapter', 'folder');
});

test('ListStore should be initialized correctly on init and update', () => {
    const listStore = createListStore();

    render(<List adapters={['table']} store={listStore} />);

    expect(listStore.updateLoadingStrategy).toHaveBeenCalledWith(expect.any(LoadingStrategy));
    expect(listStore.updateStructureStrategy).toHaveBeenCalledWith(expect.any(StructureStrategy));
});

test('Correct LoadingStrategyOptions should be passed to the LoadingStrategy if paginated prop is set', () => {
    const listStore = createListStore();
    const LoadingStrategyMock = jest.fn();

    class PaginatableAdapter extends TestAdapter {
        static LoadingStrategy = (LoadingStrategyMock: any);
        static paginatable = true;
    }

    listAdapterRegistry.get.mockReturnValue(PaginatableAdapter);

    render(<List adapters={['table']} paginated={true} store={listStore} />);

    expect(LoadingStrategyMock).toHaveBeenCalledWith({paginated: true});
});

test('Correct LoadingStrategyOptions should not be passed to the LoadingStrategy if adapter is not paginatable', () => {
    const listStore = createListStore();
    const LoadingStrategyMock = jest.fn();

    class NotPaginatableAdapter extends TestAdapter {
        static LoadingStrategy = (LoadingStrategyMock: any);
        static paginatable = false;
    }

    listAdapterRegistry.get.mockReturnValue(NotPaginatableAdapter);

    render(<List adapters={['column_list']} paginated={true} store={listStore} />);

    expect(LoadingStrategyMock).toHaveBeenCalledWith({paginated: false});
});

test('ListStore should be updated with current active element', () => {
    class ActivatingAdapter extends TestAdapter {
        constructor(props: *) {
            super(props);

            const {onItemActivate} = this.props;
            if (onItemActivate) {
                onItemActivate('some-uuid');
            }
        }
    }

    listAdapterRegistry.get.mockReturnValue(ActivatingAdapter);

    const listStore = createListStore();
    expect(listStore.active.get()).toBe(undefined);

    render(<List adapters={['test']} store={listStore} />);

    expect(listStore.activate).toHaveBeenCalledWith('some-uuid');
});

test('SingleListOverlay should disappear when onRequestItemCopy callback is called and overlay is closed', async() => {
    const user = userEvent.setup();
    const listStore = createListStore('test', 'test_list');

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'request-copy'}));
    expect(screen.getByTestId('copy-overlay')).toHaveAttribute('data-open', 'true');
    expect(screen.getByTestId('copy-overlay')).toHaveAttribute('data-clear-selection-on-close', 'true');
    expect(screen.getByTestId('copy-overlay')).not.toHaveAttribute('data-disabled-ids');
    expect(screen.getByTestId('copy-overlay')).toHaveAttribute('data-resource-key', 'test');
    expect(screen.getByTestId('copy-overlay')).toHaveAttribute('data-list-key', 'test_list');

    await user.click(screen.getByRole('button', {name: 'close-copy'}));

    await waitFor(() => expect(screen.getByTestId('copy-overlay')).toHaveAttribute('data-open', 'false'));
    expect(listStore.copy).not.toHaveBeenCalled();
});

test('ListStore should copy item when onRequestItemCopy callback is called and overlay is confirmed', async() => {
    const user = userEvent.setup();
    const copyPromise = Promise.resolve();
    const copyFinishedSpy = jest.fn();
    const listStore = createListStore();
    listStore.copy.mockReturnValue(copyPromise);

    render(<List adapters={['test']} onCopyFinished={copyFinishedSpy} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'request-copy'}));
    expect(screen.getByTestId('copy-overlay')).toHaveAttribute('data-open', 'true');

    await user.click(screen.getByRole('button', {name: 'confirm-copy'}));

    await waitFor(() => expect(listStore.copy).toHaveBeenCalledWith(5, 8, copyFinishedSpy));
    await waitFor(() => expect(screen.getByTestId('copy-overlay')).toHaveAttribute('data-open', 'false'));
});

test('SingleListOverlay should disappear when onRequestItemMove callback is called and overlay is closed', async() => {
    const user = userEvent.setup();
    const listStore = createListStore('test', 'test_list');

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'request-move'}));
    expect(screen.getByTestId('move-overlay')).toHaveAttribute('data-open', 'true');
    expect(screen.getByTestId('move-overlay')).toHaveAttribute('data-disabled-ids', JSON.stringify([5]));
    expect(screen.getByTestId('move-overlay')).toHaveAttribute('data-resource-key', 'test');
    expect(screen.getByTestId('move-overlay')).toHaveAttribute('data-list-key', 'test_list');

    await user.click(screen.getByRole('button', {name: 'close-move'}));

    await waitFor(() => expect(screen.getByTestId('move-overlay')).toHaveAttribute('data-open', 'false'));
    expect(listStore.move).not.toHaveBeenCalled();
});

test('ListStore should move item when onRequestItemMove callback is called and overlay is confirmed', async() => {
    const user = userEvent.setup();
    const movePromise = Promise.resolve();
    const listStore = createListStore();
    listStore.move.mockReturnValue(movePromise);
    listStore.findById.mockReturnValue({_hasPermissions: false});

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'request-move'}));
    await user.click(screen.getByRole('button', {name: 'confirm-move'}));

    await waitFor(() => expect(listStore.move).toHaveBeenCalledWith(5, 8));
    await waitFor(() => expect(screen.getByTestId('move-overlay')).toHaveAttribute('data-open', 'false'));
});

test(
    'ListStore should move item when onRequestItemMove callback is called and '
    + 'permission dialog is confirmed',
    async() => {
        const user = userEvent.setup();
        const movePromise = Promise.resolve();
        const listStore = createListStore();
        listStore.move.mockReturnValue(movePromise);
        listStore.findById.mockReturnValue({_hasPermissions: true});

        render(<List adapters={['test']} store={listStore} />);

        await user.click(screen.getByRole('button', {name: 'request-move'}));
        await user.click(screen.getByRole('button', {name: 'confirm-move'}));

        expect(getOpenDialog('sulu_security.move_permission_title')).toBeInTheDocument();

        await clickOpenDialogButton('sulu_security.move_permission_title', 'sulu_admin.confirm');

        await waitFor(() => expect(listStore.move).toHaveBeenCalledWith(5, 8));
        await waitFor(() => expect(screen.getByTestId('move-overlay')).toHaveAttribute('data-open', 'false'));
    }
);

test('ListStore should not move when onRequestItemMove callback is called and permission dialog is denied', async() => {
    const user = userEvent.setup();
    const movePromise = Promise.resolve();
    const listStore = createListStore();
    listStore.move.mockReturnValue(movePromise);
    listStore.findById.mockReturnValue({_hasPermissions: false});

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'request-move'}));
    await user.click(screen.getByRole('button', {name: 'confirm-restricted-move'}));

    expect(getOpenDialog('sulu_security.move_permission_title')).toBeInTheDocument();

    await clickOpenDialogButton('sulu_security.move_permission_title', 'sulu_admin.cancel');

    expect(listStore.move).not.toHaveBeenCalledWith(5, 8);
    expect(screen.getByTestId('move-overlay')).toHaveAttribute('data-open', 'true');
});

test('Delete warning should disappear when deleting selection was requested and overlay is cancelled', async() => {
    const listStore = createListStore();
    listStore.selections.push({}, {});
    const listRef = createListRef();

    render(<List adapters={['test']} ref={listRef} store={listStore} />);

    act(() => {
        listRef.current.requestSelectionDelete();
    });

    expect(getOpenDialog('sulu_admin.delete_warning_title')).toBeInTheDocument();
    expect(translate).toHaveBeenCalledWith('sulu_admin.delete_selection_warning_text', {count: 2});

    await clickOpenDialogButton('sulu_admin.delete_warning_title', 'sulu_admin.cancel');

    await waitFor(() => {
        expect(screen.getAllByTestId('dialog-sulu_admin.delete_warning_title')[0])
            .toHaveAttribute('data-open', 'false');
    });
});

test('ListStore should delete selections when deleting selection was requested and overlay is confirmed', async() => {
    const deleteSelectionPromise = Promise.resolve();
    const listStore = createListStore();
    listStore.selections.push({}, {}, {});
    listStore.deleteSelection.mockReturnValue(deleteSelectionPromise);
    const listRef = createListRef();

    render(<List adapters={['test']} ref={listRef} store={listStore} />);

    act(() => {
        listRef.current.requestSelectionDelete();
    });

    expect(getOpenDialog('sulu_admin.delete_warning_title')).toBeInTheDocument();
    expect(translate).toHaveBeenCalledWith('sulu_admin.delete_selection_warning_text', {count: 3});

    await clickOpenDialogButton('sulu_admin.delete_warning_title', 'sulu_admin.ok');

    expect(listStore.deleteSelection).toHaveBeenCalledWith();
    await waitFor(() => {
        expect(screen.getAllByTestId('dialog-sulu_admin.delete_warning_title')[0])
            .toHaveAttribute('data-open', 'false');
    });
});

test(
    'Delete warning should disappear when onRequestItemDelete callback is called and '
    + 'overlay is cancelled',
    async() => {
        const user = userEvent.setup();
        const listStore = createListStore();

        render(<List adapters={['test']} store={listStore} />);

        await user.click(screen.getByRole('button', {name: 'request-delete'}));
        expect(getOpenDialog('sulu_admin.delete_warning_title')).toBeInTheDocument();

        await clickOpenDialogButton('sulu_admin.delete_warning_title', 'sulu_admin.cancel');

        await waitFor(() => expect(listStore.delete).not.toHaveBeenCalled());
    }
);

test('ListStore should delete item when onRequestItemDelete callback is called and overlay is confirmed', async() => {
    const user = userEvent.setup();
    const deletePromise = Promise.resolve();
    const listStore = createListStore();
    listStore.delete.mockReturnValue(deletePromise);

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'request-delete'}));
    await clickOpenDialogButton('sulu_admin.delete_warning_title', 'sulu_admin.ok');

    await waitFor(() => expect(listStore.delete).toHaveBeenCalledWith(5));
    await waitFor(() => expect(screen.queryByTestId('delete-referenced-dialog')).not.toBeInTheDocument());
});

test('ListStore should delete linked item when onRequestItemDelete callback is is confirmed twice', async() => {
    const user = userEvent.setup();
    const listStore = createListStore();
    listStore.delete.mockReturnValueOnce(Promise.reject(createReferencedResponse()));

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'request-delete'}));
    await clickOpenDialogButton('sulu_admin.delete_warning_title', 'sulu_admin.ok');

    expect(await screen.findByTestId('delete-referenced-dialog')).toBeInTheDocument();

    listStore.delete.mockReturnValueOnce(Promise.resolve());
    await user.click(screen.getByRole('button', {name: 'confirm-referenced-delete'}));

    await waitFor(() => expect(listStore.delete).toHaveBeenCalledWith(5, {force: true}));
    await waitFor(() => expect(screen.queryByTestId('delete-referenced-dialog')).not.toBeInTheDocument());
});

test('ListStore should not delete linked item when onRequestItemDelete callback is is confirmed once', async() => {
    const user = userEvent.setup();
    const listStore = createListStore();
    listStore.delete.mockReturnValueOnce(Promise.reject(createReferencedResponse()));

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'request-delete'}));
    await clickOpenDialogButton('sulu_admin.delete_warning_title', 'sulu_admin.ok');

    expect(await screen.findByTestId('delete-referenced-dialog')).toBeInTheDocument();
    expect(screen.getByText('Item 1')).toBeInTheDocument();
    expect(screen.getByText('Item 2')).toBeInTheDocument();

    listStore.delete.mockClear();
    await user.click(screen.getByRole('button', {name: 'cancel-referenced-delete'}));

    expect(listStore.delete).not.toHaveBeenCalled();
    await waitFor(() => expect(screen.queryByTestId('delete-referenced-dialog')).not.toBeInTheDocument());
});

test('ListStore should delete linked item when called with allowConflictDeletion value of true', async() => {
    const user = userEvent.setup();
    const listStore = createListStore();
    listStore.deleteSelection.mockReturnValueOnce(Promise.reject(createReferencedResponse()));
    listStore.selectionIds.push(5);
    const listRef = createListRef();

    render(<List adapters={['test']} ref={listRef} store={listStore} />);

    act(() => {
        listRef.current.requestSelectionDelete(true);
    });

    await clickOpenDialogButton('sulu_admin.delete_warning_title', 'sulu_admin.ok');

    expect(await screen.findByTestId('delete-referenced-dialog')).toBeInTheDocument();

    listStore.delete.mockReturnValueOnce(Promise.resolve());
    await user.click(screen.getByRole('button', {name: 'confirm-referenced-delete'}));

    await waitFor(() => expect(listStore.delete).toHaveBeenCalledWith(5, {force: true}));
    await waitFor(() => expect(screen.queryByTestId('delete-referenced-dialog')).not.toBeInTheDocument());
});

test('ListStore should not delete linked item when called with allowConflictDeletion value of false', async() => {
    const user = userEvent.setup();
    const listStore = createListStore();
    listStore.deleteSelection.mockReturnValueOnce(Promise.reject(createReferencedResponse()));
    listStore.selectionIds.push(5);
    const listRef = createListRef();

    render(<List adapters={['test']} ref={listRef} store={listStore} />);

    act(() => {
        listRef.current.requestSelectionDelete(false);
    });

    await clickOpenDialogButton('sulu_admin.delete_warning_title', 'sulu_admin.ok');

    expect(await screen.findByTestId('delete-referenced-dialog')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'confirm-referenced-delete'}));

    expect(listStore.delete).not.toHaveBeenCalledWith(5, {force: true});
    await waitFor(() => expect(screen.queryByTestId('delete-referenced-dialog')).not.toBeInTheDocument());
});

test('ListStore should delete item with dependants when onFinish callback called', async() => {
    const user = userEvent.setup();
    const listStore = createListStore();
    listStore.delete.mockReturnValueOnce(Promise.reject(createDependantResponse()));

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'request-delete'}));
    await clickOpenDialogButton('sulu_admin.delete_warning_title', 'sulu_admin.ok');

    expect(await screen.findByTestId('delete-dependant-dialog')).toBeInTheDocument();

    listStore.delete.mockReturnValueOnce(Promise.resolve());
    await user.click(screen.getByRole('button', {name: 'finish-dependant-delete'}));

    await waitFor(() => expect(listStore.delete).toHaveBeenCalledWith(5));
    expect(listStore.delete).toHaveBeenCalledTimes(2);
    await waitFor(() => expect(screen.queryByTestId('delete-dependant-dialog')).not.toBeInTheDocument());
});

test('ListStore should not delete item with dependants when onCancel callback called', async() => {
    const user = userEvent.setup();
    const listStore = createListStore();
    listStore.delete.mockReturnValueOnce(Promise.reject(createDependantResponse()));

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'request-delete'}));
    await clickOpenDialogButton('sulu_admin.delete_warning_title', 'sulu_admin.ok');

    expect(await screen.findByTestId('delete-dependant-dialog')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'cancel-dependant-delete'}));

    expect(listStore.delete).toHaveBeenCalledTimes(1);
    await waitFor(() => expect(screen.queryByTestId('delete-dependant-dialog')).not.toBeInTheDocument());
});

test(
    'Order warning should just disappear when onRequestItemOrder callback is called and '
    + 'overlay is cancelled',
    async() => {
        const user = userEvent.setup();
        const listStore = createListStore();

        render(<List adapters={['test']} store={listStore} />);

        await user.click(screen.getByRole('button', {name: 'request-order'}));
        expect(getOpenDialog('sulu_admin.order_warning_title')).toBeInTheDocument();

        await clickOpenDialogButton('sulu_admin.order_warning_title', 'sulu_admin.cancel');

        expect(listStore.order).not.toHaveBeenCalled();
    }
);

test('ListStore should order item when onRequestItemOrder callback is called and overlay is confirmed', async() => {
    const user = userEvent.setup();
    const orderPromise = Promise.resolve();
    const listStore = createListStore();
    listStore.order.mockReturnValue(orderPromise);

    render(<List adapters={['test']} store={listStore} />);

    await user.click(screen.getByRole('button', {name: 'request-order'}));
    await clickOpenDialogButton('sulu_admin.order_warning_title', 'sulu_admin.ok');

    await waitFor(() => expect(listStore.order).toHaveBeenCalledWith(5, 8));
    await waitFor(() => {
        expect(screen.getByTestId('dialog-sulu_admin.order_warning_title')).toHaveAttribute('data-open', 'false');
    });
});
