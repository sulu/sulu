// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import ColumnListAdapter from '../../adapters/ColumnListAdapter';

jest.mock('../../../../utils/Translator', () => ({
    translate: (key) => key,
}));

test('Render different kind of data with edit button', () => {
    const data = [
        [
            {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
                publishedState: false,
            },
            {
                id: 2,
                title: 'Page 2',
                hasChildren: false,
                publishedState: false,
                published: '2017-08-23',
            },
        ],
        [
            {
                id: 4,
                title: 'Page 2.1',
                hasChildren: true,
                type: {
                    name: 'ghost',
                    value: 'nl',
                },
            },
            {
                id: 5,
                title: 'Page 2.2',
                hasChildren: true,
                publishedState: false,
                published: '2017-07-02',
                type: {
                    name: 'ghost',
                    value: 'nl',
                },
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
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={jest.fn()}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onMoveClick={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
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
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onMoveClick={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
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
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={jest.fn()}
            onMoveClick={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
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
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={jest.fn()}
            onMoveClick={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
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
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={jest.fn()}
            onAllSelectionChange={undefined}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onMoveClick={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
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
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onMoveClick={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
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
            onDeleteClick={jest.fn()}
            onItemActivation={onItemActivationSpy}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onMoveClick={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
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
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={itemSelectionChangeSpy}
            onMoveClick={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
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

test('Execute onMoveClick callback when an item is moved with the correct id', () => {
    const moveClickSpy = jest.fn();

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
            onDeleteClick={undefined}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onMoveClick={moveClickSpy}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    columnListAdapter.find('ToolbarDropdown').simulate('click');
    columnListAdapter.find('ToolbarDropdown').find('ToolbarDropdownListOption button').at(0).simulate('click');

    expect(moveClickSpy).toBeCalledWith(3);
});

test('Execute onDeleteClick callback when an item is deleted with the correct id', () => {
    const deleteClickSpy = jest.fn();

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
            onDeleteClick={deleteClickSpy}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onMoveClick={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    columnListAdapter.find('ToolbarDropdown').simulate('click');
    columnListAdapter.find('ToolbarDropdown').find('ToolbarDropdownListOption button').at(0).simulate('click');

    expect(deleteClickSpy).toBeCalledWith(3);
});

test('Enable delete and move button if an item in this column has been activated', () => {
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
        [],
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
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onMoveClick={jest.fn()}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    columnListAdapter.find('Toolbar ToolbarDropdown').simulate('click');
    expect(columnListAdapter.find('Toolbar ToolbarDropdown button').at(0).prop('disabled')).toEqual(false);
    expect(columnListAdapter.find('Toolbar ToolbarDropdown button').at(1).prop('disabled')).toEqual(false);
});

test('Disable delete and move button if no item in this column has been activated', () => {
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
        [],
    ];

    const columnListAdapter = mount(
        <ColumnListAdapter
            active={3}
            activeItems={[1]}
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
            onMoveClick={jest.fn()}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    columnListAdapter.find('Toolbar ToolbarDropdown').simulate('click');
    expect(columnListAdapter.find('Toolbar ToolbarDropdown button').at(0).prop('disabled')).toEqual(true);
    expect(columnListAdapter.find('Toolbar ToolbarDropdown button').at(1).prop('disabled')).toEqual(true);
});

test('Do not show settings if no options are available', () => {
    const columnListAdapter = mount(
        <ColumnListAdapter
            active={3}
            activeItems={[1]}
            data={[]}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onDeleteClick={undefined}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={undefined}
            onMoveClick={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(columnListAdapter.find('Toolbar ToolbarDropdown')).toHaveLength(0);
});
