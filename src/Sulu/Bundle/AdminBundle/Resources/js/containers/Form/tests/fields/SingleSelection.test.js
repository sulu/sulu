// @flow
import React from 'react';
import {shallow} from 'enzyme';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import SingleSelection from '../../fields/SingleSelection';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass correct props to AutoComplete', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const value = {
        test: 'value',
    };

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            dataPath=""
            error={undefined}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={value}
        />
    );

    expect(singleSelection.find('AutoComplete').props()).toEqual(expect.objectContaining({
        displayProperty: 'name',
        resourceKey: 'accounts',
        searchProperties: ['name', 'number'],
        value,
    }));
});

test('Call onChange and onFinish when AutoComplete changes', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const value = {
        test: 'value',
    };

    const fieldTypeOptions = {
        default_type: 'auto_complete',
        resource_key: 'accounts',
        types: {
            auto_complete: {
                display_property: 'name',
                search_properties: ['name', 'number'],
            },
        },
    };

    const singleSelection = shallow(
        <SingleSelection
            dataPath=""
            error={undefined}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={value}
        />
    );

    singleSelection.find('AutoComplete').simulate('change', undefined);

    expect(changeSpy).toBeCalledWith(undefined);
    expect(finishSpy).toBeCalledWith();
});

test('Throw an error if the auto_complete configuration was omitted', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const fieldTypeOptions = {
        default_type: 'auto_complete',
        types: {},
    };

    expect(
        () => shallow(
            <SingleSelection
                dataPath=""
                error={undefined}
                fieldTypeOptions={fieldTypeOptions}
                formInspector={formInspector}
                maxOccurs={undefined}
                minOccurs={undefined}
                onChange={jest.fn()}
                onFinish={jest.fn()}
                schemaPath=""
                showAllErrors={false}
                types={undefined}
                value={undefined}
            />
        )
    ).toThrow(/"auto_complete"/);
});
