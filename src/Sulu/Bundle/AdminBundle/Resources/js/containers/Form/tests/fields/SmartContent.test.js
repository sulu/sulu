// @flow
import React from 'react';
import {render, waitFor} from '@testing-library/react';
import {extendObservable as mockExtendObservable} from 'mobx';
import Router from '../../../../services/Router';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import SmartContent from '../../fields/SmartContent';
import {SmartContentStore, smartContentConfigStore} from '../../../SmartContent';
import smartContentStorePool from '../../fields/smartContentStorePool';

let mockSmartContentProps: Object = {};

jest.mock('../../../../containers/MultiListOverlay', () => jest.fn(() => null));

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id) {
    this.resourceKey = resourceKey;
    this.id = id;
}));

jest.mock('../../../../utils/Translator');

jest.mock('../../stores/ResourceFormStore', () => jest.fn(function(resourceStore, formKey, options, metadataOptions) {
    this.resourceKey = resourceStore.resourceKey;
    this.id = resourceStore.id;
    this.metadataOptions = metadataOptions;
}));

jest.mock('../../FormInspector', () => jest.fn(function(formStore) {
    this.resourceKey = formStore.resourceKey;
    this.id = formStore.id;
    this.metadataOptions = formStore.metadataOptions;
}));

jest.mock('../../../SmartContent', () => {
    const mockReact = require('react');

    const SmartContentStoreMock = jest.fn(function() {
        this.loading = false;
        this.destroy = jest.fn();
        this.start = jest.fn();

        mockExtendObservable(this, {items: [], itemsLoading: false, filterCriteria: {}});
    });

    return {
        __esModule: true,
        default: jest.fn((props) => {
            mockSmartContentProps = props;

            return mockReact.createElement('div');
        }),
        SmartContentStore: SmartContentStoreMock,
        smartContentConfigStore: {
            getConfig: jest.fn(),
            getDefaultValue: jest.fn().mockReturnValue({audienceTargeting: false}),
        },
    };
});

jest.mock('../../fields/smartContentStorePool', () => ({
    add: jest.fn(),
    findPreviousStores: jest.fn().mockReturnValue([]),
    stores: [],
    remove: jest.fn(),
    updateExcludedIds: jest.fn(),
}));

beforeEach(() => {
    jest.clearAllMocks();
    mockSmartContentProps = {};
    smartContentConfigStore.getConfig.mockReturnValue({});
    smartContentConfigStore.getDefaultValue.mockReturnValue({audienceTargeting: false});
    smartContentStorePool.findPreviousStores.mockReturnValue([]);
});

test('Should correctly initialize SmartContentStore', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(new ResourceStore('test', 1), 'test', {}, {webspace: 'sulu_io'})
    );
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

    const {unmount} = render(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    const smartContentStore = smartContentStorePool.add.mock.calls[0][0];

    expect(smartContentStore.start).toHaveBeenCalledWith();

    expect(smartContentStorePool.add).toHaveBeenCalledWith(smartContentStore, false);
    expect(smartContentConfigStore.getConfig).toHaveBeenCalledWith('media');
    expect(SmartContentStore)
        .toHaveBeenCalledWith('media', value, undefined, 'collections', undefined, schemaOptions, 'sulu_io');

    unmount();
    expect(smartContentStorePool.remove).toHaveBeenCalledWith(smartContentStore);
});

test('Should correctly initialize SmartContentStore with a exclude_duplicates value of false', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test', 1), 'test'));
    smartContentConfigStore.getConfig.mockReturnValue({datasourceResourceKey: 'collections'});

    const schemaOptions = {
        exclude_duplicates: {
            name: 'exclude_duplicates',
            value: true,
        },
    };

    render(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    const smartContentStore = smartContentStorePool.add.mock.calls[0][0];

    expect(smartContentStore.start).toHaveBeenCalledWith();
    expect(smartContentStorePool.add).toHaveBeenCalledWith(smartContentStore, true);
});

test('Defer start of smartContentStore until all previous stores have loaded their items', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test', 1), 'test'));
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

    render(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    const smartContentStore = smartContentStorePool.add.mock.calls[0][0];

    expect(smartContentStorePool.updateExcludedIds).not.toHaveBeenCalled();
    expect(smartContentStore.start).not.toHaveBeenCalled();

    smartContentStore1.itemsLoading = false;
    expect(smartContentStorePool.updateExcludedIds).not.toHaveBeenCalled();
    expect(smartContentStore.start).not.toHaveBeenCalled();

    smartContentStore2.itemsLoading = false;

    await waitFor(() => expect(smartContentStorePool.updateExcludedIds).toHaveBeenCalledWith());
    expect(smartContentStore.start).toHaveBeenCalledWith();
});

test('Should pass id to SmartContentStore if resourceKeys match', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('pages', 4), 'pages'));
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

    render(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(smartContentConfigStore.getConfig).toHaveBeenCalledWith('pages');
    expect(SmartContentStore).toHaveBeenCalledWith('pages', value, undefined, 'pages', 4, schemaOptions, undefined);
});

test('Pass correct props to SmartContent component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

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

    render(
        <SmartContent
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            label="Test"
            schemaOptions={schemaOptions}
        />
    );

    expect(mockSmartContentProps.categoryRootKey).toEqual('test1');
    expect(mockSmartContentProps.presentations).toEqual([
        {name: 'one', value: 'One column'},
        {name: 'two', value: 'Two column'},
    ]);
    expect(mockSmartContentProps.fieldLabel).toEqual('Test');
    expect(mockSmartContentProps.disabled).toEqual(true);
    expect(mockSmartContentProps.onItemClick).toEqual(undefined);
});

test('Should not call the onChange and onFinish callbacks if SmartContentStore is still loading', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const schemaOptions = {
        provider: {
            name: 'provider',
            value: 'media',
        },
    };

    render(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    const smartContentStore = smartContentStorePool.add.mock.calls[0][0];

    changeSpy.mockReset();
    finishSpy.mockReset();

    smartContentStore.loading = true;
    smartContentStore.filterCriteria = {
        audienceTargeting: true,
    };

    expect(changeSpy).not.toHaveBeenCalled();
    expect(finishSpy).not.toHaveBeenCalled();
});

test('Should call the onChange and onFinish callbacks if SmartContentStore changes', async() => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const schemaOptions = {
        provider: {
            name: 'provider',
            value: 'media',
        },
    };

    render(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    const smartContentStore = smartContentStorePool.add.mock.calls[0][0];

    changeSpy.mockReset();
    finishSpy.mockReset();

    smartContentStore.loading = false;
    smartContentStore.filterCriteria = {
        audienceTargeting: true,
    };

    await waitFor(() => expect(changeSpy).toHaveBeenCalledWith({audienceTargeting: true}));
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Should not call the onChange and onFinish callbacks if categories only differ in order', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

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

    render(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    const smartContentStore = smartContentStorePool.add.mock.calls[0][0];

    changeSpy.mockReset();
    finishSpy.mockReset();

    smartContentStore.loading = false;
    smartContentStore.filterCriteria = {
        ...value,
        categories: [2, 1],
    };

    expect(changeSpy).not.toHaveBeenCalled();
    expect(finishSpy).not.toHaveBeenCalled();
});

test('Should not call the onChange and onFinish callbacks if tags only differ in order', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

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

    render(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    const smartContentStore = smartContentStorePool.add.mock.calls[0][0];

    changeSpy.mockReset();
    finishSpy.mockReset();

    smartContentStore.loading = false;
    smartContentStore.filterCriteria = {
        ...value,
        tags: ['Programming', 'Design'],
    };

    expect(changeSpy).not.toHaveBeenCalled();
    expect(finishSpy).not.toHaveBeenCalled();
});

test('Should navigate to view if item is clicked', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const schemaOptions = {
        provider: {
            name: 'provider',
            value: 'media',
        },
    };

    smartContentConfigStore.getConfig.mockReturnValue({sorting: [], view: 'sulu_media.form', resultToView: {id: 'id'}});

    const router = new Router();

    render(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            router={router}
            schemaOptions={schemaOptions}
        />
    );

    mockSmartContentProps.onItemClick(1, {id: 1});
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_media.form', {id: 1});
    mockSmartContentProps.onItemClick(3, {id: 3});
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_media.form', {id: 3});
});

test('Should call destroy on SmartContentStore when unmounted', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const schemaOptions = {
        provider: {
            name: 'provider',
            value: 'media',
        },
    };

    const {unmount} = render(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    const smartContentStore = smartContentStorePool.add.mock.calls[0][0];
    unmount();
    expect(smartContentStore.destroy).toHaveBeenCalledWith();
});
