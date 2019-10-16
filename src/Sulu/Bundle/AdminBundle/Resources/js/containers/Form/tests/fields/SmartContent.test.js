// @flow
import React from 'react';
import {extendObservable as mockExtendObservable} from 'mobx';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import SmartContent from '../../fields/SmartContent';
import SmartContentStore from '../../../SmartContent/stores/SmartContentStore';
import smartContentConfigStore from '../../../SmartContent/stores/smartContentConfigStore';
import smartContentStorePool from '../../fields/smartContentStorePool';

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id) {
    this.resourceKey = resourceKey;
    this.id = id;
}));

jest.mock('../../stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.resourceKey = resourceStore.resourceKey;
    this.id = resourceStore.id;
}));

jest.mock('../../FormInspector', () => jest.fn(function(formStore) {
    this.resourceKey = formStore.resourceKey;
    this.id = formStore.id;
}));

jest.mock('../../../SmartContent/stores/SmartContentStore', () => jest.fn(function() {
    this.loading = false;
    this.destroy = jest.fn();
    this.start = jest.fn();

    mockExtendObservable(this, {itemsLoading: false});
}));

jest.mock('../../../SmartContent/stores/smartContentConfigStore', () => ({
    getConfig: jest.fn().mockReturnValue({}),
}));

jest.mock('../../fields/smartContentStorePool', () => ({
    add: jest.fn(),
    stores: [],
    remove: jest.fn(),
    updateExcludedIds: jest.fn(),
}));

test('Should correctly initialize SmartContentStore', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test', 1), 'test'));
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
    };

    const schemaOptions = {
        provider: {
            value: 'media',
        },
    };

    const smartContent = shallow(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    const smartContentStore = smartContent.instance().smartContentStore;

    expect(smartContentStore.start).toBeCalledWith();

    expect(smartContentStorePool.add).toBeCalledWith(smartContentStore);
    expect(smartContentConfigStore.getConfig).toBeCalledWith('media');
    expect(SmartContentStore).toBeCalledWith('media', value, undefined, 'collections', undefined);

    smartContent.unmount();
    expect(smartContentStorePool.remove).toBeCalledWith(smartContentStore);
});

test('Defer start of smartContentStore until all previous stores have loaded their items', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test', 1), 'test'));
    const smartContentStore1 = new SmartContentStore('pages');
    smartContentStore1.itemsLoading = true;
    const smartContentStore2 = new SmartContentStore('pages');
    smartContentStore2.itemsLoading = true;
    smartContentStorePool.stores = [smartContentStore1, smartContentStore2];

    const schemaOptions = {
        exclude_duplicates: {
            value: true,
        },
    };

    const smartContent = shallow(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    const smartContentStore = smartContent.instance().smartContentStore;

    expect(smartContentStorePool.updateExcludedIds).not.toBeCalled();
    expect(smartContentStore.start).not.toBeCalled();

    smartContentStore1.itemsLoading = false;
    expect(smartContentStorePool.updateExcludedIds).not.toBeCalled();
    expect(smartContentStore.start).not.toBeCalled();

    smartContentStore2.itemsLoading = false;
    expect(smartContentStorePool.updateExcludedIds).toBeCalledWith();
    expect(smartContentStore.start).toBeCalledWith();
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
    };

    const schemaOptions = {
        provider: {
            value: 'pages',
        },
    };

    shallow(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(smartContentConfigStore.getConfig).toBeCalledWith('pages');
    expect(SmartContentStore).toBeCalledWith('pages', value, undefined, 'pages', 4);
});

test('Pass correct props to SmartContent component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const schemaOptions = {
        category_root: {
            value: 'test1',
        },
        provider: {
            value: 'media',
        },
        present_as: {
            value: [
                {name: 'one', title: 'One column'},
                {name: 'two', title: 'Two column'},
            ],
        },
    };

    const smartContent = shallow(
        <SmartContent
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            label="Test"
            schemaOptions={schemaOptions}
        />
    );

    expect(smartContent.find('SmartContent').prop('categoryRootKey')).toEqual('test1');
    expect(smartContent.find('SmartContent').prop('presentations')).toEqual([
        {name: 'one', value: 'One column'},
        {name: 'two', value: 'Two column'},
    ]);
    expect(smartContent.find('SmartContent').prop('fieldLabel')).toEqual('Test');
    expect(smartContent.find('SmartContent').prop('disabled')).toEqual(true);
});

test('Should not call the onChange and onFinish callbacks if SmartContentStore is still loading', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const schemaOptions = {
        provider: {
            value: 'media',
        },
    };

    const smartContent = shallow(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    smartContent.instance().smartContentStore.loading = true;
    smartContent.instance().smartContentStore.filterCriteria = {
        audienceTargeting: true,
    };
    smartContent.instance().handleFilterCriteriaChange();

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should call the onChange and onFinish callbacks if SmartContentStore changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const schemaOptions = {
        provider: {
            value: 'media',
        },
    };

    const smartContent = shallow(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    smartContent.instance().smartContentStore.loading = false;
    smartContent.instance().smartContentStore.filterCriteria = {
        audienceTargeting: true,
    };
    smartContent.instance().handleFilterCriteriaChange();

    expect(changeSpy).toBeCalledWith({audienceTargeting: true});
    expect(finishSpy).toBeCalledWith();
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
    };

    const schemaOptions = {
        provider: {
            value: 'media',
        },
    };

    const smartContent = shallow(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    changeSpy.mockReset();
    finishSpy.mockReset();

    smartContent.instance().smartContentStore.loading = false;
    smartContent.instance().smartContentStore.filterCriteria = {
        ...value,
        categories: [2, 1],
    };
    smartContent.instance().handleFilterCriteriaChange();

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
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
    };

    const schemaOptions = {
        provider: {
            value: 'media',
        },
    };

    const smartContent = shallow(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    changeSpy.mockReset();
    finishSpy.mockReset();

    smartContent.instance().smartContentStore.loading = false;
    smartContent.instance().smartContentStore.filterCriteria = {
        ...value,
        tags: ['Programming', 'Design'],
    };
    smartContent.instance().handleFilterCriteriaChange();

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should call destroy on SmartContentStore when unmounted', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const schemaOptions = {
        provider: {
            value: 'media',
        },
    };

    const smartContent = shallow(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    const smartContentStore = smartContent.instance().smartContentStore;
    smartContent.unmount();
    expect(smartContentStore.destroy).toBeCalledWith();
});
