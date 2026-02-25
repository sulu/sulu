/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import List from '../List';
import listAdapterRegistry from '../registries/listAdapterRegistry';
import userStore from '../../../stores/userStore';

const TableAdapter = jest.fn(({disabledIds = []}) => (
    <div data-disabled-ids={JSON.stringify(disabledIds)} data-testid="table-adapter" />
));
TableAdapter.icon = 'su-table';
TableAdapter.searchable = true;
TableAdapter.hasColumnOptions = true;
TableAdapter.paginatable = true;
TableAdapter.LoadingStrategy = class LoadingStrategy {};
TableAdapter.StructureStrategy = class StructureStrategy {};

const ColumnAdapter = jest.fn(({disabledIds = []}) => (
    <div data-disabled-ids={JSON.stringify(disabledIds)} data-testid="column-adapter" />
));
ColumnAdapter.icon = 'su-th-large';
ColumnAdapter.searchable = false;
ColumnAdapter.hasColumnOptions = false;
ColumnAdapter.paginatable = false;
ColumnAdapter.LoadingStrategy = class LoadingStrategy {};
ColumnAdapter.StructureStrategy = class StructureStrategy {};

jest.mock('../../../stores/userStore', () => ({
    setPersistentSetting: jest.fn(),
    getPersistentSetting: jest.fn(),
}));

jest.mock('../registries/listAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    getOptions: jest.fn(() => ({})),
    has: jest.fn(() => true),
}));

jest.mock('../registries/listFieldFilterTypeRegistry', () => ({
    get: jest.fn(),
    getOptions: jest.fn(() => ({})),
}));

jest.mock('../registries/listFieldTransformerRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));
// Rendering the real overlay creates nested list stores that trigger `fetch` in jsdom.
jest.mock('../../SingleListOverlay', () => jest.fn(() => <div data-testid="single-list-overlay" />));

const createStore = (overrides = {}) => ({
    active: observable.box(undefined),
    activeItems: [],
    copy: jest.fn(() => Promise.resolve()),
    copying: false,
    data: [{id: 1, title: 'Title 1'}, {id: 2, title: 'Title 2', enabled: false}],
    delete: jest.fn(() => Promise.resolve()),
    deleting: false,
    deletingSelection: false,
    deleteSelection: jest.fn(() => Promise.resolve()),
    filter: jest.fn(),
    filterOptions: observable.box({}),
    filterableFields: {},
    findById: jest.fn((id) => ({id})),
    getPage: jest.fn(() => 1),
    limit: observable.box(10),
    listKey: 'test-list',
    loading: false,
    loadingStrategy: null,
    metadataOptions: {},
    move: jest.fn(() => Promise.resolve()),
    moving: false,
    movingSelection: false,
    observableOptions: {},
    options: {},
    order: jest.fn(() => Promise.resolve()),
    ordering: false,
    pageCount: 2,
    queryOptions: {},
    reload: jest.fn(),
    remove: jest.fn(),
    resourceKey: 'test',
    schemaLoading: false,
    search: jest.fn(),
    searchTerm: observable.box(''),
    select: jest.fn(),
    selectionIds: [],
    selections: [],
    setLimit: jest.fn(),
    sort: jest.fn(),
    sortColumn: observable.box(undefined),
    sortOrder: observable.box(undefined),
    structureStrategy: null,
    updateLoadingStrategy: jest.fn(),
    updateStructureStrategy: jest.fn(),
    userSchema: {title: {label: 'Title', type: 'string'}},
    userSettingsKey: 'list',
    visibleItems: [{id: 1, enabled: true}, {id: 2, enabled: false}],
    ...overrides,
});

beforeEach(() => {
    jest.clearAllMocks();
    userStore.getPersistentSetting.mockReturnValue(undefined);

    listAdapterRegistry.get.mockImplementation((key) => {
        if (key === 'column') {
            return ColumnAdapter;
        }
        return TableAdapter;
    });
});

test('renders list adapter and toolbar', () => {
    const store = createStore();
    const {asFragment} = render(<List adapters={['table']} store={store} />);

    expect(TableAdapter).toHaveBeenCalled();
    expect(asFragment()).toMatchSnapshot();
});

test('renders loader when loading and no pages loaded', () => {
    const store = createStore({loading: true, pageCount: 0});

    render(<List adapters={['table']} store={store} />);
    expect(document.querySelector('.spinner')).toBeInTheDocument();
});

test('renders forbidden hint when list store is forbidden', () => {
    const store = createStore({forbidden: true});

    render(<List adapters={['table']} store={store} />);
    expect(screen.getByText('sulu_admin.no_permissions')).toBeInTheDocument();
    expect(TableAdapter).not.toHaveBeenCalled();
});

test('passes disabled ids from static list and condition to adapter', () => {
    const store = createStore();

    render(
        <List
            adapters={['table']}
            disabledIds={[5]}
            itemDisabledCondition="!enabled"
            store={store}
        />
    );

    expect(screen.getByTestId('table-adapter')).toHaveAttribute('data-disabled-ids', '[5,2]');
});

test('changes adapter through AdapterSwitch and persists setting', async() => {
    const user = userEvent.setup();
    const store = createStore();
    render(<List adapters={['table', 'column']} store={store} />);

    await user.click(screen.getByRole('button', {name: 'su-th-large'}));

    expect(userStore.setPersistentSetting).toHaveBeenCalledWith(
        'sulu_admin.list.test-list.list.adapter',
        'column'
    );
    expect(store.updateLoadingStrategy).toHaveBeenCalled();
    expect(store.updateStructureStrategy).toHaveBeenCalled();
});

test('calls store.deleteSelection after confirming selection delete', async() => {
    const store = createStore();
    const ref = React.createRef();

    render(<List adapters={['table']} ref={ref} store={store} />);

    ref.current.requestSelectionDelete();
    ref.current.handleSelectionDeleteDialogConfirmClick();

    await waitFor(() => expect(store.deleteSelection).toHaveBeenCalled());
});

test('requests and confirms item delete', async() => {
    const store = createStore();
    const ref = React.createRef();

    render(<List adapters={['table']} ref={ref} store={store} />);

    const deletePromise = ref.current.handleRequestItemDelete(12);
    ref.current.handleDeleteDialogConfirmClick();

    await deletePromise;
    await waitFor(() => expect(store.delete).toHaveBeenCalledWith(12));
});

test('forwards search and filter changes to store', () => {
    const store = createStore();
    const ref = React.createRef();

    render(<List adapters={['table']} filterable={true} ref={ref} store={store} />);

    ref.current.handleSearch('my query');
    ref.current.handleFilterChange({status: 'published'});

    expect(store.search).toHaveBeenCalledWith('my query');
    expect(store.filter).toHaveBeenCalledWith({status: 'published'});
});
