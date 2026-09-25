// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import TreeTableAdapter from '../../adapters/TreeTableAdapter';
import StringFieldTransformer from '../../fieldTransformers/StringFieldTransformer';
import listFieldTransformerRegistry from '../../registries/listFieldTransformerRegistry';

jest.mock('../../../../utils/Translator', () => ({
    translate(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
            case 'sulu_admin.per_page':
                return 'per page';
            default:
                return key;
        }
    },
}));

jest.mock('../../registries/listFieldTransformerRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

const TITLE_SCHEMA = {
    title: {
        filterType: null,
        filterTypeParameters: null,
        transformerTypeParameters: {},
        type: 'string',
        sortable: true,
        visibility: 'yes',
        label: 'Title',
    },
};

const TITLE_DESCRIPTION_SCHEMA = {
    title: {
        filterType: null,
        filterTypeParameters: null,
        transformerTypeParameters: {},
        label: 'Title',
        sortable: true,
        type: 'string',
        visibility: 'no',
    },
    description: {
        filterType: null,
        filterTypeParameters: null,
        transformerTypeParameters: {},
        label: 'Description',
        sortable: true,
        type: 'string',
        visibility: 'yes',
    },
};

const TREE_DATA = [
    {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    },
    {
        data: {
            id: 3,
            title: 'Test2',
        },
        children: [],
        hasChildren: true,
    },
    {
        data: {
            id: 6,
            title: 'Test3',
        },
        children: [
            {
                data: {
                    id: 7,
                    title: 'Test4',
                },
                children: [],
                hasChildren: false,
            },
        ],
        hasChildren: true,
    },
];

function renderTreeTableAdapter(props: Object = {}) {
    return render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            {...props}
        />
    );
}

function getButtonsByIcon(icon: string): Array<HTMLButtonElement> {
    const buttons: Array<HTMLButtonElement> = [];

    screen.queryAllByLabelText(icon).forEach((iconElement) => {
        const button = iconElement.closest('button');

        if (button instanceof HTMLButtonElement) {
            buttons.push(button);
        }
    });

    return buttons;
}

function getButtonByIconInRow(row: HTMLElement, icon: string): HTMLButtonElement {
    const button = within(row).getByLabelText(icon).closest('button');

    if (!(button instanceof HTMLButtonElement)) {
        throw new Error('The button with icon "' + icon + '" was not rendered in the row.');
    }

    return button;
}

function getNavigationButtonByIcon(icon: string): HTMLButtonElement {
    const button = getButtonsByIcon(icon).find((button) => button.closest('nav'));

    if (!(button instanceof HTMLButtonElement)) {
        throw new Error('The pagination button with icon "' + icon + '" was not rendered.');
    }

    return button;
}

function getRowByText(text: string): HTMLTableRowElement {
    const row = screen.getByText(text).closest('tr');

    if (!(row instanceof HTMLTableRowElement)) {
        throw new Error('Row with text "' + text + '" was not rendered.');
    }

    return row;
}

function getBodyRows(): Array<HTMLTableRowElement> {
    const rows: Array<HTMLTableRowElement> = [];

    screen.getAllByRole('row').forEach((row) => {
        if (row instanceof HTMLTableRowElement && row.closest('tbody')) {
            rows.push(row);
        }
    });

    return rows;
}

function getCheckboxByValue(value: string): HTMLInputElement {
    const checkbox = screen.getAllByRole('checkbox').find((checkbox) => checkbox.getAttribute('value') === value);

    if (!(checkbox instanceof HTMLInputElement)) {
        throw new Error('The checkbox with value "' + value + '" was not rendered.');
    }

    return checkbox;
}

beforeEach(() => {
    jest.clearAllMocks();
    listFieldTransformerRegistry.get.mockReturnValue(new StringFieldTransformer());
});

test('Render data with schema', () => {
    const data = [
        {
            data: {
                id: 1,
                title: 'Page 1',
                published: '2017-08-23',
                publishedState: true,
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 2,
                title: 'Page 2',
                publishedState: true,
                published: null,
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 3,
                title: 'Page 3',
                publishedState: false,
                published: '2017-08-23',
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 4,
                title: 'Page 4',
                publishedState: false,
                published: null,
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 5,
                title: 'Page 5',
                published: '2017-08-23',
                publishedState: true,
                ghostLocale: 'de',
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 6,
                title: 'Page 6',
                publishedState: true,
                published: null,
                ghostLocale: 'de',
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 7,
                title: 'Page 7',
                publishedState: false,
                published: '2017-08-23',
                ghostLocale: 'de',
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 8,
                title: 'Page 8',
                publishedState: false,
                published: null,
                ghostLocale: 'de',
            },
            children: [],
            hasChildren: true,
        },
    ];

    renderTreeTableAdapter({
        data,
        schema: TITLE_SCHEMA,
    });

    expect(screen.getByRole('columnheader', {name: 'Title'})).toBeInTheDocument();
    expect(screen.getByText('Page 1')).toBeInTheDocument();
    expect(screen.getByText('Page 8')).toBeInTheDocument();
    expect(screen.getAllByText('de')).toHaveLength(4);
    expect(screen.getAllByLabelText('su-angle-right')).toHaveLength(8);
    expect(document.querySelector('.publishIndicator')).toBeInTheDocument();
});

test('Render data without header using the configured skin', () => {
    renderTreeTableAdapter({
        adapterOptions: {show_header: false},
        data: TREE_DATA,
        page: 1,
        pageCount: 2,
        paginated: false,
        schema: TITLE_SCHEMA,
    });

    expect(screen.queryByRole('columnheader')).not.toBeInTheDocument();
    expect(screen.getByText('Test1')).toBeInTheDocument();
    expect(screen.getByText('Test4')).toBeInTheDocument();
});

test('Render data with skin', () => {
    renderTreeTableAdapter({
        adapterOptions: {skin: 'flat'},
        data: TREE_DATA,
        page: 1,
        pageCount: 2,
        paginated: false,
        schema: TITLE_SCHEMA,
    });

    expect(screen.getByRole('table').closest('.tableContainer')).toHaveClass('flat');
    expect(screen.getByRole('columnheader', {name: 'Title'})).toBeInTheDocument();
});

test('Render data without header', () => {
    renderTreeTableAdapter({
        adapterOptions: {show_header: false},
        data: TREE_DATA,
        page: 1,
        pageCount: 2,
        paginated: false,
        schema: TITLE_SCHEMA,
    });

    expect(screen.queryByRole('columnheader')).not.toBeInTheDocument();
    expect(screen.getByText('Test1')).toBeInTheDocument();
    expect(screen.getByText('Test4')).toBeInTheDocument();
});

test('Attach onClick handler for sorting if schema says the header is sortable', async() => {
    const user = userEvent.setup();
    const sortSpy = jest.fn();

    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: false,
            visibility: 'yes',
            label: 'Description',
        },
    };

    renderTreeTableAdapter({
        onSort: sortSpy,
        schema,
    });

    await user.click(screen.getByRole('button', {name: 'Title'}));

    expect(sortSpy).toHaveBeenCalledWith('title', 'asc');
    expect(screen.queryByRole('button', {name: 'Description'})).not.toBeInTheDocument();
    expect(screen.getByRole('columnheader', {name: 'Description'})).toBeInTheDocument();
});

test('Render data with two columns', () => {
    const data = [
        {
            data: {
                id: 2,
                title: 'Test1',
                title2: 'Title2 - Test1',
            },
            children: [],
            hasChildren: false,
        },
        {
            data: {
                id: 3,
                title: 'Test2',
                title2: 'Title2 - Test2',
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 6,
                title: 'Test3',
                title2: 'Title2 - Test3',
            },
            children: [],
            hasChildren: true,
        },
    ];

    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
        title2: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title2',
        },
    };

    renderTreeTableAdapter({
        data,
        schema,
    });

    expect(screen.getByRole('columnheader', {name: 'Title'})).toBeInTheDocument();
    expect(screen.getByRole('columnheader', {name: 'Title2'})).toBeInTheDocument();
    expect(getRowByText('Test1')).toHaveTextContent('Title2 - Test1');
    expect(getRowByText('Test2')).toHaveTextContent('Title2 - Test2');
});

test('Render data with schema and selections', () => {
    renderTreeTableAdapter({
        data: TREE_DATA,
        onItemSelectionChange: jest.fn(),
        schema: TITLE_SCHEMA,
        selections: [1, 3],
    });

    expect(getCheckboxByValue('2')).not.toBeChecked();
    expect(getCheckboxByValue('3')).toBeChecked();
    expect(getCheckboxByValue('6')).not.toBeChecked();
});

test('Execute onItemActivate respectively onItemDeactivate callback when an item is clicked', async() => {
    const user = userEvent.setup();

    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const test21 = {
        data: {
            id: 4,
            title: 'Test2.1',
        },
        children: [],
        hasChildren: false,
    };
    const test22 = {
        data: {
            id: 5,
            title: 'Test2.2',
        },
        children: [],
        hasChildren: false,
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
        },
        children: [
            test21,
            test22,
        ],
        hasChildren: true,
    };
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
        },
        children: [],
        hasChildren: true,
    };

    const onItemActivateSpy = jest.fn();
    const onItemDeactivateSpy = jest.fn();

    renderTreeTableAdapter({
        data: [test1, test2, test3],
        onItemActivate: onItemActivateSpy,
        onItemDeactivate: onItemDeactivateSpy,
        schema: TITLE_SCHEMA,
    });

    await user.click(within(getRowByText('Test3')).getByLabelText('su-angle-right'));
    expect(onItemActivateSpy).toHaveBeenCalledWith(6);

    await user.click(within(getRowByText('Test2')).getByLabelText('su-angle-down'));
    expect(onItemDeactivateSpy).toHaveBeenCalledWith(3);
});

test('Render data with pencil button and given itemActions when onItemEdit callback is passed', () => {
    const actionsProvider = () => [
        {
            icon: 'su-process',
            onClick: undefined,
        },
        {
            icon: 'su-trash',
            onClick: undefined,
        },
    ];

    renderTreeTableAdapter({
        data: [
            {
                data: {
                    id: 2,
                    title: 'Test1',
                },
                children: [],
                hasChildren: false,
            },
        ],
        itemActionsProvider: actionsProvider,
        onItemClick: jest.fn(),
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    expect(getButtonsByIcon('su-pen')).toHaveLength(1);
    expect(getButtonsByIcon('su-process')).toHaveLength(1);
    expect(getButtonsByIcon('su-trash')).toHaveLength(1);
    expect(screen.getByRole('columnheader', {name: /Description/})).toBeInTheDocument();
});

test('Render correct buttons based on permissions when item permissions are provided', () => {
    const data = [
        {
            data: {
                id: 1,
                title: 'Missing view permission',
                _permissions: {
                    view: false,
                },
            },
            children: [],
            hasChildren: false,
        },
        {
            data: {
                id: 2,
                title: 'Missing edit permission',
                _permissions: {
                    edit: false,
                },
            },
            children: [],
            hasChildren: false,
        },
        {
            data: {
                id: 3,
                title: 'Missing add permission',
                _permissions: {
                    add: false,
                },
            },
            children: [],
            hasChildren: false,
        },
        {
            data: {
                id: 4,
                title: 'No missing permissions',
            },
            children: [],
            hasChildren: false,
        },
    ];

    renderTreeTableAdapter({
        data,
        onItemAdd: jest.fn(),
        onItemClick: jest.fn(),
        schema: {
            title: {
                filterType: null,
                filterTypeParameters: null,
                transformerTypeParameters: {},
                label: 'Title',
                sortable: true,
                type: 'string',
                visibility: 'no',
            },
        },
    });

    const rows = getBodyRows();

    expect(getButtonByIconInRow(rows[0], 'su-pen')).toBeDisabled();
    expect(getButtonByIconInRow(rows[0], 'su-plus-circle')).toBeEnabled();

    expect(getButtonByIconInRow(rows[1], 'su-eye')).toBeEnabled();
    expect(getButtonByIconInRow(rows[1], 'su-plus-circle')).toBeEnabled();

    expect(getButtonByIconInRow(rows[2], 'su-pen')).toBeEnabled();
    expect(getButtonByIconInRow(rows[2], 'su-plus-circle')).toBeDisabled();

    expect(getButtonByIconInRow(rows[3], 'su-pen')).toBeEnabled();
    expect(getButtonByIconInRow(rows[3], 'su-plus-circle')).toBeEnabled();
});

test('Render disabled rows based on given disabledIds prop', () => {
    const data = [
        {
            data: {
                id: 1,
                title: 'First item',
            },
            children: [],
            hasChildren: false,
        },
        {
            data: {
                id: 2,
                title: 'Second item',
            },
            children: [
                {
                    data: {
                        id: 3,
                        title: 'Child item',
                    },
                    children: [],
                    hasChildren: false,
                },
            ],
            hasChildren: true,
        },
    ];

    renderTreeTableAdapter({
        data,
        disabledIds: [1, 3],
        onItemAdd: jest.fn(),
        onItemClick: jest.fn(),
        schema: TITLE_SCHEMA,
    });

    expect(getRowByText('First item')).toHaveClass('disabled');
    expect(getRowByText('Second item')).not.toHaveClass('disabled');
    expect(getRowByText('Child item')).toHaveClass('disabled');
});

test('Render data with plus button when onItemAdd callback is passed', () => {
    renderTreeTableAdapter({
        data: [
            {
                data: {
                    id: 2,
                    title: 'Test1',
                },
                children: [],
                hasChildren: false,
            },
        ],
        onItemAdd: jest.fn(),
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    expect(getButtonsByIcon('su-plus-circle')).toHaveLength(1);
    expect(screen.getByRole('columnheader', {name: /Description/})).toBeInTheDocument();
});

test('Click on pencil should execute onItemClick callback', async() => {
    const user = userEvent.setup();
    const rowEditClickSpy = jest.fn();

    renderTreeTableAdapter({
        data: [
            {
                data: {
                    id: 2,
                    title: 'Test1',
                },
                children: [],
                hasChildren: false,
            },
        ],
        onItemClick: rowEditClickSpy,
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    await user.click(getButtonsByIcon('su-pen')[0]);

    expect(rowEditClickSpy).toHaveBeenCalledWith(2, 0);
});

test('Click on add should execute onItemAdd callback', async() => {
    const user = userEvent.setup();
    const rowAddClickSpy = jest.fn();

    renderTreeTableAdapter({
        data: [
            {
                data: {
                    id: 2,
                    title: 'Test1',
                },
                children: [],
                hasChildren: false,
            },
        ],
        onItemAdd: rowAddClickSpy,
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    await user.click(getButtonsByIcon('su-plus-circle')[0]);

    expect(rowAddClickSpy).toHaveBeenCalledWith(2, 0);
});

test('Click on itemAction should execute its callback', async() => {
    const user = userEvent.setup();
    const actionClickSpy = jest.fn();
    const item1Data = {
        id: 2,
        title: 'Test1',
    };
    const item1 = {
        data: item1Data,
        children: [],
        hasChildren: false,
    };
    const actionsProvider = jest.fn(() => [
        {
            icon: 'su-process',
            onClick: actionClickSpy,
        },
    ]);

    renderTreeTableAdapter({
        data: [item1],
        itemActionsProvider: actionsProvider,
        onItemAdd: jest.fn(),
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    expect(actionsProvider).toHaveBeenCalledWith(item1Data);

    await user.click(getButtonsByIcon('su-process')[0]);

    expect(actionClickSpy).toHaveBeenCalledWith(2, 0);
});

test('Pagination should be passed correct props', () => {
    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();

    renderTreeTableAdapter({
        data: [
            {
                data: {
                    id: 2,
                    title: 'Test1',
                },
                children: [],
                hasChildren: false,
            },
        ],
        limit: 10,
        onLimitChange: limitChangeSpy,
        onPageChange: pageChangeSpy,
        page: 2,
        pageCount: 7,
    });

    expect(screen.getByDisplayValue('2')).toBeInTheDocument();
    expect(screen.getByText(/of/)).toHaveTextContent('of 7');
    expect(getNavigationButtonByIcon('su-angle-left')).toBeEnabled();
    expect(getNavigationButtonByIcon('su-angle-right')).toBeEnabled();
});

test('Pagination should not be rendered if API is not paginated', () => {
    const item1 = {
        data: {
            id: 1,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };

    const item2 = {
        data: {
            id: 2,
            title: 'Test2',
        },
        children: [],
        hasChildren: false,
    };

    const item3 = {
        data: {
            id: 3,
            title: 'Test3',
        },
        children: [item2],
        hasChildren: true,
    };

    renderTreeTableAdapter({
        data: [item1, item3],
        onLimitChange: jest.fn(),
        onPageChange: jest.fn(),
        page: 1,
        pageCount: undefined,
    });

    expect(screen.queryByDisplayValue('1')).not.toBeInTheDocument();
    expect(screen.queryByText(/of/)).not.toBeInTheDocument();
});

test('Pagination should not be rendered if no data is available', () => {
    renderTreeTableAdapter({
        onLimitChange: jest.fn(),
        onPageChange: jest.fn(),
        page: 1,
    });

    expect(screen.queryByDisplayValue('1')).not.toBeInTheDocument();
    expect(screen.queryByText(/of/)).not.toBeInTheDocument();
});

test('Pagination should not be rendered if pagination is false', () => {
    renderTreeTableAdapter({
        limit: 10,
        page: 2,
        pageCount: 7,
        paginated: false,
    });

    expect(screen.queryByDisplayValue('2')).not.toBeInTheDocument();
    expect(screen.queryByText(/of/)).not.toBeInTheDocument();
});

test('Next page should call onItemActiveate with undefined', async() => {
    const user = userEvent.setup();

    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const test21 = {
        data: {
            id: 4,
            title: 'Test2.1',
        },
        children: [],
        hasChildren: false,
    };
    const test22 = {
        data: {
            id: 5,
            title: 'Test2.2',
        },
        children: [],
        hasChildren: false,
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
        },
        children: [
            test21,
            test22,
        ],
        hasChildren: true,
    };

    const onPageChangeSpy = jest.fn();
    const onItemActivateSpy = jest.fn();

    renderTreeTableAdapter({
        data: [test1, test2],
        onItemActivate: onItemActivateSpy,
        onPageChange: onPageChangeSpy,
        page: 1,
        pageCount: 2,
        schema: TITLE_SCHEMA,
    });

    await user.click(getNavigationButtonByIcon('su-angle-right'));

    expect(onPageChangeSpy).toHaveBeenCalledWith(2);
    expect(onItemActivateSpy).toHaveBeenCalledWith(undefined);
});
