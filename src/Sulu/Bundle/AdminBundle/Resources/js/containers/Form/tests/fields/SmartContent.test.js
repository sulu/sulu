// @flow
import React from 'react';
import {shallow} from 'enzyme';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import SmartContent from '../../fields/SmartContent';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../SmartContent/stores/SmartContentStore', () => jest.fn(function() {
    this.loading = false;
    this.destroy = jest.fn();
}));

test('Should not call the onChange and onFinish callbacks if SmartContentStore is still loading', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

    const smartContent = shallow(
        <SmartContent
            dataPath="/"
            error={{}}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={0}
            minOccurs={0}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaPath="/"
            showAllErrors={false}
            types={undefined}
            value={undefined}
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

    const smartContent = shallow(
        <SmartContent
            dataPath="/"
            error={{}}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={0}
            minOccurs={0}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaPath="/"
            showAllErrors={false}
            types={undefined}
            value={undefined}
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

    const smartContent = shallow(
        <SmartContent
            dataPath="/"
            error={{}}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={0}
            minOccurs={0}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaPath="/"
            showAllErrors={false}
            types={undefined}
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

test('Should call destroy on SmartContentStore when unmounted', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

    const smartContent = shallow(
        <SmartContent
            dataPath="/"
            error={{}}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={0}
            minOccurs={0}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath="/"
            showAllErrors={false}
            types={undefined}
            value={undefined}
        />
    );

    const smartContentStore = smartContent.instance().smartContentStore;
    smartContent.unmount();
    expect(smartContentStore.destroy).toBeCalledWith();
});
