// @flow
import 'url-search-params-polyfill';
import {autorun, observable, toJS, when} from 'mobx';
import log from 'loglevel';
import ResourceRequester, {RequestPromise} from '../../../../services/ResourceRequester';
import ListStore from '../../stores/ListStore';
import metadataStore from '../../stores/metadataStore';
import {userStore} from '../../../../stores';

jest.mock('loglevel', () => ({
    info: jest.fn(),
    warn: jest.fn(),
}));

jest.mock('../../stores/metadataStore', () => ({
    getSchema: jest.fn(() => Promise.resolve()),
}));

jest.mock('../../../../services/ResourceRequester/ResourceRequester', () => ({
    delete: jest.fn(),
    post: jest.fn(),
}));

jest.mock('../../../../services/Requester/RequestPromise', () => {
    // $FlowFixMe
    return class RequestPromise extends Promise {
        abort = jest.fn();
    };
});

jest.mock('../../../../stores/userStore', () => ({
    getPersistentSetting: jest.fn(),
    setPersistentSetting: jest.fn(),
}));

class LoadingStrategy {
    destroy = jest.fn();
    initialize = jest.fn();
    load = jest.fn().mockReturnValue(new RequestPromise(function() {}));
    reset = jest.fn();
    setStructureStrategy = jest.fn();
}

class OtherLoadingStrategy {
    destroy = jest.fn();
    initialize = jest.fn();
    load = jest.fn().mockReturnValue(new RequestPromise(function() {}));
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

test('The active item should be updated when set from the outside', () => {
    const listStore = new ListStore('tests', 'tests', 'list_test', {page: observable.box()});
    expect(listStore.active.get()).toEqual();

    listStore.active.set('123');
    expect(userStore.setPersistentSetting).toBeCalledWith('sulu_admin.list_store.tests.list_test.active', '123');
});

test('The filter value should be updated when set from the outside', () => {
    const listStore = new ListStore('tests', 'tests', 'list_test', {page: observable.box()});
    expect(listStore.filterOptions.get()).toEqual({});

    listStore.filterOptions.set({test: {eq: 'Test'}});
    expect(userStore.setPersistentSetting)
        .toBeCalledWith('sulu_admin.list_store.tests.list_test.filter', {test: {eq: 'Test'}});
});

test('The filter value should be updated when set from the outside and only an undefined value was added', () => {
    const listStore = new ListStore('tests', 'tests', 'list_test', {page: observable.box()});
    expect(listStore.filterOptions.get()).toEqual({});

    listStore.filterOptions.set({test: undefined});
    expect(userStore.setPersistentSetting)
        .toBeCalledWith('sulu_admin.list_store.tests.list_test.filter', {test: undefined});
});

test('The filter value should be updated when set from the outside and only a single false value was added', () => {
    const listStore = new ListStore('tests', 'tests', 'list_test', {page: observable.box()});
    expect(listStore.filterOptions.get()).toEqual({});

    listStore.filterOptions.set({test: false});
    expect(userStore.setPersistentSetting)
        .toBeCalledWith('sulu_admin.list_store.tests.list_test.filter', {test: false});
});

test('The filter value should be updated when set from the outside and only a single false value was removed', () => {
    const listStore = new ListStore('tests', 'tests', 'list_test', {page: observable.box()});
    listStore.filterOptions.set({test: false});
    expect(listStore.filterOptions.get()).toEqual({test: false});

    listStore.filterOptions.set({});
    expect(userStore.setPersistentSetting)
        .toBeCalledWith('sulu_admin.list_store.tests.list_test.filter', {});
});

test('The limit value should be updated when set from the outside', () => {
    const listStore = new ListStore('tests', 'tests', 'list_test', {page: observable.box()});
    expect(listStore.limit.get()).toEqual(10);

    listStore.limit.set(20);
    expect(userStore.setPersistentSetting).toBeCalledWith('sulu_admin.list_store.tests.list_test.limit', 20);
});

test('The sort column value should be updated when set from the outside', () => {
    const listStore = new ListStore('tests', 'tests', 'list_test', {page: observable.box()});
    expect(listStore.sortColumn.get()).toEqual();

    listStore.sortColumn.set('title');
    expect(userStore.setPersistentSetting).toBeCalledWith('sulu_admin.list_store.tests.list_test.sort_column', 'title');
});

test('The sort order value should be updated when set from the outside', () => {
    const listStore = new ListStore('tests', 'tests', 'list_test', {page: observable.box()});
    expect(listStore.sortOrder.get()).toEqual();

    listStore.sortOrder.set('asc');
    expect(userStore.setPersistentSetting).toBeCalledWith('sulu_admin.list_store.tests.list_test.sort_order', 'asc');
});

test('The loading strategy should get passed the structure strategy', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();

    const listStore = new ListStore('tests', 'tests', 'list_test', {page: observable.box()});
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);
    expect(loadingStrategy.setStructureStrategy).toBeCalledWith(structureStrategy);
});

test('The loading strategy should be called when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const locale = observable.box();
    const additionalValue = observable.box(5);

    const listStore = new ListStore(
        'tests',
        'tests',
        'list_test',
        {
            page,
            locale,
            additionalValue,
        },
        {
            test: 'value',
        }
    );
    listStore.schema = {};

    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

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

    listStore.destroy();
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

    const listStore = new ListStore(
        'tests',
        'tests',
        'list_test',
        {
            page,
            locale,
            additionalValue,
        },
        {
            test: 'value',
        },
        {
            id: 1,
        }
    );

    expect((metadataStore).getSchema).toBeCalledWith('tests', {id: 1});

    return schemaPromise.then(() => {
        const newSchema = {
            id: {
                filterType: null,
                filterTypeParameters: null,
                transformerTypeParameters: {},
                label: 'ID',
                name: 'id',
                sortable: true,
                type: 'string',
                visibility: 'no',
            },
            changed: {
                filterType: null,
                filterTypeParameters: null,
                transformerTypeParameters: {},
                label: 'Changed at',
                name: 'changed',
                sortable: true,
                type: 'datetime',
                visibility: 'no',
            },
            title: {
                filterType: null,
                filterTypeParameters: null,
                transformerTypeParameters: {},
                label: 'Title',
                name: 'title',
                sortable: true,
                type: 'string',
                visibility: 'no',
            },
            name: {
                filterType: null,
                filterTypeParameters: null,
                transformerTypeParameters: {},
                label: 'Name',
                name: 'name',
                sortable: true,
                type: 'string',
                visibility: 'always',
            },
        };
        listStore.changeUserSchema(newSchema);

        expect(userStore.setPersistentSetting).toBeCalledWith(
            'sulu_admin.list_store.tests.list_test.schema',
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

        listStore.destroy();
    });
});

test('The userSchema should include schema properties that are not present in the schemaSetting of the user', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);

    const schemaSetting = [
        {
            'schemaKey': 'id',
            'visibility': 'no',
        },
        {
            'schemaKey': 'title',
            'visibility': 'no',
        },
    ];
    userStore.getPersistentSetting.mockReturnValueOnce(schemaSetting);

    const listStore = new ListStore(
        'tests',
        'tests',
        'list_test',
        {
            page,
        },
        {
            test: 'value',
        },
        {
            id: 1,
        }
    );

    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);
    listStore.schema = {
        id: {
            filterType: null,
            filterTypeParameters: {},
            transformerTypeParameters: {},
            label: 'ID',
            name: 'id',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        title: {
            filterType: null,
            filterTypeParameters: {},
            transformerTypeParameters: {},
            label: 'Title',
            name: 'title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        newSchemaProperty: {
            filterType: null,
            filterTypeParameters: {},
            transformerTypeParameters: {},
            label: 'New Schema Property',
            name: 'newSchemaProperty',
            sortable: true,
            type: 'string',
            visibility: 'always',
        },
    };

    expect(listStore.userSchema).toEqual(
        {
            id: {
                filterType: null,
                filterTypeParameters: {},
                transformerTypeParameters: {},
                label: 'ID',
                name: 'id',
                sortable: true,
                type: 'string',
                visibility: 'no',
            },
            title: {
                filterType: null,
                filterTypeParameters: {},
                transformerTypeParameters: {},
                label: 'Title',
                name: 'title',
                sortable: true,
                type: 'string',
                visibility: 'no',
            },
            newSchemaProperty: {
                filterType: null,
                filterTypeParameters: {},
                transformerTypeParameters: {},
                label: 'New Schema Property',
                name: 'newSchemaProperty',
                sortable: true,
                type: 'string',
                visibility: 'always',
            },
        }
    );
    expect((userStore).getPersistentSetting).toBeCalledWith('sulu_admin.list_store.tests.list_test.schema');

    listStore.destroy();
});

test('The userSchema should reflect the order of the schemaSetting of the user', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);

    const schemaSetting = [
        {
            'schemaKey': 'title',
            'visibility': 'no',
        },
        {
            'schemaKey': 'id',
            'visibility': 'no',
        },
    ];
    userStore.getPersistentSetting.mockReturnValueOnce(schemaSetting);

    const listStore = new ListStore(
        'tests',
        'tests',
        'list_test',
        {
            page,
        },
        {
            test: 'value',
        },
        {
            id: 1,
        }
    );

    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);
    listStore.schema = {
        id: {
            filterType: null,
            filterTypeParameters: {},
            transformerTypeParameters: {},
            label: 'ID',
            name: 'id',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        title: {
            filterType: null,
            filterTypeParameters: {},
            transformerTypeParameters: {},
            label: 'Title',
            name: 'title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
    };

    expect(Object.keys(listStore.userSchema)).toEqual(['title', 'id']);
    expect((userStore).getPersistentSetting).toBeCalledWith('sulu_admin.list_store.tests.list_test.schema');

    listStore.destroy();
});

test('The loading strategy should be called with a different resourceKey when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const locale = observable.box();
    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {
            page,
            locale,
        },
        {
            test: 'value',
        },
        undefined
    );
    listStore.schema = {};

    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

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

    listStore.destroy();
});

test('The loading strategy should be called with a different page when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const locale = observable.box();
    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {
            page,
            locale,
        },
        {
            test: 'value',
        }
    );
    listStore.schema = {};
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

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

    listStore.destroy();
});

test('The loading strategy should be called with a different page when a request is sent ', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const locale = observable.box();
    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {
            page,
            locale,
        },
        {
            test: 'value',
        }
    );
    listStore.schema = {};
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

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

    listStore.destroy();
});

test('The loading strategy should be called with a different locale when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const locale = observable.box('en');
    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {
            page,
            locale,
        },
        {
            test: 'value',
        }
    );
    listStore.schema = {};
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

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

    listStore.destroy();
});

test('The loading strategy should be called with the defined sortings', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {
            page,
        }
    );
    listStore.schema = {};
    listStore.sort('title', 'desc');
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

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

    listStore.destroy();
});

test('The loading strategy should be called with the defined search', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(2);
    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {
            page,
        }
    );
    listStore.schema = {};

    structureStrategy.clear = jest.fn();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.search('search-value');

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

    listStore.destroy();
});

test('The loading strategy should be called with the defined filter', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {
            page,
        }
    );
    listStore.schema = {};

    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.filter({title: 'Test Title', template: 'test'});

    expect(loadingStrategy.load).toBeCalledWith(
        'snippets',
        {
            fields: [
                'id',
            ],
            filter: {title: 'Test Title', template: 'test'},
            page: 1,
            limit: 10,
            sortBy: undefined,
            sortOrder: undefined,
        },
        undefined
    );

    listStore.destroy();
});

test('The loading strategy should be called with the active item as parentId', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {
            page,
        }
    );
    listStore.schema = {};

    // $FlowFixMe
    structureStrategy.findById.mockReturnValue({});
    listStore.setActive('some-uuid');
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

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

    listStore.destroy();
});

test('The loading strategy should be called with expandedIds if the active item is not available', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {
            page,
        }
    );
    listStore.schema = {};

    listStore.setActive('some-uuid');
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

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

    listStore.destroy();
});

test('The loading strategy should be called with expandedIds if some items are already selected', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const listStore = new ListStore(
        'categories',
        'categories',
        'list_test',
        {
            page,
        },
        {},
        {},
        [1, 5, 10]
    );
    listStore.schema = {};

    const promise = new RequestPromise(function(resolve) {
        resolve({});
    });
    loadingStrategy.load.mockReturnValue(promise);

    // $FlowFixMe
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

    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

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

        expect(listStore.selectionIds).toEqual([1, 5, 10]);
        expect(listStore.initialSelectionIds).toEqual(undefined);

        listStore.destroy();
    });
});

test('The loading strategy should be called only once even if the data changes afterwards for some reason', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {
            page,
        }
    );
    listStore.schema = {};
    listStore.setActive('some-uuid');
    // $FlowFixMe
    structureStrategy.findById.mockImplementation(() => Array.from(structureStrategy.data));
    listStore.updateStructureStrategy(structureStrategy);
    listStore.updateLoadingStrategy(loadingStrategy);

    expect(structureStrategy.findById).toBeCalledWith('some-uuid');
    expect(loadingStrategy.load).toHaveBeenCalledTimes(1);
    structureStrategy.data.push({});
    expect(loadingStrategy.load).toHaveBeenCalledTimes(1);

    listStore.destroy();
});

test('The active item should not be passed as parent if undefined', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {
            page,
        },
        {
            parent: 9,
        }
    );
    listStore.schema = {};
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

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

    listStore.destroy();
});

test('The activeItems from the StructureStrategy should be passed', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);

    const listStore = new ListStore('snippets', 'snippets', 'list_test', {
        page,
    });
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    const activeItems = [1, 2, 3];
    structureStrategy.activeItems = activeItems;
    expect(listStore.activeItems).toBe(activeItems);
});

test('Set loading flag to true before schema is loaded', () => {
    const promise = Promise.resolve();
    metadataStore.getSchema.mockReturnValueOnce(promise);
    const page = observable.box();
    const listStore = new ListStore('tests', 'tests', 'list_test', {page});
    listStore.updateLoadingStrategy(new LoadingStrategy());
    listStore.updateStructureStrategy(new StructureStrategy());
    page.set(1);
    listStore.setDataLoading(false);
    expect(listStore.loading).toEqual(true);
    return promise.then(() => {
        expect(listStore.loading).toEqual(false);
        listStore.destroy();
    });
});

test('Set loading flag to true before request', () => {
    const page = observable.box();
    const listStore = new ListStore('tests', 'tests', 'list_test', {page});
    listStore.updateLoadingStrategy(new LoadingStrategy());
    listStore.updateStructureStrategy(new StructureStrategy());
    page.set(1);
    listStore.setDataLoading(false);
    listStore.sendRequest();
    expect(listStore.loading).toEqual(true);
    listStore.destroy();
});

test('Set loading flag to false after request', (done) => {
    const page = observable.box();
    const listStore = new ListStore('tests', 'tests', 'list_test', {page});
    listStore.schema = {};
    const promise = new RequestPromise(function(resolve) {
        resolve({pages: 3});
    });
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    loadingStrategy.load.mockReturnValue(promise);
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);
    listStore.sendRequest();
    return promise.then(() => {
        expect(listStore.loading).toEqual(false);
        listStore.destroy();
        done();
    });
});

test('Set forbidden flag to true if the response returned a 403', (done) => {
    const listStore = new ListStore('tests', 'tests', 'list_test', {page: observable.box()});
    listStore.schema = {};

    const promise = new RequestPromise(function(resolve, reject) {
        reject({status: 403});
    });

    const loadingStrategy = new LoadingStrategy();
    loadingStrategy.load.mockReturnValue(promise);
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.sendRequest();
    expect(listStore.forbidden).toEqual(false);

    setTimeout(() => {
        expect(listStore.forbidden).toEqual(true);
        done();
    });
});

test('Cancel request when a second one is started before finishing the first one', () => {
    const page = observable.box(1);
    const listStore = new ListStore('tests', 'tests', 'list_test', {page});
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();

    loadingStrategy.load.mockReturnValueOnce(new RequestPromise(function(resolve){
        resolve();
    }));
    loadingStrategy.load.mockReturnValueOnce(new RequestPromise(function(resolve){
        resolve();
    }));

    listStore.schema = {};

    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    expect(loadingStrategy.load).toHaveBeenLastCalledWith(
        'tests',
        {fields: ['id'], limit: 10, page: 1, sortBy: undefined, sortOrder: undefined},
        undefined
    );

    const requestPromise1 = listStore.pendingRequest;

    listStore.sendRequest();

    expect(loadingStrategy.load).toHaveBeenLastCalledWith(
        'tests',
        {fields: ['id'], limit: 10, page: 1, sortBy: undefined, sortOrder: undefined},
        undefined
    );

    const requestPromise2 = listStore.pendingRequest;

    // $FlowFixMe
    expect(requestPromise1.abort).toBeCalledWith();
    // $FlowFixMe
    expect(requestPromise2.abort).not.toBeCalledWith();
});

test('Set active to undefined if the response returned a 404', (done) => {
    const listStore = new ListStore('tests', 'tests', 'list_test', {page: observable.box()}, undefined);
    listStore.schema = {};
    const promise = new RequestPromise(function(resolve, reject) {
        reject({status: 404});
    });

    const loadingStrategy = new LoadingStrategy();
    loadingStrategy.load.mockReturnValue(promise);
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setActive('some-uuid');
    listStore.sendRequest();

    expect(listStore.active.get()).toEqual('some-uuid');

    setTimeout(() => {
        const promise = new RequestPromise(function(resolve) {
            resolve({pages: 3});
        });
        loadingStrategy.load.mockReturnValue(promise);
        expect(listStore.active.get()).toEqual(undefined);
        expect(userStore.setPersistentSetting).toBeCalledWith(
            'sulu_admin.list_store.tests.list_test.active',
            undefined
        );
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
    const listStore = new ListStore('tests', 'tests', 'list_test', {
        page,
    });
    listStore.updateLoadingStrategy(new LoadingStrategy());
    listStore.updateStructureStrategy(new StructureStrategy());
    expect(metadataStore.getSchema).toBeCalledWith('tests', undefined);
    return schemaPromise.then(() => {
        expect(listStore.schema).toEqual(schema);
        listStore.destroy();
    });
});

test('After initialization no row should be selected', () => {
    const page = observable.box();
    const listStore = new ListStore('tests', 'tests', 'list_test', {
        page,
    });
    listStore.updateLoadingStrategy(new LoadingStrategy());
    listStore.updateStructureStrategy(new StructureStrategy());
    expect(listStore.selections.length).toBe(0);
    listStore.destroy();
});

test('Select an item', () => {
    const page = observable.box();
    const listStore = new ListStore('tests', 'tests', 'list_test', {
        page,
    });
    listStore.updateLoadingStrategy(new LoadingStrategy());
    listStore.updateStructureStrategy(new StructureStrategy());
    listStore.select({id: 1}) ;
    listStore.select({id: 2}) ;
    expect(toJS(listStore.selectionIds)).toEqual([1, 2]);

    listStore.deselect({id: 1});
    expect(toJS(listStore.selectionIds)).toEqual([2]);
    listStore.destroy();
});

test('Deselect an item that has not been selected yet', () => {
    const page = observable.box();
    const listStore = new ListStore('tests', 'tests', 'list_test', {
        page,
    });
    listStore.updateLoadingStrategy(new LoadingStrategy());
    listStore.updateStructureStrategy(new StructureStrategy());
    listStore.select({id: 1}) ;
    listStore.deselect({id: 2});

    expect(toJS(listStore.selectionIds)).toEqual([1]);
    listStore.destroy();
});

test('Select all visible items', () => {
    const page = observable.box();
    const listStore = new ListStore('tests', 'tests', 'list_test', {page});
    listStore.updateLoadingStrategy(new LoadingStrategy());
    listStore.updateStructureStrategy(new StructureStrategy());
    // $FlowFixMe
    listStore.structureStrategy.visibleItems = [{id: 1}, {id: 2}, {id: 3}];
    listStore.selections = [
        {id: 1},
        {id: 7},
    ];
    listStore.selectVisibleItems();

    expect(toJS(listStore.selectionIds)).toEqual([1, 7, 2, 3]);
    expect(log.warn).toBeCalledWith(expect.stringContaining('The "selectVisibleItems" method'));

    listStore.destroy();
});

test('Deselect all visible items', () => {
    const page = observable.box();
    const listStore = new ListStore('tests', 'tests', 'list_test', {
        page,
    });
    listStore.updateLoadingStrategy(new LoadingStrategy());
    listStore.updateStructureStrategy(new StructureStrategy());
    // $FlowFixMe
    listStore.structureStrategy.visibleItems = [{id: 1}, {id: 2}, {id: 3}];
    listStore.selections = [
        {id: 1},
        {id: 2},
        {id: 7},
    ];
    listStore.deselectVisibleItems();

    expect(toJS(listStore.selectionIds)).toEqual([7]);
    expect(log.warn).toBeCalledWith(expect.stringContaining('The "deselectVisibleItems" method'));

    listStore.destroy();
});

test('Deselect an item by id', () => {
    const page = observable.box();
    const listStore = new ListStore('tests', 'tests', 'list_test', {
        page,
    });
    listStore.updateLoadingStrategy(new LoadingStrategy());
    listStore.updateStructureStrategy(new StructureStrategy());
    // $FlowFixMe
    listStore.structureStrategy.visibleItems = [{id: 1}, {id: 2}, {id: 3}];
    listStore.selections = [
        {id: 1},
        {id: 2},
        {id: 7},
    ];
    listStore.deselectById(7);
    expect(toJS(listStore.selectionIds)).toEqual([1, 2]);
    listStore.destroy();
});

test('Clear the selection', () => {
    const page = observable.box();
    const listStore = new ListStore('tests', 'tests', 'list_test', {
        page,
    });
    listStore.updateLoadingStrategy(new LoadingStrategy());
    listStore.updateStructureStrategy(new StructureStrategy());
    listStore.selections = [{id: 1}, {id: 4}, {id: 5}];
    page.set(1);
    expect(listStore.selections).toHaveLength(3);

    listStore.clearSelection();
    expect(listStore.selections).toHaveLength(0);
});

test('Clear the data', () => {
    const listStore = new ListStore('tests', 'tests', 'list_test', {
        page: observable.box(),
    });
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(new LoadingStrategy());
    listStore.updateStructureStrategy(structureStrategy);

    listStore.clear();
    expect(structureStrategy.clear).toBeCalled();
});

test('Should reload data but not change the page or the active item when the reload method is called', (done) => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();

    const promise = new RequestPromise(function(resolve) {
        resolve({});
    });
    loadingStrategy.load.mockReturnValue(promise);

    const page = observable.box();
    const locale = observable.box();
    const listStore = new ListStore(
        'tests',
        'tests',
        'list_test',
        {
            page,
            locale,
        },
        {}
    );
    listStore.schema = {};
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    locale.set('en');
    page.set(3);
    // $FlowFixMe
    structureStrategy.findById.mockReturnValue({});
    listStore.setActive(1);
    expect(structureStrategy.findById).toBeCalledWith(1);

    when(
        () => !listStore.loading,
        (): void => {
            expect(page.get()).toBe(3);

            listStore.reload();

            expect(listStore.active.get()).toBe(1);
            expect(page.get()).toBe(3);
            expect(loadingStrategy.load).toBeCalled();

            listStore.destroy();
            done();
        }
    );
});

test('Should reset page count to 0 and page to 1 when locale is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setPage(2);
    listStore.pageCount = 7;
    locale.set('de');

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(listStore.pageCount).toEqual(0);
    listStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when locale is changed before completely initialized', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};

    listStore.setPage(2);
    listStore.pageCount = 7;
    locale.set('de');

    expect(page.get()).toEqual(2);
    expect(listStore.pageCount).toEqual(7);
    listStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when locale stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setPage(2);
    listStore.pageCount = 7;
    locale.set('en');

    expect(page.get()).toEqual(2);
    expect(listStore.pageCount).toEqual(7);
    listStore.destroy();
});

test('Should reset page count to 0 and page to 1 when search is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setPage(2);
    listStore.pageCount = 7;
    listStore.searchTerm.set('test');

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(listStore.pageCount).toEqual(0);
    listStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when search is changed before completely initialized', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};

    listStore.setPage(2);
    listStore.pageCount = 7;
    listStore.searchTerm.set('test');

    expect(page.get()).toEqual(2);
    expect(listStore.pageCount).toEqual(7);
    listStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when search stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};
    listStore.searchTerm.set('test');

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setPage(2);
    listStore.pageCount = 7;
    listStore.searchTerm.set('test');

    expect(structureStrategy.clear).not.toBeCalled();
    expect(page.get()).toEqual(2);
    expect(listStore.pageCount).toEqual(7);
    listStore.destroy();
});

test('Should reset page count to 0 and page to 1 when filter is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setPage(2);
    listStore.pageCount = 7;
    listStore.filter({test: {eq: 'Test'}});

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(listStore.pageCount).toEqual(0);
    listStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when filter stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};
    listStore.filter({test: {eq: 'Test'}});

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setPage(2);
    listStore.pageCount = 7;
    listStore.filter({test: {eq: 'Test'}});

    expect(structureStrategy.clear).not.toBeCalled();
    expect(page.get()).toEqual(2);
    expect(listStore.pageCount).toEqual(7);
    listStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when filter stays the same except for undefined fields', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};
    listStore.filter({test: {eq: 'Test'}});

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setPage(2);
    listStore.pageCount = 7;
    listStore.filter({test: {eq: 'Test'}, test2: undefined});

    expect(structureStrategy.clear).not.toBeCalled();
    expect(page.get()).toEqual(2);
    expect(listStore.pageCount).toEqual(7);
    listStore.destroy();
});

test('Should reset page count to 0 and page to 1 when sort column is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setPage(2);
    listStore.pageCount = 7;
    listStore.sortColumn.set('test');

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(listStore.pageCount).toEqual(0);
    listStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when sort column is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};

    listStore.setPage(2);
    listStore.pageCount = 7;
    listStore.sortColumn.set('test');

    expect(page.get()).toEqual(2);
    expect(listStore.pageCount).toEqual(7);
    listStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when sort column stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};
    listStore.sortColumn.set('test');

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setPage(2);
    listStore.pageCount = 7;
    listStore.sortColumn.set('test');

    expect(structureStrategy.clear).not.toBeCalled();
    expect(page.get()).toEqual(2);
    expect(listStore.pageCount).toEqual(7);
    listStore.destroy();
});

test('Should reset page count to 0 and page to 1 when sort order is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setPage(2);
    listStore.pageCount = 7;
    listStore.sortOrder.set('asc');

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(listStore.pageCount).toEqual(0);
    listStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when sort order is changed before completely initialized', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};

    listStore.setPage(2);
    listStore.pageCount = 7;
    listStore.sortOrder.set('asc');

    expect(page.get()).toEqual(2);
    expect(listStore.pageCount).toEqual(7);
    listStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when sort order stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};
    listStore.sortOrder.set('asc');

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setPage(2);
    listStore.pageCount = 7;
    listStore.sortOrder.set('asc');

    expect(structureStrategy.clear).not.toBeCalled();
    expect(page.get()).toEqual(2);
    expect(listStore.pageCount).toEqual(7);
    listStore.destroy();
});

test('Should reset page count to 0 and page to 1 when limit is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setPage(2);
    listStore.pageCount = 7;
    listStore.limit.set(50);

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(listStore.pageCount).toEqual(0);
    listStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when limit stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};
    listStore.limit.set(20);

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setPage(2);
    listStore.pageCount = 7;
    listStore.limit.set(20);

    expect(structureStrategy.clear).not.toBeCalled();
    expect(page.get()).toEqual(2);
    expect(listStore.pageCount).toEqual(7);
    listStore.destroy();
});

test('Should reset page count and page when loading strategy changes', () => {
    const page = observable.box();
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page});
    listStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const otherLoadingStrategy = new OtherLoadingStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.setPage(5);
    listStore.pageCount = 7;
    listStore.updateLoadingStrategy(otherLoadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    expect(page.get()).toEqual(1);
    expect(listStore.pageCount).toEqual(0);
    listStore.destroy();
});

test('Should clear the StructureStrategy when the clear method is called', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page: observable.box()});
    listStore.schema = {};
    const structureStrategy = new StructureStrategy();
    listStore.clear();
    expect(structureStrategy.clear).not.toBeCalledWith();

    listStore.updateStructureStrategy(structureStrategy);
    listStore.clear();

    expect(structureStrategy.clear).toBeCalledWith();
});

test('Should trigger a mobx autorun if activate is called with the same id', () => {
    const page = observable.box();
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page});
    listStore.schema = {};
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    // $FlowFixMe
    structureStrategy.findById.mockReturnValue({});

    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);
    listStore.activate(3);

    let lastActive;
    const autorunDisposer = autorun(() => {
        lastActive = listStore.active.get();
    });
    lastActive = undefined;
    listStore.activate(3);
    expect(lastActive).toBe(3);

    autorunDisposer();
});

test('Should call the reload method if structure strategy is changed', () => {
    const page = observable.box();
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page});
    const reloadSpy = jest.spyOn(listStore, 'reload');
    listStore.schema = {};

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    // $FlowFixMe
    structureStrategy.findById.mockReturnValue({});
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.activate(3);

    const otherStructureStrategy = new StructureStrategy();
    listStore.updateStructureStrategy(otherStructureStrategy);
    expect(reloadSpy).toBeCalled();
});

test('Should call the activate method of the structure strategy if an item gets activated', () => {
    const page = observable.box();
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page});
    listStore.schema = {};
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    // $FlowFixMe
    structureStrategy.findById.mockReturnValue({});
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.activate(3);

    expect(structureStrategy.activate).toBeCalledWith(3);
});

test('Should call the deactivate method of the structure strategy if an item gets deactivated', () => {
    const page = observable.box();
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page});
    listStore.schema = {};
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.deactivate(2);

    expect(structureStrategy.deactivate).toBeCalledWith(2);
});

test('Should call the remove method of the structure strategy if an item gets removed', () => {
    const page = observable.box();
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page});
    listStore.schema = {};
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.remove(2);

    expect(structureStrategy.remove).toBeCalledWith(2);
});

test('Should move the item with the given ID to the new given parent and reload the list', () => {
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

    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {page: observable.box(), locale},
        {webspace: 'sulu'}
    );

    return schemaPromise.then(() => {
        const loadingStrategy = new LoadingStrategy();
        const structureStrategy = new StructureStrategy();
        listStore.updateLoadingStrategy(loadingStrategy);
        listStore.updateStructureStrategy(structureStrategy);

        const postWithIdPromise = Promise.resolve();
        ResourceRequester.post.mockReturnValue(postWithIdPromise);

        listStore.move(3, 8);

        expect(ResourceRequester.post).toBeCalledWith(
            'snippets',
            undefined,
            {action: 'move', destination: 8, id: 3, locale: 'de', webspace: 'sulu'}
        );

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

test('Should move all selected items to the new given parent and reload the list', () => {
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

    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {page: observable.box(), locale},
        {webspace: 'sulu'}
    );

    return schemaPromise.then(() => {
        const loadingStrategy = new LoadingStrategy();
        const structureStrategy = new StructureStrategy();
        listStore.updateLoadingStrategy(loadingStrategy);
        listStore.updateStructureStrategy(structureStrategy);

        const postWithIdPromise = Promise.resolve();
        ResourceRequester.post.mockReturnValue(postWithIdPromise);

        listStore.select({id: 1});
        listStore.select({id: 2});
        listStore.select({id: 4});

        expect(listStore.movingSelection).toEqual(false);

        const moveSelectionPromise = listStore.moveSelection(3);

        expect(listStore.movingSelection).toEqual(true);

        expect(ResourceRequester.post).toBeCalledWith(
            'snippets',
            undefined,
            {action: 'move', destination: 3, id: 1, locale: 'de', webspace: 'sulu'}
        );
        expect(ResourceRequester.post).toBeCalledWith(
            'snippets',
            undefined,
            {action: 'move', destination: 3, id: 2, locale: 'de', webspace: 'sulu'}
        );
        expect(ResourceRequester.post).toBeCalledWith(
            'snippets',
            undefined,
            {action: 'move', destination: 3, id: 4, locale: 'de', webspace: 'sulu'}
        );

        return moveSelectionPromise.then(() => {
            expect(listStore.movingSelection).toEqual(false);
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

test('Should copy the item with the given ID to the new given parent and reload the list', () => {
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

    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {page: observable.box(), locale},
        {webspace: 'sulu'}
    );

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    return schemaPromise.then(() => {
        const postWithIdPromise = Promise.resolve({id: 9});
        ResourceRequester.post.mockReturnValue(postWithIdPromise);

        const callbackSpy = jest.fn();

        listStore.copy(3, 8, callbackSpy);

        expect(ResourceRequester.post).toBeCalledWith(
            'snippets',
            undefined,
            {action: 'copy', destination: 8, id: 3, locale: 'de', webspace: 'sulu'}
        );

        expect(callbackSpy).not.toBeCalled();

        return postWithIdPromise.then(() => {
            expect(callbackSpy).toBeCalledWith({id: 9});
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
    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {page, locale},
        {webspace: 'sulu'}
    );
    listStore.schema = {};
    const deletePromise = Promise.resolve();
    ResourceRequester.delete.mockReturnValue(deletePromise);

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.delete(5, {force: true});

    expect(ResourceRequester.delete).toBeCalledWith('snippets', {force: true, id: 5, locale: 'en', webspace: 'sulu'});

    return deletePromise.then(() => {
        expect(structureStrategy.remove).toBeCalledWith(5);
    });
});

test('Should delete the item with the given ID and remove it from the selection afterwards', () => {
    const page = observable.box(1);
    const locale = observable.box('en');
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page, locale});
    listStore.schema = {};
    const deletePromise = Promise.resolve({id: 5});
    ResourceRequester.delete.mockReturnValue(deletePromise);

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.select({id: 5});
    expect(toJS(listStore.selections)).toEqual([{id: 5}]);
    listStore.delete(5);

    expect(ResourceRequester.delete).toBeCalledWith('snippets', {id: 5, locale: 'en'});

    return deletePromise.then(() => {
        expect(toJS(listStore.selections)).toEqual([]);
        expect(structureStrategy.remove).toBeCalledWith(5);
    });
});

test('Should delete the item with the given ID without locale', () => {
    const page = observable.box(1);
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page}, {webspace: 'sulu'});
    listStore.schema = {};
    const deletePromise = Promise.resolve();
    ResourceRequester.delete.mockReturnValue(deletePromise);

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.delete(5);

    expect(ResourceRequester.delete).toBeCalledWith('snippets', {id: 5, webspace: 'sulu'});

    return deletePromise.then(() => {
        expect(structureStrategy.remove).toBeCalledWith(5);
    });
});

test('Should delete all selected items and reload the list afterwards', () => {
    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValueOnce(schemaPromise);

    ResourceRequester.delete.mockReturnValue(Promise.resolve());

    const page = observable.box(1);
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page});

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    return schemaPromise.then(() => {
        listStore.select({id: 1});
        listStore.select({id: 2});

        expect(listStore.deletingSelection).toEqual(false);
        expect(loadingStrategy.load).toBeCalledTimes(1);

        const deletePromise = listStore.deleteSelection();

        expect(listStore.deletingSelection).toEqual(true);
        expect(loadingStrategy.load).toBeCalledTimes(1);

        return deletePromise.then(() => {
            expect(ResourceRequester.delete).toHaveBeenCalledTimes(2);
            expect(ResourceRequester.delete).toBeCalledWith('snippets', {id: 1});
            expect(ResourceRequester.delete).toBeCalledWith('snippets', {id: 2});
            expect(structureStrategy.remove).toBeCalledWith(1);
            expect(structureStrategy.remove).toBeCalledWith(2);
            expect(listStore.selections).toEqual([]);
            expect(listStore.deletingSelection).toEqual(false);
            expect(loadingStrategy.load).toBeCalledTimes(2);
        });
    });
});

test('Should delete all selected items and succeed even if one of them returns a 404', () => {
    const page = observable.box(1);
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page});
    listStore.schema = {};
    const structureStrategy = new StructureStrategy();
    listStore.updateStructureStrategy(structureStrategy);

    ResourceRequester.delete.mockReturnValue(Promise.reject({status: 404}));

    listStore.select({id: 1});
    listStore.select({id: 2});

    const deletePromise = listStore.deleteSelection();

    return deletePromise.then(() => {
        expect(ResourceRequester.delete).toHaveBeenCalledTimes(2);
        expect(ResourceRequester.delete).toBeCalledWith('snippets', {id: 1});
        expect(ResourceRequester.delete).toBeCalledWith('snippets', {id: 2});
        expect(structureStrategy.remove).toBeCalledWith(1);
        expect(structureStrategy.remove).toBeCalledWith(2);
        expect(listStore.selections).toEqual([]);
    });
});

test('Should crash when deleting all selected items and one request fails with another error than 404', (done) => {
    const page = observable.box(1);
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page});
    listStore.schema = {};
    const structureStrategy = new StructureStrategy();
    listStore.updateStructureStrategy(structureStrategy);

    ResourceRequester.delete.mockReturnValue(Promise.reject({status: 500}));

    listStore.select({id: 1});
    listStore.select({id: 2});

    expect(listStore.deletingSelection).toEqual(false);

    const deletePromise = listStore.deleteSelection();

    expect(listStore.deletingSelection).toEqual(true);

    deletePromise.catch((error) => {
        expect(error.status).toEqual(500);
        expect(listStore.deletingSelection).toEqual(false);
        done();
    });
});

test('Should order the item with the given ID and options to the given position', () => {
    const page = observable.box(1);
    const locale = observable.box('en');
    const listStore = new ListStore(
        'snippets',
        'snippets',
        'list_test',
        {page, locale},
        {webspace: 'sulu'}
    );
    listStore.schema = {};
    const orderPromise = Promise.resolve();
    ResourceRequester.post.mockReturnValue(orderPromise);

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    listStore.updateLoadingStrategy(loadingStrategy);
    listStore.updateStructureStrategy(structureStrategy);

    listStore.order(5, 1);

    expect(ResourceRequester.post)
        .toBeCalledWith('snippets', {position: 1}, {action: 'order', id: 5, locale: 'en', webspace: 'sulu'});

    return orderPromise.then(() => {
        expect(structureStrategy.order).toBeCalledWith(5, 1);
    });
});

test('Should call all disposers if destroy is called', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_test', {page: observable.box()});
    listStore.sendRequestDisposer = jest.fn();
    listStore.localeDisposer = jest.fn();
    listStore.searchDisposer = jest.fn();
    listStore.filterDisposer = jest.fn();
    listStore.sortColumnDisposer = jest.fn();
    listStore.sortOrderDisposer = jest.fn();
    listStore.limitDisposer = jest.fn();
    listStore.activeSettingDisposer = jest.fn();

    listStore.destroy();

    expect(listStore.sendRequestDisposer).toBeCalledWith();
    expect(listStore.localeDisposer).toBeCalledWith();
    expect(listStore.searchDisposer).toBeCalledWith();
    expect(listStore.filterDisposer).toBeCalledWith();
    expect(listStore.sortColumnDisposer).toBeCalledWith();
    expect(listStore.sortOrderDisposer).toBeCalledWith();
    expect(listStore.limitDisposer).toBeCalledWith();
    expect(listStore.activeSettingDisposer).toBeCalledWith();
});
