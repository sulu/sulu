// @flow
import React from 'react';
import {act, render, waitFor} from '@testing-library/react';
import Router from '../../../../services/Router';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import SmartContentField from '../../fields/SmartContent';
import SmartContentComponent, {smartContentConfigStore, SmartContentStore} from '../../../SmartContent';
import smartContentStorePool from '../../fields/smartContentStorePool';

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id) {
    this.resourceKey = resourceKey;
    this.id = id;
}));

jest.mock('../../stores/ResourceFormStore', () => jest.fn(function(resourceStore, formKey, options, metadataOptions) {
    this.resourceKey = resourceStore.resourceKey;
    this.id = resourceStore.id;
    this.metadataOptions = metadataOptions;
    this.options = options;
}));

jest.mock('../../FormInspector', () => jest.fn(function(formStore) {
    this.resourceKey = formStore.resourceKey;
    this.id = formStore.id;
    this.metadataOptions = formStore.metadataOptions;
}));

jest.mock('../../../SmartContent', () => {
    const {extendObservable} = require('mobx');

    const SmartContentComponent = jest.fn(() => null);
    const SmartContentStore = jest.fn(function() {
        this.loading = false;
        this.destroy = jest.fn();
        this.start = jest.fn();

        extendObservable(this, {items: [], itemsLoading: false, filterCriteria: {}});
    });

    const smartContentConfigStore = {
        getConfig: jest.fn(),
        getDefaultValue: jest.fn().mockReturnValue({audienceTargeting: false}),
    };

    return {
        __esModule: true,
        default: SmartContentComponent,
        smartContentConfigStore,
        SmartContentStore,
    };
});

jest.mock('../../fields/smartContentStorePool', () => ({
    add: jest.fn(),
    findPreviousStores: jest.fn().mockReturnValue([]),
    stores: [],
    remove: jest.fn(),
    updateExcludedIds: jest.fn(),
}));

function createFormInspector(
    resourceKey: string = 'test',
    id: ?number = 1,
    options?: Object = {},
    metadataOptions?: Object
) {
    return new FormInspector(
        new ResourceFormStore(new ResourceStore(resourceKey, id), 'test', options, metadataOptions)
    );
}

function renderSmartContent(props: Object = {}) {
    return render(
        <SmartContentField
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            {...props}
        />
    );
}

function getLatestSmartContentStore() {
    const instances = ((SmartContentStore: any).mock.instances: any);
    return instances[instances.length - 1];
}

function getLatestSmartContentProps() {
    const calls = ((SmartContentComponent: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
    smartContentStorePool.findPreviousStores.mockReturnValue([]);
    smartContentConfigStore.getConfig.mockReturnValue({});
});

test('Should correctly initialize SmartContentStore', () => {
    const formInspector = createFormInspector('test', 1, {}, {webspace: 'sulu_io'});
    smartContentConfigStore.getConfig.mockReturnValue({datasourceResourceKey: 'collections'});

    const value = {
        audienceTargeting: undefined,
        categoryOperator: undefined,
        categories: [1, 2],
        dataSource: undefined,
        includeSubFolders: undefined,
        limitResult: undefined,
        presentAs: 'large',
        sortBy: undefined,
        sortMethod: undefined,
        tagOperator: undefined,
        tags: undefined,
        types: ['default', 'homepage'],
    };

    const schemaOptions = {
        provider: {
            name: 'provider',
            value: 'media',
        },
    };

    const {unmount} = renderSmartContent({
        formInspector,
        schemaOptions,
        value,
    });

    const smartContentStore = getLatestSmartContentStore();

    expect(smartContentStore.start).toBeCalledWith();
    expect(smartContentStorePool.add).toBeCalledWith(smartContentStore, false);
    expect(smartContentConfigStore.getConfig).toBeCalledWith('media');
    expect(SmartContentStore)
        .toBeCalledWith('media', value, undefined, 'collections', undefined, schemaOptions, 'sulu_io');

    unmount();
    expect(smartContentStorePool.remove).toBeCalledWith(smartContentStore);
});

test('Should correctly initialize SmartContentStore with a exclude_duplicates value of false', () => {
    const formInspector = createFormInspector();
    smartContentConfigStore.getConfig.mockReturnValue({datasourceResourceKey: 'collections'});

    const schemaOptions = {
        exclude_duplicates: {
            name: 'exclude_duplicates',
            value: true,
        },
    };

    renderSmartContent({
        formInspector,
        schemaOptions,
    });

    const smartContentStore = getLatestSmartContentStore();

    expect(smartContentStore.start).toBeCalledWith();
    expect(smartContentStorePool.add).toBeCalledWith(smartContentStore, true);
});

test('Defer start of smartContentStore until all previous stores have loaded their items', async() => {
    const formInspector = createFormInspector();
    const smartContentStore1 = new SmartContentStore('pages');
    smartContentStore1.itemsLoading = true;
    const smartContentStore2 = new SmartContentStore('pages');
    smartContentStore2.itemsLoading = true;
    smartContentStorePool.findPreviousStores.mockReturnValue([smartContentStore1, smartContentStore2]);

    const schemaOptions = {
        exclude_duplicates: {
            name: 'exclude_duplicates',
            value: true,
        },
    };

    renderSmartContent({
        formInspector,
        schemaOptions,
    });

    const smartContentStore = getLatestSmartContentStore();

    expect(smartContentStorePool.updateExcludedIds).not.toBeCalled();
    expect(smartContentStore.start).not.toBeCalled();

    act(() => {
        smartContentStore1.itemsLoading = false;
    });
    expect(smartContentStorePool.updateExcludedIds).not.toBeCalled();
    expect(smartContentStore.start).not.toBeCalled();

    act(() => {
        smartContentStore2.itemsLoading = false;
    });

    await waitFor(() => expect(smartContentStorePool.updateExcludedIds).toBeCalledWith());
    expect(smartContentStore.start).toBeCalledWith();
});

test('Should pass id to SmartContentStore if resourceKeys match', () => {
    const formInspector = createFormInspector('pages', 4);
    smartContentConfigStore.getConfig.mockReturnValue({datasourceResourceKey: 'pages'});

    const value = {
        audienceTargeting: undefined,
        categoryOperator: undefined,
        categories: [1, 2],
        dataSource: undefined,
        includeSubFolders: undefined,
        limitResult: undefined,
        presentAs: 'large',
        sortBy: undefined,
        sortMethod: undefined,
        tagOperator: undefined,
        tags: undefined,
        types: undefined,
    };

    const schemaOptions = {
        provider: {
            name: 'provider',
            value: 'pages',
        },
    };

    renderSmartContent({
        formInspector,
        schemaOptions,
        value,
    });

    expect(smartContentConfigStore.getConfig).toBeCalledWith('pages');
    expect(SmartContentStore).toBeCalledWith('pages', value, undefined, 'pages', 4, schemaOptions, undefined);
});

test('Pass correct props to SmartContent component', () => {
    const formInspector = createFormInspector();

    const schemaOptions = {
        category_root: {
            name: 'category_root',
            value: 'test1',
        },
        provider: {
            name: 'provider',
            value: 'media',
        },
        present_as: {
            name: 'present_as',
            value: [
                {name: 'one', title: 'One column'},
                {name: 'two', title: 'Two column'},
            ],
        },
    };

    renderSmartContent({
        disabled: true,
        formInspector,
        label: 'Test',
        schemaOptions,
    });

    expect(getLatestSmartContentProps().categoryRootKey).toEqual('test1');
    expect(getLatestSmartContentProps().presentations).toEqual([
        {name: 'one', value: 'One column'},
        {name: 'two', value: 'Two column'},
    ]);
    expect(getLatestSmartContentProps().fieldLabel).toEqual('Test');
    expect(getLatestSmartContentProps().disabled).toEqual(true);
    expect(getLatestSmartContentProps().onItemClick).toEqual(undefined);
});

test('Should not call the onChange and onFinish callbacks if SmartContentStore is still loading', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = createFormInspector();

    const schemaOptions = {
        provider: {
            name: 'provider',
            value: 'media',
        },
    };

    renderSmartContent({
        formInspector,
        onChange: changeSpy,
        onFinish: finishSpy,
        schemaOptions,
    });

    const smartContentStore = getLatestSmartContentStore();
    changeSpy.mockReset();
    finishSpy.mockReset();

    act(() => {
        smartContentStore.loading = true;
        smartContentStore.filterCriteria = {
            audienceTargeting: true,
        };
    });

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should call the onChange and onFinish callbacks if SmartContentStore changes', async() => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = createFormInspector();

    const schemaOptions = {
        provider: {
            name: 'provider',
            value: 'media',
        },
    };

    renderSmartContent({
        formInspector,
        onChange: changeSpy,
        onFinish: finishSpy,
        schemaOptions,
    });

    const smartContentStore = getLatestSmartContentStore();

    act(() => {
        smartContentStore.loading = false;
        smartContentStore.filterCriteria = {
            audienceTargeting: true,
        };
    });

    await waitFor(() => expect(changeSpy).toBeCalledWith({audienceTargeting: true}));
    expect(finishSpy).toBeCalledWith();
});

test('Should not call the onChange and onFinish callbacks if categories only differ in order', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = createFormInspector();

    const value = {
        audienceTargeting: undefined,
        categoryOperator: undefined,
        categories: [1, 2],
        dataSource: undefined,
        includeSubFolders: undefined,
        limitResult: undefined,
        presentAs: 'large',
        sortBy: undefined,
        sortMethod: undefined,
        tagOperator: undefined,
        tags: undefined,
        types: undefined,
    };

    const schemaOptions = {
        provider: {
            name: 'provider',
            value: 'media',
        },
    };

    renderSmartContent({
        formInspector,
        onChange: changeSpy,
        onFinish: finishSpy,
        schemaOptions,
        value,
    });

    const smartContentStore = getLatestSmartContentStore();
    changeSpy.mockReset();
    finishSpy.mockReset();

    act(() => {
        smartContentStore.loading = false;
        smartContentStore.filterCriteria = {
            ...value,
            categories: [2, 1],
        };
    });

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should not call the onChange and onFinish callbacks if tags only differ in order', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = createFormInspector();

    const value = {
        audienceTargeting: undefined,
        categoryOperator: undefined,
        categories: undefined,
        dataSource: undefined,
        includeSubFolders: undefined,
        limitResult: undefined,
        presentAs: 'large',
        sortBy: undefined,
        sortMethod: undefined,
        tagOperator: undefined,
        tags: ['Design', 'Programming'],
        types: undefined,
    };

    const schemaOptions = {
        provider: {
            name: 'provider',
            value: 'media',
        },
    };

    renderSmartContent({
        formInspector,
        onChange: changeSpy,
        onFinish: finishSpy,
        schemaOptions,
        value,
    });

    const smartContentStore = getLatestSmartContentStore();
    changeSpy.mockReset();
    finishSpy.mockReset();

    act(() => {
        smartContentStore.loading = false;
        smartContentStore.filterCriteria = {
            ...value,
            tags: ['Programming', 'Design'],
        };
    });

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should navigate to view if item is clicked', () => {
    const formInspector = createFormInspector();

    const schemaOptions = {
        provider: {
            name: 'provider',
            value: 'media',
        },
    };

    smartContentConfigStore.getConfig.mockReturnValue({
        sorting: [],
        view: 'sulu_media.form',
        resultToView: {id: 'id'},
    });

    const router = new Router();

    renderSmartContent({
        formInspector,
        router,
        schemaOptions,
    });

    getLatestSmartContentProps().onItemClick(1, {id: 1});
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_media.form', {id: 1});

    getLatestSmartContentProps().onItemClick(3, {id: 3});
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_media.form', {id: 3});
});

test('Should call destroy on SmartContentStore when unmounted', () => {
    const formInspector = createFormInspector();

    const schemaOptions = {
        provider: {
            name: 'provider',
            value: 'media',
        },
    };

    const {unmount} = renderSmartContent({
        formInspector,
        schemaOptions,
    });

    const smartContentStore = getLatestSmartContentStore();
    unmount();

    expect(smartContentStore.destroy).toBeCalledWith();
});
