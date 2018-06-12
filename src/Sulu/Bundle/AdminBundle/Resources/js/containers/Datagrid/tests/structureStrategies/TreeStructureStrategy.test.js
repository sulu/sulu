// @flow
import {toJS} from 'mobx';
import TreeStructureStrategy from '../../structureStrategies/TreeStructureStrategy';

test('Should be empty after intialization', () => {
    const treeStructureStrategy = new TreeStructureStrategy();
    expect(toJS(treeStructureStrategy.data.length)).toEqual(0);
});

test('Should only return items when parent is expanded', () => {
    const treeStructureStrategy = new TreeStructureStrategy();

    const data1 = {
        id: 2,
        title: 'Test1',
        hasChildren: false,
    };
    const test1 = {
        data: data1,
        children: [],
    };
    const data21 = {
        id: 4,
        title: 'Test2.1',
        hasChildren: false,
    };
    const test21 = {
        data: data21,
        children: [],
    };
    const data22 = {
        id: 5,
        title: 'Test2.2',
        hasChildren: false,
    };
    const test22 = {
        data: data22,
        children: [],
    };
    const data2 = {
        id: 3,
        title: 'Test2',
        hasChildren: true,
    };
    const test2 = {
        data: data2,
        children: [
            test21,
            test22,
        ],
    };
    const data31 = {
        id: 7,
        title: 'Test3.1',
        hasChildren: false,
    };
    const test31 = {
        data: data31,
        children: [],
    };
    const data32 = {
        id: 8,
        title: 'Test3.2',
        hasChildren: false,
    };
    const test32 = {
        data: data32,
        children: [],
    };
    const data3 = {
        id: 6,
        title: 'Test3',
        hasChildren: true,
    };
    const test3 = {
        data: data3,
        children: [
            test31,
            test32,
        ],
    };

    const data = {
        id: 1,
        title: 'Homepage',
        hasChildren: true,
    };

    const test = [
        {
            data,
            children: [
                test1,
                test2,
                test3,
            ],
        },
    ];

    treeStructureStrategy.rawData = test;
    expect(treeStructureStrategy.data).toEqual([
        {
            children: [],
            data,
        },
    ]);

    treeStructureStrategy.activate(1);
    expect(treeStructureStrategy.data).toEqual([
        {
            children: [
                {
                    children: [],
                    data: data1,
                },
                {
                    children: [],
                    data: data2,
                },
                {
                    children: [],
                    data: data3,
                },
            ],
            data,
        },
    ]);

    treeStructureStrategy.activate(3);
    expect(treeStructureStrategy.data).toEqual([
        {
            children: [
                {
                    children: [],
                    data: data1,
                },
                {
                    children: [
                        {
                            children: [],
                            data: data21,
                        },
                        {
                            children: [],
                            data: data22,
                        },
                    ],
                    data: data2,
                },
                {
                    children: [],
                    data: data3,
                },
            ],
            data,
        },
    ]);

    treeStructureStrategy.deactivate(3);
    expect(treeStructureStrategy.data).toEqual([
        {
            children: [
                {
                    children: [],
                    data: data1,
                },
                {
                    children: [],
                    data: data2,
                },
                {
                    children: [],
                    data: data3,
                },
            ],
            data,
        },
    ]);

    treeStructureStrategy.activate(3);
    treeStructureStrategy.deactivate(1);
    expect(treeStructureStrategy.data).toEqual([
        {
            children: [],
            data,
        },
    ]);
});

test('Should not add the same item twice as expanded', () => {
    const treeStructureStrategy = new TreeStructureStrategy();
    treeStructureStrategy.activate(1);
    treeStructureStrategy.activate(1);
    expect(treeStructureStrategy.expandedItems).toEqual([1]);
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

    treeStructureStrategy.rawData = data;
    expect(toJS(treeStructureStrategy.getData(test1.data.id))).toEqual(test1.children);
    expect(toJS(treeStructureStrategy.getData(test2.data.id))).toEqual(test2.children);
    expect(toJS(treeStructureStrategy.getData(test21.data.id))).toEqual(test21.children);
    expect(toJS(treeStructureStrategy.getData(test31.data.id))).toEqual(test31.children);
    expect(toJS(treeStructureStrategy.getData(undefined))).toEqual(data);
    expect(toJS(treeStructureStrategy.getData('test'))).toEqual(undefined);
});

test('Should be empty after clear was called', () => {
    const treeStructureStrategy = new TreeStructureStrategy();
    treeStructureStrategy.rawData = [
        {
            data: {
                id: 1,
            },
            children: [],
        },
    ];

    treeStructureStrategy.clear();
    expect(toJS(treeStructureStrategy.rawData.length)).toEqual(0);
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

    treeStructureStrategy.rawData = [
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
