// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import ColumnListAdapter from '../../adapters/ColumnListAdapter';

test('Render data with edit button', () => {
    const data = [
        {
            children: [
                {
                    children: [],
                    data: {
                        id: 3,
                        title: 'Page 1.1',
                        hasChildren: false,
                    },
                },
            ],
            data: {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
            },
        },
        {
            children: [
                {
                    children: [],
                    data: {
                        id: 4,
                        title: 'Page 2.1',
                        hasChildren: true,
                    },
                },
            ],
            data: {
                id: 2,
                title: 'Page 2',
                hasChildren: false,
            },
        },
    ];

    const columnListAdapter = render(
        <ColumnListAdapter
            active={4}
            data={data}
            loading={false}
            onItemClick={jest.fn()}
            onPageChange={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
        />
    );

    expect(columnListAdapter).toMatchSnapshot();
});

test('Render data without edit button', () => {
    const data = [
        {
            children: [
                {
                    children: [],
                    data: {
                        id: 3,
                        title: 'Page 1.1',
                        hasChildren: false,
                    },
                },
            ],
            data: {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
            },
        },
    ];

    const columnListAdapter = render(
        <ColumnListAdapter
            active={4}
            data={data}
            loading={false}
            onPageChange={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
        />
    );

    expect(columnListAdapter).toMatchSnapshot();
});

test('Render data with selection', () => {
    const data = [
        {
            children: [
                {
                    children: [],
                    data: {
                        id: 3,
                        title: 'Page 1.1',
                        hasChildren: false,
                    },
                },
            ],
            data: {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
            },
        },
    ];

    const columnListAdapter = render(
        <ColumnListAdapter
            active={4}
            data={data}
            loading={false}
            onItemSelectionChange={jest.fn()}
            onPageChange={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
        />
    );

    expect(columnListAdapter).toMatchSnapshot();
});

test('Render with add button in toolbar when onAddClick callback is given', () => {
    const data = [];

    const columnListAdapter = render(
        <ColumnListAdapter
            active={4}
            data={data}
            loading={false}
            onAddClick={jest.fn()}
            onPageChange={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
        />
    );

    expect(columnListAdapter).toMatchSnapshot();
});

test('Render data with loading column', () => {
    const data = [
        {
            children: [],
            data: {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
            },
        },
        {
            children: [],
            data: {
                id: 2,
                title: 'Page 2',
                hasChildren: false,
            },
        },
    ];

    const columnListAdapter = render(
        <ColumnListAdapter
            active={1}
            data={data}
            loading={true}
            onPageChange={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
        />
    );

    expect(columnListAdapter).toMatchSnapshot();
});

test('Execute onItemActivation callback when an item is clicked with the correct parameter', () => {
    const onItemActivationSpy = jest.fn();

    const data = [
        {
            children: [
                {
                    children: [],
                    data: {
                        id: 3,
                        title: 'Page 1.1',
                        hasChildren: false,
                    },
                },
            ],
            data: {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
            },
        },
        {
            children: [
                {
                    children: [],
                    data: {
                        id: 4,
                        title: 'Page 2.1',
                        hasChildren: true,
                    },
                },
            ],
            data: {
                id: 2,
                title: 'Page 2',
                hasChildren: false,
            },
        },
    ];

    const columnListAdapter = mount(
        <ColumnListAdapter
            active={3}
            data={data}
            loading={false}
            onItemActivation={onItemActivationSpy}
            onPageChange={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
        />
    );

    columnListAdapter.find('Item').at(1).simulate('click');

    expect(onItemActivationSpy).toBeCalledWith(2);
});
