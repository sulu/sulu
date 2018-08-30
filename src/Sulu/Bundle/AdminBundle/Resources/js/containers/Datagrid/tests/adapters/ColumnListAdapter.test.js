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
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemAdd={jest.fn()}
            onItemClick={jest.fn()}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={undefined}
            onRequestItemOrder={undefined}
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
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemAdd={undefined}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={undefined}
            onRequestItemOrder={undefined}
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
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemClick={undefined}
            onItemAdd={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={jest.fn()}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={undefined}
            onRequestItemOrder={undefined}
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
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemClick={undefined}
            onItemAdd={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={jest.fn()}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={undefined}
            onRequestItemOrder={undefined}
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

test('Render with add button in toolbar when onItemAdd callback is given', () => {
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
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemAdd={jest.fn()}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={undefined}
            onRequestItemOrder={undefined}
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
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemAdd={undefined}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={undefined}
            onRequestItemOrder={undefined}
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

test('Execute onItemActivate callback when an item is clicked with the correct parameter', () => {
    const itemActivateSpy = jest.fn();

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
            onAllSelectionChange={undefined}
            onItemActivate={itemActivateSpy}
            onItemAdd={undefined}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={undefined}
            onRequestItemOrder={undefined}
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

    expect(itemActivateSpy).toBeCalledWith(2);
});

test('Do not show order button if onRequestItemOrder callback is undefined', () => {
    const data = [
        [
            {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
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
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemAdd={undefined}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={jest.fn()}
            onRequestItemOrder={undefined}
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
    expect(columnListAdapter.find('Toolbar button').find({children: 'sulu_admin.order'})).toHaveLength(0);
});

test('Call onRequestItemOrder callback when a item ordering has been changed', () => {
    const requestItemOrderSpy = jest.fn();

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
    ];

    const columnListAdapter = mount(
        <ColumnListAdapter
            active={1}
            activeItems={[1, 3]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemAdd={undefined}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={undefined}
            onRequestItemOrder={requestItemOrderSpy}
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
    columnListAdapter.find('ToolbarDropdownListOption').find({children: 'sulu_admin.order'}).at(0).prop('onClick')(0);
    columnListAdapter.update();

    columnListAdapter.find('Item Input').at(0).prop('onChange')(5);
    columnListAdapter.find('Item Input').at(0).prop('onBlur')();

    expect(requestItemOrderSpy).toBeCalledWith(1, 2);
});

test('Do not execute onItemActivate callback when a column is ordering', () => {
    const itemActivateSpy = jest.fn();

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
            active={1}
            activeItems={[1, 3]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAllSelectionChange={undefined}
            onItemActivate={itemActivateSpy}
            onItemAdd={undefined}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={undefined}
            onRequestItemOrder={jest.fn()}
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
    columnListAdapter.find('ToolbarDropdownListOption').find({children: 'sulu_admin.order'}).at(0).prop('onClick')(0);

    columnListAdapter.find('Item').at(0).simulate('click');
    columnListAdapter.find('Item').at(1).simulate('click');

    expect(itemActivateSpy).not.toBeCalled();
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
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemAdd={undefined}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={itemSelectionChangeSpy}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={undefined}
            onRequestItemOrder={undefined}
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

test('Execute onRequestItemCopy callback when an item is moved with the correct id', () => {
    const copyClickSpy = jest.fn();

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
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemAdd={undefined}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onRequestItemCopy={copyClickSpy}
            onRequestItemDelete={undefined}
            onRequestItemMove={undefined}
            onRequestItemOrder={undefined}
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

    expect(copyClickSpy).toBeCalledWith(3);
});

test('Execute onRequestItemMove callback when an item is moved with the correct id', () => {
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
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemAdd={undefined}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={undefined}
            onRequestItemMove={moveClickSpy}
            onRequestItemOrder={undefined}
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

test('Execute onRequestItemDelete callback when an item is deleted with the correct id', () => {
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
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemAdd={undefined}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={deleteClickSpy}
            onRequestItemMove={undefined}
            onRequestItemOrder={undefined}
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
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemAdd={undefined}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={jest.fn()}
            onRequestItemOrder={undefined}
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
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemAdd={undefined}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={jest.fn()}
            onRequestItemOrder={undefined}
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
            onAllSelectionChange={undefined}
            onItemActivate={jest.fn()}
            onItemAdd={undefined}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={undefined}
            onRequestItemMove={undefined}
            onRequestItemOrder={undefined}
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
