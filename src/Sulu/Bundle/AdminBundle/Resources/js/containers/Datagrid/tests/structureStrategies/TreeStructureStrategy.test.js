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
        {
            data: {
                id: 1,
                title: 'Homepage',
            },
            children: [
                test1,
                test2,
                test3,
            ],
            hasChildren: true,
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
    const test31 = {
        data: {
            id: 7,
            title: 'Test3.1',
        },
        children: [],
        hasChildren: false,
    };
    const test32 = {
        data: {
            id: 8,
            title: 'Test3.2',
        },
        children: [],
        hasChildren: false,
    };
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
        },
        children: [
            test31,
            test32,
        ],
        hasChildren: true,
    };

    const data = [
        {
            data: {
                id: 1,
                title: 'Homepage',
            },
            children: [
                test1,
                test2,
                test3,
            ],
            hasChildren: true,
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
            hasChildren: false,
        },
    ];

    treeStructureStrategy.clear();
    expect(toJS(treeStructureStrategy.data.length)).toEqual(0);
});

test('Should enhance the items with data and children', () => {
    const treeStructureStrategy = new TreeStructureStrategy();
    expect(treeStructureStrategy.enhanceItem({id: 1, hasChildren: false})).toEqual({
        data: {
            id: 1,
            hasChildren: false,
        },
        children: [],
        hasChildren: false,
    });
});

test('Should find nested items by id or return undefined', () => {
    const treeStructureStrategy = new TreeStructureStrategy();

    const homeData = {
        id: 0,
        title: 'Homepage',
    };

    const child1 = {
        data: {
            id: 1,
            title: 'Child1',
        },
        children: [],
        hasChildren: false,
    };

    const child2 = {
        data: {
            id: 2,
            title: 'Child2',
        },
        children: [],
        hasChildren: false,
    };

    const child3a = {
        data: {
            id: '3a',
            title: 'Child3a',
        },
        children: [],
        hasChildren: false,
    };

    const child3b = {
        data: {
            id: '3b',
            title: 'Child3b',
        },
        children: [],
        hasChildren: false,
    };

    const child3 = {
        data: {
            id: 3,
            title: 'Child3',
        },
        children: [
            child3a,
            child3b,
        ],
        hasChildren: true,
    };

    treeStructureStrategy.data = [
        {
            data: homeData,
            children: [
                child1,
                child2,
                child3,
            ],
            hasChildren: true,
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
    const test31 = {
        data: {
            id: 7,
            title: 'Test3.1',
        },
        children: [],
        hasChildren: false,
    };
    const test32 = {
        data: {
            id: 8,
            title: 'Test3.2',
        },
        children: [],
        hasChildren: false,
    };
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
        },
        children: [
            test31,
            test32,
        ],
        hasChildren: true,
    };

    const data = [
        {
            data: {
                id: 1,
                title: 'Homepage',
            },
            children: [
                test1,
                test2,
                test3,
            ],
            hasChildren: true,
        },
    ];

    treeStructureStrategy.data = data;
    treeStructureStrategy.deactivate(6);
    expect(treeStructureStrategy.data).toMatchSnapshot();
});

test('Should remove items when an item gets removed', () => {
    const treeStructureStrategy = new TreeStructureStrategy();

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
        {
            data: {
                id: 1,
                title: 'Homepage',
            },
            children: [
                test1,
                test2,
                test3,
            ],
            hasChildren: true,
        },
    ];

    treeStructureStrategy.data = data;
    expect(treeStructureStrategy.data[0].children).toHaveLength(3);
    treeStructureStrategy.remove(6);
    expect(treeStructureStrategy.data).toMatchSnapshot();
});

test('Should remove items and set hasChildren to false if it was the last child', () => {
    const treeStructureStrategy = new TreeStructureStrategy();

    const data = [
        {
            data: {
                id: 1,
                title: 'Test',
            },
            children: [
                {
                    data: {
                        id: 4,
                        title: 'Subtest',
                    },
                    children: [],
                    hasChildren: false,
                },
            ],
            hasChildren: true,
        },
        {
            data: {
                id: 2,
                title: 'Test1',
            },
            children: [
                {
                    data: {
                        id: 3,
                    },
                    children: [],
                    hasChildren: false,
                },
            ],
            hasChildren: true,
        },
    ];

    treeStructureStrategy.data = data;
    treeStructureStrategy.remove(3);
    expect(treeStructureStrategy.data).toMatchSnapshot();
});
