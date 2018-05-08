// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import TreeListAdapter from '../../adapters/TreeListAdapter';

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

jest.mock('../../registries/DatagridFieldTransformerRegistry', () => ({
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
            hasChildren: false,
        },
        children: [],
    };
    const test21 = {
        data: {
            id: 4,
            title: 'Test2.1',
            hasChildren: false,
        },
        children: [],
    };
    const test22 = {
        data: {
            id: 5,
            title: 'Test2.2',
            hasChildren: false,
        },
        children: [],
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
            hasChildren: true,
        },
        children: [
            test21,
            test22,
        ],
    };
    const test31 = {
        data: {
            id: 7,
            title: 'Test3.1',
            hasChildren: false,
        },
        children: [],
    };
    const test32 = {
        data: {
            id: 8,
            title: 'Test3.2',
            hasChildren: false,
        },
        children: [],
    };
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
            hasChildren: true,
        },
        children: [
            test31,
            test32,
        ],
    };

    const data = [
        test1,
        test2,
        test3,
    ];
    const schema = {
        title: {
            type: 'string',
            visibility: 'yes',
            label: 'Title',
        },
    };
    const treeListAdapter = render(
        <TreeListAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            schema={schema}
            selections={[]}
            onPageChange={jest.fn()}
            page={1}
            pageCount={2}
            onSort={jest.fn()}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Render data with two columns', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
            title2: 'Title2 - Test1',
            hasChildren: false,
        },
        children: [],
    };
    const test21 = {
        data: {
            id: 4,
            title: 'Test2.1',
            title2: 'Title2 - Test2.1',
            hasChildren: false,
        },
        children: [],
    };
    const test22 = {
        data: {
            id: 5,
            title: 'Test2.2',
            title2: 'Title2 - Test2.2',
            hasChildren: false,
        },
        children: [],
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
            title2: 'Title2 - Test2',
            hasChildren: true,
        },
        children: [
            test21,
            test22,
        ],
    };
    const test31 = {
        data: {
            id: 7,
            title: 'Test3.1',
            title2: 'Title2 - Test3.1',
            hasChildren: false,
        },
        children: [],
    };
    const test32 = {
        data: {
            id: 8,
            title: 'Test3.2',
            title2: 'Title2 - Test3.2',
            hasChildren: false,
        },
        children: [],
    };
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
            title2: 'Title2 - Test3',
            hasChildren: true,
        },
        children: [
            test31,
            test32,
        ],
    };

    const data = [
        test1,
        test2,
        test3,
    ];
    const schema = {
        title: {
            type: 'string',
            visibility: 'yes',
            label: 'Title',
        },
        title2: {
            type: 'string',
            visibility: 'yes',
            label: 'Title2',
        },
    };
    const treeListAdapter = render(
        <TreeListAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            schema={schema}
            selections={[]}
            onPageChange={jest.fn()}
            page={1}
            pageCount={2}
            onSort={jest.fn()}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Render data with schema and selections', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
            hasChildren: false,
        },
        children: [],
    };
    const test21 = {
        data: {
            id: 4,
            title: 'Test2.1',
            hasChildren: false,
        },
        children: [],
    };
    const test22 = {
        data: {
            id: 5,
            title: 'Test2.2',
            hasChildren: false,
        },
        children: [],
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
            hasChildren: true,
        },
        children: [
            test21,
            test22,
        ],
    };
    const test31 = {
        data: {
            id: 7,
            title: 'Test3.1',
            hasChildren: false,
        },
        children: [],
    };
    const test32 = {
        data: {
            id: 8,
            title: 'Test3.2',
            hasChildren: false,
        },
        children: [],
    };
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
            hasChildren: true,
        },
        children: [
            test31,
            test32,
        ],
    };

    const data = [
        test1,
        test2,
        test3,
    ];
    const schema = {
        title: {
            type: 'string',
            visibility: 'yes',
            label: 'Title',
        },
    };
    const treeListAdapter = render(
        <TreeListAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            schema={schema}
            selections={[1, 3]}
            onPageChange={jest.fn()}
            page={1}
            pageCount={2}
            onSort={jest.fn()}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Execute onItemActivation callback when an item is expanded', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
            hasChildren: false,
        },
        children: [],
    };
    const test21 = {
        data: {
            id: 4,
            title: 'Test2.1',
            hasChildren: false,
        },
        children: [],
    };
    const test22 = {
        data: {
            id: 5,
            title: 'Test2.2',
            hasChildren: false,
        },
        children: [],
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
            hasChildren: true,
        },
        children: [
            test21,
            test22,
        ],
    };
    const test31 = {
        data: {
            id: 7,
            title: 'Test3.1',
            hasChildren: false,
        },
        children: [],
    };
    const test32 = {
        data: {
            id: 8,
            title: 'Test3.2',
            hasChildren: false,
        },
        children: [],
    };
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
            hasChildren: true,
        },
        children: [
            test31,
            test32,
        ],
    };

    const data = [
        test1,
        test2,
        test3,
    ];
    const schema = {
        title: {
            type: 'string',
            visibility: 'yes',
            label: 'Title',
        },
    };
    const onItemActivationSpy = jest.fn();
    const treeListAdapter = mount(
        <TreeListAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            schema={schema}
            selections={[]}
            onPageChange={jest.fn()}
            page={1}
            pageCount={1}
            onItemActivation={onItemActivationSpy}
            onSort={jest.fn()}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    // expand the row
    treeListAdapter.find('Row').at(2).find('span.toggleIcon').simulate('click');
    expect(onItemActivationSpy).toBeCalledWith(6);
    expect(treeListAdapter.render()).toMatchSnapshot();

    // close the row
    treeListAdapter.find('Row').at(2).find('span.toggleIcon').simulate('click');
    expect(treeListAdapter.render()).toMatchSnapshot();
});

test('Render data with pencil button when onItemEdit callback is passed', () => {
    const rowEditClickSpy = jest.fn();
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
            hasChildren: false,
        },
        children: [],
    };
    const data = [
        test1,
    ];
    const schema = {
        title: {
            label: 'Title',
            type: 'string',
            visibility: 'no',
        },
        description: {
            label: 'Description',
            type: 'string',
            visibility: 'yes',
        },
    };
    const treeListAdapter = render(
        <TreeListAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            schema={schema}
            selections={[]}
            onPageChange={jest.fn()}
            page={1}
            pageCount={1}
            onItemClick={rowEditClickSpy}
            onSort={jest.fn()}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Render data with plus button when onItemAdd callback is passed', () => {
    const rowAddClickSpy = jest.fn();
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
            hasChildren: false,
        },
        children: [],
    };
    const data = [
        test1,
    ];
    const schema = {
        title: {
            label: 'Title',
            type: 'string',
            visibility: 'no',
        },
        description: {
            label: 'Description',
            type: 'string',
            visibility: 'yes',
        },
    };
    const treeListAdapter = render(
        <TreeListAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            schema={schema}
            selections={[]}
            onPageChange={jest.fn()}
            page={1}
            pageCount={1}
            onItemAdd={rowAddClickSpy}
            onSort={jest.fn()}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Render data with plus button when onItemEdit callback is passed', () => {
    const rowEditClickSpy = jest.fn();
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
            hasChildren: false,
        },
        children: [],
    };
    const data = [
        test1,
    ];
    const schema = {
        title: {
            label: 'Title',
            type: 'string',
            visibility: 'no',
        },
        description: {
            label: 'Description',
            type: 'string',
            visibility: 'yes',
        },
    };
    const treeListAdapter = render(
        <TreeListAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            schema={schema}
            selections={[]}
            onPageChange={jest.fn()}
            page={1}
            pageCount={1}
            onItemClick={rowEditClickSpy}
            onSort={jest.fn()}
            sortColumn={undefined}
            sortOrder={undefined}
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
            hasChildren: false,
        },
        children: [],
    };
    const data = [
        test1,
    ];
    const schema = {
        title: {
            label: 'Title',
            type: 'string',
            visibility: 'no',
        },
        description: {
            label: 'Description',
            type: 'string',
            visibility: 'yes',
        },
    };
    const treeListAdapter = shallow(
        <TreeListAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            schema={schema}
            selections={[]}
            onPageChange={jest.fn()}
            page={1}
            pageCount={1}
            onItemClick={rowEditClickSpy}
            onSort={jest.fn()}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );
    const buttons = treeListAdapter.find('Table').prop('buttons');
    expect(buttons).toHaveLength(1);
    expect(buttons[0].icon).toBe('su-pen');

    buttons[0].onClick(1);
    expect(rowEditClickSpy).toBeCalledWith(1);
});

test('Click on add should execute onAddClick callback', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
            hasChildren: false,
        },
        children: [],
    };
    const data = [
        test1,
    ];
    const schema = {
        title: {
            label: 'Title',
            type: 'string',
            visibility: 'no',
        },
        description: {
            label: 'Description',
            type: 'string',
            visibility: 'yes',
        },
    };
    const rowAddClickSpy = jest.fn();
    const treeListAdapter = shallow(
        <TreeListAdapter
            data={data}
            disabledIds={[]}
            loading={false}
            schema={schema}
            selections={[]}
            onPageChange={jest.fn()}
            page={1}
            pageCount={1}
            onAddClick={rowAddClickSpy}
            onSort={jest.fn()}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );
    const buttons = treeListAdapter.find('Table').prop('buttons');
    expect(buttons).toHaveLength(1);
    expect(buttons[0].icon).toBe('su-plus');

    buttons[0].onClick(1);
    expect(rowAddClickSpy).toBeCalledWith(1);
});
