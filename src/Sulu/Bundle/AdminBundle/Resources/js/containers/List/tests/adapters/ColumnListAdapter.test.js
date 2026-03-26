// @flow
import React from 'react';
import {render} from '@testing-library/react';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import ColumnListAdapter from '../../adapters/ColumnListAdapter';

jest.mock('../../../../utils/Translator', () => ({
    translate: (key) => key,
}));

const renderColumnListAdapter = (customProps: Object = {}) => {
    let props = {
        ...listAdapterDefaultProps,
        ...customProps,
    };
    const columnListAdapterRef: any = React.createRef();
    const view = render(<ColumnListAdapter {...props} ref={columnListAdapterRef} />);

    const getInstance = () => {
        if (!columnListAdapterRef.current) {
            throw new Error('Expected ColumnListAdapter to be rendered');
        }

        return columnListAdapterRef.current;
    };

    return {
        ...view,
        getInstance,
        setProps: (newProps: Object) => {
            props = {...props, ...newProps};
            view.rerender(<ColumnListAdapter {...props} ref={columnListAdapterRef} />);
        },
    };
};

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
                _hasPermissions: true,
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
                ghostLocale: 'nl',
            },
            {
                id: 5,
                title: 'Page 2.2',
                hasChildren: true,
                publishedState: false,
                published: '2017-07-02',
                ghostLocale: 'nl',
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
                shadowLocale: 'en',
            },
            {
                id: 11,
                title: 'Page 2.1.2',
                hasChildren: false,
                publishedState: true,
                published: '2018-10-16',
                shadowLocale: 'en',
                _permissions: {
                    view: false,
                },
            },
        ],
        [],
    ];

    const view = render(
        <ColumnListAdapter
            {...listAdapterDefaultProps}
            activeItems={[2, 4]}
            adapterOptions={{get_indicators: (item) => item.hasChildren ? ['has-children-indicator'] : []}}
            data={data}
            onItemAdd={jest.fn()}
            onItemClick={jest.fn()}
            onRequestItemDelete={jest.fn()}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render correct icon in edit button based on permissions', () => {
    const data = [
        [
            {
                // button should not be visible because view permission is missing
                id: 1,
                title: 'Missing view permission',
                hasChildren: false,
                _permissions: {
                    view: false,
                },
            },
            {
                // button should contain eye icon because edit permission is missing
                id: 2,
                title: 'Missing edit permission',
                hasChildren: false,
                _permissions: {
                    edit: false,
                    view: true,
                },
            },
            {
                // button should contain pen icon because user has sufficient permissions
                id: 3,
                title: 'Sufficient Permissions',
                hasChildren: false,
                _permissions: {
                    edit: true,
                    view: true,
                },
            },
            {
                // button should contain plus icon because item is a ghost page
                id: 4,
                title: 'Ghost Page',
                hasChildren: false,
                ghostLocale: 'en',
            },
        ],
        [],
    ];

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [],
        data,
        onItemClick: jest.fn(),
    });
    const columnListAdapterInstance = columnListAdapter.getInstance();
    const firstItemButtons = columnListAdapterInstance.getButtons(data[0][0]);
    const secondItemButtons = columnListAdapterInstance.getButtons(data[0][1]);
    const thirdItemButtons = columnListAdapterInstance.getButtons(data[0][2]);
    const fourthItemButtons = columnListAdapterInstance.getButtons(data[0][3]);

    expect(firstItemButtons[0].icon).toEqual('su-pen');
    expect(firstItemButtons[0].visible).toEqual(false);

    expect(secondItemButtons[0].icon).toEqual('su-eye');
    expect(secondItemButtons[0].visible).toEqual(true);

    expect(thirdItemButtons[0].icon).toEqual('su-pen');
    expect(thirdItemButtons[0].visible).toEqual(true);

    expect(fourthItemButtons[0].icon).toEqual('su-plus-circle');
    expect(fourthItemButtons[0].visible).toEqual(true);
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

    const view = render(
        <ColumnListAdapter
            {...listAdapterDefaultProps}
            activeItems={[]}
            data={data}
            onRequestItemDelete={jest.fn()}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
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

    const view = render(
        <ColumnListAdapter
            {...listAdapterDefaultProps}
            activeItems={[]}
            data={data}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
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

    const view = render(
        <ColumnListAdapter
            {...listAdapterDefaultProps}
            activeItems={[]}
            data={data}
            onItemSelectionChange={jest.fn()}
            onRequestItemDelete={jest.fn()}
            selections={[1]}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
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

    const view = render(
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

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render with add button in toolbar when onItemAdd callback is given', () => {
    const data = [
        [],
    ];

    const view = render(
        <ColumnListAdapter
            {...listAdapterDefaultProps}
            activeItems={[]}
            data={data}
            onItemAdd={jest.fn()}
            onRequestItemDelete={jest.fn()}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render without add button in toolbar when onItemAdd callback is given but permission is not granted', () => {
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
                hasChildren: true,
            },
            {
                id: 4,
                title: 'Page 1.2',
                hasChildren: false,
                _permissions: {
                    add: false,
                },
            },
        ],
        [],
    ];

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [undefined, 1, 4],
        data,
        onItemAdd: jest.fn(),
        onRequestItemDelete: jest.fn(),
    });
    const toolbarItems = columnListAdapter.getInstance().getToolbarItems(2);

    expect(toolbarItems && toolbarItems.find((item) => item.icon === 'su-plus-circle')).toEqual(undefined);
});

test('Render without toolbar for first column if display_root_level_toolbar option is set', () => {
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
                hasChildren: true,
            },
            {
                id: 4,
                title: 'Page 1.2',
                hasChildren: false,
                _permissions: {
                    add: false,
                },
            },
        ],
        [],
    ];

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [undefined, 1, 4],
        adapterOptions: {display_root_level_toolbar: false},
        data,
        onItemAdd: jest.fn(),
        onRequestItemDelete: jest.fn(),
    });

    expect(columnListAdapter.getInstance().getToolbarItems(0)).toHaveLength(0);
    expect(columnListAdapter.getInstance().getToolbarItems(2)).toHaveLength(1);
});

test('Render without toolbar when all actions would be deactivated', () => {
    const data = [
        [
            {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
            },
        ],
    ];

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [undefined],
        data,
    });

    expect(columnListAdapter.getInstance().getToolbarItems(0)).toEqual(undefined);
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

    const view = render(
        <ColumnListAdapter
            {...listAdapterDefaultProps}
            activeItems={[1]}
            data={data}
            loading={true}
            onRequestItemDelete={jest.fn()}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
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

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onItemActivate: itemActivateSpy,
    });

    columnListAdapter.getInstance().handleItemClick(2);

    expect(itemActivateSpy).toBeCalledWith(2);
});

test('Execute onItemClick callback when an item is double-clicked', () => {
    const itemClickSpy = jest.fn();

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

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onItemClick: itemClickSpy,
    });

    columnListAdapter.getInstance().handleItemDoubleClick(2);

    expect(itemClickSpy).toBeCalledWith(2);
});

test('Do not execute onItemClick callback when an item without view permissions is double-clicked', () => {
    const itemClickSpy = jest.fn();

    const data = [
        [
            {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
                _permissions: {
                    view: true,
                },
            },
            {
                id: 2,
                title: 'Page 2',
                hasChildren: false,
                _permissions: {
                    view: false,
                },
            },
        ],
        [
            {
                id: 3,
                title: 'Page 1.1',
                hasChildren: false,
                _permissions: {
                    view: true,
                },
            },
        ],
    ];

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onItemClick: itemClickSpy,
    });

    columnListAdapter.getInstance().handleItemDoubleClick(2);

    expect(itemClickSpy).not.toBeCalled();
});

test('Show all setting buttons', () => {
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

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [undefined, 1],
        data,
        onRequestItemCopy: jest.fn(),
        onRequestItemDelete: jest.fn(),
        onRequestItemMove: jest.fn(),
        onRequestItemOrder: jest.fn(),
    });
    const toolbarItems = columnListAdapter.getInstance().getToolbarItems(0);
    const dropdown = toolbarItems && toolbarItems.find((item) => item.type === 'dropdown');

    expect(dropdown && dropdown.options[0].disabled).toEqual(false);
    expect(dropdown && dropdown.options[1].disabled).toEqual(false);
    expect(dropdown && dropdown.options[2].disabled).toEqual(false);
    expect(dropdown && dropdown.options[3].disabled).toEqual(false);
});

test('Disable delete button if permission is missing', () => {
    const data = [
        [
            {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
                _permissions: {
                    delete: false,
                },
            },
        ],
        [],
    ];

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [undefined, 1],
        data,
        onRequestItemCopy: jest.fn(),
        onRequestItemDelete: jest.fn(),
        onRequestItemMove: jest.fn(),
        onRequestItemOrder: jest.fn(),
    });
    const toolbarItems = columnListAdapter.getInstance().getToolbarItems(0);
    const dropdown = toolbarItems && toolbarItems.find((item) => item.type === 'dropdown');

    expect(dropdown && dropdown.options[0].disabled).toEqual(true);
    expect(dropdown && dropdown.options[1].disabled).toEqual(false);
    expect(dropdown && dropdown.options[2].disabled).toEqual(false);
    expect(dropdown && dropdown.options[3].disabled).toEqual(false);
});

test('Disable move and copy button if permission is missing', () => {
    const data = [
        [
            {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
                _permissions: {
                    edit: false,
                },
            },
        ],
        [],
    ];

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [undefined, 1],
        data,
        onRequestItemCopy: jest.fn(),
        onRequestItemDelete: jest.fn(),
        onRequestItemMove: jest.fn(),
        onRequestItemOrder: jest.fn(),
    });
    const toolbarItems = columnListAdapter.getInstance().getToolbarItems(0);
    const dropdown = toolbarItems && toolbarItems.find((item) => item.type === 'dropdown');

    expect(dropdown && dropdown.options[0].disabled).toEqual(false);
    expect(dropdown && dropdown.options[1].disabled).toEqual(true);
    expect(dropdown && dropdown.options[2].disabled).toEqual(true);
    expect(dropdown && dropdown.options[3].disabled).toEqual(false);
});

test('Disable sort button if edit permission on parent is missing', () => {
    const data = [
        [
            {
                id: 1,
                title: 'Page 1',
                hasChildren: true,
                _permissions: {
                    edit: false,
                },
            },
        ],
        [
            {
                id: 2,
                title: 'Page 1.1',
                hasChildren: true,
            },
        ],
        [],
    ];

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [undefined, 1, 2],
        data,
        onRequestItemCopy: jest.fn(),
        onRequestItemDelete: jest.fn(),
        onRequestItemMove: jest.fn(),
        onRequestItemOrder: jest.fn(),
    });
    const toolbarItems = columnListAdapter.getInstance().getToolbarItems(1);
    const dropdown = toolbarItems && toolbarItems.find((item) => item.type === 'dropdown');

    expect(dropdown && dropdown.options[0].disabled).toEqual(false);
    expect(dropdown && dropdown.options[1].disabled).toEqual(false);
    expect(dropdown && dropdown.options[2].disabled).toEqual(false);
    expect(dropdown && dropdown.options[3].disabled).toEqual(true);
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

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onRequestItemMove: jest.fn(),
        onRequestItemOrder: undefined,
    });
    const toolbarItems = columnListAdapter.getInstance().getToolbarItems(0);
    const dropdown = toolbarItems && toolbarItems.find((item) => item.type === 'dropdown');

    expect(dropdown && dropdown.options.find((option) => option.label === 'sulu_admin.order')).toEqual(undefined);
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

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onRequestItemOrder: requestItemOrderSpy,
    });
    const toolbarItems = columnListAdapter.getInstance().getToolbarItems(0);
    const dropdown = toolbarItems && toolbarItems.find((item) => item.type === 'dropdown');
    const orderOption = dropdown && dropdown.options.find((option) => option.label === 'sulu_admin.order');
    if (!orderOption) {
        throw new Error('Expected order option to be available');
    }

    orderOption.onClick(0);
    columnListAdapter.getInstance().handleOrderChange(1, 5);

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

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onItemActivate: itemActivateSpy,
        onRequestItemOrder: jest.fn(),
    });
    const toolbarItems = columnListAdapter.getInstance().getToolbarItems(0);
    const dropdown = toolbarItems && toolbarItems.find((item) => item.type === 'dropdown');
    const orderOption = dropdown && dropdown.options.find((option) => option.label === 'sulu_admin.order');
    if (!orderOption) {
        throw new Error('Expected order option to be available');
    }

    orderOption.onClick(0);
    columnListAdapter.getInstance().handleItemClick(1);
    columnListAdapter.getInstance().handleItemClick(2);

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

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [],
        data,
        onItemSelectionChange: itemSelectionChangeSpy,
        selections: [2],
    });

    columnListAdapter.getInstance().handleItemSelectionChange(2);
    expect(itemSelectionChangeSpy).toHaveBeenLastCalledWith(2, false);

    columnListAdapter.getInstance().handleItemSelectionChange(1);
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

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onRequestItemCopy: copyClickSpy,
    });
    const toolbarItems = columnListAdapter.getInstance().getToolbarItems(0);
    const dropdown = toolbarItems && toolbarItems.find((item) => item.type === 'dropdown');
    const copyOption = dropdown && dropdown.options.find((option) => option.label === 'sulu_admin.copy');
    if (!copyOption) {
        throw new Error('Expected copy option to be available');
    }

    copyOption.onClick();

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

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onRequestItemMove: moveClickSpy,
    });
    const toolbarItems = columnListAdapter.getInstance().getToolbarItems(0);
    const dropdown = toolbarItems && toolbarItems.find((item) => item.type === 'dropdown');
    const moveOption = dropdown && dropdown.options.find((option) => option.label === 'sulu_admin.move');
    if (!moveOption) {
        throw new Error('Expected move option to be available');
    }

    moveOption.onClick();

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

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onRequestItemDelete: deleteClickSpy,
    });
    const toolbarItems = columnListAdapter.getInstance().getToolbarItems(0);
    const dropdown = toolbarItems && toolbarItems.find((item) => item.type === 'dropdown');
    const deleteOption = dropdown && dropdown.options.find((option) => option.label === 'sulu_admin.delete');
    if (!deleteOption) {
        throw new Error('Expected delete option to be available');
    }

    deleteOption.onClick();

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

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onRequestItemDelete: jest.fn(),
        onRequestItemMove: jest.fn(),
    });
    const toolbarItems = columnListAdapter.getInstance().getToolbarItems(0);
    const dropdown = toolbarItems && toolbarItems.find((item) => item.type === 'dropdown');

    expect(dropdown && dropdown.options[0].disabled).toEqual(false);
    expect(dropdown && dropdown.options[1].disabled).toEqual(false);
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

    const columnListAdapter = renderColumnListAdapter({
        activeItems: [1],
        data,
        onRequestItemDelete: jest.fn(),
        onRequestItemMove: jest.fn(),
    });
    const toolbarItems = columnListAdapter.getInstance().getToolbarItems(0);
    const dropdown = toolbarItems && toolbarItems.find((item) => item.type === 'dropdown');

    expect(dropdown && dropdown.options[0].disabled).toEqual(true);
    expect(dropdown && dropdown.options[1].disabled).toEqual(true);
});

test('Do not show settings if no options are available', () => {
    const columnListAdapter = renderColumnListAdapter({
        activeItems: [1],
    });

    expect(columnListAdapter.getInstance().getToolbarItems(0)).toEqual(undefined);
});
