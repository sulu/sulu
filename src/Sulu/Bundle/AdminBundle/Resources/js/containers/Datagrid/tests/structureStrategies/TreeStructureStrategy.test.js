// @flow
import {toJS} from 'mobx';
import TreeStructureStrategy from '../../structureStrategies/TreeStructureStrategy';

test('Should be empty after intialization', () => {
    const treeStructureStrategy = new TreeStructureStrategy();
    expect(toJS(treeStructureStrategy.data.length)).toEqual(0);
});

test('Should return the visible data as flat list', () => {
    const treeStructureStrategy = new TreeStructureStrategy();

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
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
            hasChildren: true,
        },
        children: [],
    };

    const data = [
        {
            data: {
                id: 1,
                title: 'Homepage',
                hasChildren: true,
            },
            children: [
                test1,
                test2,
                test3,
            ],
        },
    ];

    treeStructureStrategy.data = data;
    expect(treeStructureStrategy.visibleData).toHaveLength(6);
    expect(treeStructureStrategy.visibleData[0].title).toEqual('Homepage');
    expect(treeStructureStrategy.visibleData[1].title).toEqual('Test1');
    expect(treeStructureStrategy.visibleData[2].title).toEqual('Test2');
    expect(treeStructureStrategy.visibleData[3].title).toEqual('Test3');
    expect(treeStructureStrategy.visibleData[4].title).toEqual('Test2.1');
    expect(treeStructureStrategy.visibleData[5].title).toEqual('Test2.2');
});

test('Should return the correct child array on a getData call', () => {
    const treeStructureStrategy = new TreeStructureStrategy();

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
        {
            data: {
                id: 1,
                title: 'Homepage',
                hasChildren: true,
            },
            children: [
                test1,
                test2,
                test3,
            ],
        },
    ];

    treeStructureStrategy.data = data;
    expect(toJS(treeStructureStrategy.getData(test1.data.id))).toEqual(test1.children);
    expect(toJS(treeStructureStrategy.getData(test2.data.id))).toEqual(test2.children);
    expect(toJS(treeStructureStrategy.getData(test21.data.id))).toEqual(test21.children);
    expect(toJS(treeStructureStrategy.getData(test31.data.id))).toEqual(test31.children);
    expect(toJS(treeStructureStrategy.getData(undefined))).toEqual(data);
    expect(toJS(treeStructureStrategy.getData('test'))).toEqual(undefined);
});

test('Should be empty after clear was called', () => {
    const treeStructureStrategy = new TreeStructureStrategy();
    treeStructureStrategy.data = [
        {
            data: {
                id: 1,
            },
            children: [],
        },
    ];

    treeStructureStrategy.clear();
    expect(toJS(treeStructureStrategy.data.length)).toEqual(0);
});

test('Should enhance the items with data and children', () => {
    const treeStructureStrategy = new TreeStructureStrategy();
    expect(treeStructureStrategy.enhanceItem({id: 1})).toEqual({
        data: {
            id: 1,
        },
        children: [],
    });
});

test('Should find nested items by id or return undefined', () => {
    const treeStructureStrategy = new TreeStructureStrategy();

    const homeData = {
        id: 0,
        title: 'Homepage',
        hasChildren: true,
    };

    const child1 = {
        data: {
            id: 1,
            title: 'Child1',
            hasChildren: false,
        },
        children: [],
    };

    const child2 = {
        data: {
            id: 2,
            title: 'Child2',
            hasChildren: false,
        },
        children: [],
    };

    const child3a = {
        data: {
            id: '3a',
            title: 'Child3a',
            hasChildren: false,
        },
        children: [],
    };

    const child3b = {
        data: {
            id: '3b',
            title: 'Child3b',
            hasChildren: false,
        },
        children: [],
    };

    const child3 = {
        data: {
            id: 3,
            title: 'Child3',
            hasChildren: true,
        },
        children: [
            child3a,
            child3b,
        ],
    };

    treeStructureStrategy.data = [
        {
            data: homeData,
            children: [
                child1,
                child2,
                child3,
            ],
        },
    ];

    expect(treeStructureStrategy.findById(0)).toEqual(homeData);
    expect(treeStructureStrategy.findById(2)).toEqual(child2.data);
    expect(treeStructureStrategy.findById('3a')).toEqual(child3a.data);
    expect(treeStructureStrategy.findById(4)).toEqual(undefined);
});

test('Should delete children when an item gets deactivated', () => {
    const treeStructureStrategy = new TreeStructureStrategy();

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
        {
            data: {
                id: 1,
                title: 'Homepage',
                hasChildren: true,
            },
            children: [
                test1,
                test2,
                test3,
            ],
        },
    ];

    treeStructureStrategy.data = data;
    treeStructureStrategy.deactivate(6);
    expect(treeStructureStrategy.data).toMatchSnapshot();
});
