// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
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
            {
                id: 6,
                title: 'Page 3',
                hasChildren: false,
                publishedState: false,
                published: '2017-08-23',
                linked: 'internal',
            },
            {
                id: 7,
                title: 'Page 4',
                hasChildren: false,
                publishedState: true,
                published: '2017-08-23',
                linked: 'internal',
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
            {
                id: 8,
                title: 'Page 2.3',
                hasChildren: false,
                publishedState: false,
                published: '2017-08-23',
                linked: 'external',
            },
            {
                id: 9,
                title: 'Page 2.4',
                hasChildren: false,
                publishedState: true,
                published: '2017-08-23',
                linked: 'external',
            },
        ],
        [
            {
                id: 10,
                title: 'Page 2.1.1',
                hasChildren: false,
                publishedState: false,
                published: null,
                type: {
                    name: 'shadow',
                },
            },
            {
                id: 11,
                title: 'Page 2.1.2',
                hasChildren: false,
                publishedState: true,
                published: '2018-10-16',
                type: {
                    name: 'shadow',
                },
            },
        ],
        [],
    ];

    const columnListAdapter = render(
        <ColumnListAdapter
            {...listAdapterDefaultProps}
            activeItems={[2, 4]}
            data={data}
            onItemAdd={jest.fn()}
            onItemClick={jest.fn()}
            onRequestItemDelete={jest.fn()}
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
            {...listAdapterDefaultProps}
            activeItems={[]}
            data={data}
            onRequestItemDelete={jest.fn()}
        />
    );

    expect(columnListAdapter).toMatchSnapshot();
});

test('Render data with name as fallback for title', () => {
    const data = [
        [
            {
                id: 1,
                name: 'Page 1',
            },
        ],
    ];

    const columnListAdapter = render(
        <ColumnListAdapter
            {...listAdapterDefaultProps}
            activeItems={[]}
            data={data}
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
            {...listAdapterDefaultProps}
            activeItems={[]}
            data={data}
            onItemSelectionChange={jest.fn()}
            onRequestItemDelete={jest.fn()}
            selections={[1]}
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
            {...listAdapterDefaultProps}
            activeItems={[1, 3]}
            data={data}
            disabledIds={[3]}
            onItemSelectionChange={jest.fn()}
            onRequestItemDelete={jest.fn()}
            selections={[1]}
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
            {...listAdapterDefaultProps}
            activeItems={[]}
            data={data}
            onItemAdd={jest.fn()}
            onRequestItemDelete={jest.fn()}
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
            {...listAdapterDefaultProps}
            activeItems={[1]}
            data={data}
            loading={true}
            onRequestItemDelete={jest.fn()}
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
            {...listAdapterDefaultProps}
            activeItems={[1, 3]}
            data={data}
            onItemActivate={itemActivateSpy}
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
            {...listAdapterDefaultProps}
            activeItems={[1, 3]}
            data={data}
            onRequestItemMove={jest.fn()}
            onRequestItemOrder={undefined}
        />
    );

    columnListAdapter.find('Toolbar ToolbarDropdown').simulate('click');
    expect(columnListAdapter.find('Toolbar button').find({children: 'sulu_admin.order'})).toHaveLength(0);
});

test('Call onRequestItemOrder callback when an item ordering has been changed', () => {
    const requestItemOrderPromise = Promise.resolve({ordered: true});
    const requestItemOrderSpy = jest.fn().mockReturnValue(requestItemOrderPromise);

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
            {...listAdapterDefaultProps}
            activeItems={[1, 3]}
            data={data}
            onRequestItemOrder={requestItemOrderSpy}
        />
    );

    columnListAdapter.find('Toolbar ToolbarDropdown a').simulate('click');
    columnListAdapter.find('ToolbarDropdown').find('ArrowMenu Action[children="sulu_admin.order"]').prop('onClick')(0);

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
            {...listAdapterDefaultProps}
            activeItems={[1, 3]}
            data={data}
            onItemActivate={itemActivateSpy}
            onRequestItemOrder={jest.fn()}
        />
    );

    columnListAdapter.find('Toolbar ToolbarDropdown a').simulate('click');
    columnListAdapter.find('ToolbarDropdown').find('ArrowMenu Action[children="sulu_admin.order"]').prop('onClick')(0)

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
            }, {
                id: 2,
                title: 'Page 2',
                hasChildren: false,
            },
        ],
    ];

    const columnListAdapter = mount(
        <ColumnListAdapter
            {...listAdapterDefaultProps}
            activeItems={[]}
            data={data}
            onItemSelectionChange={itemSelectionChangeSpy}
            selections={[2]}
        />
    );

    columnListAdapter.find('Item').at(1).find('.su-check').simulate('click');
    expect(itemSelectionChangeSpy).toHaveBeenLastCalledWith(2, false);

    columnListAdapter.find('Item').at(0).find('.su-check').simulate('click');
    expect(itemSelectionChangeSpy).toHaveBeenLastCalledWith(1, true);
});

test('Execute onRequestItemCopy callback when an item is copied with the correct id', () => {
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
            {...listAdapterDefaultProps}
            activeItems={[1, 3]}
            data={data}
            onRequestItemCopy={copyClickSpy}
        />
    );

    columnListAdapter.find('ToolbarDropdown a').simulate('click');
    columnListAdapter.find('ToolbarDropdown').find('ArrowMenu Action[children="sulu_admin.copy"]').simulate('click');

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
            {...listAdapterDefaultProps}
            activeItems={[1, 3]}
            data={data}
            onRequestItemMove={moveClickSpy}
        />
    );

    columnListAdapter.find('ToolbarDropdown a').simulate('click');
    columnListAdapter.find('ToolbarDropdown').find('ArrowMenu Action[children="sulu_admin.move"]').simulate('click');

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
            {...listAdapterDefaultProps}
            activeItems={[1, 3]}
            data={data}
            onRequestItemDelete={deleteClickSpy}
        />
    );

    columnListAdapter.find('ToolbarDropdown a').simulate('click');
    columnListAdapter.find('ToolbarDropdown').find('ArrowMenu Action[children="sulu_admin.delete"]').simulate('click')

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
            {...listAdapterDefaultProps}
            activeItems={[1, 3]}
            data={data}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={jest.fn()}
        />
    );

    columnListAdapter.find('Toolbar ToolbarDropdown a').simulate('click');
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
            {...listAdapterDefaultProps}
            activeItems={[1]}
            data={data}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={jest.fn()}
        />
    );

    columnListAdapter.find('Toolbar ToolbarDropdown a').simulate('click');
    expect(columnListAdapter.find('Toolbar ToolbarDropdown button').at(0).prop('disabled')).toEqual(true);
    expect(columnListAdapter.find('Toolbar ToolbarDropdown button').at(1).prop('disabled')).toEqual(true);
});

test('Do not show settings if no options are available', () => {
    const columnListAdapter = mount(
        <ColumnListAdapter
            {...listAdapterDefaultProps}
            activeItems={[1]}
        />
    );

    expect(columnListAdapter.find('Toolbar ToolbarDropdown')).toHaveLength(0);
});
