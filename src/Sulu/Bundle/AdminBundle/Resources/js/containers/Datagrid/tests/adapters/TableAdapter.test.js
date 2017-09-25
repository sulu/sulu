/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, shallow} from 'enzyme';
import TableAdapter from '../../adapters/TableAdapter';

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
    const tableAdapter = render(<TableAdapter data={data} schema={schema} selections={[]} />);

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
    const tableAdapter = render(<TableAdapter data={data} schema={schema} selections={[1, 3]} />);

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
    const tableAdapter = render(<TableAdapter data={data} schema={schema} selections={[]} />);

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
    const tableAdapter = render(<TableAdapter data={data} schema={schema} selections={[]} />);

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
        <TableAdapter data={data} schema={schema} onItemEditClick={rowEditClickSpy} selections={[]} />
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
        <TableAdapter data={data} schema={schema} onItemEditClick={rowEditClickSpy} selections={[]} />
    );
    const buttons = tableAdapter.find('Table').prop('buttons');
    expect(buttons).toHaveLength(1);
    expect(buttons[0].icon).toBe('pencil');

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
        <TableAdapter data={data} schema={schema} onItemSelectionChange={rowSelectionChangeSpy} selections={[]} />
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
        <TableAdapter data={data} schema={schema} onAllSelectionChange={allSelectionChangeSpy} selections={[]} />
    );

    expect(tableAdapter.find('Table').get(0).props.onAllSelectionChange).toBe(allSelectionChangeSpy);
});
