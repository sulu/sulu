// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import log from 'loglevel';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import TableAdapter from '../../adapters/TableAdapter';
import StringFieldTransformer from '../../fieldTransformers/StringFieldTransformer';
import IconFieldTransformer from '../../fieldTransformers/IconFieldTransformer';
import listFieldTransformerRegistry from '../../registries/listFieldTransformerRegistry';

jest.mock('../../../../utils/Translator', () => ({
    translate(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
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

jest.mock('loglevel', () => ({
    warn: jest.fn(),
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

const TITLE_DESCRIPTION_DATA = [
    {
        id: 1,
        title: 'Title 1',
        description: 'Description 1',
    },
    {
        id: 2,
        title: 'Title 2',
        description: 'Description 2',
    },
];

function renderTableAdapter(props: Object = {}) {
    return render(
        <TableAdapter
            {...listAdapterDefaultProps}
            {...props}
        />
    );
}

function getButtonsByIcon(icon: string): Array<HTMLButtonElement> {
    return screen.getAllByLabelText(icon).reduce((buttons, iconElement) => {
        const button = iconElement.closest('button');

        if (button instanceof HTMLButtonElement) {
            buttons.push(button);
        }

        return buttons;
    }, []);
}

function getRowByText(text: string): HTMLTableRowElement {
    const row = screen.getByText(text).closest('tr');

    if (!(row instanceof HTMLTableRowElement)) {
        throw new Error('Row with text "' + text + '" was not rendered.');
    }

    return row;
}

beforeEach(() => {
    jest.clearAllMocks();
    listFieldTransformerRegistry.get.mockReturnValue(new StringFieldTransformer());
});

test('Render data with schema', () => {
    renderTableAdapter({
        data: [
            {id: 1, title: 'Page 1', published: '2017-08-23', publishedState: true},
            {id: 2, title: 'Page 2', publishedState: true, published: null},
            {id: 3, title: 'Page 3', publishedState: false, published: '2017-08-23'},
            {id: 4, title: 'Page 4', publishedState: false, published: null},
            {id: 5, title: 'Page 5', published: '2017-08-23', publishedState: true, ghostLocale: 'de'},
        ],
        page: 2,
        pageCount: 5,
        schema: TITLE_SCHEMA,
    });

    expect(screen.getByRole('columnheader', {name: 'Title'})).toBeInTheDocument();
    expect(screen.getByText('Page 1')).toBeInTheDocument();
    expect(screen.getByText('Page 5')).toBeInTheDocument();
    expect(screen.getByText('de')).toBeInTheDocument();
    expect(document.querySelector('.publishIndicator')).toBeInTheDocument();
    expect(screen.getByDisplayValue('2')).toBeInTheDocument();
    expect(screen.getByText(/of/)).toHaveTextContent('of 5');
});

test('Render data as icons', () => {
    listFieldTransformerRegistry.get.mockReturnValue(new IconFieldTransformer());

    renderTableAdapter({
        data: [
            {id: 1, status: 'planned'},
            {id: 2, status: 'running'},
            {id: 3, status: 'succeeded'},
            {id: 4, status: 'failed'},
        ],
        page: 1,
        pageCount: 1,
        schema: {
            status: {
                filterType: null,
                filterTypeParameters: null,
                transformerTypeParameters: {
                    mapping: {
                        planned: 'su-clock',
                        succeeded: {
                            icon: 'su-check-circle',
                            color: 'green',
                        },
                        failed: {
                            icon: 'su-ban',
                        },
                    },
                },
                type: 'icon',
                sortable: false,
                visibility: 'always',
                label: 'Status',
            },
        },
    });

    expect(screen.getByLabelText('su-clock')).toBeInTheDocument();
    expect(screen.getByText('running')).toBeInTheDocument();
    expect(log.warn).toHaveBeenCalledWith(
        'There was no icon specified in the "mapping" transformer parameter for the value "running".'
    );
    expect(screen.getByLabelText('su-check-circle')).toHaveStyle('color: rgb(0, 128, 0)');
    expect(screen.getByLabelText('su-ban')).toBeInTheDocument();
});

test('Render data with skin', () => {
    renderTableAdapter({
        adapterOptions: {
            skin: 'light',
        },
        data: [],
        page: 2,
        pageCount: 5,
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    expect(screen.getByRole('table').closest('.tableContainer')).toHaveClass('light');
    expect(screen.getByRole('columnheader', {name: 'Description'})).toBeInTheDocument();
    expect(screen.queryByRole('columnheader', {name: 'Title'})).not.toBeInTheDocument();
});

test('Render data with shrunken cell', () => {
    renderTableAdapter({
        data: [
            {id: 1, title: '1', description: 'planned'},
        ],
        page: 2,
        pageCount: 5,
        schema: {
            title: {
                filterType: null,
                filterTypeParameters: null,
                transformerTypeParameters: {},
                type: 'string',
                sortable: true,
                visibility: 'yes',
                label: 'Title',
                width: 'shrink',
            },
            description: {
                filterType: null,
                filterTypeParameters: null,
                transformerTypeParameters: {},
                type: 'string',
                sortable: true,
                visibility: 'yes',
                label: 'Description',
                width: 'auto',
            },
        },
    });

    expect(screen.getByRole('columnheader', {name: 'Title'})).toHaveClass('shrink');
    expect(screen.getByTitle('1').closest('td')).toHaveClass('shrink');
});

test('Render data without header', () => {
    renderTableAdapter({
        adapterOptions: {
            show_header: false,
        },
        data: [],
        page: 2,
        pageCount: 5,
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    expect(screen.queryByRole('columnheader')).not.toBeInTheDocument();
});

test('Attach onClick handler for sorting if schema says the header is sortable', async() => {
    const user = userEvent.setup();
    const sortSpy = jest.fn();

    renderTableAdapter({
        onSort: sortSpy,
        schema: {
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
        },
    });

    await user.click(screen.getByRole('button', {name: 'Title'}));

    expect(sortSpy).toHaveBeenCalledWith('title', 'asc');
    expect(screen.queryByRole('button', {name: 'Description'})).not.toBeInTheDocument();
});

test('Render data with all different visibility types schema', () => {
    renderTableAdapter({
        data: [
            {id: 1, title: 'Title 1', description: 'Description 1', test1: 'Always 1', test2: 'Never 1'},
            {id: 2, title: 'Title 2', description: 'Description 2', test1: 'Always 2', test2: 'Never 2'},
        ],
        page: 2,
        pageCount: 5,
        schema: {
            title: {
                filterType: null,
                filterTypeParameters: null,
                transformerTypeParameters: {},
                type: 'string',
                sortable: true,
                visibility: 'no',
                label: 'Title',
            },
            description: {
                filterType: null,
                filterTypeParameters: null,
                transformerTypeParameters: {},
                type: 'string',
                sortable: true,
                visibility: 'yes',
                label: 'Description',
            },
            test1: {
                filterType: null,
                filterTypeParameters: null,
                transformerTypeParameters: {},
                type: 'string',
                sortable: true,
                visibility: 'always',
                label: 'Test 1',
            },
            test2: {
                filterType: null,
                filterTypeParameters: null,
                transformerTypeParameters: {},
                type: 'string',
                sortable: true,
                visibility: 'never',
                label: 'Test 2',
            },
        },
    });

    expect(screen.queryByText('Title 1')).not.toBeInTheDocument();
    expect(screen.getByText('Description 1')).toBeInTheDocument();
    expect(screen.getByText('Always 1')).toBeInTheDocument();
    expect(screen.queryByText('Never 1')).not.toBeInTheDocument();
});

test('Render data with schema and selections', () => {
    renderTableAdapter({
        data: [
            {id: 1, title: 'Title 1', description: 'Description 1'},
            {id: 2, title: 'Title 2', description: 'Description 2'},
            {id: 3, title: 'Title 3', description: 'Description 3'},
        ],
        onItemSelectionChange: jest.fn(),
        page: 1,
        pageCount: 3,
        schema: TITLE_DESCRIPTION_SCHEMA,
        selections: [1, 3],
    });

    const checkboxes = screen.getAllByRole('checkbox');
    expect(checkboxes[1]).toBeChecked();
    expect(checkboxes[2]).not.toBeChecked();
    expect(checkboxes[3]).toBeChecked();
});

test('Render data with schema in different order', () => {
    renderTableAdapter({
        data: TITLE_DESCRIPTION_DATA,
        page: 2,
        pageCount: 3,
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    expect(screen.getAllByRole('row')[1]).toHaveTextContent('Description 1');
    expect(screen.queryByText('Title 1')).not.toBeInTheDocument();
});

test('Render data with schema not containing all fields', () => {
    renderTableAdapter({
        data: TITLE_DESCRIPTION_DATA,
        page: 1,
        pageCount: 3,
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

    expect(screen.queryByText('Title 1')).not.toBeInTheDocument();
    expect(screen.queryByText('Description 1')).not.toBeInTheDocument();
});

test('Render data with pencil button when onItemEdit callback is passed', () => {
    renderTableAdapter({
        data: TITLE_DESCRIPTION_DATA,
        onItemClick: jest.fn(),
        page: 1,
        pageCount: 3,
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    expect(getButtonsByIcon('su-pen')).toHaveLength(2);
});

test('Render correct button based on permissions when item permissions are provided', () => {
    renderTableAdapter({
        data: [
            {
                id: 1,
                title: 'Missing view permission',
                _permissions: {
                    view: false,
                },
            },
            {
                id: 2,
                title: 'Missing edit permission',
                _permissions: {
                    edit: false,
                },
            },
            {
                id: 3,
                title: 'No missing permissions',
            },
        ],
        onItemClick: jest.fn(),
        page: 1,
        pageCount: 3,
        schema: TITLE_SCHEMA,
    });

    const editButtons = getButtonsByIcon('su-pen');
    const viewButtons = getButtonsByIcon('su-eye');

    expect(editButtons[0]).toBeDisabled();
    expect(viewButtons[0]).toBeEnabled();
    expect(editButtons[1]).toBeEnabled();
});

test('Render disabled rows based on given disabledIds prop', () => {
    renderTableAdapter({
        data: [
            {id: 1, title: 'First item'},
            {id: 2, title: 'Second item'},
            {id: 3, title: 'third item'},
        ],
        disabledIds: [1, 3],
        onItemClick: jest.fn(),
        page: 1,
        pageCount: 3,
        schema: TITLE_SCHEMA,
    });

    expect(getRowByText('First item')).toHaveClass('disabled');
    expect(getRowByText('Second item')).not.toHaveClass('disabled');
    expect(getRowByText('third item')).toHaveClass('disabled');
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

    renderTableAdapter({
        data: TITLE_DESCRIPTION_DATA,
        itemActionsProvider: actionsProvider,
        onItemClick: jest.fn(),
        page: 1,
        pageCount: 3,
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    expect(getButtonsByIcon('su-pen')).toHaveLength(2);
    expect(getButtonsByIcon('su-process')).toHaveLength(2);
    expect(getButtonsByIcon('su-trash')).toHaveLength(2);
});

test('Render column with ascending sort icon', () => {
    renderTableAdapter({
        data: [
            {id: 1, title: 'Title 1', description: 'Description 1'},
        ],
        page: 1,
        pageCount: 3,
        schema: {
            title: {...TITLE_SCHEMA.title, visibility: 'yes'},
            description: {...TITLE_SCHEMA.title, label: 'Description', visibility: 'yes'},
        },
        sortColumn: 'title',
        sortOrder: 'asc',
    });

    expect(screen.getByRole('button', {name: /Title/})).toContainElement(screen.getByLabelText('su-angle-up'));
});

test('Render column with descending sort icon', () => {
    renderTableAdapter({
        data: [
            {id: 1, title: 'Title 1', description: 'Description 1'},
        ],
        page: 1,
        pageCount: 3,
        schema: {
            title: {...TITLE_SCHEMA.title, visibility: 'yes'},
            description: {...TITLE_SCHEMA.title, label: 'Description', visibility: 'yes'},
        },
        sortColumn: 'description',
        sortOrder: 'desc',
    });

    expect(screen.getByRole('button', {name: /Description/}))
        .toContainElement(within(screen.getByRole('button', {name: /Description/})).getByLabelText('su-angle-down'));
});

test('Click on pencil should execute onItemClick callback', async() => {
    const user = userEvent.setup();
    const rowEditClickSpy = jest.fn();

    renderTableAdapter({
        data: TITLE_DESCRIPTION_DATA,
        onItemClick: rowEditClickSpy,
        page: 1,
        pageCount: 3,
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    await user.click(getButtonsByIcon('su-pen')[0]);

    expect(rowEditClickSpy).toHaveBeenCalledWith(1, 0);
});

test('Click on itemAction should execute its callback', async() => {
    const user = userEvent.setup();
    const actionClickSpy = jest.fn();
    const item1 = {id: 1, title: 'Title 1', description: 'Description 1'};
    const item2 = {id: 2, title: 'Title 2', description: 'Description 2'};
    const actionsProvider = jest.fn(() => [
        {
            icon: 'su-process',
            onClick: actionClickSpy,
        },
    ]);

    renderTableAdapter({
        data: [item1, item2],
        itemActionsProvider: actionsProvider,
        onItemClick: jest.fn(),
        page: 1,
        pageCount: 3,
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    expect(actionsProvider).toHaveBeenCalledWith(item1);
    expect(actionsProvider).toHaveBeenCalledWith(item2);

    await user.click(getButtonsByIcon('su-process')[0]);

    expect(actionClickSpy).toHaveBeenCalledWith(1, 0);
});

test('Click on checkbox should call onItemSelectionChange callback', async() => {
    const user = userEvent.setup();
    const rowSelectionChangeSpy = jest.fn();

    renderTableAdapter({
        data: TITLE_DESCRIPTION_DATA,
        onItemSelectionChange: rowSelectionChangeSpy,
        page: 1,
        pageCount: 3,
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    await user.click(screen.getAllByRole('checkbox')[1]);

    expect(rowSelectionChangeSpy).toHaveBeenCalledWith(1, true);
});

test('Click on checkbox in header should call onAllSelectionChange callback', async() => {
    const user = userEvent.setup();
    const allSelectionChangeSpy = jest.fn();

    renderTableAdapter({
        data: [],
        onAllSelectionChange: allSelectionChangeSpy,
        onItemSelectionChange: jest.fn(),
        schema: TITLE_SCHEMA,
    });

    await user.click(screen.getByRole('checkbox'));

    expect(allSelectionChangeSpy).toHaveBeenCalledWith(true);
});

test('Pagination should be passed correct props', async() => {
    const user = userEvent.setup();
    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();

    renderTableAdapter({
        data: TITLE_DESCRIPTION_DATA,
        limit: 10,
        onLimitChange: limitChangeSpy,
        onPageChange: pageChangeSpy,
        page: 2,
        pageCount: 7,
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    expect(screen.getByDisplayValue('2')).toBeInTheDocument();
    expect(screen.getByText(/of/)).toHaveTextContent('of 7');

    await user.click(getButtonsByIcon('su-angle-right')[0]);
    expect(pageChangeSpy).toHaveBeenCalledWith(3);

    await user.click(getButtonsByIcon('su-angle-left')[0]);
    expect(pageChangeSpy).toHaveBeenCalledWith(1);

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByText('20'));
    expect(limitChangeSpy).toHaveBeenCalledWith(20);
});

test('Pagination should not be rendered if API is not paginated', () => {
    renderTableAdapter({
        data: [
            {id: 1, title: 'Title 1', description: 'Description 1'},
        ],
        onLimitChange: jest.fn(),
        onPageChange: jest.fn(),
        page: 1,
        pageCount: undefined,
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    expect(screen.queryByText(/Page/)).not.toBeInTheDocument();
});

test('Pagination should not be rendered if no data is available', () => {
    renderTableAdapter({
        onLimitChange: jest.fn(),
        onPageChange: jest.fn(),
        page: 1,
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    expect(screen.queryByText(/Page/)).not.toBeInTheDocument();
});

test('Pagination should not be rendered if pagination is false', () => {
    renderTableAdapter({
        data: TITLE_DESCRIPTION_DATA,
        limit: 10,
        page: 2,
        pageCount: 7,
        paginated: false,
        schema: TITLE_DESCRIPTION_SCHEMA,
    });

    expect(screen.queryByText(/Page/)).not.toBeInTheDocument();
});
