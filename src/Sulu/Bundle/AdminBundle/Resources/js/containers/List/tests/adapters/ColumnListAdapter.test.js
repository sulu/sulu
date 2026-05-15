// @flow
import React from 'react';
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import ColumnListAdapter from '../../adapters/ColumnListAdapter';

const renderColumnListAdapter = (customProps: Object = {}) => {
    const props = {
        ...listAdapterDefaultProps,
        ...customProps,
    };

    return render(<ColumnListAdapter {...props} />);
};

const getIconButton = (name: string): HTMLElement => {
    const button = screen.getByLabelText(name).closest('button');

    if (!(button instanceof HTMLElement)) {
        throw new Error('Expected button with icon "' + name + '"');
    }

    return button;
};

const getItemElement = (label: string): HTMLElement => {
    const item = screen.getByLabelText(label).closest('.item');

    if (!(item instanceof HTMLElement)) {
        throw new Error('Expected item with label "' + label + '"');
    }

    return item;
};

const getItemIcon = (item: HTMLElement, icon: string): HTMLElement => {
    const iconElement = item.querySelector('[aria-label="' + icon + '"]');

    if (!(iconElement instanceof HTMLElement)) {
        throw new Error('Expected item icon "' + icon + '"');
    }

    return iconElement;
};

const openToolbarDropdown = async(user: Object) => {
    await user.click(getIconButton('su-cog'));
};

const hoverColumn = async(user: Object, index: number) => {
    const column = document.querySelectorAll('.column')[index];

    if (!(column instanceof HTMLElement)) {
        throw new Error('Expected column with index "' + index + '"');
    }

    await user.hover(column);
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

    renderColumnListAdapter({
        activeItems: [],
        data,
        onItemClick: jest.fn(),
    });

    expect(getItemIcon(getItemElement('Missing view permission'), 'su-pen')).not.toHaveClass('visible');
    expect(getItemIcon(getItemElement('Missing edit permission'), 'su-eye')).toHaveClass('visible');
    expect(getItemIcon(getItemElement('Sufficient Permissions'), 'su-pen')).toHaveClass('visible');
    expect(getItemIcon(getItemElement('Ghost Page'), 'su-plus-circle')).toHaveClass('visible');
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

test('Render without add button in toolbar when onItemAdd callback is given but permission is not granted', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [undefined, 1, 4],
        data,
        onItemAdd: jest.fn(),
        onRequestItemDelete: jest.fn(),
    });
    await hoverColumn(user, 2);

    expect(screen.queryByLabelText('su-plus-circle')).not.toBeInTheDocument();
});

test('Render without toolbar for first column if display_root_level_toolbar option is set', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [undefined, 1, 4],
        adapterOptions: {display_root_level_toolbar: false},
        data,
        onItemAdd: jest.fn(),
        onRequestItemDelete: jest.fn(),
    });

    expect(screen.queryByLabelText('su-cog')).not.toBeInTheDocument();

    await hoverColumn(user, 2);

    expect(screen.getByLabelText('su-cog')).toBeInTheDocument();
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

    renderColumnListAdapter({
        activeItems: [undefined],
        data,
    });

    expect(screen.queryByLabelText('su-cog')).not.toBeInTheDocument();
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

test('Execute onItemActivate callback when an item is clicked with the correct parameter', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onItemActivate: itemActivateSpy,
    });

    await user.click(getItemElement('Page 2'));

    expect(itemActivateSpy).toBeCalledWith(2);
});

test('Execute onItemClick callback when an item is double-clicked', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onItemClick: itemClickSpy,
    });

    await user.dblClick(getItemElement('Page 2'));

    expect(itemClickSpy).toBeCalledWith(2);
});

test('Do not execute onItemClick callback when an item without view permissions is double-clicked', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onItemClick: itemClickSpy,
    });

    await user.dblClick(getItemElement('Page 2'));

    expect(itemClickSpy).not.toBeCalled();
});

test('Show all setting buttons', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [undefined, 1],
        data,
        onRequestItemCopy: jest.fn(),
        onRequestItemDelete: jest.fn(),
        onRequestItemMove: jest.fn(),
        onRequestItemOrder: jest.fn(),
    });

    await openToolbarDropdown(user);

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.move'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.copy'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.order'})).toBeEnabled();
});

test('Disable delete button if permission is missing', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [undefined, 1],
        data,
        onRequestItemCopy: jest.fn(),
        onRequestItemDelete: jest.fn(),
        onRequestItemMove: jest.fn(),
        onRequestItemOrder: jest.fn(),
    });

    await openToolbarDropdown(user);

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.move'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.copy'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.order'})).toBeEnabled();
});

test('Disable move and copy button if permission is missing', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [undefined, 1],
        data,
        onRequestItemCopy: jest.fn(),
        onRequestItemDelete: jest.fn(),
        onRequestItemMove: jest.fn(),
        onRequestItemOrder: jest.fn(),
    });

    await openToolbarDropdown(user);

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.move'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.copy'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.order'})).toBeEnabled();
});

test('Disable sort button if edit permission on parent is missing', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [undefined, 1, 2],
        data,
        onRequestItemCopy: jest.fn(),
        onRequestItemDelete: jest.fn(),
        onRequestItemMove: jest.fn(),
        onRequestItemOrder: jest.fn(),
    });
    await hoverColumn(user, 1);

    await openToolbarDropdown(user);

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.move'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.copy'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.order'})).toBeDisabled();
});

test('Do not show order button if onRequestItemOrder callback is undefined', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onRequestItemMove: jest.fn(),
        onRequestItemOrder: undefined,
    });

    await openToolbarDropdown(user);

    expect(screen.queryByRole('button', {name: 'sulu_admin.order'})).not.toBeInTheDocument();
});

test('Call onRequestItemOrder callback when an item ordering has been changed', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onRequestItemOrder: requestItemOrderSpy,
    });

    await openToolbarDropdown(user);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.order'}));

    const orderInput = screen.getByDisplayValue('1');
    await user.clear(orderInput);
    await user.type(orderInput, '5');
    fireEvent.blur(orderInput);

    expect(requestItemOrderSpy).toBeCalledWith(1, 2);
});

test('Do not execute onItemActivate callback when a column is ordering', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onItemActivate: itemActivateSpy,
        onRequestItemOrder: jest.fn(),
    });

    await openToolbarDropdown(user);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.order'}));

    await user.click(getItemElement('Page 1'));
    await user.click(getItemElement('Page 2'));

    expect(itemActivateSpy).not.toBeCalled();
});

test('Execute onItemSelectionChange callback when an item is selected', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [],
        data,
        onItemSelectionChange: itemSelectionChangeSpy,
        selections: [2],
    });
    const selectionButtons = screen.getAllByRole('button', {name: 'su-check'});

    await user.click(selectionButtons[1]);
    expect(itemSelectionChangeSpy).toHaveBeenLastCalledWith(2, false);

    await user.click(selectionButtons[0]);
    expect(itemSelectionChangeSpy).toHaveBeenLastCalledWith(1, true);
});

test('Execute onRequestItemCopy callback when an item is copied with the correct id', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onRequestItemCopy: copyClickSpy,
    });

    await openToolbarDropdown(user);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.copy'}));

    expect(copyClickSpy).toBeCalledWith(3);
});

test('Execute onRequestItemMove callback when an item is moved with the correct id', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onRequestItemMove: moveClickSpy,
    });

    await openToolbarDropdown(user);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.move'}));

    expect(moveClickSpy).toBeCalledWith(3);
});

test('Execute onRequestItemDelete callback when an item is deleted with the correct id', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onRequestItemDelete: deleteClickSpy,
    });

    await openToolbarDropdown(user);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.delete'}));

    expect(deleteClickSpy).toBeCalledWith(3);
});

test('Enable delete and move button if an item in this column has been activated', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        onRequestItemDelete: jest.fn(),
        onRequestItemMove: jest.fn(),
    });

    await openToolbarDropdown(user);

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.move'})).toBeEnabled();
});

test('Disable delete and move button if no item in this column has been activated', async() => {
    const user = userEvent.setup();
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

    renderColumnListAdapter({
        activeItems: [1],
        data,
        onRequestItemDelete: jest.fn(),
        onRequestItemMove: jest.fn(),
    });

    await openToolbarDropdown(user);

    expect(screen.getByRole('button', {name: 'sulu_admin.delete'})).toBeDisabled();
    expect(screen.getByRole('button', {name: 'sulu_admin.move'})).toBeDisabled();
});

test('Do not show settings if no options are available', () => {
    renderColumnListAdapter({
        activeItems: [1],
    });

    expect(screen.queryByLabelText('su-cog')).not.toBeInTheDocument();
});
