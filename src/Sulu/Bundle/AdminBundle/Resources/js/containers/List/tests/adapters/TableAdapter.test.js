// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
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
        }
    },
}));

jest.mock('../../registries/listFieldTransformerRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

const renderTableAdapter = (customProps: Object = {}) => {
    const props = {
        ...listAdapterDefaultProps,
        ...customProps,
    };

    return render(<TableAdapter {...props} />);
};

beforeEach(() => {
    listFieldTransformerRegistry.get.mockReturnValue(new StringFieldTransformer());
});

test('Render data with schema', () => {
    const data = [
        {
            id: 1,
            title: 'Page 1',
            published: '2017-08-23',
            publishedState: true,
        },
        {
            id: 2,
            title: 'Page 2',
            publishedState: true,
            published: null,
        },
        {
            id: 3,
            title: 'Page 3',
            publishedState: false,
            published: '2017-08-23',
        },
        {
            id: 4,
            title: 'Page 4',
            publishedState: false,
            published: null,
        },
        {
            id: 5,
            title: 'Page 5',
            published: '2017-08-23',
            publishedState: true,
            ghostLocale: 'de',
        },
        {
            id: 6,
            title: 'Page 6',
            publishedState: true,
            published: null,
            ghostLocale: 'de',
        },
        {
            id: 7,
            title: 'Page 7',
            publishedState: false,
            published: '2017-08-23',
            ghostLocale: 'de',
        },
        {
            id: 8,
            title: 'Page 8',
            publishedState: false,
            published: null,
            ghostLocale: 'de',
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
    };

    const view = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={2}
            pageCount={5}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render data as icons', () => {
    listFieldTransformerRegistry.get.mockReturnValue(new IconFieldTransformer());

    const data = [
        {
            id: 1,
            status: 'planned',
        },
        {
            id: 2,
            status: 'running',
        },
        {
            id: 3,
            status: 'succeeded',
        },
        {
            id: 4,
            status: 'failed',
        },
    ];
    const schema = {
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
    };
    renderTableAdapter({
        data,
        page: 1,
        pageCount: 1,
        schema,
    });

    expect(screen.getByLabelText('su-clock')).toBeInTheDocument();
    expect(screen.getByText('running')).toBeInTheDocument();

    expect(log.warn).toBeCalledWith(
        'There was no icon specified in the "mapping" transformer parameter for the value "running".'
    );

    expect(screen.getByLabelText('su-check-circle')).toHaveStyle({color: 'green'});
    expect(screen.getByLabelText('su-ban')).toBeInTheDocument();
});

test('Render data with skin', () => {
    const data = [];

    const schema = {
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
    };
    const view = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            adapterOptions={{
                skin: 'light',
            }}
            data={data}
            page={2}
            pageCount={5}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render data with shrunken cell', () => {
    const data = [
        {
            id: 1,
            title: '1',
            description: 'planned',
        },
        {
            id: 2,
            title: '2',
            description: 'running',
        },
        {
            id: 3,
            title: '3',
            description: 'succeeded',
        },
        {
            id: 4,
            title: '4',
            description: 'failed',
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
    };
    const view = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={2}
            pageCount={5}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render data without header', () => {
    const data = [];

    const schema = {
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
    };
    const view = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            adapterOptions={{
                show_header: false,
            }}
            data={data}
            page={2}
            pageCount={5}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
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

    renderTableAdapter({
        onSort: sortSpy,
        schema,
    });

    await user.click(screen.getByRole('button', {name: 'Title'}));

    expect(sortSpy).toBeCalledWith('title', 'asc');
    expect(screen.queryByRole('button', {name: 'Description'})).not.toBeInTheDocument();
});

test('Render data with all different visibility types schema', () => {
    const data = [
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
    const schema = {
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
    };
    const view = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={2}
            pageCount={5}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render data with schema and selections', () => {
    const data = [
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
        {
            id: 3,
            title: 'Title 3',
            description: 'Description 3',
        },
    ];
    const schema = {
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
    const view = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemSelectionChange={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
            selections={[1, 3]}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render data with schema in different order', () => {
    const data = [
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
    const schema = {
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
    const view = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={2}
            pageCount={3}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render data with schema not containing all fields', () => {
    const data = [
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
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
    };
    const view = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={1}
            pageCount={3}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render data with pencil button when onItemEdit callback is passed', () => {
    const data = [
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
    const schema = {
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
    const view = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemClick={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render correct button based on permissions when item permissions are provided', () => {
    const data = [
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
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
    };
    renderTableAdapter({
        data,
        onItemClick: jest.fn(),
        page: 1,
        pageCount: 3,
        schema,
    });
    const penButtons = screen.getAllByRole('button', {name: 'su-pen'});
    const eyeButtons = screen.getAllByRole('button', {name: 'su-eye'});

    expect(penButtons[0]).toBeDisabled();

    expect(eyeButtons[0]).toBeEnabled();

    expect(penButtons[1]).toBeEnabled();
});

test('Render disabled rows based on given disabledIds prop', () => {
    const data = [
        {
            id: 1,
            title: 'First item',
        },
        {
            id: 2,
            title: 'Second item',
        },
        {
            id: 3,
            title: 'third item',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
    };
    renderTableAdapter({
        data,
        disabledIds: [1, 3],
        onItemClick: jest.fn(),
        page: 1,
        pageCount: 3,
        schema,
    });
    const rows = screen.getAllByRole('row');

    expect(rows[1]).toHaveClass('disabled');
    expect(rows[2]).not.toHaveClass('disabled');
    expect(rows[3]).toHaveClass('disabled');
});

test('Render data with pencil button and given itemActions when onItemEdit callback is passed', () => {
    const data = [
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
    const schema = {
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

    const view = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            /* eslint-disable-next-line react/jsx-no-bind */
            itemActionsProvider={actionsProvider}
            onItemClick={jest.fn()}
            page={1}
            pageCount={3}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render column with ascending sort icon', () => {
    const data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
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
    const view = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={1}
            pageCount={3}
            schema={schema}
            sortColumn="title"
            sortOrder="asc"
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render column with descending sort icon', () => {
    const data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
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
    const view = render(
        <TableAdapter
            {...listAdapterDefaultProps}
            data={data}
            page={1}
            pageCount={3}
            schema={schema}
            sortColumn="description"
            sortOrder="desc"
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Click on pencil should execute onItemClick callback', async() => {
    const user = userEvent.setup();
    const rowEditClickSpy = jest.fn();
    const data = [
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
    const schema = {
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
    renderTableAdapter({
        data,
        onItemClick: rowEditClickSpy,
        page: 1,
        pageCount: 3,
        schema,
    });

    await user.click(screen.getAllByRole('button', {name: 'su-pen'})[0]);

    expect(rowEditClickSpy.mock.calls[0][0]).toEqual(1);
});

test('Click on itemAction should execute its callback', async() => {
    const user = userEvent.setup();
    const actionClickSpy = jest.fn();
    const item1 = {
        id: 1,
        title: 'Title 1',
        description: 'Description 1',
    };
    const item2 = {
        id: 2,
        title: 'Title 2',
        description: 'Description 2',
    };
    const data = [item1, item2];
    const schema = {
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
    const actionsProvider = jest.fn(() => [
        {
            icon: 'su-process',
            onClick: actionClickSpy,
        },
    ]);

    renderTableAdapter({
        data,
        itemActionsProvider: actionsProvider,
        onItemClick: jest.fn(),
        page: 1,
        pageCount: 3,
        schema,
    });

    expect(actionsProvider).toBeCalledWith(item1);
    expect(actionsProvider).toBeCalledWith(item2);

    await user.click(screen.getAllByRole('button', {name: 'su-process'})[0]);

    expect(actionClickSpy.mock.calls[0][0]).toEqual(1);
});

test('Click on checkbox should call onItemSelectionChange callback', async() => {
    const user = userEvent.setup();
    const rowSelectionChangeSpy = jest.fn();
    const data = [
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
    const schema = {
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
    renderTableAdapter({
        data,
        onItemSelectionChange: rowSelectionChangeSpy,
        page: 1,
        pageCount: 3,
        schema,
    });

    await user.click(screen.getAllByRole('checkbox')[1]);

    expect(rowSelectionChangeSpy).toBeCalledWith(1, true);
});

test('Click on checkbox in header should call onAllSelectionChange callback', async() => {
    const user = userEvent.setup();
    const allSelectionChangeSpy = jest.fn();
    const data = [];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
    };
    renderTableAdapter({
        data,
        onAllSelectionChange: allSelectionChangeSpy,
        onItemSelectionChange: jest.fn(),
        schema,
    });

    await user.click(screen.getAllByRole('checkbox')[0]);

    expect(allSelectionChangeSpy).toBeCalledWith(true);
});

test('Pagination should be passed correct props', async() => {
    const user = userEvent.setup();
    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();
    renderTableAdapter({
        limit: 10,
        onLimitChange: limitChangeSpy,
        onPageChange: pageChangeSpy,
        page: 2,
        pageCount: 7,
    });

    expect(screen.getByDisplayValue('2')).toBeInTheDocument();
    expect(screen.getByText(/of\s+7/)).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'su-angle-right'}));

    expect(pageChangeSpy).toBeCalledWith(3);
});

test('Pagination should not be rendered if API is not paginated', () => {
    const data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
    ];

    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();
    renderTableAdapter({
        data,
        onLimitChange: limitChangeSpy,
        onPageChange: pageChangeSpy,
        page: 1,
        pageCount: undefined,
    });
    expect(screen.queryByRole('navigation')).not.toBeInTheDocument();
});

test('Pagination should not be rendered if no data is available', () => {
    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();
    renderTableAdapter({
        onLimitChange: limitChangeSpy,
        onPageChange: pageChangeSpy,
        page: 1,
    });
    expect(screen.queryByRole('navigation')).not.toBeInTheDocument();
});

test('Pagination should not be rendered if pagination is false', () => {
    renderTableAdapter({
        limit: 10,
        page: 2,
        pageCount: 7,
        paginated: false,
    });
    expect(screen.queryByRole('navigation')).not.toBeInTheDocument();
});
