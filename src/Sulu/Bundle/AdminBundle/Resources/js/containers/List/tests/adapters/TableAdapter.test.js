// @flow
import React from 'react';
import {render} from '@testing-library/react';
import log from 'loglevel';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import TableAdapter from '../../adapters/TableAdapter';
import StringFieldTransformer from '../../fieldTransformers/StringFieldTransformer';
import IconFieldTransformer from '../../fieldTransformers/IconFieldTransformer';
import listFieldTransformerRegistry from '../../registries/listFieldTransformerRegistry';
import Icon from '../../../../components/Icon';
import Pagination from '../../../../components/Pagination';
import Table from '../../../../components/Table';
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
    get: jest.fn(),
    has: jest.fn(),
}));

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('../../../../components/Icon', () => jest.fn(() => <span data-testid="icon" />));

jest.mock('../../../../components/Pagination', () => jest.fn(({children}) => (
    <div data-testid="pagination">{children}</div>
)));

jest.mock('../../../../components/GhostIndicator', () => jest.fn(() => <span data-testid="ghost-indicator" />));
jest.mock('../../../../components/PublishIndicator', () => jest.fn(() => <span data-testid="publish-indicator" />));

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

const IconMock = (Icon: any);
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

beforeEach(() => {
    jest.clearAllMocks();
    listFieldTransformerRegistry.get.mockReturnValue(new StringFieldTransformer());
});

test('renders table with schema and matches snapshot', () => {
    const data = [
        {id: 1, published: '2017-08-23', publishedState: true, title: 'Page 1'},
        {id: 2, published: null, publishedState: true, title: 'Page 2'},
        {id: 3, published: '2017-08-23', publishedState: false, title: 'Page 3'},
        {ghostLocale: 'de', id: 4, published: null, publishedState: false, title: 'Page 4'},
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
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={2}
            pageCount={5}
            schema={schema}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('renders icon transformer values and warns for missing mapping', () => {
    listFieldTransformerRegistry.get.mockReturnValue(new IconFieldTransformer());

    const data = [
        {id: 1, status: 'planned'},
        {id: 2, status: 'running'},
        {id: 3, status: 'succeeded'},
        {id: 4, status: 'failed'},
    ];

    const schema = {
        status: {
            filterType: null,
            filterTypeParameters: null,
            label: 'Status',
            sortable: false,
            transformerTypeParameters: {
                mapping: {
                    failed: {icon: 'su-ban'},
                    planned: 'su-clock',
                    succeeded: {color: 'green', icon: 'su-check-circle'},
                },
            },
            type: 'icon',
            visibility: 'always',
        },
    };

    render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={1}
            pageCount={1}
            schema={schema}
        />
    );

    expect(getMockCallArg(IconMock, 0, 0)).toEqual(expect.objectContaining({name: 'su-clock'}));
    expect(getMockCallArg(IconMock, 1, 0)).toEqual(
        expect.objectContaining({name: 'su-check-circle', style: {color: 'green'}})
    );
    expect(getMockCallArg(IconMock, 2, 0)).toEqual(expect.objectContaining({name: 'su-ban', style: {}}));

    expect(log.warn).toBeCalledWith(
        'There was no icon specified in the "mapping" transformer parameter for the value "running".'
    );
});

test('passes adapter skin and show_header options to table structure', () => {
    const schema = {
        description: {
            filterType: null,
            filterTypeParameters: null,
            label: 'Description',
            sortable: true,
            transformerTypeParameters: {},
            type: 'string',
            visibility: 'yes',
        },
    };

    render(
        <TableAdapter
            {...listAdapterDefaultProps}
            adapterOptions={{show_header: false, skin: 'light'}}
            data={[]}
            page={2}
            pageCount={5}
            schema={schema}
        />
    );

    expect(getTableProps().skin).toEqual('light');
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
        <TableAdapter
            {...listAdapterDefaultProps}
            onSort={sortSpy}
            schema={schema}
        />
    );

    expect(getHeaderCellProps(0).onClick).toEqual(undefined);
    expect(getHeaderCellProps(1).onClick).toBe(sortSpy);
});

test('applies visibility filtering and sort state to header cells', () => {
    const schema = {
        hidden: {
            filterType: null,
            filterTypeParameters: null,
            label: 'Hidden',
            sortable: true,
            transformerTypeParameters: {},
            type: 'string',
            visibility: 'never',
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
        <TableAdapter
            {...listAdapterDefaultProps}
            data={[{id: 1, title: 'Title 1'}]}
            schema={schema}
            sortColumn="title"
            sortOrder="asc"
        />
    );

    expect(TableMock.HeaderCell).toBeCalledTimes(1);
    expect(getHeaderCellProps(0)).toEqual(expect.objectContaining({name: 'title', sortOrder: 'asc'}));
});

test('builds row buttons based on permissions and item actions', () => {
    const onItemClick = jest.fn();
    const actionClick = jest.fn();
    const itemActionsProvider = jest.fn((item) => [{icon: 'su-process', onClick: () => actionClick(item?.id)}]);

    const data = [
        {id: 1, title: 'Missing view', _permissions: {view: false}},
        {id: 2, title: 'Missing edit', _permissions: {edit: false}},
        {id: 3, title: 'All allowed'},
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
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            itemActionsProvider={itemActionsProvider}
            onItemClick={onItemClick}
            page={1}
            pageCount={3}
            schema={schema}
        />
    );

    expect(itemActionsProvider).toBeCalledWith(data[0]);
    expect(itemActionsProvider).toBeCalledWith(data[1]);
    expect(itemActionsProvider).toBeCalledWith(data[2]);

    expect(getRowProps(0).buttons[0]).toEqual(expect.objectContaining({disabled: true, icon: 'su-pen'}));
    expect(getRowProps(1).buttons[0]).toEqual(expect.objectContaining({disabled: false, icon: 'su-eye'}));
    expect(getRowProps(2).buttons[0]).toEqual(expect.objectContaining({disabled: false, icon: 'su-pen'}));

    expect(getRowProps(0).buttons[1]).toEqual(expect.objectContaining({icon: 'su-process'}));
});

test('marks disabled and selected rows correctly', () => {
    const data = [
        {id: 1, title: 'First'},
        {id: 2, title: 'Second'},
        {id: 3, title: 'Third'},
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
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            disabledIds={[1, 3]}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[1, 2]}
        />
    );

    expect(getRowProps(0)).toEqual(expect.objectContaining({disabled: true, selected: true}));
    expect(getRowProps(1)).toEqual(expect.objectContaining({disabled: false, selected: true}));
    expect(getRowProps(2)).toEqual(expect.objectContaining({disabled: true, selected: false}));
});

test('passes table selection callbacks through', () => {
    const onAllSelectionChange = jest.fn();
    const onItemSelectionChange = jest.fn();

    render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={[]}
            onAllSelectionChange={onAllSelectionChange}
            onItemSelectionChange={onItemSelectionChange}
            schema={{}}
        />
    );

    expect(getTableProps().onAllSelectionChange).toBe(onAllSelectionChange);
    expect(getTableProps().onRowSelectionChange).toBe(onItemSelectionChange);
    expect(getTableProps().selectMode).toEqual('multiple');
});

test('passes top-level edit button action to table header buttons', () => {
    const onItemClick = jest.fn();

    render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={[{id: 1, title: 'Title 1'}]}
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

    expect(getTableProps().buttons).toHaveLength(1);
    expect(getTableProps().buttons[0].icon).toEqual('su-pen');

    getTableProps().buttons[0].onClick(1);
    expect(onItemClick).toBeCalledWith(1);
});

test('passes pagination props when paginated', () => {
    const onLimitChange = jest.fn();
    const onPageChange = jest.fn();

    render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={[{id: 1, title: 'Title 1'}]}
            limit={10}
            onLimitChange={onLimitChange}
            onPageChange={onPageChange}
            page={2}
            pageCount={7}
        />
    );

    expect(PaginationMock).toBeCalledTimes(1);
    expect(getLatestMockProps(PaginationMock)).toEqual(expect.objectContaining({
        currentLimit: 10,
        currentPage: 2,
        loading: false,
        onLimitChange,
        onPageChange,
        totalPages: 7,
    }));
});

test('does not render pagination if pageCount is undefined', () => {
    render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={[{id: 1, title: 'Title 1'}]}
            page={1}
            pageCount={undefined}
        />
    );

    expect(PaginationMock).not.toBeCalled();
});

test('does not render pagination if first page has no data', () => {
    render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={[]}
            page={1}
            pageCount={7}
        />
    );

    expect(PaginationMock).not.toBeCalled();
});

test('does not render pagination when paginated is false', () => {
    render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={[{id: 1, title: 'Title 1'}]}
            page={2}
            pageCount={7}
            paginated={false}
        />
    );

    expect(PaginationMock).not.toBeCalled();
});
