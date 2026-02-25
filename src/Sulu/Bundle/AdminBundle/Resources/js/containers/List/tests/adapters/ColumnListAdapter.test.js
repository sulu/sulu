// @flow
import React from 'react';
import {render} from '@testing-library/react';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import ColumnListAdapter from '../../adapters/ColumnListAdapter';
import ColumnList from '../../../../components/ColumnList';
import getMockCallArg from '../../../../utils/TestHelper/getMockCallArg';
import getLatestMockProps from '../../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../../utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../../../../components/ColumnList', () => {
    const React = require('react');

    const ColumnListMock: any = jest.fn(({children}) => <div data-testid="column-list">{children}</div>);
    ColumnListMock.Column = jest.fn(({children}) => <div data-testid="column-list-column">{children}</div>);
    ColumnListMock.Item = jest.fn(({children}) => <div data-testid="column-list-item">{children}</div>);

    return ColumnListMock;
});

const ColumnListMock = (ColumnList: any);

function createItem(id, title, options = {}) {
    return {
        hasChildren: false,
        id,
        title,
        ...options,
    };
}

function getColumnListProps() {
    return getLatestMockProps(ColumnListMock);
}

function getColumnProps(index: number = 0) {
    return getMockCallArg(ColumnListMock.Column, index, 0);
}

function getItemProps(index: number = 0) {
    return getMockCallArg(ColumnListMock.Item, index, 0);
}

function renderAdapter(props = {}) {
    const ref = React.createRef();
    const adapterProps: any = {
        ...listAdapterDefaultProps,
        data: props.data || [[]],
        ...props,
    };

    const view = render(
        <ColumnListAdapter
            {...adapterProps}
            ref={ref}
        />
    );

    return {
        ...view,
        ref,
    };
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('renders column list and matches snapshot', () => {
    const data = [
        [
            createItem(1, 'Page 1', {publishedState: false}),
            createItem(2, 'Page 2', {_hasPermissions: true, published: '2017-08-23', publishedState: false}),
            createItem(3, 'Page 3', {linkProvider: 'page', published: '2017-08-23', publishedState: true}),
        ],
        [
            createItem(4, 'Page 2.1', {ghostLocale: 'nl'}),
            createItem(5, 'Page 2.2', {linkProvider: 'external'}),
        ],
    ];

    const {asFragment} = renderAdapter({
        activeItems: [2, 4],
        adapterOptions: {get_indicators: (item) => item.hasChildren ? ['has-children-indicator'] : []},
        data,
        onItemAdd: jest.fn(),
        onItemClick: jest.fn(),
        onRequestItemDelete: jest.fn(),
    });

    expect(asFragment()).toMatchSnapshot();
});

test('uses name as title fallback', () => {
    renderAdapter({
        activeItems: [],
        data: [[{id: 1, name: 'Page 1'}]],
    });

    expect(getItemProps(0).children).toEqual('Page 1');
});

test('passes item active, disabled, selected and loading states', () => {
    const data = [
        [createItem(1, 'Page 1'), createItem(2, 'Page 2')],
        [createItem(3, 'Page 1.1')],
        [],
    ];

    renderAdapter({
        activeItems: [1, 3],
        data,
        disabledIds: [3],
        loading: true,
        selections: [2],
    });

    expect(getColumnProps(0).loading).toEqual(false);
    expect(getColumnProps(2).loading).toEqual(true);

    expect(getItemProps(0)).toEqual(expect.objectContaining({active: true, disabled: false, selected: false}));
    expect(getItemProps(1)).toEqual(expect.objectContaining({active: false, disabled: false, selected: true}));
    expect(getItemProps(2)).toEqual(expect.objectContaining({active: true, disabled: true, selected: false}));
});

test('renders edit buttons based on permissions and ghost state', () => {
    const data = [[
        createItem(1, 'Missing view', {_permissions: {view: false}}),
        createItem(2, 'Missing edit', {_permissions: {edit: false, view: true}}),
        createItem(3, 'Sufficient permissions', {_permissions: {edit: true, view: true}}),
        createItem(4, 'Ghost page', {ghostLocale: 'en'}),
    ]];

    renderAdapter({
        activeItems: [],
        data,
        onItemClick: jest.fn(),
    });

    expect(getItemProps(0).buttons[0]).toEqual(expect.objectContaining({icon: 'su-pen', visible: false}));
    expect(getItemProps(1).buttons[0]).toEqual(expect.objectContaining({icon: 'su-eye', visible: true}));
    expect(getItemProps(2).buttons[0]).toEqual(expect.objectContaining({icon: 'su-pen', visible: true}));
    expect(getItemProps(3).buttons[0]).toEqual(expect.objectContaining({icon: 'su-plus-circle', visible: true}));
});

test('adds selection button and toggles onItemSelectionChange callback', () => {
    const onItemSelectionChange = jest.fn();

    renderAdapter({
        activeItems: [],
        data: [[createItem(1, 'Page 1'), createItem(2, 'Page 2')]],
        onItemSelectionChange,
        selections: [2],
    });

    expect(getItemProps(0).buttons[0].icon).toEqual('su-check');
    getItemProps(1).buttons[0].onClick(2);
    expect(onItemSelectionChange).toHaveBeenLastCalledWith(2, false);

    getItemProps(0).buttons[0].onClick(1);
    expect(onItemSelectionChange).toHaveBeenLastCalledWith(1, true);
});

test('calls onItemActivate on click unless item is in ordering column', () => {
    const onItemActivate = jest.fn();

    const {ref} = renderAdapter({
        activeItems: [1, 3],
        data: [
            [createItem(1, 'Page 1'), createItem(2, 'Page 2')],
            [createItem(3, 'Page 1.1')],
        ],
        onItemActivate,
    });

    getColumnListProps().onItemClick(2);
    expect(onItemActivate).toBeCalledWith(2);

    ref.current.orderColumn = 0;
    getColumnListProps().onItemClick(2);
    expect(onItemActivate).toBeCalledTimes(1);
});

test('calls onItemClick on double click only with view permission', () => {
    const onItemClick = jest.fn();

    renderAdapter({
        activeItems: [1, 3],
        data: [
            [
                createItem(1, 'Page 1', {_permissions: {view: true}}),
                createItem(2, 'Page 2', {_permissions: {view: false}}),
            ],
            [createItem(3, 'Page 1.1', {_permissions: {view: true}})],
        ],
        onItemClick,
    });

    getColumnListProps().onItemDoubleClick(1);
    expect(onItemClick).toBeCalledWith(1);

    getColumnListProps().onItemDoubleClick(2);
    expect(onItemClick).toBeCalledTimes(1);
});

test('throws if toolbar provider is called without activeItems', () => {
    renderAdapter({activeItems: undefined, data: [[]]});

    expect(() => getColumnListProps().toolbarItemsProvider(0)).toThrow('does not work without activeItems');
});

test('returns add button in toolbar when onItemAdd is available and allowed', () => {
    const onItemAdd = jest.fn();

    renderAdapter({
        activeItems: [],
        data: [[]],
        onItemAdd,
    });

    const toolbarItems = getColumnListProps().toolbarItemsProvider(0);
    expect(toolbarItems[0]).toEqual(expect.objectContaining({icon: 'su-plus-circle', type: 'button'}));

    toolbarItems[0].onClick();
    expect(onItemAdd).toBeCalledWith(undefined);
});

test('hides root toolbar when display_root_level_toolbar is false', () => {
    renderAdapter({
        activeItems: [undefined, 1],
        adapterOptions: {display_root_level_toolbar: false},
        data: [[createItem(1, 'Page 1')], []],
        onItemAdd: jest.fn(),
    });

    expect(getColumnListProps().toolbarItemsProvider(0)).toEqual([]);
    expect(getColumnListProps().toolbarItemsProvider(1).length).toBeGreaterThan(0);
});

test('builds settings dropdown with correct disabled states from permissions', () => {
    renderAdapter({
        activeItems: [undefined, 1],
        data: [[createItem(1, 'Page 1', {_permissions: {delete: false, edit: false}})], []],
        onRequestItemCopy: jest.fn(),
        onRequestItemDelete: jest.fn(),
        onRequestItemMove: jest.fn(),
        onRequestItemOrder: jest.fn(),
    });

    const toolbarItems = getColumnListProps().toolbarItemsProvider(0);
    const dropdown = toolbarItems.find((item) => item.type === 'dropdown');
    expect(dropdown).toBeDefined();

    expect(dropdown.options[0]).toEqual(expect.objectContaining({disabled: true, label: 'sulu_admin.delete'}));
    expect(dropdown.options[1]).toEqual(expect.objectContaining({disabled: true, label: 'sulu_admin.move'}));
    expect(dropdown.options[2]).toEqual(expect.objectContaining({disabled: true, label: 'sulu_admin.copy'}));
    expect(dropdown.options[3]).toEqual(expect.objectContaining({disabled: false, label: 'sulu_admin.order'}));
});

test('triggers request callbacks from settings dropdown with active item id', () => {
    const onRequestItemCopy = jest.fn();
    const onRequestItemDelete = jest.fn();
    const onRequestItemMove = jest.fn();

    renderAdapter({
        activeItems: [1, 3],
        data: [[createItem(1, 'Page 1')], [createItem(3, 'Page 1.1')]],
        onRequestItemCopy,
        onRequestItemDelete,
        onRequestItemMove,
    });

    const toolbarItems = getColumnListProps().toolbarItemsProvider(0);
    const dropdown = toolbarItems.find((item) => item.type === 'dropdown');

    dropdown.options.find((option) => option.label === 'sulu_admin.copy').onClick();
    dropdown.options.find((option) => option.label === 'sulu_admin.move').onClick();
    dropdown.options.find((option) => option.label === 'sulu_admin.delete').onClick();

    expect(onRequestItemCopy).toBeCalledWith(3);
    expect(onRequestItemMove).toBeCalledWith(3);
    expect(onRequestItemDelete).toBeCalledWith(3);
});

test('enables ordering mode and returns cancel toolbar button for that column', () => {
    renderAdapter({
        activeItems: [1, 3],
        data: [[createItem(1, 'Page 1')], [createItem(3, 'Page 1.1')]],
        onRequestItemOrder: jest.fn(),
    });

    const toolbarItems = getColumnListProps().toolbarItemsProvider(0);
    const dropdown = toolbarItems.find((item) => item.type === 'dropdown');
    const orderOption = dropdown.options.find((option) => option.label === 'sulu_admin.order');

    orderOption.onClick();

    const orderingToolbarItems = getColumnListProps().toolbarItemsProvider(0);
    expect(orderingToolbarItems).toHaveLength(1);
    expect(orderingToolbarItems[0]).toEqual(expect.objectContaining({icon: 'su-times'}));

    orderingToolbarItems[0].onClick();
    expect(getColumnListProps().toolbarItemsProvider(0)).not.toEqual(orderingToolbarItems);
});

test('calls onRequestItemOrder with clamped order in handleOrderChange', async() => {
    const onRequestItemOrder = jest.fn(() => Promise.resolve({ordered: true}));

    const {ref} = renderAdapter({
        activeItems: [1, 3],
        data: [[createItem(1, 'Page 1'), createItem(2, 'Page 2')]],
        onRequestItemOrder,
    });

    ref.current.orderColumn = 0;
    const result = await ref.current.handleOrderChange(1, 5);

    expect(onRequestItemOrder).toBeCalledWith(1, 2);
    expect(result).toEqual(true);
});

test('throws in handleOrderChange for missing callback or missing orderColumn', () => {
    const {ref} = renderAdapter({
        activeItems: [1],
        data: [[createItem(1, 'Page 1')]],
        onRequestItemOrder: undefined,
    });

    expect(() => ref.current.handleOrderChange(1, 1)).toThrow('no onRequestItemOrder callback');

    const {ref: secondRef} = renderAdapter({
        activeItems: [1],
        data: [[createItem(1, 'Page 1')]],
        onRequestItemOrder: jest.fn(),
    });

    secondRef.current.orderColumn = undefined;
    expect(() => secondRef.current.handleOrderChange(1, 1)).toThrow('column has been selected to be ordered');
});

test('hides settings dropdown when no settings callbacks are provided', () => {
    renderAdapter({
        activeItems: [1],
        data: [[createItem(1, 'Page 1')]],
    });

    const toolbarItems = getColumnListProps().toolbarItemsProvider(0);
    expect(toolbarItems).toEqual(undefined);
});
