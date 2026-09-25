// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import ColumnListAdapter from '../../adapters/ColumnListAdapter';

jest.mock('../../../../utils/Translator');

function renderColumnListAdapter(props: Object = {}) {
    return render(
        <ColumnListAdapter
            {...listAdapterDefaultProps}
            {...props}
        />
    );
}

function getItemByTitle(title: string): HTMLElement {
    const item = screen.getByTitle(title).closest('.item');

    if (!(item instanceof HTMLElement)) {
        throw new Error('The item with title "' + title + '" was not rendered.');
    }

    return item;
}

function getColumns(container: HTMLElement): Array<HTMLElement> {
    const columns = [];

    container.querySelectorAll('.column').forEach((column) => {
        if (column instanceof HTMLElement) {
            columns.push(column);
        }
    });

    return columns;
}

function getToolbarButtonsByIcon(icon: string): Array<HTMLButtonElement> {
    const buttons: Array<HTMLButtonElement> = [];

    screen.queryAllByLabelText(icon).forEach((iconElement) => {
        const button = iconElement.closest('button');

        if (button instanceof HTMLButtonElement) {
            buttons.push(button);
        }
    });

    return buttons;
}

function getItemIconButton(title: string, icon: string): HTMLElement {
    const iconElement = within(getItemByTitle(title)).getByLabelText(icon);

    if (!(iconElement instanceof HTMLElement)) {
        throw new Error('The item button with icon "' + icon + '" was not rendered.');
    }

    return iconElement;
}

async function openToolbarDropdown(user: Object, index: number = 0) {
    await user.click(getToolbarButtonsByIcon('su-cog')[index]);
}

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

    renderColumnListAdapter({
        activeItems: [2, 4],
        adapterOptions: {get_indicators: (item) => item.hasChildren ? ['has-children-indicator'] : []},
        data,
        onItemAdd: jest.fn(),
        onItemClick: jest.fn(),
        onRequestItemDelete: jest.fn(),
    });

    expect(screen.getByTitle('Page 1')).toBeInTheDocument();
    expect(screen.getByTitle('Page 2.1.2')).toBeInTheDocument();
    expect(getItemByTitle('Page 2')).toHaveClass('active');
    expect(getItemByTitle('Page 2.1')).toHaveClass('active');
    expect(screen.getAllByText('nl')).toHaveLength(2);
    expect(screen.getByLabelText('su-permissions')).toBeInTheDocument();
    expect(screen.getAllByLabelText('su-link2')).toHaveLength(2);
    expect(screen.getAllByLabelText('su-link')).toHaveLength(2);
    expect(screen.getAllByText('has-children-indicator')).toHaveLength(1);
    expect(screen.getAllByLabelText('su-shadow-page')).toHaveLength(2);
    expect(document.querySelector('.publishIndicator')).toBeInTheDocument();
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

    expect(getItemIconButton('Missing view permission', 'su-pen')).not.toHaveClass('visible');
    expect(getItemIconButton('Missing edit permission', 'su-eye')).toHaveClass('visible');
    expect(getItemIconButton('Sufficient Permissions', 'su-pen')).toHaveClass('visible');
    expect(getItemIconButton('Ghost Page', 'su-plus-circle')).toHaveClass('visible');
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

    renderColumnListAdapter({
        activeItems: [],
        data,
        onRequestItemDelete: jest.fn(),
    });

    expect(screen.getByTitle('Page 1')).toBeInTheDocument();
    expect(screen.getByLabelText('su-angle-right')).toBeInTheDocument();
    expect(within(getItemByTitle('Page 1')).queryByLabelText('su-pen')).not.toBeInTheDocument();
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

    renderColumnListAdapter({
        activeItems: [],
        data,
    });

    expect(screen.getByTitle('Page 1')).toBeInTheDocument();
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

    renderColumnListAdapter({
        activeItems: [],
        data,
        onItemSelectionChange: jest.fn(),
        onRequestItemDelete: jest.fn(),
        selections: [1],
    });

    expect(getItemByTitle('Page 1')).toHaveClass('selected');
    expect(getItemIconButton('Page 1', 'su-check')).toBeInTheDocument();
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

    renderColumnListAdapter({
        activeItems: [1, 3],
        data,
        disabledIds: [3],
        onItemSelectionChange: jest.fn(),
        onRequestItemDelete: jest.fn(),
        selections: [1],
    });

    expect(getItemByTitle('Page 1')).toHaveClass('selected');
    expect(getItemByTitle('Page 1.1')).toHaveClass('disabled');
});

test('Render with add button in toolbar when onItemAdd callback is given', () => {
    const data = [
        [],
    ];

    renderColumnListAdapter({
        activeItems: [],
        data,
        onItemAdd: jest.fn(),
        onRequestItemDelete: jest.fn(),
    });

    expect(getToolbarButtonsByIcon('su-plus-circle')).toHaveLength(1);
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

    const {container} = renderColumnListAdapter({
        activeItems: [undefined, 1, 4],
        data,
        onItemAdd: jest.fn(),
        onRequestItemDelete: jest.fn(),
    });

    await user.hover(getColumns(container)[2]);

    expect(getToolbarButtonsByIcon('su-plus-circle')).toHaveLength(0);
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

    const {container} = renderColumnListAdapter({
        activeItems: [undefined, 1, 4],
        adapterOptions: {display_root_level_toolbar: false},
        data,
        onItemAdd: jest.fn(),
        onRequestItemDelete: jest.fn(),
    });

    await user.hover(getColumns(container)[0]);
    expect(getToolbarButtonsByIcon('su-plus-circle')).toHaveLength(0);
    expect(getToolbarButtonsByIcon('su-cog')).toHaveLength(0);

    await user.hover(getColumns(container)[1]);
    expect(getToolbarButtonsByIcon('su-plus-circle')).toHaveLength(1);
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

    expect(getToolbarButtonsByIcon('su-cog')).toHaveLength(0);
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

    renderColumnListAdapter({
        activeItems: [1],
        data,
        loading: true,
        onRequestItemDelete: jest.fn(),
    });

    expect(screen.getByTitle('Page 1')).toBeInTheDocument();
    expect(screen.getByText((content, element) => !!element && element.classList.contains('spinner')))
        .toBeInTheDocument();
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

    await user.click(getItemByTitle('Page 2'));

    expect(itemActivateSpy).toHaveBeenCalledWith(2);
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

    await user.dblClick(getItemByTitle('Page 2'));

    expect(itemClickSpy).toHaveBeenCalledWith(2);
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

    await user.dblClick(getItemByTitle('Page 2'));

    expect(itemClickSpy).not.toHaveBeenCalled();
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

    const {container} = renderColumnListAdapter({
        activeItems: [undefined, 1, 2],
        data,
        onRequestItemCopy: jest.fn(),
        onRequestItemDelete: jest.fn(),
        onRequestItemMove: jest.fn(),
        onRequestItemOrder: jest.fn(),
    });

    await user.hover(getColumns(container)[1]);
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

    await user.clear(screen.getAllByRole('textbox')[0]);
    await user.type(screen.getAllByRole('textbox')[0], '5');
    await user.tab();

    expect(requestItemOrderSpy).toHaveBeenCalledWith(1, 2);
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

    await user.click(screen.getAllByRole('textbox')[0]);
    await user.click(screen.getAllByRole('textbox')[1]);

    expect(itemActivateSpy).not.toHaveBeenCalled();
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

    await user.click(getItemIconButton('Page 2', 'su-check'));
    expect(itemSelectionChangeSpy).toHaveBeenLastCalledWith(2, false);

    await user.click(getItemIconButton('Page 1', 'su-check'));
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

    expect(copyClickSpy).toHaveBeenCalledWith(3);
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

    expect(moveClickSpy).toHaveBeenCalledWith(3);
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

    expect(deleteClickSpy).toHaveBeenCalledWith(3);
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

    expect(getToolbarButtonsByIcon('su-cog')).toHaveLength(0);
});
