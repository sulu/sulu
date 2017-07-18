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
    const tableAdapter = render(<TableAdapter data={data} schema={schema} />);

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
    const tableAdapter = render(<TableAdapter data={data} schema={schema} />);

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
    const tableAdapter = render(<TableAdapter data={data} schema={schema} />);

    expect(tableAdapter).toMatchSnapshot();
});

test('Render data with pencil button when onRowEdit callback is passed', () => {
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
    const tableAdapter = render(<TableAdapter data={data} schema={schema} onRowEditClick={rowEditClickSpy} />);

    expect(tableAdapter).toMatchSnapshot();
});

test('Click on pencil should execute onRowEdit callback', () => {
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
    const tableAdapter = shallow(<TableAdapter data={data} schema={schema} onRowEditClick={rowEditClickSpy} />);
    const buttons = tableAdapter.find('Table').prop('buttons');
    expect(buttons).toHaveLength(1);
    expect(buttons[0].icon).toBe('pencil');

    buttons[0].onClick(1);
    expect(rowEditClickSpy).toBeCalledWith(1);
});
