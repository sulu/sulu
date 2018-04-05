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
            disabledIds={[]}
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
            disabledIds={[]}
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
            disabledIds={[]}
            loading={false}
            onItemSelectionChange={jest.fn()}
            onPageChange={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[1]}
        />
    );

    expect(columnListAdapter).toMatchSnapshot();
});

test('Render data with disabled items', () => {
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
            active={3}
            data={data}
            disabledIds={[3]}
            loading={false}
            onItemSelectionChange={jest.fn()}
            onPageChange={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[1]}
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
            disabledIds={[]}
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
            disabledIds={[]}
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
            disabledIds={[]}
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

test('Execute onItemSelectionChange callback when an item is selected', () => {
    const itemSelectionChangeSpy = jest.fn();

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

    const columnListAdapter = mount(
        <ColumnListAdapter
            active={3}
            data={data}
            disabledIds={[]}
            loading={false}
            onItemSelectionChange={itemSelectionChangeSpy}
            onPageChange={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[2]}
        />
    );

    columnListAdapter.find('Item').at(1).find('.su-checkmark').simulate('click');
    expect(itemSelectionChangeSpy).toHaveBeenLastCalledWith(2, false);

    columnListAdapter.find('Item').at(0).find('.su-checkmark').simulate('click');
    expect(itemSelectionChangeSpy).toHaveBeenLastCalledWith(1, true);
});
