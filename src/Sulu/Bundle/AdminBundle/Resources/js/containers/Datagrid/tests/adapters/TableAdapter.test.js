// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import TableAdapter from '../../adapters/TableAdapter';
import StringFieldTransformer from '../../fieldTransformers/StringFieldTransformer';
import datagridFieldTransformerRegistry from '../../registries/DatagridFieldTransformerRegistry';

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

jest.mock('../../registries/DatagridFieldTransformerRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

beforeEach(() => {
    datagridFieldTransformerRegistry.get.mockReturnValue(new StringFieldTransformer());
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
            active={undefined}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            page={2}
            pageCount={5}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            schema={schema}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
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
            active={undefined}
            activeItems={[]}
            data={[]}
            disabledIds={[]}
            loading={false}
            page={2}
            pageCount={5}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={sortSpy}
            schema={schema}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
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
            active={undefined}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            page={2}
            pageCount={5}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            schema={schema}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
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
            active={undefined}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={jest.fn()}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[1, 3]}
            sortColumn={undefined}
            sortOrder={undefined}
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
            active={undefined}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={2}
            pageCount={3}
            schema={schema}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
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
            active={undefined}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
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
            active={undefined}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={rowEditClickSpy}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
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
            active={undefined}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[]}
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
            active={undefined}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[]}
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
            active={undefined}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemDeactivation={jest.fn()}
            onItemClick={rowEditClickSpy}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );
    const buttons = tableAdapter.find('Table').prop('buttons');
    expect(buttons).toHaveLength(1);
    expect(buttons[0].icon).toBe('su-pen');

    buttons[0].onClick(1);
    expect(rowEditClickSpy).toBeCalledWith(1);
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
            active={undefined}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={rowSelectionChangeSpy}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
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
            active={undefined}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={allSelectionChangeSpy}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(tableAdapter.find('Table').get(0).props.onAllSelectionChange).toBe(allSelectionChangeSpy);
});

test('Pagination should be passed correct props', () => {
    const pageChangeSpy = jest.fn();
    const tableAdapter = shallow(
        <TableAdapter
            active={undefined}
            activeItems={[]}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={pageChangeSpy}
            onSort={jest.fn()}
            page={2}
            pageCount={7}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );
    expect(tableAdapter.find('Pagination').get(0).props).toEqual({
        total: 7,
        current: 2,
        loading: false,
        onChange: pageChangeSpy,
        children: expect.anything(),
    });
});
