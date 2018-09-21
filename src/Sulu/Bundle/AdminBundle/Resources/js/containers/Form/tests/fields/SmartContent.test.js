// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import SmartContent from '../../fields/SmartContent';
import SmartContentStore from '../../../SmartContent/stores/SmartContentStore';
import smartContentConfigStore from '../../../SmartContent/stores/SmartContentConfigStore';

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id) {
    this.resourceKey = resourceKey;
    this.id = id;
}));

jest.mock('../../stores/FormStore', () => jest.fn(function(resourceStore) {
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
}));
jest.mock('../../../SmartContent/stores/SmartContentConfigStore', () => ({
    getConfig: jest.fn().mockReturnValue({}),
}));

test('Should correctly initialize SmartContentStore', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test', 1)));
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

    shallow(
        <SmartContent
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={value}
        />
    );

    expect(smartContentConfigStore.getConfig).toBeCalledWith('media');
    expect(SmartContentStore).toBeCalledWith('media', value, undefined, 'collections', undefined);
});

test('Should pass id to SmartContentStore if resourceKeys match', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('pages', 4)));
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
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

    const schemaOptions = {
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
            formInspector={formInspector}
            label="Test"
            schemaOptions={schemaOptions}
        />
    );

    expect(smartContent.find('SmartContent').prop('presentations')).toEqual([
        {name: 'one', value: 'One column'},
        {name: 'two', value: 'Two column'},
    ]);
    expect(smartContent.find('SmartContent').prop('fieldLabel')).toEqual('Test');
});

test('Should not call the onChange and onFinish callbacks if SmartContentStore is still loading', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

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
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

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
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

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
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

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
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

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
