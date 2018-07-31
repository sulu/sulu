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
    expect(treeStructureStrategy.visibleItems).toHaveLength(6);
    expect(treeStructureStrategy.visibleItems[0].title).toEqual('Homepage');
    expect(treeStructureStrategy.visibleItems[1].title).toEqual('Test1');
    expect(treeStructureStrategy.visibleItems[2].title).toEqual('Test2');
    expect(treeStructureStrategy.visibleItems[3].title).toEqual('Test3');
    expect(treeStructureStrategy.visibleItems[4].title).toEqual('Test2.1');
    expect(treeStructureStrategy.visibleItems[5].title).toEqual('Test2.2');
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

test('Should only clear children under given parent', () => {
    const treeStructureStrategy = new TreeStructureStrategy();
    treeStructureStrategy.data = [
        {
            data: {
                id: 1,
            },
            children: [
                {
                    data: {
                        id: 4,
                    },
                    children: [],
                    hasChildren: true,
                },
            ],
            hasChildren: false,
        },
    ];

    treeStructureStrategy.clear(1);
    expect(toJS(treeStructureStrategy.data.length)).toEqual(1);
    expect(toJS(treeStructureStrategy.data[0].children.length)).toEqual(0);
});

test('Should recursively add the items with data and children', () => {
    const treeStructureStrategy = new TreeStructureStrategy();

    const child4 = {
        id: 6,
        hasChildren: false,
    };
    const child2 = {
        id: 2,
        hasChildren: false,
    };
    const child3 = {
        id: 3,
        hasChildren: true,
        _embedded: {
            nodes: [
                child4,
            ],
        },
    };
    const child1 = {
        id: 1,
        hasChildren: true,
        _embedded: {
            nodes: [
                child2,
                child3,
            ],
        },
    };

    treeStructureStrategy.addItem(child1);
    expect(treeStructureStrategy.data).toHaveLength(1);
    expect(treeStructureStrategy.data[0].data.id).toEqual(1);
    expect(treeStructureStrategy.data[0].children).toHaveLength(2);
    expect(treeStructureStrategy.data[0].children[0].data.id).toEqual(2);
    expect(treeStructureStrategy.data[0].children[0].children).toHaveLength(0);
    expect(treeStructureStrategy.data[0].children[1].data.id).toEqual(3);
    expect(treeStructureStrategy.data[0].children[1].children).toHaveLength(1);
    expect(treeStructureStrategy.data[0].children[1].children[0].data.id).toEqual(6);
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
