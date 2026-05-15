// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import TreeTableAdapter from '../../adapters/TreeTableAdapter';

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
    get: jest.fn(() => {
        return {
            transform(value) {
                return value;
            },
        };
    }),
    has: jest.fn(),
}));

const renderTreeTableAdapter = (customProps: Object = {}) => {
    const props = {
        ...listAdapterDefaultProps,
        ...customProps,
    };

    return render(<TreeTableAdapter {...props} />);
};

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
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render data without header', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
        },
        children: [],
        hasChildren: true,
    };
    const test3 = {
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
    };

    const data = [
        test1,
        test2,
        test3,
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
    };
    const view = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            adapterOptions={{show_header: false}}
            data={data}
            page={1}
            pageCount={2}
            paginated={false}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render data with skin', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
        },
        children: [],
        hasChildren: true,
    };
    const test3 = {
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
    };

    const data = [
        test1,
        test2,
        test3,
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
    };
    const view = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            adapterOptions={{skin: 'flat'}}
            data={data}
            page={1}
            pageCount={2}
            paginated={false}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render data without header', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
        },
        children: [],
        hasChildren: true,
    };
    const test3 = {
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
    };

    const data = [
        test1,
        test2,
        test3,
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
    };
    const view = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            adapterOptions={{show_header: false}}
            data={data}
            page={1}
            pageCount={2}
            paginated={false}
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

    renderTreeTableAdapter({
        onSort: sortSpy,
        schema,
    });

    await user.click(screen.getByRole('button', {name: 'Title'}));

    expect(sortSpy).toBeCalledWith('title', 'asc');
    expect(screen.queryByRole('button', {name: 'Description'})).not.toBeInTheDocument();
});

test('Render data with two columns', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
            title2: 'Title2 - Test1',
        },
        children: [],
        hasChildren: false,
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
            title2: 'Title2 - Test2',
        },
        children: [],
        hasChildren: true,
    };
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
            title2: 'Title2 - Test3',
        },
        children: [],
        hasChildren: true,
    };

    const data = [
        test1,
        test2,
        test3,
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
    const view = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Render data with schema and selections', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
        },
        children: [],
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

    const data = [
        test1,
        test2,
        test3,
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
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            schema={schema}
            selections={[1, 3]}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
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

    const data = [
        test1,
        test2,
        test3,
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

    const onItemActivateSpy = jest.fn();
    const onItemDeactivateSpy = jest.fn();

    renderTreeTableAdapter({
        data,
        onItemActivate: onItemActivateSpy,
        onItemDeactivate: onItemDeactivateSpy,
        schema,
    });

    await user.click(screen.getByLabelText('su-angle-right'));
    expect(onItemActivateSpy).toBeCalledWith(6);

    await user.click(screen.getByLabelText('su-angle-down'));
    expect(onItemDeactivateSpy).toBeCalledWith(3);
});

test('Render data with pencil button and given itemActions when onItemEdit callback is passed', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const data = [
        test1,
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
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            /* eslint-disable-next-line react/jsx-no-bind */
            itemActionsProvider={actionsProvider}
            onItemClick={jest.fn()}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
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
    renderTreeTableAdapter({
        data,
        onItemAdd: jest.fn(),
        onItemClick: jest.fn(),
        schema,
    });
    const penButtons = screen.getAllByRole('button', {name: 'su-pen'});
    const eyeButtons = screen.getAllByRole('button', {name: 'su-eye'});
    const addButtons = screen.getAllByRole('button', {name: 'su-plus-circle'});

    expect(penButtons[0]).toBeDisabled();
    expect(addButtons[0]).toBeEnabled();

    expect(eyeButtons[0]).toBeEnabled();
    expect(addButtons[1]).toBeEnabled();

    expect(penButtons[1]).toBeEnabled();
    expect(addButtons[2]).toBeDisabled();

    expect(penButtons[2]).toBeEnabled();
    expect(addButtons[3]).toBeEnabled();
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
    renderTreeTableAdapter({
        data,
        disabledIds: [1, 3],
        onItemAdd: jest.fn(),
        onItemClick: jest.fn(),
        schema,
    });
    const rows = screen.getAllByRole('row');

    expect(rows[1]).toHaveClass('disabled');
    expect(rows[2]).not.toHaveClass('disabled');
    expect(rows[3]).toHaveClass('disabled');
});

test('Render data with plus button when onItemAdd callback is passed', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const data = [
        test1,
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
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemAdd={jest.fn()}
            schema={schema}
        />
    );

    expect(view.container.firstChild).toMatchSnapshot();
});

test('Click on pencil should execute onItemClick callback', async() => {
    const user = userEvent.setup();
    const rowEditClickSpy = jest.fn();
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const data = [
        test1,
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
    renderTreeTableAdapter({
        data,
        onItemClick: rowEditClickSpy,
        schema,
    });

    await user.click(screen.getByRole('button', {name: 'su-pen'}));

    expect(rowEditClickSpy.mock.calls[0][0]).toEqual(2);
});

test('Click on add should execute onItemAdd callback', async() => {
    const user = userEvent.setup();
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const data = [
        test1,
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
    const rowAddClickSpy = jest.fn();
    renderTreeTableAdapter({
        data,
        onItemAdd: rowAddClickSpy,
        schema,
    });

    await user.click(screen.getByRole('button', {name: 'su-plus-circle'}));

    expect(rowAddClickSpy.mock.calls[0][0]).toEqual(2);
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
    const data = [item1];
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

    renderTreeTableAdapter({
        data,
        itemActionsProvider: actionsProvider,
        onItemAdd: jest.fn(),
        schema,
    });

    expect(actionsProvider).toBeCalledWith(item1Data);

    await user.click(screen.getByRole('button', {name: 'su-process'}));

    expect(actionClickSpy.mock.calls[0][0]).toEqual(2);
});

test('Pagination should be passed correct props', async() => {
    const user = userEvent.setup();
    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();
    const itemActivateSpy = jest.fn();

    const item1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const data = [item1];

    renderTreeTableAdapter({
        data,
        limit: 10,
        onItemActivate: itemActivateSpy,
        onLimitChange: limitChangeSpy,
        onPageChange: pageChangeSpy,
        page: 2,
        pageCount: 7,
    });

    expect(screen.getByDisplayValue('2')).toBeInTheDocument();
    expect(screen.getByText(/of\s+7/)).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'su-angle-right'}));

    expect(pageChangeSpy).toBeCalledWith(3);
    expect(itemActivateSpy).toBeCalledWith(undefined);
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
    const data = [item1, item3];

    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();
    renderTreeTableAdapter({
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
    renderTreeTableAdapter({
        onLimitChange: limitChangeSpy,
        onPageChange: pageChangeSpy,
        page: 1,
    });
    expect(screen.queryByRole('navigation')).not.toBeInTheDocument();
});

test('Pagination should not be rendered if pagination is false', () => {
    renderTreeTableAdapter({
        limit: 10,
        page: 2,
        pageCount: 7,
        paginated: false,
    });
    expect(screen.queryByRole('navigation')).not.toBeInTheDocument();
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

    const data = [
        test1,
        test2,
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

    const onPageChangeSpy = jest.fn();
    const onItemActivateSpy = jest.fn();

    renderTreeTableAdapter({
        data,
        onItemActivate: onItemActivateSpy,
        onPageChange: onPageChangeSpy,
        page: 1,
        pageCount: 2,
        schema,
    });

    await user.click(screen.getByRole('button', {name: 'su-angle-right'}));

    expect(onPageChangeSpy).toBeCalledWith(2);
    expect(onItemActivateSpy).toBeCalledWith(undefined);
});
