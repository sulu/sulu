// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import log from 'loglevel';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import TableAdapter from '../../adapters/TableAdapter';
import StringFieldTransformer from '../../fieldTransformers/StringFieldTransformer';
import IconFieldTransformer from '../../fieldTransformers/IconFieldTransformer';
import listFieldTransformerRegistry from '../../registries/listFieldTransformerRegistry';

jest.mock('../../../../utils/Translator', () => ({
    translate(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
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

beforeEach(() => {
    listFieldTransformerRegistry.get.mockReturnValue(new StringFieldTransformer());
});

test('Render data with schema', () => {
    const data = [
        {
            id: 1,
            title: 'Page 1',
            published: '2017-08-23',
            publishedState: true,
        },
        {
            id: 2,
            title: 'Page 2',
            publishedState: true,
            published: null,
        },
        {
            id: 3,
            title: 'Page 3',
            publishedState: false,
            published: '2017-08-23',
        },
        {
            id: 4,
            title: 'Page 4',
            publishedState: false,
            published: null,
        },
        {
            id: 5,
            title: 'Page 5',
            published: '2017-08-23',
            publishedState: true,
            ghostLocale: 'de',
        },
        {
            id: 6,
            title: 'Page 6',
            publishedState: true,
            published: null,
            ghostLocale: 'de',
        },
        {
            id: 7,
            title: 'Page 7',
            publishedState: false,
            published: '2017-08-23',
            ghostLocale: 'de',
        },
        {
            id: 8,
            title: 'Page 8',
            publishedState: false,
            published: null,
            ghostLocale: 'de',
        },
    ];

    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
    };

    const tableAdapter = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={2}
            pageCount={5}
            schema={schema}
        />
    );

    expect(tableAdapter).toMatchSnapshot();
});

test('Render data as icons', () => {
    listFieldTransformerRegistry.get.mockReturnValue(new IconFieldTransformer());

    const data = [
        {
            id: 1,
            status: 'planned',
        },
        {
            id: 2,
            status: 'running',
        },
        {
            id: 3,
            status: 'succeeded',
        },
        {
            id: 4,
            status: 'failed',
        },
    ];
    const schema = {
        status: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {
                mapping: {
                    planned: 'su-clock',
                    succeeded: {
                        icon: 'su-check-circle',
                        color: 'green',
                    },
                    failed: {
                        icon: 'su-ban',
                    },
                },
            },
            type: 'icon',
            sortable: false,
            visibility: 'always',
            label: 'Status',
        },
    };
    const tableAdapter = mount(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={1}
            pageCount={1}
            schema={schema}
        />
    );

    expect(tableAdapter.find('Row').at(0).find('Icon').props().name).toEqual('su-clock');
    expect(tableAdapter.find('Row').at(0).find('Icon').props().style).toEqual(undefined);

    expect(tableAdapter.find('Row').at(1).find('Cell').text()).toEqual('running');
    expect(tableAdapter.find('Row').at(1).find('Icon')).toHaveLength(0);
    expect(log.warn).toBeCalledWith(
        'There was no icon specified in the "mapping" transformer parameter for the value "running".'
    );

    expect(tableAdapter.find('Row').at(2).find('Icon').props().name).toEqual('su-check-circle');
    expect(tableAdapter.find('Row').at(2).find('Icon').props().style).toEqual({color: 'green'});

    expect(tableAdapter.find('Row').at(3).find('Icon').props().name).toEqual('su-ban');
    expect(tableAdapter.find('Row').at(3).find('Icon').props().style).toEqual({});
});

test('Render data with skin', () => {
    const data = [];

    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'no',
            label: 'Title',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Description',
        },
    };
    const tableAdapter = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            adapterOptions={{
                skin: 'light',
            }}
            data={data}
            page={2}
            pageCount={5}
            schema={schema}
        />
    );

    expect(tableAdapter).toMatchSnapshot();
});

test('Render data with shrunken cell', () => {
    const data = [
        {
            id: 1,
            title: '1',
            description: 'planned',
        },
        {
            id: 2,
            title: '2',
            description: 'running',
        },
        {
            id: 3,
            title: '3',
            description: 'succeeded',
        },
        {
            id: 4,
            title: '4',
            description: 'failed',
        },
    ];

    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
            width: 'shrink',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Description',
            width: 'auto',
        },
    };
    const tableAdapter = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={2}
            pageCount={5}
            schema={schema}
        />
    );

    expect(tableAdapter).toMatchSnapshot();
});

test('Render data without header', () => {
    const data = [];

    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'no',
            label: 'Title',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Description',
        },
    };
    const tableAdapter = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            adapterOptions={{
                show_header: false,
            }}
            data={data}
            page={2}
            pageCount={5}
            schema={schema}
        />
    );

    expect(tableAdapter).toMatchSnapshot();
});

test('Attach onClick handler for sorting if schema says the header is sortable', () => {
    const sortSpy = jest.fn();

    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: false,
            visibility: 'yes',
            label: 'Description',
        },
    };

    const tableAdapter = shallow(
        <TableAdapter
            {...listAdapterDefaultProps}
            onSort={sortSpy}
            schema={schema}
        />
    );

    expect(tableAdapter.find('HeaderCell').at(0).prop('onClick')).toBe(sortSpy);
    expect(tableAdapter.find('HeaderCell').at(1).prop('onClick')).toEqual(undefined);
});

test('Render data with all different visibility types schema', () => {
    const data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
        {
            id: 2,
            title: 'Title 2',
            description: 'Description 2',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'no',
            label: 'Title',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Description',
        },
        test1: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'always',
            label: 'Test 1',
        },
        test2: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'never',
            label: 'Test 2',
        },
    };
    const tableAdapter = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={2}
            pageCount={5}
            schema={schema}
        />
    );

    expect(tableAdapter).toMatchSnapshot();
});

test('Render data with schema and selections', () => {
    const data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
        {
            id: 2,
            title: 'Title 2',
            description: 'Description 2',
        },
        {
            id: 3,
            title: 'Title 3',
            description: 'Description 3',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const tableAdapter = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemSelectionChange={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[1, 3]}
        />
    );

    expect(tableAdapter).toMatchSnapshot();
});

test('Render data with schema in different order', () => {
    const data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
        {
            id: 2,
            title: 'Title 2',
            description: 'Description 2',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const tableAdapter = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={2}
            pageCount={3}
            schema={schema}
        />
    );

    expect(tableAdapter).toMatchSnapshot();
});

test('Render data with schema not containing all fields', () => {
    const data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
        {
            id: 2,
            title: 'Title 2',
            description: 'Description 2',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
    };
    const tableAdapter = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={1}
            pageCount={3}
            schema={schema}
        />
    );

    expect(tableAdapter).toMatchSnapshot();
});

test('Render data with pencil button when onItemEdit callback is passed', () => {
    const data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
        {
            id: 2,
            title: 'Title 2',
            description: 'Description 2',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const tableAdapter = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemClick={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
        />
    );

    expect(tableAdapter).toMatchSnapshot();
});

test('Render correct button based on permissions when item permissions are provided', () => {
    const data = [
        {
            id: 1,
            title: 'Missing view permission',
            _permissions: {
                view: false,
            },
        },
        {
            id: 2,
            title: 'Missing edit permission',
            _permissions: {
                edit: false,
            },
        },
        {
            id: 3,
            title: 'No missing permissions',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
    };
    const tableAdapter = mount(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemClick={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
        />
    );

    expect(tableAdapter.find('Row').at(0).find('ButtonCell').props().icon).toEqual('su-pen');
    expect(tableAdapter.find('Row').at(0).find('ButtonCell').props().disabled).toEqual(true);

    expect(tableAdapter.find('Row').at(1).find('ButtonCell').props().icon).toEqual('su-eye');
    expect(tableAdapter.find('Row').at(1).find('ButtonCell').props().disabled).toEqual(false);

    expect(tableAdapter.find('Row').at(2).find('ButtonCell').props().icon).toEqual('su-pen');
    expect(tableAdapter.find('Row').at(2).find('ButtonCell').props().disabled).toEqual(false);
});

test('Render disabled rows based on given disabledIds prop', () => {
    const data = [
        {
            id: 1,
            title: 'First item',
        },
        {
            id: 2,
            title: 'Second item',
        },
        {
            id: 3,
            title: 'third item',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
    };
    const tableAdapter = mount(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            disabledIds={[1, 3]}
            onItemClick={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
        />
    );

    expect(tableAdapter.find('Row').at(0).props().disabled).toEqual(true);
    expect(tableAdapter.find('Row').at(1).props().disabled).toEqual(false);
    expect(tableAdapter.find('Row').at(2).props().disabled).toEqual(true);
});

test('Render data with pencil button and given itemActions when onItemEdit callback is passed', () => {
    const data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
        {
            id: 2,
            title: 'Title 2',
            description: 'Description 2',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const actionsProvider = () => [
        {
            icon: 'su-process',
            onClick: undefined,
        },
        {
            icon: 'su-trash',
            onClick: undefined,
        },
    ];

    const tableAdapter = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            /* eslint-disable-next-line react/jsx-no-bind */
            itemActionsProvider={actionsProvider}
            onItemClick={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
        />
    );

    expect(tableAdapter).toMatchSnapshot();
});

test('Render column with ascending sort icon', () => {
    const data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const tableAdapter = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={1}
            pageCount={3}
            schema={schema}
            sortColumn="title"
            sortOrder="asc"
        />
    );

    expect(tableAdapter).toMatchSnapshot();
});

test('Render column with descending sort icon', () => {
    const data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const tableAdapter = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={1}
            pageCount={3}
            schema={schema}
            sortColumn="description"
            sortOrder="desc"
        />
    );

    expect(tableAdapter).toMatchSnapshot();
});

test('Click on pencil should execute onItemClick callback', () => {
    const rowEditClickSpy = jest.fn();
    const data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
        {
            id: 2,
            title: 'Title 2',
            description: 'Description 2',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const tableAdapter = shallow(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemClick={rowEditClickSpy}
            page={1}
            pageCount={3}
            schema={schema}
        />
    );
    const buttons = tableAdapter.find('Table').prop('buttons');
    expect(buttons).toHaveLength(1);
    expect(buttons[0].icon).toBe('su-pen');

    buttons[0].onClick(1);
    expect(rowEditClickSpy).toBeCalledWith(1);
});

test('Click on itemAction should execute its callback', () => {
    const actionClickSpy = jest.fn();
    const item1 = {
        id: 1,
        title: 'Title 1',
        description: 'Description 1',
    };
    const item2 = {
        id: 2,
        title: 'Title 2',
        description: 'Description 2',
    };
    const data = [item1, item2];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const actionsProvider = jest.fn(() => [
        {
            icon: 'su-process',
            onClick: actionClickSpy,
        },
    ]);

    const tableAdapter = shallow(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            itemActionsProvider={actionsProvider}
            onItemClick={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
        />
    );

    expect(actionsProvider).toBeCalledWith(item1);
    expect(actionsProvider).toBeCalledWith(item2);

    const buttons = tableAdapter.find('Table').prop('buttons');
    expect(buttons).toHaveLength(2);
    expect(buttons[1].icon).toBe('su-process');

    buttons[1].onClick(1);
    expect(actionClickSpy).toBeCalledWith(1);
});

test('Click on checkbox should call onItemSelectionChange callback', () => {
    const rowSelectionChangeSpy = jest.fn();
    const data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
        {
            id: 2,
            title: 'Title 2',
            description: 'Description 2',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const tableAdapter = shallow(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemSelectionChange={rowSelectionChangeSpy}
            page={1}
            pageCount={3}
            schema={schema}
        />
    );

    expect(tableAdapter.find('Table').get(0).props.onRowSelectionChange).toBe(rowSelectionChangeSpy);
});

test('Click on checkbox in header should call onAllSelectionChange callback', () => {
    const allSelectionChangeSpy = jest.fn();
    const data = [];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
    };
    const tableAdapter = shallow(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onAllSelectionChange={allSelectionChangeSpy}
            schema={schema}
        />
    );

    expect(tableAdapter.find('Table').get(0).props.onAllSelectionChange).toBe(allSelectionChangeSpy);
});

test('Pagination should be passed correct props', () => {
    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();
    const tableAdapter = shallow(
        <TableAdapter
            {...listAdapterDefaultProps}
            limit={10}
            onLimitChange={limitChangeSpy}
            onPageChange={pageChangeSpy}
            page={2}
            pageCount={7}
        />
    );
    expect(tableAdapter.find('Pagination').get(0).props).toEqual({
        totalPages: 7,
        currentPage: 2,
        currentLimit: 10,
        loading: false,
        onLimitChange: limitChangeSpy,
        onPageChange: pageChangeSpy,
        children: expect.anything(),
    });
});

test('Pagination should not be rendered if API is not paginated', () => {
    const data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
    ];

    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();
    const tableAdapter = shallow(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onLimitChange={limitChangeSpy}
            onPageChange={pageChangeSpy}
            page={1}
            pageCount={undefined}
        />
    );
    expect(tableAdapter.find('Pagination')).toHaveLength(0);
});

test('Pagination should not be rendered if no data is available', () => {
    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();
    const tableAdapter = shallow(
        <TableAdapter
            {...listAdapterDefaultProps}
            onLimitChange={limitChangeSpy}
            onPageChange={pageChangeSpy}
            page={1}
        />
    );
    expect(tableAdapter.find('Pagination')).toHaveLength(0);
});

test('Pagination should not be rendered if pagination is false', () => {
    const tableAdapter = shallow(
        <TableAdapter
            {...listAdapterDefaultProps}
            limit={10}
            page={2}
            pageCount={7}
            paginated={false}
        />
    );
    expect(tableAdapter.find('Pagination')).toHaveLength(0);
});
