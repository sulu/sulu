// @flow
import 'url-search-params-polyfill';
import {autorun, observable, toJS, when} from 'mobx';
import ResourceRequester from '../../../../services/ResourceRequester';
import DatagridStore from '../../stores/DatagridStore';
import metadataStore from '../../stores/MetadataStore';
import {userStore} from '../../../../stores';

jest.mock('../../stores/MetadataStore', () => ({
    getSchema: jest.fn(() => Promise.resolve()),
}));

jest.mock('../../../../services/ResourceRequester', () => ({
    delete: jest.fn(),
    postWithId: jest.fn(),
}));

jest.mock('../../../../stores/UserStore', () => ({
    getPersistentSetting: jest.fn(),
    setPersistentSetting: jest.fn(),
}));

class LoadingStrategy {
    destroy = jest.fn();
    initialize = jest.fn();
    load = jest.fn().mockReturnValue(Promise.resolve({}));
    reset = jest.fn();
    setStructureStrategy = jest.fn();
}

class OtherLoadingStrategy {
    destroy = jest.fn();
    initialize = jest.fn();
    load = jest.fn().mockReturnValue(Promise.resolve({}));
    reset = jest.fn();
    setStructureStrategy = jest.fn();
}

class StructureStrategy {
    @observable data = [];
    visibleItems = [];
    addItem = jest.fn();
    clear = jest.fn();
    activeItems = [];
    activate = jest.fn();
    deactivate = jest.fn();
    remove = jest.fn();
    order = jest.fn();
    findById: (id: string | number) => ?Object = jest.fn();
}

test('The loading strategy should get passed the structure strategy', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();

    const datagridStore = new DatagridStore('tests', 'datagrid_test', {page: observable.box()});
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);
    expect(loadingStrategy.setStructureStrategy).toBeCalledWith(structureStrategy);
});

test('The loading strategy should be called when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const locale = observable.box();
    const additionalValue = observable.box(5);

    const datagridStore = new DatagridStore(
        'tests',
        'datagrid_test',
        {
            page,
            locale,
            additionalValue,
        },
        {
            test: 'value',
        }
    );
    datagridStore.schema = {};

    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        'tests',
        {
            additionalValue: 5,
            fields: [
                'id',
            ],
            locale: undefined,
            page: 1,
            test: 'value',
            limit: 10,
            sortBy: undefined,
            sortOrder: undefined,
        },
        undefined
    );

    datagridStore.destroy();
});

test('The user store should be called correctly when changing the schema', () => {
    const page = observable.box(1);
    const locale = observable.box();
    const additionalValue = observable.box(5);
    const schema = {
        id: {
            label: 'ID',
            name: 'id',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        changed: {
            label: 'Changed at',
            name: 'changed',
            sortable: true,
            type: 'datetime',
            visibility: 'no',
        },
        title: {
            label: 'Title',
            name: 'title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
        name: {
            label: 'Name',
            name: 'name',
            sortable: true,
            type: 'string',
            visibility: 'always',
        },
    };
    const schemaPromise = Promise.resolve(schema);
    metadataStore.getSchema.mockReturnValueOnce(schemaPromise);

    const datagridStore = new DatagridStore(
        'tests',
        'datagrid_test',
        {
            page,
            locale,
            additionalValue,
        },
        {
            test: 'value',
        }
    );

    return schemaPromise.then(() => {
        const newSchema = {
            id: {
                label: 'ID',
                name: 'id',
                sortable: true,
                type: 'string',
                visibility: 'no',
            },
            changed: {
                label: 'Changed at',
                name: 'changed',
                sortable: true,
                type: 'datetime',
                visibility: 'no',
            },
            title: {
                label: 'Title',
                name: 'title',
                sortable: true,
                type: 'string',
                visibility: 'no',
            },
            name: {
                label: 'Name',
                name: 'name',
                sortable: true,
                type: 'string',
                visibility: 'always',
            },
        };
        datagridStore.changeUserSchema(newSchema);

        expect(userStore.setPersistentSetting).toBeCalledWith(
            'sulu_admin.datagrid_store.tests.datagrid_test.schema',
            [
                {
                    'schemaKey': 'id',
                    'visibility': 'no',
                },
                {
                    'schemaKey': 'changed',
                    'visibility': 'no',
                },
                {
                    'schemaKey': 'title',
                    'visibility': 'no',
                },
                {
                    'schemaKey': 'name',
                    'visibility': 'always',
                },
            ]
        );

        datagridStore.destroy();
    });
});

test('The loading strategy should be called with a different resourceKey when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const locale = observable.box();
    const datagridStore = new DatagridStore(
        'snippets',
        'datagrid_test',
        {
            page,
            locale,
        },
        {
            test: 'value',
        }
    );
    datagridStore.schema = {};

    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        'snippets',
        {
            fields: [
                'id',
            ],
            locale: undefined,
            page: 1,
            test: 'value',
            limit: 10,
            sortBy: undefined,
            sortOrder: undefined,
        },
        undefined
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with a different page when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const locale = observable.box();
    const datagridStore = new DatagridStore(
        'snippets',
        'datagrid_test',
        {
            page,
            locale,
        },
        {
            test: 'value',
        }
    );
    datagridStore.schema = {};
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        'snippets',
        {
            fields: [
                'id',
            ],
            locale: undefined,
            page: 1,
            test: 'value',
            limit: 10,
            sortBy: undefined,
            sortOrder: undefined,
        },
        undefined
    );

    page.set(3);
    expect(loadingStrategy.load).toBeCalledWith(
        'snippets',
        {
            fields: [
                'id',
            ],
            locale: undefined,
            page: 3,
            test: 'value',
            limit: 10,
            sortBy: undefined,
            sortOrder: undefined,
        },
        undefined
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with a different page when a request is sent ', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const locale = observable.box();
    const datagridStore = new DatagridStore(
        'snippets',
        'datagrid_test',
        {
            page,
            locale,
        },
        {
            test: 'value',
        }
    );
    datagridStore.schema = {};
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        'snippets',
        {
            fields: [
                'id',
            ],
            locale: undefined,
            page: 1,
            test: 'value',
            limit: 10,
            sortBy: undefined,
            sortOrder: undefined,
        },
        undefined
    );

    page.set(3);
    expect(loadingStrategy.load).toBeCalledWith(
        'snippets',
        {
            fields: [
                'id',
            ],
            locale: undefined,
            page: 3,
            test: 'value',
            limit: 10,
            sortBy: undefined,
            sortOrder: undefined,
        },
        undefined
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with a different locale when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore(
        'snippets',
        'datagrid_test',
        {
            page,
            locale,
        },
        {
            test: 'value',
        }
    );
    datagridStore.schema = {};
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        'snippets',
        {
            fields: [
                'id',
            ],
            locale: 'en',
            page: 1,
            test: 'value',
            limit: 10,
            sortBy: undefined,
            sortOrder: undefined,
        },
        undefined
    );

    locale.set('de');
    expect(loadingStrategy.load).toBeCalledWith(
        'snippets',
        {
            fields: [
                'id',
            ],
            locale: 'de',
            page: 1,
            test: 'value',
            limit: 10,
            sortBy: undefined,
            sortOrder: undefined,
        },
        undefined
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with the defined sortings', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const datagridStore = new DatagridStore(
        'snippets',
        'datagrid_test',
        {
            page,
        }
    );
    datagridStore.schema = {};
    datagridStore.sort('title', 'desc');
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        'snippets',
        {
            fields: [
                'id',
            ],
            page: 1,
            sortBy: 'title',
            sortOrder: 'desc',
            limit: 10,
        },
        undefined
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with the defined search', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(2);
    const datagridStore = new DatagridStore(
        'snippets',
        'datagrid_test',
        {
            page,
        }
    );
    datagridStore.schema = {};

    structureStrategy.clear = jest.fn();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.search('search-value');

    expect(loadingStrategy.load).toBeCalledWith(
        'snippets',
        {
            fields: [
                'id',
            ],
            page: 1,
            search: 'search-value',
            limit: 10,
            sortBy: undefined,
            sortOrder: undefined,
        },
        undefined
    );

    expect(structureStrategy.clear).toBeCalled();

    datagridStore.destroy();
});

test('The loading strategy should be called with the active item as parentId', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const datagridStore = new DatagridStore(
        'snippets',
        'datagrid_test',
        {
            page,
        }
    );
    datagridStore.schema = {};

    structureStrategy.findById.mockReturnValue({});
    datagridStore.setActive('some-uuid');
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    expect(structureStrategy.findById).toBeCalledWith('some-uuid');
    expect(loadingStrategy.load).toBeCalledWith(
        'snippets',
        {
            fields: [
                'id',
            ],
            page: 1,
            parentId: 'some-uuid',
            limit: 10,
            sortBy: undefined,
            sortOrder: undefined,
        },
        'some-uuid'
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with expandedIds if the active item is not available', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const datagridStore = new DatagridStore(
        'snippets',
        'datagrid_test',
        {
            page,
        }
    );
    datagridStore.schema = {};

    datagridStore.setActive('some-uuid');
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    expect(structureStrategy.findById).toBeCalledWith('some-uuid');
    expect(structureStrategy.clear).toBeCalledWith();
    expect(loadingStrategy.load).toBeCalledWith(
        'snippets',
        {
            fields: [
                'id',
            ],
            expandedIds: 'some-uuid',
            page: 1,
            limit: 10,
            sortBy: undefined,
            sortOrder: undefined,
        },
        undefined
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with expandedIds if some items are already selected', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const datagridStore = new DatagridStore(
        'categories',
        'datagrid_test',
        {
            page,
        },
        {},
        [1, 5, 10]
    );
    datagridStore.schema = {};

    const promise = Promise.resolve({});
    loadingStrategy.load.mockReturnValue(promise);

    structureStrategy.findById.mockImplementation((id) => {
        switch (id) {
            case 1:
                return {id: 1};
            case 5:
                return {id: 5};
            case 10:
                return {id: 10};
        }
    });

    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        'categories',
        {
            fields: [
                'id',
            ],
            selectedIds: '1,5,10',
            page: 1,
            limit: 10,
            sortBy: undefined,
            sortOrder: undefined,
        },
        undefined
    );

    return promise.then(() => {
        expect(structureStrategy.findById).toBeCalledWith(1);
        expect(structureStrategy.findById).toBeCalledWith(5);
        expect(structureStrategy.findById).toBeCalledWith(10);

        expect(datagridStore.selectionIds).toEqual([1, 5, 10]);
        expect(datagridStore.initialSelectionIds).toEqual(undefined);

        datagridStore.destroy();
    });
});

test('The loading strategy should be called only once even if the data changes afterwards for some reason', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const datagridStore = new DatagridStore(
        'snippets',
        'datagrid_test',
        {
            page,
        }
    );
    datagridStore.schema = {};
    datagridStore.setActive('some-uuid');
    structureStrategy.findById.mockImplementation(() => Array.from(structureStrategy.data));
    datagridStore.updateStructureStrategy(structureStrategy);
    datagridStore.updateLoadingStrategy(loadingStrategy);

    expect(structureStrategy.findById).toBeCalledWith('some-uuid');
    expect(loadingStrategy.load).toHaveBeenCalledTimes(1);
    structureStrategy.data.push({});
    expect(loadingStrategy.load).toHaveBeenCalledTimes(1);

    datagridStore.destroy();
});

test('The active item should not be passed as parent if undefined', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const datagridStore = new DatagridStore(
        'snippets',
        'datagrid_test',
        {
            page,
        },
        {
            parent: 9,
        }
    );
    datagridStore.schema = {};
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        'snippets',
        {
            fields: [
                'id',
            ],
            page: 1,
            parent: 9,
            limit: 10,
            sortBy: undefined,
            sortOrder: undefined,
        },
        undefined
    );

    datagridStore.destroy();
});

test('The activeItems from the StructureStrategy should be passed', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);

    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {
        page,
    });
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    const activeItems = [1, 2, 3];
    structureStrategy.activeItems = activeItems;
    expect(datagridStore.activeItems).toBe(activeItems);
});

test('Set loading flag to true before schema is loaded', () => {
    const promise = Promise.resolve();
    metadataStore.getSchema.mockReturnValueOnce(promise);
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', 'datagrid_test', {page});
    datagridStore.updateLoadingStrategy(new LoadingStrategy());
    datagridStore.updateStructureStrategy(new StructureStrategy());
    page.set(1);
    datagridStore.setDataLoading(false);
    expect(datagridStore.loading).toEqual(true);
    return promise.then(() => {
        expect(datagridStore.loading).toEqual(false);
        datagridStore.destroy();
    });
});

test('Set loading flag to true before request', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', 'datagrid_test', {page});
    datagridStore.updateLoadingStrategy(new LoadingStrategy());
    datagridStore.updateStructureStrategy(new StructureStrategy());
    page.set(1);
    datagridStore.setDataLoading(false);
    datagridStore.sendRequest();
    expect(datagridStore.loading).toEqual(true);
    datagridStore.destroy();
});

test('Set loading flag to false after request', (done) => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', 'datagrid_test', {page});
    datagridStore.schema = {};
    const promise = Promise.resolve({
        pages: 3,
    });
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    loadingStrategy.load.mockReturnValue(promise);
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);
    datagridStore.sendRequest();
    return promise.then(() => {
        expect(datagridStore.loading).toEqual(false);
        datagridStore.destroy();
        done();
    });
});

test('Get schema from MetadataStore for correct resourceKey', () => {
    const schema = {
        id: {
            label: 'ID',
            name: 'id',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        changed: {
            label: 'Changed at',
            name: 'changed',
            sortable: true,
            type: 'datetime',
            visibility: 'no',
        },
        title: {
            label: 'Title',
            name: 'title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
        name: {
            label: 'Name',
            name: 'name',
            sortable: true,
            type: 'string',
            visibility: 'always',
        },
    };
    const schemaPromise = Promise.resolve(schema);
    metadataStore.getSchema.mockReturnValueOnce(schemaPromise);

    const page = observable.box();
    const datagridStore = new DatagridStore('tests', 'datagrid_test', {
        page,
    });
    datagridStore.updateLoadingStrategy(new LoadingStrategy());
    datagridStore.updateStructureStrategy(new StructureStrategy());
    expect(metadataStore.getSchema).toBeCalledWith('tests');
    return schemaPromise.then(() => {
        expect(datagridStore.schema).toEqual(schema);
        datagridStore.destroy();
    });
});

test('After initialization no row should be selected', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', 'datagrid_test', {
        page,
    });
    datagridStore.updateLoadingStrategy(new LoadingStrategy());
    datagridStore.updateStructureStrategy(new StructureStrategy());
    expect(datagridStore.selections.length).toBe(0);
    datagridStore.destroy();
});

test('Select an item', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', 'datagrid_test', {
        page,
    });
    datagridStore.updateLoadingStrategy(new LoadingStrategy());
    datagridStore.updateStructureStrategy(new StructureStrategy());
    datagridStore.select({id: 1}) ;
    datagridStore.select({id: 2}) ;
    expect(toJS(datagridStore.selectionIds)).toEqual([1, 2]);

    datagridStore.deselect({id: 1});
    expect(toJS(datagridStore.selectionIds)).toEqual([2]);
    datagridStore.destroy();
});

test('Deselect an item that has not been selected yet', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', 'datagrid_test', {
        page,
    });
    datagridStore.updateLoadingStrategy(new LoadingStrategy());
    datagridStore.updateStructureStrategy(new StructureStrategy());
    datagridStore.select({id: 1}) ;
    datagridStore.deselect({id: 2});

    expect(toJS(datagridStore.selectionIds)).toEqual([1]);
    datagridStore.destroy();
});

test('Select all visible items', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', 'datagrid_test', {page});
    datagridStore.updateLoadingStrategy(new LoadingStrategy());
    datagridStore.updateStructureStrategy(new StructureStrategy());
    // $FlowFixMe
    datagridStore.structureStrategy.visibleItems = [{id: 1}, {id: 2}, {id: 3}];
    datagridStore.selections = [
        {id: 1},
        {id: 7},
    ];
    datagridStore.selectVisibleItems();
    expect(toJS(datagridStore.selectionIds)).toEqual([1, 7, 2, 3]);
    datagridStore.destroy();
});

test('Deselect all visible items', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', 'datagrid_test', {
        page,
    });
    datagridStore.updateLoadingStrategy(new LoadingStrategy());
    datagridStore.updateStructureStrategy(new StructureStrategy());
    // $FlowFixMe
    datagridStore.structureStrategy.visibleItems = [{id: 1}, {id: 2}, {id: 3}];
    datagridStore.selections = [
        {id: 1},
        {id: 2},
        {id: 7},
    ];
    datagridStore.deselectVisibleItems();
    expect(toJS(datagridStore.selectionIds)).toEqual([7]);
    datagridStore.destroy();
});

test('Deselect an item by id', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', 'datagrid_test', {
        page,
    });
    datagridStore.updateLoadingStrategy(new LoadingStrategy());
    datagridStore.updateStructureStrategy(new StructureStrategy());
    // $FlowFixMe
    datagridStore.structureStrategy.visibleItems = [{id: 1}, {id: 2}, {id: 3}];
    datagridStore.selections = [
        {id: 1},
        {id: 2},
        {id: 7},
    ];
    datagridStore.deselectById(7);
    expect(toJS(datagridStore.selectionIds)).toEqual([1, 2]);
    datagridStore.destroy();
});

test('Clear the selection', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', 'datagrid_test', {
        page,
    });
    datagridStore.updateLoadingStrategy(new LoadingStrategy());
    datagridStore.updateStructureStrategy(new StructureStrategy());
    datagridStore.selections = [{id: 1}, {id: 4}, {id: 5}];
    page.set(1);
    expect(datagridStore.selections).toHaveLength(3);

    datagridStore.clearSelection();
    expect(datagridStore.selections).toHaveLength(0);
});

test('Clear the data', () => {
    const datagridStore = new DatagridStore('tests', 'datagrid_test', {
        page: observable.box(),
    });
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(new LoadingStrategy());
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.clear();
    expect(structureStrategy.clear).toBeCalled();
});

test('Should reset the data array and set page to 1 when the reload method is called', (done) => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();

    const promise = Promise.resolve({});
    loadingStrategy.load.mockReturnValue(promise);

    const page = observable.box();
    const locale = observable.box();
    const datagridStore = new DatagridStore(
        'tests',
        'datagrid_test',
        {
            page,
            locale,
        },
        {}
    );
    datagridStore.schema = {};
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    locale.set('en');
    page.set(3);
    structureStrategy.findById.mockReturnValue({});
    datagridStore.setActive(1);
    expect(structureStrategy.findById).toBeCalledWith(1);

    when(
        () => !datagridStore.loading,
        (): void => {
            expect(page.get()).toBe(3);

            datagridStore.reload();
            expect(structureStrategy.clear).toBeCalled();
            expect(datagridStore.active.get()).toBe(undefined);

            expect(page.get()).toBe(1);
            expect(loadingStrategy.load).toBeCalled();

            datagridStore.destroy();
            done();
        }
    );
});

test('Should reset page count to 0 and page to 1 when locale is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    locale.set('de');

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
    datagridStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when locale is changed before completely initialized', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    locale.set('de');

    expect(page.get()).toEqual(2);
    expect(datagridStore.pageCount).toEqual(7);
    datagridStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when locale stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    locale.set('en');

    expect(page.get()).toEqual(2);
    expect(datagridStore.pageCount).toEqual(7);
    datagridStore.destroy();
});

test('Should reset page count to 0 and page to 1 when search is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.searchTerm.set('test');

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
    datagridStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when search is changed before completely initialized', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.searchTerm.set('test');

    expect(page.get()).toEqual(2);
    expect(datagridStore.pageCount).toEqual(7);
    datagridStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when search stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};
    datagridStore.searchTerm.set('test');

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.searchTerm.set('test');

    expect(structureStrategy.clear).not.toBeCalled();
    expect(page.get()).toEqual(2);
    expect(datagridStore.pageCount).toEqual(7);
    datagridStore.destroy();
});

test('Should reset page count to 0 and page to 1 when sort column is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.sortColumn.set('test');

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
    datagridStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when sort column is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.sortColumn.set('test');

    expect(page.get()).toEqual(2);
    expect(datagridStore.pageCount).toEqual(7);
    datagridStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when sort column stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};
    datagridStore.sortColumn.set('test');

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.sortColumn.set('test');

    expect(structureStrategy.clear).not.toBeCalled();
    expect(page.get()).toEqual(2);
    expect(datagridStore.pageCount).toEqual(7);
    datagridStore.destroy();
});

test('Should reset page count to 0 and page to 1 when sort order is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.sortOrder.set('asc');

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
    datagridStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when sort order is changed before completely initialized', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.sortOrder.set('asc');

    expect(page.get()).toEqual(2);
    expect(datagridStore.pageCount).toEqual(7);
    datagridStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when sort order stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};
    datagridStore.sortOrder.set('asc');

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.sortOrder.set('asc');

    expect(structureStrategy.clear).not.toBeCalled();
    expect(page.get()).toEqual(2);
    expect(datagridStore.pageCount).toEqual(7);
    datagridStore.destroy();
});

test('Should reset page count to 0 and page to 1 when limit is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.limit.set(50);

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
    datagridStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when limit stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};
    datagridStore.limit.set(20);

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.limit.set(20);

    expect(structureStrategy.clear).not.toBeCalled();
    expect(page.get()).toEqual(2);
    expect(datagridStore.pageCount).toEqual(7);
    datagridStore.destroy();
});

test('Should reset page count and page when loading strategy changes', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page});
    datagridStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const otherLoadingStrategy = new OtherLoadingStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.setPage(5);
    datagridStore.pageCount = 7;
    datagridStore.updateLoadingStrategy(otherLoadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
    datagridStore.destroy();
});

test('Should clear the StructureStrategy when the clear method is called', () => {
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page: observable.box()});
    datagridStore.schema = {};
    const structureStrategy = new StructureStrategy();
    datagridStore.clear();
    expect(structureStrategy.clear).not.toBeCalledWith();

    datagridStore.updateStructureStrategy(structureStrategy);
    datagridStore.clear();

    expect(structureStrategy.clear).toBeCalledWith();
});

test('Should trigger a mobx autorun if activate is called with the same id', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page});
    datagridStore.schema = {};
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    structureStrategy.findById.mockReturnValue({});

    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);
    datagridStore.activate(3);

    let lastActive;
    const autorunDisposer = autorun(() => {
        lastActive = datagridStore.active.get();
    });
    lastActive = undefined;
    datagridStore.activate(3);
    expect(lastActive).toBe(3);

    autorunDisposer();
});

test('Should activate the current item if structure strategy is changed to trigger a reload', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page});
    datagridStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    structureStrategy.findById.mockReturnValue({});
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.activate(3);

    const otherStructureStrategy = new StructureStrategy();
    datagridStore.updateStructureStrategy(otherStructureStrategy);
    expect(otherStructureStrategy.activate).toBeCalledWith(3);
});

test('Should call the activate method of the structure strategy if an item gets activated', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page});
    datagridStore.schema = {};
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    structureStrategy.findById.mockReturnValue({});
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.activate(3);

    expect(structureStrategy.activate).toBeCalledWith(3);
});

test('Should call the deactivate method of the structure strategy if an item gets deactivated', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page});
    datagridStore.schema = {};
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.deactivate(2);

    expect(structureStrategy.deactivate).toBeCalledWith(2);
});

test('Should call the remove method of the structure strategy if an item gets removed', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page});
    datagridStore.schema = {};
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.remove(2);

    expect(structureStrategy.remove).toBeCalledWith(2);
});

test('Should move the item with the given ID to the new given parent and reload the datagrid', () => {
    const schema = {
        id: {
            label: 'ID',
            name: 'id',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        title: {
            label: 'Title',
            name: 'title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const schemaPromise = Promise.resolve(schema);
    metadataStore.getSchema.mockReturnValueOnce(schemaPromise);
    const locale = observable.box('de');

    const datagridStore = new DatagridStore(
        'snippets',
        'datagrid_test',
        {page: observable.box(), locale},
        {webspace: 'sulu'}
    );

    return schemaPromise.then(() => {
        const loadingStrategy = new LoadingStrategy();
        const structureStrategy = new StructureStrategy();
        datagridStore.updateLoadingStrategy(loadingStrategy);
        datagridStore.updateStructureStrategy(structureStrategy);

        const postWithIdPromise = Promise.resolve();
        ResourceRequester.postWithId.mockReturnValue(postWithIdPromise);

        datagridStore.move(3, 8);

        expect(ResourceRequester.postWithId)
            .toBeCalledWith('snippets', 3, {action: 'move', destination: 8, locale: 'de', webspace: 'sulu'});

        return postWithIdPromise.then(() => {
            expect(structureStrategy.clear).toBeCalledWith();
            expect(loadingStrategy.load).toHaveBeenLastCalledWith(
                'snippets',
                {
                    fields: [
                        'title',
                        'id',
                    ],
                    expandedIds: 3,
                    limit: 10,
                    locale: 'de',
                    page: undefined,
                    sortBy: undefined,
                    sortOrder: undefined,
                    webspace: 'sulu',
                },
                undefined
            );
        });
    });
});

test('Should copy the item with the given ID to the new given parent and reload the datagrid', () => {
    const locale = observable.box('de');
    const schema = {
        id: {
            label: 'ID',
            name: 'id',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        title: {
            label: 'Title',
            name: 'title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const schemaPromise = Promise.resolve(schema);
    metadataStore.getSchema.mockReturnValueOnce(schemaPromise);

    const datagridStore = new DatagridStore(
        'snippets',
        'datagrid_test',
        {page: observable.box(), locale},
        {webspace: 'sulu'}
    );

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    return schemaPromise.then(() => {
        const postWithIdPromise = Promise.resolve({id: 9});
        ResourceRequester.postWithId.mockReturnValue(postWithIdPromise);

        datagridStore.copy(3, 8);

        expect(ResourceRequester.postWithId)
            .toBeCalledWith('snippets', 3, {action: 'copy', destination: 8, locale: 'de', webspace: 'sulu'});

        return postWithIdPromise.then(() => {
            expect(structureStrategy.clear).toBeCalledWith();
            expect(loadingStrategy.load).toHaveBeenLastCalledWith(
                'snippets',
                {
                    fields: [
                        'title',
                        'id',
                    ],
                    expandedIds: 9,
                    limit: 10,
                    locale: 'de',
                    page: undefined,
                    sortBy: undefined,
                    sortOrder: undefined,
                    webspace: 'sulu',
                },
                undefined
            );
        });
    });
});

test('Should delete the item with the given ID and options', () => {
    const page = observable.box(1);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale}, {webspace: 'sulu'});
    datagridStore.schema = {};
    const deletePromise = Promise.resolve();
    ResourceRequester.delete.mockReturnValue(deletePromise);

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.delete(5);

    expect(ResourceRequester.delete).toBeCalledWith('snippets', 5, {locale: 'en', webspace: 'sulu'});

    return deletePromise.then(() => {
        expect(structureStrategy.remove).toBeCalledWith(5);
    });
});

test('Should delete the item with the given ID and remove it from the selection afterwards', () => {
    const page = observable.box(1);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale});
    datagridStore.schema = {};
    const deletePromise = Promise.resolve({id: 5});
    ResourceRequester.delete.mockReturnValue(deletePromise);

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.select({id: 5});
    expect(toJS(datagridStore.selections)).toEqual([{id: 5}]);
    datagridStore.delete(5);

    expect(ResourceRequester.delete).toBeCalledWith('snippets', 5, {locale: 'en'});

    return deletePromise.then(() => {
        expect(toJS(datagridStore.selections)).toEqual([]);
        expect(structureStrategy.remove).toBeCalledWith(5);
    });
});

test('Should delete the item with the given ID without locale', () => {
    const page = observable.box(1);
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page}, {webspace: 'sulu'});
    datagridStore.schema = {};
    const deletePromise = Promise.resolve();
    ResourceRequester.delete.mockReturnValue(deletePromise);

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.delete(5);

    expect(ResourceRequester.delete).toBeCalledWith('snippets', 5, {webspace: 'sulu'});

    return deletePromise.then(() => {
        expect(structureStrategy.remove).toBeCalledWith(5);
    });
});

test('Should delete all selected items', () => {
    const page = observable.box(1);
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page});
    datagridStore.schema = {};
    const structureStrategy = new StructureStrategy();
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.select({id: 1});
    datagridStore.select({id: 2});

    const deletePromise = datagridStore.deleteSelection();

    return deletePromise.then(() => {
        expect(ResourceRequester.delete).toHaveBeenCalledTimes(2);
        expect(ResourceRequester.delete).toBeCalledWith('snippets', 1, {});
        expect(ResourceRequester.delete).toBeCalledWith('snippets', 2, {});
        expect(structureStrategy.remove).toBeCalledWith(1);
        expect(structureStrategy.remove).toBeCalledWith(2);
        expect(datagridStore.selections).toEqual([]);
    });
});

test('Should delete all selected items and succeed even if one of them returns a 404', () => {
    const page = observable.box(1);
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page});
    datagridStore.schema = {};
    const structureStrategy = new StructureStrategy();
    datagridStore.updateStructureStrategy(structureStrategy);

    ResourceRequester.delete.mockReturnValue(Promise.reject({status: 404}));

    datagridStore.select({id: 1});
    datagridStore.select({id: 2});

    const deletePromise = datagridStore.deleteSelection();

    return deletePromise.then(() => {
        expect(ResourceRequester.delete).toHaveBeenCalledTimes(2);
        expect(ResourceRequester.delete).toBeCalledWith('snippets', 1, {});
        expect(ResourceRequester.delete).toBeCalledWith('snippets', 2, {});
        expect(structureStrategy.remove).toBeCalledWith(1);
        expect(structureStrategy.remove).toBeCalledWith(2);
        expect(datagridStore.selections).toEqual([]);
    });
});

test('Should crash when deleting all selected items and one request fails with another error than 404', (done) => {
    const page = observable.box(1);
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page});
    datagridStore.schema = {};
    const structureStrategy = new StructureStrategy();
    datagridStore.updateStructureStrategy(structureStrategy);

    ResourceRequester.delete.mockReturnValue(Promise.reject({status: 500}));

    datagridStore.select({id: 1});
    datagridStore.select({id: 2});

    const deletePromise = datagridStore.deleteSelection();
    deletePromise.catch((error) => {
        expect(error.status).toEqual(500);
        done();
    });
});

test('Should order the item with the given ID and options to the given position', () => {
    const page = observable.box(1);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page, locale}, {webspace: 'sulu'});
    datagridStore.schema = {};
    const orderPromise = Promise.resolve();
    ResourceRequester.postWithId.mockReturnValue(orderPromise);

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateLoadingStrategy(loadingStrategy);
    datagridStore.updateStructureStrategy(structureStrategy);

    datagridStore.order(5, 1);

    expect(ResourceRequester.postWithId)
        .toBeCalledWith('snippets', 5, {position: 1}, {action: 'order', locale: 'en', webspace: 'sulu'});

    return orderPromise.then(() => {
        expect(structureStrategy.order).toBeCalledWith(5, 1);
    });
});

test('Should call all disposers if destroy is called', () => {
    const datagridStore = new DatagridStore('snippets', 'datagrid_test', {page: observable.box()});
    datagridStore.sendRequestDisposer = jest.fn();
    datagridStore.localeDisposer = jest.fn();
    datagridStore.searchDisposer = jest.fn();
    datagridStore.sortColumnDisposer = jest.fn();
    datagridStore.sortOrderDisposer = jest.fn();
    datagridStore.limitDisposer = jest.fn();

    datagridStore.destroy();

    expect(datagridStore.sendRequestDisposer).toBeCalledWith();
    expect(datagridStore.localeDisposer).toBeCalledWith();
    expect(datagridStore.searchDisposer).toBeCalledWith();
    expect(datagridStore.sortColumnDisposer).toBeCalledWith();
    expect(datagridStore.sortOrderDisposer).toBeCalledWith();
    expect(datagridStore.limitDisposer).toBeCalledWith();
});
