// @flow
import React from 'react';
import {render} from '@testing-library/react';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import TreeTableAdapter from '../../adapters/TreeTableAdapter';
import Pagination from '../../../../components/Pagination';
import Table from '../../../../components/Table';
import Loader from '../../../../components/Loader';
import getMockCallArg from '../../../../utils/TestHelper/getMockCallArg';
import getLatestMockProps from '../../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../../utils/Translator', () => ({
    translate(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
            default:
                return key;
        }
    },
}));

jest.mock('../../registries/listFieldTransformerRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(() => ({
        transform(value) {
            return value;
        },
    })),
    has: jest.fn(),
}));

jest.mock('../../../../components/Loader', () => jest.fn(() => <div data-testid="loader" />));

jest.mock('../../../../components/Pagination', () => jest.fn(({children}) => (
    <div data-testid="pagination">{children}</div>
)));

jest.mock('../../../../components/Table', () => {
    const React = require('react');

    const TableMock: any = jest.fn(({children}) => <div data-testid="table">{children}</div>);

    TableMock.Header = jest.fn(({children}) => <div data-testid="table-header">{children}</div>);
    TableMock.Body = jest.fn(({children}) => <div data-testid="table-body">{children}</div>);
    TableMock.Row = jest.fn(({children}) => <div data-testid="table-row">{children}</div>);
    TableMock.Cell = jest.fn(({children}) => <div data-testid="table-cell">{children}</div>);
    TableMock.HeaderCell = jest.fn(({children}) => <div data-testid="table-header-cell">{children}</div>);

    return TableMock;
});

const LoaderMock = (Loader: any);
const PaginationMock = (Pagination: any);
const TableMock = (Table: any);

function getTableProps() {
    return getLatestMockProps(TableMock);
}

function getRowProps(index: number = 0) {
    return getMockCallArg(TableMock.Row, index, 0);
}

function getHeaderCellProps(index: number = 0) {
    return getMockCallArg(TableMock.HeaderCell, index, 0);
}

function createItem(id, title, children = [], extra = {}) {
    return {
        children,
        data: {
            id,
            title,
            ...extra,
        },
        hasChildren: children.length > 0,
    };
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('renders tree table and matches snapshot', () => {
    const data = [
        createItem(1, 'Page 1'),
        createItem(2, 'Page 2'),
        createItem(3, 'Page 3', [createItem(4, 'Page 4')]),
    ];

    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            label: 'Title',
            sortable: true,
            transformerTypeParameters: {},
            type: 'string',
            visibility: 'yes',
        },
    };

    const {asFragment} = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            schema={schema}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('renders loader while loading root level', () => {
    render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            active={undefined}
            data={[]}
            loading={true}
            schema={{}}
        />
    );

    expect(LoaderMock).toBeCalledTimes(1);
    expect(TableMock).not.toBeCalled();
});

test('respects header options and table skin', () => {
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            label: 'Title',
            sortable: true,
            transformerTypeParameters: {},
            type: 'string',
            visibility: 'yes',
        },
    };

    render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            adapterOptions={{show_header: false, skin: 'flat'}}
            data={[createItem(1, 'Title 1')]}
            options={{showHeader: true}}
            schema={schema}
        />
    );

    expect(getTableProps().skin).toEqual('flat');
    expect(TableMock.Header).not.toBeCalled();
});

test('attaches sort handler only to sortable headers', () => {
    const sortSpy = jest.fn();

    const schema = {
        description: {
            filterType: null,
            filterTypeParameters: null,
            label: 'Description',
            sortable: false,
            transformerTypeParameters: {},
            type: 'string',
            visibility: 'yes',
        },
        title: {
            filterType: null,
            filterTypeParameters: null,
            label: 'Title',
            sortable: true,
            transformerTypeParameters: {},
            type: 'string',
            visibility: 'yes',
        },
    };

    render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            onSort={sortSpy}
            schema={schema}
        />
    );

    expect(getHeaderCellProps(0).onClick).toEqual(undefined);
    expect(getHeaderCellProps(1).onClick).toBe(sortSpy);
});

test('renders nested rows with depth and expansion state', () => {
    const data = [
        createItem(1, 'Root 1'),
        createItem(2, 'Root 2', [
            createItem(3, 'Child 1'),
            createItem(4, 'Child 2'),
        ]),
    ];

    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            label: 'Title',
            sortable: true,
            transformerTypeParameters: {},
            type: 'string',
            visibility: 'yes',
        },
    };

    render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            schema={schema}
            selections={[3]}
        />
    );

    expect(TableMock.Row).toBeCalledTimes(4);
    expect(getRowProps(0)).toEqual(expect.objectContaining({depth: 0, expanded: false, hasChildren: false, id: 1}));
    expect(getRowProps(1)).toEqual(expect.objectContaining({depth: 0, expanded: true, hasChildren: true, id: 2}));
    expect(getRowProps(2)).toEqual(
        expect.objectContaining({depth: 1, expanded: false, hasChildren: false, id: 3, selected: true})
    );
    expect(getRowProps(3)).toEqual(expect.objectContaining({depth: 1, expanded: false, hasChildren: false, id: 4}));
});

test('calls activate and deactivate callbacks when rows are expanded or collapsed', () => {
    const onItemActivate = jest.fn();
    const onItemDeactivate = jest.fn();

    render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={[createItem(1, 'Root')]}
            onItemActivate={onItemActivate}
            onItemDeactivate={onItemDeactivate}
            schema={{}}
        />
    );

    getTableProps().onRowExpand(6);
    getTableProps().onRowCollapse(3);

    expect(onItemActivate).toBeCalledWith(6);
    expect(onItemDeactivate).toBeCalledWith(3);
});

test('builds row buttons based on permissions, add and click callbacks', () => {
    const onItemAdd = jest.fn();
    const onItemClick = jest.fn();

    const data = [
        createItem(1, 'Missing view', [], {_permissions: {view: false}}),
        createItem(2, 'Missing edit', [], {_permissions: {edit: false}}),
        createItem(3, 'Missing add', [], {_permissions: {add: false}}),
        createItem(4, 'No missing permissions'),
    ];

    render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemAdd={onItemAdd}
            onItemClick={onItemClick}
            schema={{
                title: {
                    filterType: null,
                    filterTypeParameters: null,
                    label: 'Title',
                    sortable: true,
                    transformerTypeParameters: {},
                    type: 'string',
                    visibility: 'yes',
                },
            }}
        />
    );

    expect(getRowProps(0).buttons[0]).toEqual(expect.objectContaining({disabled: true, icon: 'su-pen'}));
    expect(getRowProps(1).buttons[0]).toEqual(expect.objectContaining({disabled: false, icon: 'su-eye'}));
    expect(getRowProps(2).buttons[1]).toEqual(expect.objectContaining({disabled: true, icon: 'su-plus-circle'}));
    expect(getRowProps(3).buttons[1]).toEqual(expect.objectContaining({disabled: false, icon: 'su-plus-circle'}));
});

test('adds item actions from provider', () => {
    const actionClick = jest.fn();
    const itemActionsProvider = jest.fn((item) => [{icon: 'su-process', onClick: () => actionClick(item?.id)}]);

    const data = [createItem(2, 'Test1')];

    render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            itemActionsProvider={itemActionsProvider}
            onItemAdd={jest.fn()}
            onItemClick={jest.fn()}
            schema={{
                title: {
                    filterType: null,
                    filterTypeParameters: null,
                    label: 'Title',
                    sortable: true,
                    transformerTypeParameters: {},
                    type: 'string',
                    visibility: 'yes',
                },
            }}
        />
    );

    expect(itemActionsProvider).toBeCalledWith(data[0].data);
    expect(getRowProps(0).buttons[2]).toEqual(expect.objectContaining({icon: 'su-process'}));
});

test('marks disabled rows using disabledIds', () => {
    const data = [
        createItem(1, 'First'),
        createItem(2, 'Second', [createItem(3, 'Child')]),
    ];

    render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            disabledIds={[1, 3]}
            schema={{
                title: {
                    filterType: null,
                    filterTypeParameters: null,
                    label: 'Title',
                    sortable: true,
                    transformerTypeParameters: {},
                    type: 'string',
                    visibility: 'yes',
                },
            }}
        />
    );

    expect(getRowProps(0).disabled).toEqual(true);
    expect(getRowProps(1).disabled).toEqual(false);
    expect(getRowProps(2).disabled).toEqual(true);
});

test('passes table-level buttons and selection callback props', () => {
    const onAllSelectionChange = jest.fn();
    const onItemAdd = jest.fn();
    const onItemClick = jest.fn();
    const onItemSelectionChange = jest.fn();

    render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={[createItem(1, 'Test')]}
            onAllSelectionChange={onAllSelectionChange}
            onItemAdd={onItemAdd}
            onItemClick={onItemClick}
            onItemSelectionChange={onItemSelectionChange}
            schema={{
                title: {
                    filterType: null,
                    filterTypeParameters: null,
                    label: 'Title',
                    sortable: true,
                    transformerTypeParameters: {},
                    type: 'string',
                    visibility: 'yes',
                },
            }}
        />
    );

    expect(getTableProps().buttons).toHaveLength(2);
    expect(getTableProps().buttons[0].icon).toEqual('su-pen');
    expect(getTableProps().buttons[1].icon).toEqual('su-plus-circle');
    expect(getTableProps().onAllSelectionChange).toBe(onAllSelectionChange);
    expect(getTableProps().onRowSelectionChange).toBe(onItemSelectionChange);
});

test('passes pagination props and page change resets active item', () => {
    const onItemActivate = jest.fn();
    const onPageChange = jest.fn();

    render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={[createItem(1, 'Test')]}
            limit={10}
            onItemActivate={onItemActivate}
            onPageChange={onPageChange}
            page={2}
            pageCount={7}
            schema={{}}
        />
    );

    expect(PaginationMock).toBeCalledTimes(1);
    expect(getLatestMockProps(PaginationMock)).toEqual(expect.objectContaining({
        currentLimit: 10,
        currentPage: 2,
        onLimitChange: listAdapterDefaultProps.onLimitChange,
        totalPages: 7,
    }));

    getLatestMockProps(PaginationMock).onPageChange(3);
    expect(onItemActivate).toBeCalledWith(undefined);
    expect(onPageChange).toBeCalledWith(3);
});

test('does not render pagination if pageCount is undefined', () => {
    render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={[createItem(1, 'Test')]}
            page={1}
            pageCount={undefined}
            schema={{}}
        />
    );

    expect(PaginationMock).not.toBeCalled();
});

test('does not render pagination if first page has no data', () => {
    render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={[]}
            page={1}
            pageCount={7}
            schema={{}}
        />
    );

    expect(PaginationMock).not.toBeCalled();
});

test('does not render pagination when paginated is false', () => {
    render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={[createItem(1, 'Test')]}
            page={2}
            pageCount={7}
            paginated={false}
            schema={{}}
        />
    );

    expect(PaginationMock).not.toBeCalled();
});
