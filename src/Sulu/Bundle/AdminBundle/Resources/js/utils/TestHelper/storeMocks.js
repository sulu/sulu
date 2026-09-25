// @flow
import {extendObservable} from 'mobx';

function mockResourceStoreImplementation(ResourceStore: any, implementation: Function) {
    ResourceStore.mockImplementation(function(...args) {
        this.destroy = jest.fn();
        implementation.apply(this, args);

        if (!this.destroy) {
            this.destroy = jest.fn();
        }
    });
}

function createListStoreMock(
    resourceKey: string,
    listKey: string,
    userSettingsKey: string,
    observableOptions: Object,
    options: Object,
    metadataOptions: Object,
    overrides: Object = {},
    target?: Object
) {
    const store = target || {};

    Object.assign(store, {
        resourceKey,
        listKey,
        userSettingsKey,
        observableOptions,
        options,
        metadataOptions,
        filterOptions: {
            get: jest.fn().mockReturnValue({}),
        },
        loading: false,
        pageCount: 3,
        active: {
            get: jest.fn(),
        },
        sortColumn: {
            get: jest.fn(),
        },
        sortOrder: {
            get: jest.fn(),
        },
        searchTerm: {
            get: jest.fn(),
        },
        limit: {
            get: jest.fn().mockReturnValue(10),
        },
        setLimit: jest.fn(),
        updateLoadingStrategy: jest.fn(),
        updateStructureStrategy: jest.fn(),
        data: [
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
        ],
        selections: [],
        selectionIds: [],
        deleteSelection: jest.fn(),
        getPage: jest.fn().mockReturnValue(2),
        userSchema: {
            title: {
                type: 'string',
                sortable: true,
                visibility: 'no',
                label: 'Title',
            },
            description: {
                type: 'string',
                sortable: true,
                visibility: 'yes',
                label: 'Description',
            },
        },
        filterQueryOption: {},
        destroy: jest.fn(),
        reset: jest.fn(),
        reload: jest.fn(),
        clearSelection: jest.fn(),
        remove: jest.fn(),
        moveSelection: jest.fn(),
        ...overrides,
    });

    if (!store.visibleItems) {
        store.visibleItems = store.data;
    }

    extendObservable(store, {
        moving: false,
        movingSelection: false,
    });

    return store;
}

export {
    createListStoreMock,
    mockResourceStoreImplementation,
};
