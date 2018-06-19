// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import ColumnListAdapter from '../../adapters/ColumnListAdapter';

test('Render data with edit button', () => {
    const data = [
        [
            {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
            },
            {
                id: 2,
                title: 'Page 2',
                hasChildren: false,
            },
        ],
        [
            {
                id: 4,
                title: 'Page 2.1',
                hasChildren: true,
            },
        ],
        [],
    ];

    const columnListAdapter = render(
        <ColumnListAdapter
            active={4}
            activeItems={[2, 4]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onItemActivation={jest.fn()}
            onItemClick={jest.fn()}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(columnListAdapter).toMatchSnapshot();
});

test('Render data without edit button', () => {
    const data = [
        [
            {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
            },
        ],
    ];

    const columnListAdapter = render(
        <ColumnListAdapter
            active={4}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(columnListAdapter).toMatchSnapshot();
});

test('Render data with selection', () => {
    const data = [
        [
            {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
            },
        ],
    ];

    const columnListAdapter = render(
        <ColumnListAdapter
            active={4}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={jest.fn()}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[1]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(columnListAdapter).toMatchSnapshot();
});

test('Render data with disabled items', () => {
    const data = [
        [
            {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
            },
        ],
        [
            {
                id: 3,
                title: 'Page 1.1',
                hasChildren: false,
            },
        ],
        [],
    ];

    const columnListAdapter = render(
        <ColumnListAdapter
            active={3}
            activeItems={[1, 3]}
            data={data}
            disabledIds={[3]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={jest.fn()}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[1]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(columnListAdapter).toMatchSnapshot();
});

test('Render with add button in toolbar when onAddClick callback is given', () => {
    const data = [
        [],
    ];

    const columnListAdapter = render(
        <ColumnListAdapter
            active={4}
            activeItems={undefined}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={jest.fn()}
            onAllSelectionChange={undefined}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(columnListAdapter).toMatchSnapshot();
});

test('Render data with loading column', () => {
    const data = [
        [
            {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
            },
            {
                id: 2,
                title: 'Page 2',
                hasChildren: false,
            },
        ],
        [],
    ];

    const columnListAdapter = render(
        <ColumnListAdapter
            active={1}
            activeItems={[1]}
            data={data}
            disabledIds={[]}
            loading={true}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(columnListAdapter).toMatchSnapshot();
});

test('Execute onItemActivation callback when an item is clicked with the correct parameter', () => {
    const onItemActivationSpy = jest.fn();

    const data = [
        [
            {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
            },
            {
                id: 2,
                title: 'Page 2',
                hasChildren: false,
            },
        ],
        [
            {
                id: 3,
                title: 'Page 1.1',
                hasChildren: false,
            },
        ],
    ];

    const columnListAdapter = mount(
        <ColumnListAdapter
            active={3}
            activeItems={[1, 3]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onItemActivation={onItemActivationSpy}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    columnListAdapter.find('Item').at(1).simulate('click');

    expect(onItemActivationSpy).toBeCalledWith(2);
});

test('Execute onItemSelectionChange callback when an item is selected', () => {
    const itemSelectionChangeSpy = jest.fn();

    const data = [
        [
            {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
            },{
                id: 2,
                title: 'Page 2',
                hasChildren: false,
            },
        ],
    ];

    const columnListAdapter = mount(
        <ColumnListAdapter
            active={3}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={itemSelectionChangeSpy}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[2]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    columnListAdapter.find('Item').at(1).find('.su-check').simulate('click');
    expect(itemSelectionChangeSpy).toHaveBeenLastCalledWith(2, false);

    columnListAdapter.find('Item').at(0).find('.su-check').simulate('click');
    expect(itemSelectionChangeSpy).toHaveBeenLastCalledWith(1, true);
});
