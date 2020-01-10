// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import TableAdapter from '../../adapters/TableAdapter';
import StringFieldTransformer from '../../fieldTransformers/StringFieldTransformer';
import listFieldTransformerRegistry from '../../registries/listFieldTransformerRegistry';

jest.mock('../../../../utils/Translator', () => ({
    translate: function(key) {
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

beforeEach(() => {
    listFieldTransformerRegistry.get.mockReturnValue(new StringFieldTransformer());
});

test('Render data with schema', () => {
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
            ghostLocale: 'en',
        },
    ];
    const schema = {
        title: {
            type: 'string',
            sortable: true,
            visibility: 'no',
            label: 'Title',
        },
        description: {
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Description',
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

test('Render data with skin', () => {
    const data = [];

    const schema = {
        title: {
            type: 'string',
            sortable: true,
            visibility: 'no',
            label: 'Title',
        },
        description: {
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Description',
        },
    };
    const tableAdapter = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            options={{
                skin: 'light',
            }}
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
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
        description: {
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
            type: 'string',
            sortable: true,
            visibility: 'no',
            label: 'Title',
        },
        description: {
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Description',
        },
        test1: {
            type: 'string',
            sortable: true,
            visibility: 'always',
            label: 'Test 1',
        },
        test2: {
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
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
            onItemClick={rowEditClickSpy}
            page={1}
            pageCount={3}
            schema={schema}
        />
    );

    expect(tableAdapter).toMatchSnapshot();
});

test('Render correct button based on permissions when item permissions are provided', () => {
    const rowEditClickSpy = jest.fn();
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
            onItemClick={rowEditClickSpy}
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

test('Render data with pencil button and given actions when onItemEdit callback is passed', () => {
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const actions = [
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
            actions={actions}
            data={data}
            onItemClick={rowEditClickSpy}
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
        description: {
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
        description: {
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
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

test('Click on action should execute its callback', () => {
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const actions = [
        {
            icon: 'su-process',
            onClick: jest.fn(),
        },
    ];

    const tableAdapter = shallow(
        <TableAdapter
            {...listAdapterDefaultProps}
            actions={actions}
            data={data}
            onItemClick={rowEditClickSpy}
            page={1}
            pageCount={3}
            schema={schema}
        />
    );
    const buttons = tableAdapter.find('Table').prop('buttons');
    expect(buttons).toHaveLength(2);
    expect(buttons[1].icon).toBe('su-process');

    buttons[1].onClick(1);
    expect(actions[0].onClick).toBeCalledWith(1);
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
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
