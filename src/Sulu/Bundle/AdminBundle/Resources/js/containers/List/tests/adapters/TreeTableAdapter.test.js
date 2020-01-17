// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import TreeTableAdapter from '../../adapters/TreeTableAdapter';

jest.mock('../../../../utils/Translator', () => ({
    translate: function(key) {
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

test('Render data with schema', () => {
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
            ghostLocale: 'en',
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const treeListAdapter = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            schema={schema}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const treeListAdapter = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            options={{showHeader: false}}
            page={1}
            pageCount={2}
            schema={schema}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Attach onClick handler for sorting if schema says the header is sortable', () => {
    const sortSpy = jest.fn();

    const schema = {
        title: {
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
        description: {
            type: 'string',
            sortable: false,
            visibility: 'yes',
            label: 'Description',
        },
    };

    const treeTableAdapter = shallow(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            onSort={sortSpy}
            schema={schema}
        />
    );

    expect(treeTableAdapter.find('HeaderCell').at(0).prop('onClick')).toBe(sortSpy);
    expect(treeTableAdapter.find('HeaderCell').at(1).prop('onClick')).toEqual(undefined);
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
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
        title2: {
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title2',
        },
    };
    const treeListAdapter = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            schema={schema}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
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
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
    };
    const treeListAdapter = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            schema={schema}
            selections={[1, 3]}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Execute onItemActivate respectively onItemDeactivate callback when an item is clicked', () => {
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
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
    };

    const onItemActivateSpy = jest.fn();
    const onItemDeactivateSpy = jest.fn();

    const treeListAdapter = mount(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemActivate={onItemActivateSpy}
            onItemDeactivate={onItemDeactivateSpy}
            schema={schema}
        />
    );

    // expand the row
    treeListAdapter.find('Row[id=6]').find('span.toggleIcon Icon').simulate('click');
    expect(onItemActivateSpy).toBeCalledWith(6);

    // close the row
    treeListAdapter.find('Row[id=3]').find('span.toggleIcon Icon').simulate('click');
    expect(onItemDeactivateSpy).toBeCalledWith(3);
});

test('Render data with pencil button when onItemEdit callback is passed', () => {
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const treeListAdapter = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemClick={rowEditClickSpy}
            schema={schema}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
    };
    const treeListAdapter = mount(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemAdd={jest.fn()}
            onItemClick={jest.fn()}
            schema={schema}
        />
    );

    expect(treeListAdapter.find('Row').at(0).find('ButtonCell').at(0).props().icon).toEqual('su-pen');
    expect(treeListAdapter.find('Row').at(0).find('ButtonCell').at(0).props().disabled).toEqual(true);
    expect(treeListAdapter.find('Row').at(0).find('ButtonCell').at(1).props().icon).toEqual('su-plus-circle');
    expect(treeListAdapter.find('Row').at(0).find('ButtonCell').at(1).props().disabled).toEqual(false);

    expect(treeListAdapter.find('Row').at(1).find('ButtonCell').at(0).props().icon).toEqual('su-eye');
    expect(treeListAdapter.find('Row').at(1).find('ButtonCell').at(0).props().disabled).toEqual(false);
    expect(treeListAdapter.find('Row').at(1).find('ButtonCell').at(1).props().icon).toEqual('su-plus-circle');
    expect(treeListAdapter.find('Row').at(1).find('ButtonCell').at(1).props().disabled).toEqual(false);

    expect(treeListAdapter.find('Row').at(2).find('ButtonCell').at(0).props().icon).toEqual('su-pen');
    expect(treeListAdapter.find('Row').at(2).find('ButtonCell').at(0).props().disabled).toEqual(false);
    expect(treeListAdapter.find('Row').at(2).find('ButtonCell').at(1).props().icon).toEqual('su-plus-circle');
    expect(treeListAdapter.find('Row').at(2).find('ButtonCell').at(1).props().disabled).toEqual(true);

    expect(treeListAdapter.find('Row').at(3).find('ButtonCell').at(0).props().icon).toEqual('su-pen');
    expect(treeListAdapter.find('Row').at(3).find('ButtonCell').at(0).props().disabled).toEqual(false);
    expect(treeListAdapter.find('Row').at(3).find('ButtonCell').at(1).props().icon).toEqual('su-plus-circle');
    expect(treeListAdapter.find('Row').at(3).find('ButtonCell').at(1).props().disabled).toEqual(false);
});

test('Render correct buttons based on permissions when item permissions are provided', () => {
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
                }
            ],
            hasChildren: true,
        },
    ];
    const schema = {
        title: {
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
    };
    const treeListAdapter = mount(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            disabledIds={[1, 3]}
            onItemAdd={jest.fn()}
            onItemClick={jest.fn()}
            schema={schema}
        />
    );

    expect(treeListAdapter.find('Row').at(0).props().disabled).toEqual(true);
    expect(treeListAdapter.find('Row').at(1).props().disabled).toEqual(false);
    expect(treeListAdapter.find('Row').at(2).props().disabled).toEqual(true);
});

test('Render data with plus button when onItemAdd callback is passed', () => {
    const rowAddClickSpy = jest.fn();
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const treeListAdapter = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemAdd={rowAddClickSpy}
            schema={schema}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Click on pencil should execute onItemClick callback', () => {
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const treeListAdapter = shallow(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemClick={rowEditClickSpy}
            schema={schema}
        />
    );
    const buttons = treeListAdapter.find('Table').prop('buttons');
    expect(buttons).toHaveLength(1);
    expect(buttons[0].icon).toBe('su-pen');

    buttons[0].onClick(1);
    expect(rowEditClickSpy).toBeCalledWith(1);
});

test('Click on add should execute onItemAdd callback', () => {
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
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const rowAddClickSpy = jest.fn();
    const treeListAdapter = shallow(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemAdd={rowAddClickSpy}
            schema={schema}
        />
    );
    const buttons = treeListAdapter.find('Table').prop('buttons');
    expect(buttons).toHaveLength(1);
    expect(buttons[0].icon).toBe('su-plus-circle');

    buttons[0].onClick(1);
    expect(rowAddClickSpy).toBeCalledWith(1);
});
