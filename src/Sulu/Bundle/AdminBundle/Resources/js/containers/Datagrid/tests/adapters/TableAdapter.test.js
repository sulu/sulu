// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import TableAdapter from '../../adapters/TableAdapter';

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
        title: {},
        description: {},
    };
    const tableAdapter = render(
        <TableAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            page={2}
            pageCount={5}
            onPageChange={jest.fn()}
            schema={schema}
            selections={[]}
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
        title: {},
        description: {},
    };
    const tableAdapter = render(
        <TableAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            onItemSelectionChange={jest.fn()}
            onPageChange={jest.fn()}
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
        description: {},
        title: {},
    };
    const tableAdapter = render(
        <TableAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            onPageChange={jest.fn()}
            page={2}
            pageCount={3}
            schema={schema}
            selections={[]}
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
        title: {},
    };
    const tableAdapter = render(
        <TableAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            onPageChange={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[]}
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
        title: {},
    };
    const tableAdapter = render(
        <TableAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            onItemClick={rowEditClickSpy}
            onPageChange={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[]}
        />
    );

    expect(tableAdapter).toMatchSnapshot();
});

test('Click on pencil should execute onItemEdit callback', () => {
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
        title: {},
    };
    const tableAdapter = shallow(
        <TableAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            onItemClick={rowEditClickSpy}
            onPageChange={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[]}
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
        title: {},
    };
    const tableAdapter = shallow(
        <TableAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            onItemSelectionChange={rowSelectionChangeSpy}
            onPageChange={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[]}
        />
    );

    expect(tableAdapter.find('Table').get(0).props.onRowSelectionChange).toBe(rowSelectionChangeSpy);
});

test('Click on checkbox in header should call onAllSelectionChange callback', () => {
    const allSelectionChangeSpy = jest.fn();
    const data = [];
    const schema = {
        title: {},
    };
    const tableAdapter = shallow(
        <TableAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            onAllSelectionChange={allSelectionChangeSpy}
            onPageChange={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[]}
        />
    );

    expect(tableAdapter.find('Table').get(0).props.onAllSelectionChange).toBe(allSelectionChangeSpy);
});

test('Pagination should be passed correct props', () => {
    const pageChangeSpy = jest.fn();
    const tableAdapter = shallow(
        <TableAdapter
            disabledIds={[]}
            loading={false}
            onPageChange={pageChangeSpy}
            page={2}
            pageCount={7}
            schema={{}}
            selections={[]}
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
